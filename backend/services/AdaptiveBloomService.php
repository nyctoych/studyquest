<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/SpecialEventAttempt.php';

class AdaptiveBloomService {
    private PDO $db;
    public const VALID_BLOOM_LEVELS = ['remember', 'understand', 'apply', 'analyze', 'evaluate', 'create'];

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Check if adaptive Bloom targeting is globally enabled.
     */
    public function isAdaptiveEnabled(): bool {
        $val = $this->getSetting('adaptive_enabled', 'true');
        return filter_var($val, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Analyze user's historical performance across Revised Bloom's Taxonomy.
     * Identifies the primary and secondary weakest Bloom levels using recency weighting
     * and a minimum question threshold to prevent overfitting to small samples.
     *
     * @param int $userId Target candidate ID
     * @return array Analytical breakdown including primary & secondary targets
     */
    public function analyzeUserBloomWeakness(int $userId): array {
        if ($userId <= 0) {
            return [
                'has_sufficient_data' => false,
                'reason' => 'global_template_user',
                'target_bloom' => null,
                'secondary_bloom' => null,
                'bloom_stats' => []
            ];
        }

        $minTrials = (int) $this->getSetting('adaptive_min_trials', '1');
        $minSampleThreshold = (int) $this->getSetting('adaptive_min_sample_threshold', '8');
        $lookbackDays = (int) $this->getSetting('adaptive_recent_window_days', '14');

        // Fetch user's completed attempts within the lookback window
        $stmt = $this->db->prepare("
            SELECT sea.id, sea.special_event_id, sea.completed_at, se.event_date
            FROM special_event_attempts sea
            JOIN special_events se ON sea.special_event_id = se.id
            WHERE sea.user_id = ? 
              AND sea.status = 'completed'
              AND sea.completed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY sea.completed_at DESC
        ");
        $stmt->execute([$userId, $lookbackDays]);
        $attempts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // If not enough trials within recent lookback, also check lifetime attempts up to 5
        if (count($attempts) < $minTrials) {
            $stmtLife = $this->db->prepare("
                SELECT sea.id, sea.special_event_id, sea.completed_at, se.event_date
                FROM special_event_attempts sea
                JOIN special_events se ON sea.special_event_id = se.id
                WHERE sea.user_id = ? AND sea.status = 'completed'
                ORDER BY sea.completed_at DESC
                LIMIT 5
            ");
            $stmtLife->execute([$userId]);
            $attempts = $stmtLife->fetchAll(PDO::FETCH_ASSOC);
        }

        if (count($attempts) < $minTrials) {
            return [
                'has_sufficient_data' => false,
                'reason' => 'insufficient_completed_trials',
                'completed_trials' => count($attempts),
                'min_required_trials' => $minTrials,
                'target_bloom' => null,
                'secondary_bloom' => null,
                'bloom_stats' => []
            ];
        }

        $attemptIds = array_column($attempts, 'id');
        $inPlaceholders = implode(',', array_fill(0, count($attemptIds), '?'));

        // Fetch question-level answers with Bloom metadata
        $ansStmt = $this->db->prepare("
            SELECT sea.attempt_id, seq.bloom_level, sea.is_correct, sea.response_time, att.completed_at
            FROM special_event_answers sea
            JOIN special_event_questions seq ON sea.question_id = seq.id
            JOIN special_event_attempts att ON sea.attempt_id = att.id
            WHERE sea.attempt_id IN ({$inPlaceholders})
              AND seq.bloom_level IS NOT NULL
        ");
        $ansStmt->execute($attemptIds);
        $answerRows = $ansStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($answerRows)) {
            return [
                'has_sufficient_data' => false,
                'reason' => 'no_bloom_answers_found',
                'target_bloom' => null,
                'secondary_bloom' => null,
                'bloom_stats' => []
            ];
        }

        // Initialize Bloom metrics
        $bloomMetrics = [];
        foreach (self::VALID_BLOOM_LEVELS as $bLevel) {
            $bloomMetrics[$bLevel] = [
                'bloom_level' => $bLevel,
                'total_attempted' => 0,
                'correct_count' => 0,
                'incorrect_count' => 0,
                'raw_accuracy' => 0.0,
                'weighted_correct' => 0.0,
                'weighted_attempted' => 0.0,
                'weighted_accuracy' => 0.0,
                'avg_response_time' => 0,
                'total_response_time' => 0,
                'sample_sufficient' => false
            ];
        }

        $now = time();
        foreach ($answerRows as $row) {
            $lvl = strtolower(trim($row['bloom_level']));
            if (!isset($bloomMetrics[$lvl])) {
                continue;
            }

            // Recency weighting: exponential decay weight = exp(-0.08 * days_ago)
            // Questions from today have weight 1.0; 7 days ago have weight ~0.57; 14 days ago ~0.33
            $completedTime = strtotime($row['completed_at'] ?? 'now');
            $daysAgo = max(0.0, ($now - $completedTime) / 86400.0);
            $recencyWeight = exp(-0.08 * $daysAgo);

            $isCorrect = (int) $row['is_correct'] === 1;
            $rTime = (int) ($row['response_time'] ?? 0);

            $bloomMetrics[$lvl]['total_attempted']++;
            $bloomMetrics[$lvl]['weighted_attempted'] += $recencyWeight;
            if ($isCorrect) {
                $bloomMetrics[$lvl]['correct_count']++;
                $bloomMetrics[$lvl]['weighted_correct'] += $recencyWeight;
            } else {
                $bloomMetrics[$lvl]['incorrect_count']++;
            }

            if ($rTime > 0) {
                $bloomMetrics[$lvl]['total_response_time'] += $rTime;
            }
        }

        // Finalize averages and percentages
        $eligibleForWeakness = [];
        foreach ($bloomMetrics as $lvl => &$m) {
            if ($m['total_attempted'] > 0) {
                $m['raw_accuracy'] = round(($m['correct_count'] / $m['total_attempted']) * 100, 1);
                $m['weighted_accuracy'] = $m['weighted_attempted'] > 0
                    ? round(($m['weighted_correct'] / $m['weighted_attempted']) * 100, 1)
                    : $m['raw_accuracy'];
                $m['avg_response_time'] = round($m['total_response_time'] / $m['total_attempted'], 1);
            }

            if ($m['total_attempted'] >= $minSampleThreshold) {
                $m['sample_sufficient'] = true;
                $eligibleForWeakness[$lvl] = $m;
            }
        }
        unset($m);

        if (empty($eligibleForWeakness)) {
            return [
                'has_sufficient_data' => false,
                'reason' => 'insufficient_sample_per_bloom_level',
                'min_sample_threshold' => $minSampleThreshold,
                'target_bloom' => null,
                'secondary_bloom' => null,
                'bloom_stats' => array_values($bloomMetrics)
            ];
        }

        // Sort eligible levels ascending by weighted accuracy
        // Tie-breaker: higher response time (indicates more cognitive friction/struggle)
        uasort($eligibleForWeakness, function ($a, $b) {
            if (abs($a['weighted_accuracy'] - $b['weighted_accuracy']) > 0.01) {
                return $a['weighted_accuracy'] <=> $b['weighted_accuracy'];
            }
            return $b['avg_response_time'] <=> $a['avg_response_time'];
        });

        $sortedKeys = array_keys($eligibleForWeakness);
        $primaryWeakness = $sortedKeys[0];
        $secondaryWeakness = $sortedKeys[1] ?? null;

        // Check if secondary is very close (within 5% accuracy)
        $isCloseWeakness = false;
        if ($secondaryWeakness && isset($eligibleForWeakness[$secondaryWeakness])) {
            $diff = abs($eligibleForWeakness[$secondaryWeakness]['weighted_accuracy'] - $eligibleForWeakness[$primaryWeakness]['weighted_accuracy']);
            $isCloseWeakness = ($diff <= 5.0);
        }

        $displayNames = [
            'remember' => 'Remember',
            'understand' => 'Understand',
            'apply' => 'Apply',
            'analyze' => 'Analyze',
            'evaluate' => 'Evaluate',
            'create' => 'Create'
        ];

        return [
            'has_sufficient_data' => true,
            'primary_target' => $primaryWeakness,
            'secondary_target' => $secondaryWeakness,
            'primary_target_display' => $displayNames[$primaryWeakness] ?? ucfirst($primaryWeakness),
            'secondary_target_display' => $secondaryWeakness ? ($displayNames[$secondaryWeakness] ?? ucfirst($secondaryWeakness)) : null,
            'is_close_weakness' => $isCloseWeakness,
            'target_bloom' => $primaryWeakness,
            'secondary_bloom' => $secondaryWeakness,
            'educational_summary' => "Needs more practice in " . ($displayNames[$primaryWeakness] ?? ucfirst($primaryWeakness)),
            'total_analyzed_trials' => count($attempts),
            'bloom_stats' => array_values($bloomMetrics)
        ];
    }

    /**
     * Compute the exact 140-question Bloom distribution for a daily trial.
     * If adaptive mode is active and user has sufficient history, concentrates
     * on the user's weakest Bloom processes while preserving broad academic representation.
     *
     * @param int $userId Target candidate ID (0 for global template)
     * @return array [ 'distribution' => [...], 'target_bloom' => string|null, 'secondary_bloom' => string|null, 'is_adaptive' => bool, 'analysis' => [...] ]
     */
    public function calculateAdaptiveBloomDistribution(int $userId): array {
        $baseline = $this->getBaselineDistribution();

        if (!$this->isAdaptiveEnabled() || $userId <= 0) {
            return [
                'distribution' => $baseline,
                'target_bloom' => null,
                'secondary_bloom' => null,
                'is_adaptive' => false,
                'reason' => $userId <= 0 ? 'global_template' : 'adaptive_disabled',
                'analysis' => null
            ];
        }

        $analysis = $this->analyzeUserBloomWeakness($userId);
        if (!$analysis['has_sufficient_data'] || empty($analysis['primary_target'])) {
            return [
                'distribution' => $baseline,
                'target_bloom' => null,
                'secondary_bloom' => null,
                'is_adaptive' => false,
                'reason' => $analysis['reason'] ?? 'insufficient_history',
                'analysis' => $analysis
            ];
        }

        $primary = $analysis['primary_target'];
        $secondary = $analysis['secondary_target'] ?? 'apply';
        if ($secondary === $primary) {
            $otherLevels = array_values(array_diff(self::VALID_BLOOM_LEVELS, [$primary]));
            $secondary = $otherLevels[0];
        }

        // Configurable integer distribution totaling exactly 140 items:
        // Weakest Bloom: ~40% (56 items: exactly 14 per domain across 4 domains)
        // Second Weakest: ~20% (28 items: exactly 7 per domain across 4 domains)
        // Remaining 4 Levels: ~10% each (14 items each: 4 x 14 = 56 items)
        // 56 + 28 + 56 = 140 items. Perfectly divisible by 4 (35 per domain).
        $distribution = [];
        $distribution[$primary] = 56;
        $distribution[$secondary] = 28;

        $remaining = array_values(array_diff(self::VALID_BLOOM_LEVELS, [$primary, $secondary]));
        foreach ($remaining as $rLvl) {
            $distribution[$rLvl] = 14;
        }

        // Verify exact sum = 140
        $sum = array_sum($distribution);
        if ($sum !== 140) {
            $diff = 140 - $sum;
            $distribution[$primary] += $diff;
        }

        return [
            'distribution' => $distribution,
            'target_bloom' => $primary,
            'secondary_bloom' => $secondary,
            'is_adaptive' => true,
            'reason' => 'user_weakness_targeted',
            'analysis' => $analysis
        ];
    }

    /**
     * Record adaptive plan in special_events and special_event_adaptive_history.
     */
    public function recordAdaptivePlan(int $eventId, int $userId, array $plan): void {
        $targetBloom = $plan['target_bloom'] ?? null;
        $secondaryBloom = $plan['secondary_bloom'] ?? null;
        $isAdaptive = !empty($plan['is_adaptive']) ? 1 : 0;
        $distJson = json_encode($plan['distribution'] ?? []);
        $analysisJson = !empty($plan['analysis']) ? json_encode($plan['analysis']) : null;

        // Update special_events row
        $stmt = $this->db->prepare("
            UPDATE special_events
            SET target_bloom = ?,
                secondary_bloom = ?,
                is_adaptive = ?,
                adaptive_distribution = ?
            WHERE id = ?
        ");
        $stmt->execute([$targetBloom, $secondaryBloom, $isAdaptive, $distJson, $eventId]);

        // Insert into special_event_adaptive_history for auditable tracking
        $histStmt = $this->db->prepare("
            INSERT INTO special_event_adaptive_history (
                special_event_id, user_id, target_bloom, secondary_bloom, distribution, weakness_summary, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $histStmt->execute([$eventId, $userId, $targetBloom, $secondaryBloom, $distJson, $analysisJson]);
    }

    /**
     * Retrieve user's historical adaptive targeting trajectory.
     */
    public function getUserAdaptiveHistory(int $userId, int $limit = 14): array {
        $stmt = $this->db->prepare("
            SELECT seah.*, se.event_date, se.title as event_title, sea.score, sea.percentage, sea.completed_at
            FROM special_event_adaptive_history seah
            JOIN special_events se ON seah.special_event_id = se.id
            LEFT JOIN special_event_attempts sea ON se.id = sea.special_event_id AND sea.user_id = seah.user_id AND sea.status = 'completed'
            WHERE seah.user_id = ?
            ORDER BY seah.created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = [];
        foreach ($rows as $r) {
            $results[] = [
                'id' => (int) $r['id'],
                'special_event_id' => (int) $r['special_event_id'],
                'event_date' => $r['event_date'],
                'event_title' => $r['event_title'],
                'target_bloom' => $r['target_bloom'],
                'secondary_bloom' => $r['secondary_bloom'],
                'distribution' => json_decode($r['distribution'] ?? '{}', true),
                'score' => $r['score'] !== null ? (int) $r['score'] : null,
                'percentage' => $r['percentage'] !== null ? (float) $r['percentage'] : null,
                'completed_at' => $r['completed_at'],
                'created_at' => $r['created_at']
            ];
        }
        return $results;
    }

    /**
     * Standard baseline Bloom distribution (sum = 140 items).
     */
    public function getBaselineDistribution(): array {
        $setting = $this->getSetting('bloom_distribution');
        if (!empty($setting)) {
            $decoded = json_decode($setting, true);
            if (is_array($decoded) && array_sum($decoded) === 140) {
                return $decoded;
            }
        }

        return [
            'remember' => 20,
            'understand' => 25,
            'apply' => 35,
            'analyze' => 30,
            'evaluate' => 18,
            'create' => 12
        ];
    }

    private function getSetting(string $key, ?string $default = null): ?string {
        static $cache = [];
        if (!isset($cache[$key])) {
            $stmt = $this->db->prepare("SELECT setting_value FROM special_event_settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $val = $stmt->fetchColumn();
            $cache[$key] = ($val !== false) ? $val : $default;
        }
        return $cache[$key];
    }
}
