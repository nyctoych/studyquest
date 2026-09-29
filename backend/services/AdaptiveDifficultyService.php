<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../database/AdaptiveMigration.php';

class AdaptiveDifficultyService {
    private PDO $db;

    const TIER_BEGINNER     = 'beginner';
    const TIER_INTERMEDIATE = 'intermediate';
    const TIER_ADVANCED     = 'advanced';
    const TIER_BOARD_EXAM   = 'board_exam';

    const TIERS_ORDER = [
        self::TIER_BEGINNER     => 1,
        self::TIER_INTERMEDIATE => 2,
        self::TIER_ADVANCED     => 3,
        self::TIER_BOARD_EXAM   => 4,
    ];

    public function __construct() {
        AdaptiveMigration::run();
        $this->db = Database::getInstance();
    }

    /**
     * 1. Determine baseline difficulty from user account level.
     * Level 1-5   -> Beginner
     * Level 6-15  -> Intermediate
     * Level 16-25 -> Advanced
     * Level 26+   -> Board-Exam Level
     */
    public function getBaselineDifficulty(int $userLevel): string {
        if ($userLevel <= 5) return self::TIER_BEGINNER;
        if ($userLevel <= 15) return self::TIER_INTERMEDIATE;
        if ($userLevel <= 25) return self::TIER_ADVANCED;
        return self::TIER_BOARD_EXAM;
    }

    /**
     * Get or initialize a student's learning profile.
     */
    public function getUserProfile(int $userId): array {
        $stmt = $this->db->prepare("SELECT * FROM user_learning_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $profile = $stmt->fetch();

        if (!$profile) {
            // Fetch account level
            $lvlStmt = $this->db->prepare("SELECT level FROM users WHERE id = ?");
            $lvlStmt->execute([$userId]);
            $level = (int) ($lvlStmt->fetchColumn() ?: 1);

            $baseline = $this->getBaselineDifficulty($level);
            $initialMastery = match($baseline) {
                self::TIER_BEGINNER     => 45.0,
                self::TIER_INTERMEDIATE => 55.0,
                self::TIER_ADVANCED     => 70.0,
                self::TIER_BOARD_EXAM   => 80.0,
                default                 => 50.0
            };

            $state = $this->getMasteryStateName($initialMastery);

            $insStmt = $this->db->prepare("
                INSERT INTO user_learning_profiles 
                (user_id, mastery_score, adaptive_difficulty, adaptive_state, current_streak, recent_accuracy, avg_response_time)
                VALUES (?, ?, ?, ?, 0, 0.00, 0.00)
            ");
            $insStmt->execute([$userId, $initialMastery, $baseline, $state]);

            return [
                'id'                  => (int) $this->db->lastInsertId(),
                'user_id'             => $userId,
                'mastery_score'       => $initialMastery,
                'adaptive_difficulty' => $baseline,
                'adaptive_state'      => $state,
                'current_streak'      => 0,
                'recent_accuracy'     => 0.00,
                'avg_response_time'   => 0.00,
            ];
        }

        return $profile;
    }

    /**
     * Map mastery score (0-100) to state description.
     */
    public function getMasteryStateName(float $mastery): string {
        if ($mastery < 40) return 'Needs Reinforcement';
        if ($mastery < 60) return 'Developing';
        if ($mastery < 75) return 'Competent';
        if ($mastery < 90) return 'Strong';
        return 'Advanced Readiness';
    }

    /**
     * Fetch recent question attempts for a student.
     * Default window: 10 attempts (optionally up to 20).
     */
    public function getRecentAttempts(int $userId, int $window = 10): array {
        $stmt = $this->db->prepare("
            SELECT qa.*, q.difficulty AS question_difficulty, q.category_id, q.topic_id
            FROM question_attempts qa
            LEFT JOIN questions q ON qa.question_id = q.id
            WHERE qa.user_id = ?
            ORDER BY qa.answered_at DESC, qa.id DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $window]);
        return $stmt->fetchAll();
    }

    /**
     * Calculate recent accuracy, average response time, and current streak.
     */
    public function calculateRecentPerformance(array $recentAttempts): array {
        $count = count($recentAttempts);
        if ($count === 0) {
            return [
                'count'             => 0,
                'correct'           => 0,
                'incorrect'         => 0,
                'accuracy'          => 0.0,
                'avg_response_time' => 0.0,
                'streak'            => 0,
            ];
        }

        $correct = 0;
        $totalTime = 0;
        $streak = 0;
        $streakBroken = false;

        foreach ($recentAttempts as $attempt) {
            $isCorrect = (bool) $attempt['is_correct'];
            if ($isCorrect) {
                $correct++;
                if (!$streakBroken) $streak++;
            } else {
                $streakBroken = true;
            }
            $totalTime += (float) ($attempt['response_time'] ?? 0);
        }

        return [
            'count'             => $count,
            'correct'           => $correct,
            'incorrect'         => $count - $correct,
            'accuracy'          => round(($correct / $count) * 100, 2),
            'avg_response_time' => round($totalTime / $count, 2),
            'streak'            => $streak,
        ];
    }

    /**
     * Calculate comprehensive Difficulty Mastery Score (0-100).
     * Combines:
     * - Baseline account level weight
     * - Recent performance window (last 10-20 attempts)
     * - Difficulty weighting of answered questions
     * - Response time modifier (secondary signal)
     */
    public function computeMasteryScore(int $userLevel, array $recentAttempts, float $currentMastery): float {
        $baselineTier = $this->getBaselineDifficulty($userLevel);
        $baseTierMastery = match($baselineTier) {
            self::TIER_BEGINNER     => 40.0,
            self::TIER_INTERMEDIATE => 55.0,
            self::TIER_ADVANCED     => 70.0,
            self::TIER_BOARD_EXAM   => 85.0,
            default                 => 50.0
        };

        if (empty($recentAttempts)) {
            return $currentMastery ?: $baseTierMastery;
        }

        $difficultyWeights = [
            self::TIER_BEGINNER     => 1.0,
            self::TIER_INTERMEDIATE => 1.5,
            self::TIER_ADVANCED     => 2.0,
            self::TIER_BOARD_EXAM   => 2.5,
        ];

        $totalWeight = 0;
        $earnedWeight = 0;
        $timeBonus = 0;

        foreach ($recentAttempts as $attempt) {
            $diff = $attempt['difficulty'] ?: ($attempt['question_difficulty'] ?? self::TIER_INTERMEDIATE);
            $weight = $difficultyWeights[$diff] ?? 1.5;
            $totalWeight += $weight;

            if ($attempt['is_correct']) {
                $earnedWeight += $weight;

                // Response time secondary signal:
                // Fast correct answer (<= 8s): +0.5 weight bonus
                // Normal correct answer (9-20s): full credit
                // Deliberate correct answer (> 20s): 0.95 factor
                $rt = (float) ($attempt['response_time'] ?? 10);
                if ($rt > 0 && $rt <= 8) {
                    $timeBonus += 0.3;
                } elseif ($rt > 25) {
                    $timeBonus -= 0.1;
                }
            }
        }

        $windowAccuracyPercent = $totalWeight > 0 ? (($earnedWeight + $timeBonus) / $totalWeight) * 100 : 50;
        $windowAccuracyPercent = max(0, min(100, $windowAccuracyPercent));

        // Smooth moving calculation: 60% recent window + 40% historical mastery to avoid sudden spikes
        $newMastery = ($windowAccuracyPercent * 0.60) + ($currentMastery * 0.40);

        // Cap within realistic limits of baseline tier
        // Beginners capped at 85 until level progresses, Advanced starts higher
        return round(max(5.0, min(99.0, $newMastery)), 1);
    }

    /**
     * Determine adaptive difficulty tier and question distribution.
     * Returns:
     * - primary_tier: currently targeted difficulty
     * - adaptive_tier_label: human-readable label
     * - distribution: percentage chance for [beginner, intermediate, advanced, board_exam]
     * - user_explanation: friendly user explanation
     */
    public function determineDifficultyDistribution(int $userLevel, float $mastery, array $recentPerf): array {
        $baseline = $this->getBaselineDifficulty($userLevel);
        $recentAccuracy = $recentPerf['accuracy'] ?? 50.0;
        $attemptCount = $recentPerf['count'] ?? 0;

        $distribution = [
            self::TIER_BEGINNER     => 0,
            self::TIER_INTERMEDIATE => 0,
            self::TIER_ADVANCED     => 0,
            self::TIER_BOARD_EXAM   => 0,
        ];

        $primaryTier = $baseline;
        $explanation = '';

        // If user is brand new with fewer than 3 attempts, stick close to baseline
        if ($attemptCount < 3) {
            $distribution[$baseline] = 100;
            $explanation = "Starting with baseline " . ucfirst($baseline) . " questions to establish your Psychology proficiency.";
            return [
                'primary_tier'        => $baseline,
                'adaptive_tier_label' => ucfirst($baseline),
                'distribution'        => $distribution,
                'user_explanation'   => $explanation,
            ];
        }

        // ── DIFFICULTY LOGIC BY BASELINE TIER ─────────────────────

        // Level 1-5: Baseline BEGINNER
        if ($baseline === self::TIER_BEGINNER) {
            if ($recentAccuracy >= 80.0 && $mastery >= 65.0) {
                // Promotion: introduce Intermediate
                $primaryTier = self::TIER_INTERMEDIATE;
                if ($recentAccuracy >= 90.0) {
                    $distribution = [
                        self::TIER_BEGINNER     => 35,
                        self::TIER_INTERMEDIATE => 60,
                        self::TIER_ADVANCED     => 5,
                        self::TIER_BOARD_EXAM   => 0,
                    ];
                    $explanation = "Outstanding accuracy! StudyQuest is introducing more Intermediate application questions.";
                } else {
                    $distribution = [
                        self::TIER_BEGINNER     => 70,
                        self::TIER_INTERMEDIATE => 25,
                        self::TIER_ADVANCED     => 5,
                        self::TIER_BOARD_EXAM   => 0,
                    ];
                    $explanation = "You're demonstrating solid foundational knowledge. Gradually introducing Intermediate concepts.";
                }
            } else {
                // Maintain Beginner
                $distribution = [
                    self::TIER_BEGINNER     => 85,
                    self::TIER_INTERMEDIATE => 15,
                    self::TIER_ADVANCED     => 0,
                    self::TIER_BOARD_EXAM   => 0,
                ];
                $explanation = "Reinforcing core Psychology concepts and definitions at the Beginner tier.";
            }
        }

        // Level 6-15: Baseline INTERMEDIATE
        elseif ($baseline === self::TIER_INTERMEDIATE) {
            if ($recentAccuracy >= 80.0 && $mastery >= 75.0) {
                // Promotion towards Advanced
                $primaryTier = self::TIER_ADVANCED;
                if ($recentAccuracy >= 90.0) {
                    $distribution = [
                        self::TIER_BEGINNER     => 5,
                        self::TIER_INTERMEDIATE => 45,
                        self::TIER_ADVANCED     => 50,
                        self::TIER_BOARD_EXAM   => 0,
                    ];
                    $explanation = "Mastery in Intermediate concepts confirmed! Gradually increasing Advanced analysis questions.";
                } else {
                    $distribution = [
                        self::TIER_BEGINNER     => 10,
                        self::TIER_INTERMEDIATE => 65,
                        self::TIER_ADVANCED     => 25,
                        self::TIER_BOARD_EXAM   => 0,
                    ];
                    $explanation = "You're performing strongly on Intermediate questions, so StudyQuest is introducing Advanced questions.";
                }
            } elseif ($recentAccuracy < 50.0 || $mastery < 45.0) {
                // Regression: temporary shift to Beginner for reinforcement
                $primaryTier = self::TIER_INTERMEDIATE;
                $distribution = [
                    self::TIER_BEGINNER     => 30,
                    self::TIER_INTERMEDIATE => 65,
                    self::TIER_ADVANCED     => 5,
                    self::TIER_BOARD_EXAM   => 0,
                ];
                $explanation = "Revisiting foundational concepts to build confidence and reinforce key principles.";
            } else {
                // Maintain Intermediate
                $distribution = [
                    self::TIER_BEGINNER     => 15,
                    self::TIER_INTERMEDIATE => 75,
                    self::TIER_ADVANCED     => 10,
                    self::TIER_BOARD_EXAM   => 0,
                ];
                $explanation = "Maintaining Intermediate application questions to solidify your core competencies.";
            }
        }

        // Level 16-25: Baseline ADVANCED
        elseif ($baseline === self::TIER_ADVANCED) {
            if ($recentAccuracy >= 80.0 && $mastery >= 85.0) {
                // Promotion towards Board Exam
                $primaryTier = self::TIER_BOARD_EXAM;
                if ($recentAccuracy >= 90.0) {
                    $distribution = [
                        self::TIER_BEGINNER     => 0,
                        self::TIER_INTERMEDIATE => 10,
                        self::TIER_ADVANCED     => 45,
                        self::TIER_BOARD_EXAM   => 45,
                    ];
                    $explanation = "High academic readiness! StudyQuest is introducing authentic Board-Exam scenario questions.";
                } else {
                    $distribution = [
                        self::TIER_BEGINNER     => 5,
                        self::TIER_INTERMEDIATE => 20,
                        self::TIER_ADVANCED     => 55,
                        self::TIER_BOARD_EXAM   => 20,
                    ];
                    $explanation = "Excellent performance on Advanced material. Introducing initial Board-Exam level challenges.";
                }
            } elseif ($recentAccuracy < 50.0 || $mastery < 55.0) {
                // Regression: temporary reinforcement with Intermediate
                $primaryTier = self::TIER_ADVANCED;
                $distribution = [
                    self::TIER_BEGINNER     => 5,
                    self::TIER_INTERMEDIATE => 45,
                    self::TIER_ADVANCED     => 50,
                    self::TIER_BOARD_EXAM   => 0,
                ];
                $explanation = "Providing targeted Intermediate concept reinforcement to strengthen challenging areas.";
            } else {
                // Maintain Advanced
                $distribution = [
                    self::TIER_BEGINNER     => 5,
                    self::TIER_INTERMEDIATE => 25,
                    self::TIER_ADVANCED     => 65,
                    self::TIER_BOARD_EXAM   => 5,
                ];
                $explanation = "Consistent Advanced analysis practice to refine clinical and diagnostic reasoning.";
            }
        }

        // Level 26+: Baseline BOARD-EXAM LEVEL
        else {
            if ($mastery >= 90.0 && $recentAccuracy >= 80.0) {
                $primaryTier = self::TIER_BOARD_EXAM;
                $distribution = [
                    self::TIER_BEGINNER     => 0,
                    self::TIER_INTERMEDIATE => 5,
                    self::TIER_ADVANCED     => 20,
                    self::TIER_BOARD_EXAM   => 75,
                ];
                $explanation = "Peak Board-Exam Readiness! Prioritizing comprehensive licensure and diagnostic board items.";
            } elseif ($mastery < 65.0 || $recentAccuracy < 50.0) {
                // Regression for high level student struggling: mostly Advanced reinforcement
                $primaryTier = self::TIER_ADVANCED;
                $distribution = [
                    self::TIER_BEGINNER     => 5,
                    self::TIER_INTERMEDIATE => 25,
                    self::TIER_ADVANCED     => 50,
                    self::TIER_BOARD_EXAM   => 20,
                ];
                $explanation = "Reinforcing critical Advanced concepts before returning to rigorous Board-Exam simulations.";
            } else {
                // Standard Board Exam mix
                $primaryTier = self::TIER_BOARD_EXAM;
                $distribution = [
                    self::TIER_BEGINNER     => 0,
                    self::TIER_INTERMEDIATE => 15,
                    self::TIER_ADVANCED     => 45,
                    self::TIER_BOARD_EXAM   => 40,
                ];
                $explanation = "Balanced mix of Advanced theory and rigorous Board-Exam questions.";
            }
        }

        return [
            'primary_tier'        => $primaryTier,
            'adaptive_tier_label' => ucfirst($primaryTier),
            'distribution'        => $distribution,
            'user_explanation'   => $explanation,
        ];
    }

    /**
     * Identify weak categories and specific weak topics for a student.
     */
    public function detectWeakAreas(int $userId): array {
        // 1. Weakest Categories
        $catStmt = $this->db->prepare("
            SELECT ucp.*, qc.name AS category_name, qc.slug AS category_slug, qc.icon AS category_icon, qc.color AS category_color
            FROM user_category_performance ucp
            JOIN question_categories qc ON ucp.category_id = qc.id
            WHERE ucp.user_id = ?
            ORDER BY ucp.accuracy ASC, ucp.questions_answered DESC
        ");
        $catStmt->execute([$userId]);
        $categories = $catStmt->fetchAll();

        // 2. Strongest Categories
        $strongCategories = array_values(array_filter($categories, fn($c) => $c['questions_answered'] >= 3 && $c['accuracy'] >= 75));
        $weakCategories = array_values(array_filter($categories, fn($c) => $c['questions_answered'] >= 2 && $c['accuracy'] < 65));

        $weakestCategory = !empty($weakCategories) ? $weakCategories[0] : (!empty($categories) ? $categories[0] : null);
        $strongestCategory = !empty($strongCategories) ? $strongCategories[0] : (!empty($categories) ? $categories[count($categories) - 1] : null);

        // 3. Weakest Topics
        $topicStmt = $this->db->prepare("
            SELECT utp.*, qt.name AS topic_name, qt.slug AS topic_slug, qt.category_id, qc.name AS category_name
            FROM user_topic_performance utp
            JOIN question_topics qt ON utp.topic_id = qt.id
            JOIN question_categories qc ON qt.category_id = qc.id
            WHERE utp.user_id = ?
            ORDER BY utp.accuracy ASC, utp.questions_answered DESC
        ");
        $topicStmt->execute([$userId]);
        $topics = $topicStmt->fetchAll();

        $weakTopics = array_values(array_filter($topics, fn($t) => $t['questions_answered'] >= 2 && $t['accuracy'] < 65));
        $strongTopics = array_values(array_filter($topics, fn($t) => $t['questions_answered'] >= 2 && $t['accuracy'] >= 75));

        $weakestTopic = !empty($weakTopics) ? $weakTopics[0] : (!empty($topics) ? $topics[0] : null);
        $strongestTopic = !empty($strongTopics) ? $strongTopics[0] : (!empty($topics) ? $topics[count($topics) - 1] : null);

        return [
            'weakest_category'   => $weakestCategory,
            'strongest_category' => $strongestCategory,
            'weak_categories'    => $weakCategories,
            'strong_categories'  => $strongCategories,
            'weakest_topic'      => $weakestTopic,
            'strongest_topic'    => $strongestTopic,
            'weak_topics'        => $weakTopics,
            'strong_topics'      => $strongTopics,
            'all_categories'     => $categories,
            'all_topics'         => $topics,
        ];
    }

    /**
     * Exclude IDs of questions answered very recently (anti-repetition window).
     */
    public function getRecentlyAnsweredQuestionIds(int $userId, int $limit = 20): array {
        $stmt = $this->db->prepare("
            SELECT DISTINCT question_id 
            FROM question_attempts 
            WHERE user_id = ? 
            ORDER BY answered_at DESC, id DESC 
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    /**
     * 10-STEP ADAPTIVE QUESTION SELECTION ALGORITHM
     * 
     * STEP 1: Get user account level.
     * STEP 2: Determine baseline difficulty.
     * STEP 3: Get recent performance (last 10-20).
     * STEP 4: Calculate current mastery & adaptive state.
     * STEP 5: Identify weak categories/topics.
     * STEP 6: Determine difficulty distribution.
     * STEP 7: Filter questions by eligible difficulty.
     * STEP 8: Prioritize weak categories/topics.
     * STEP 9: Exclude recently answered questions.
     * STEP 10: Randomly select from remaining valid pool.
     */
    public function selectAdaptiveQuestions(
        int $userId, 
        int $count = 10, 
        ?int $categoryId = null, 
        ?int $topicId = null, 
        string $mode = 'practice'
    ): array {
        // STEP 1: User level
        $lvlStmt = $this->db->prepare("SELECT level FROM users WHERE id = ?");
        $lvlStmt->execute([$userId]);
        $userLevel = (int) ($lvlStmt->fetchColumn() ?: 1);

        // STEP 2 & 3: Baseline & recent performance
        $profile = $this->getUserProfile($userId);
        $recentAttempts = $this->getRecentAttempts($userId, 10);
        $recentPerf = $this->calculateRecentPerformance($recentAttempts);

        // STEP 4: Mastery & State
        $mastery = (float) $profile['mastery_score'];

        // STEP 5: Identify weak categories/topics
        $weakAreas = $this->detectWeakAreas($userId);
        $targetWeakTopicId = $topicId ?: ($weakAreas['weakest_topic']['topic_id'] ?? null);
        $targetWeakCategoryId = $categoryId ?: ($weakAreas['weakest_category']['category_id'] ?? null);

        // STEP 6: Determine difficulty distribution
        $diffDist = $this->determineDifficultyDistribution($userLevel, $mastery, $recentPerf);
        $distribution = $diffDist['distribution'];

        // If in 'board_exam' mode, override to focus on Advanced and Board-Exam levels
        if ($mode === 'board_exam') {
            if ($mastery >= 75.0) {
                $distribution = [
                    self::TIER_BEGINNER     => 0,
                    self::TIER_INTERMEDIATE => 10,
                    self::TIER_ADVANCED     => 30,
                    self::TIER_BOARD_EXAM   => 60,
                ];
            } else {
                $distribution = [
                    self::TIER_BEGINNER     => 0,
                    self::TIER_INTERMEDIATE => 20,
                    self::TIER_ADVANCED     => 50,
                    self::TIER_BOARD_EXAM   => 30,
                ];
            }
        }

        // STEP 9: Exclude recently answered questions
        $recentIds = $this->getRecentlyAnsweredQuestionIds($userId, 25);

        // STEP 7, 8, 10: Sample questions according to difficulty distribution and weak topic bias
        $selectedQuestions = [];
        $selectedIds = [];

        // Build target count per tier from distribution percentages
        $tierTargets = [];
        $remainingCount = $count;

        foreach ($distribution as $tier => $pct) {
            if ($pct > 0) {
                $tierTargets[$tier] = (int) round(($pct / 100) * $count);
            }
        }

        // Adjust rounding to exactly match $count
        $sumTargets = array_sum($tierTargets);
        if ($sumTargets < $count) {
            $primary = $diffDist['primary_tier'];
            $tierTargets[$primary] = ($tierTargets[$primary] ?? 0) + ($count - $sumTargets);
        }

        foreach ($tierTargets as $tier => $targetCount) {
            if ($targetCount <= 0) continue;

            $tierQuestions = $this->fetchQuestionPool(
                $tier,
                $targetCount,
                $categoryId ?: ($targetWeakCategoryId && mt_rand(1, 100) <= 60 ? $targetWeakCategoryId : null),
                $topicId ?: ($targetWeakTopicId && mt_rand(1, 100) <= 50 ? $targetWeakTopicId : null),
                array_merge($recentIds, $selectedIds)
            );

            foreach ($tierQuestions as $q) {
                $selectedQuestions[] = $q;
                $selectedIds[] = $q['id'];
            }
        }

        // If still under count due to strict filters, fill from any approved question pool excluding selected
        if (count($selectedQuestions) < $count) {
            $needed = $count - count($selectedQuestions);
            $filler = $this->fetchQuestionPool(
                null,
                $needed,
                $categoryId,
                $topicId,
                $selectedIds
            );
            foreach ($filler as $q) {
                $selectedQuestions[] = $q;
                $selectedIds[] = $q['id'];
            }
        }

        // If pool is small and still under count, allow recycling excluding only current set
        if (count($selectedQuestions) < $count) {
            $needed = $count - count($selectedQuestions);
            $filler = $this->fetchQuestionPool(null, $needed, $categoryId, $topicId, $selectedIds);
            foreach ($filler as $q) {
                $selectedQuestions[] = $q;
                $selectedIds[] = $q['id'];
            }
        }

        // Randomize presentation order
        shuffle($selectedQuestions);

        return [
            'questions'            => $selectedQuestions,
            'adaptive_info'        => [
                'user_level'          => $userLevel,
                'baseline_difficulty' => $this->getBaselineDifficulty($userLevel),
                'adaptive_difficulty' => $diffDist['primary_tier'],
                'adaptive_state'      => $this->getMasteryStateName($mastery),
                'mastery_score'       => $mastery,
                'recent_accuracy'     => $recentPerf['accuracy'],
                'user_explanation'   => $diffDist['user_explanation'],
                'target_weak_topic'   => $weakAreas['weakest_topic']['topic_name'] ?? null,
                'target_weak_category'=> $weakAreas['weakest_category']['category_name'] ?? null,
                'distribution'        => $distribution,
            ]
        ];
    }

    /**
     * Fetch questions meeting criteria from database.
     */
    private function fetchQuestionPool(
        ?string $difficulty,
        int $limit,
        ?int $categoryId = null,
        ?int $topicId = null,
        array $excludeIds = []
    ): array {
        $where = ["q.status = 'approved'"];
        $params = [];

        if ($difficulty) {
            $where[] = "q.difficulty = ?";
            $params[] = $difficulty;
        }

        if ($categoryId) {
            $where[] = "q.category_id = ?";
            $params[] = $categoryId;
        }

        if ($topicId) {
            $where[] = "q.topic_id = ?";
            $params[] = $topicId;
        }

        if (!empty($excludeIds)) {
            $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
            $where[] = "q.id NOT IN ({$placeholders})";
            foreach ($excludeIds as $exId) {
                $params[] = (int) $exId;
            }
        }

        $whereClause = implode(' AND ', $where);

        $sql = "
            SELECT q.id, q.category_id, q.topic_id, q.question, 
                   q.choice_a, q.choice_b, q.choice_c, q.choice_d,
                   q.correct_answer, q.difficulty, q.explanation, q.source_reference,
                   qc.name AS category_name, qc.icon AS category_icon, qc.color AS category_color,
                   qt.name AS topic_name
            FROM questions q
            JOIN question_categories qc ON q.category_id = qc.id
            LEFT JOIN question_topics qt ON q.topic_id = qt.id
            WHERE {$whereClause}
            ORDER BY RAND()
            LIMIT ?
        ";

        $params[] = $limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Record a student question attempt and atomically update:
     * - question_attempts
     * - user_category_performance
     * - user_topic_performance
     * - user_learning_profiles
     * 
     * Returns updated adaptive status, explanations, and metrics.
     */
    public function recordAttempt(
        int $userId, 
        int $questionId, 
        bool $isCorrect, 
        int $responseTime = 0, 
        ?string $battleId = null
    ): array {
        // 1. Fetch question info
        $qStmt = $this->db->prepare("
            SELECT q.id, q.domain_id, q.category_id, q.topic_id, q.part_id, q.difficulty, q.correct_answer, q.explanation,
                   qc.name AS category_name, qt.name AS topic_name
            FROM questions q
            LEFT JOIN question_categories qc ON q.category_id = qc.id
            LEFT JOIN question_topics qt ON q.topic_id = qt.id
            WHERE q.id = ?
        ");
        $qStmt->execute([$questionId]);
        $question = $qStmt->fetch();

        if (!$question) {
            throw new Exception("Question not found");
        }

        $domainId   = !empty($question['domain_id']) ? (int) $question['domain_id'] : null;
        $categoryId = !empty($question['category_id']) ? (int) $question['category_id'] : ($domainId ?: 7);
        $topicId    = !empty($question['topic_id']) ? (int) $question['topic_id'] : null;
        $partId     = !empty($question['part_id']) ? (int) $question['part_id'] : null;
        $difficulty = $question['difficulty'] ?: self::TIER_INTERMEDIATE;

        // 2. Insert into question_attempts (Requirement 22)
        $insStmt = $this->db->prepare("
            INSERT INTO question_attempts (user_id, question_id, domain_id, category_id, topic_id, part_id, battle_id, is_correct, response_time, difficulty)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insStmt->execute([
            $userId, 
            $questionId, 
            $domainId,
            $categoryId, 
            $topicId, 
            $partId,
            $battleId, 
            $isCorrect ? 1 : 0, 
            $responseTime, 
            $difficulty
        ]);
        $attemptId = (int) $this->db->lastInsertId();

        // 3. Update Category Performance
        $this->updateCategoryPerformance($userId, $categoryId, $isCorrect, $responseTime);

        // 4. Update Topic Performance if topic exists
        if ($topicId) {
            $this->updateTopicPerformance($userId, $topicId, $isCorrect, $responseTime);
        }

        // 5. Update Learning Profile
        $lvlStmt = $this->db->prepare("SELECT level FROM users WHERE id = ?");
        $lvlStmt->execute([$userId]);
        $userLevel = (int) ($lvlStmt->fetchColumn() ?: 1);

        $currentProfile = $this->getUserProfile($userId);
        $recentAttempts = $this->getRecentAttempts($userId, 10);
        $recentPerf = $this->calculateRecentPerformance($recentAttempts);

        $newMastery = $this->computeMasteryScore($userLevel, $recentAttempts, (float) $currentProfile['mastery_score']);
        $newState = $this->getMasteryStateName($newMastery);
        $diffDist = $this->determineDifficultyDistribution($userLevel, $newMastery, $recentPerf);
        $newAdaptiveDifficulty = $diffDist['primary_tier'];

        $updProfile = $this->db->prepare("
            UPDATE user_learning_profiles
            SET mastery_score = ?,
                adaptive_difficulty = ?,
                adaptive_state = ?,
                current_streak = ?,
                recent_accuracy = ?,
                avg_response_time = ?
            WHERE user_id = ?
        ");
        $updProfile->execute([
            $newMastery,
            $newAdaptiveDifficulty,
            $newState,
            $recentPerf['streak'],
            $recentPerf['accuracy'],
            $recentPerf['avg_response_time'],
            $userId
        ]);

        return [
            'attempt_id'          => $attemptId,
            'is_correct'          => $isCorrect,
            'correct_answer'      => strtoupper($question['correct_answer']),
            'explanation'         => $question['explanation'],
            'response_time'       => $responseTime,
            'mastery_score'       => $newMastery,
            'adaptive_difficulty' => $newAdaptiveDifficulty,
            'adaptive_state'      => $newState,
            'current_streak'      => $recentPerf['streak'],
            'recent_accuracy'     => $recentPerf['accuracy'],
            'user_explanation'   => $diffDist['user_explanation'],
            'category_name'       => $question['category_name'],
            'topic_name'          => $question['topic_name'],
        ];
    }

    /**
     * Requirement 23: Study Recommendations linking back to the exact Study Hall Domain & Part
     */
    public function resolveStudyLinkForTopic(?int $topicId, ?string $topicName = null, ?int $categoryId = null): array {
        $topic = null;
        if ($topicId) {
            $stmt = $this->db->prepare("SELECT * FROM psychiatric_topics WHERE id = ?");
            $stmt->execute([$topicId]);
            $topic = $stmt->fetch();
        }
        if (!$topic && $topicName) {
            $stmt = $this->db->prepare("SELECT * FROM psychiatric_topics WHERE name LIKE ? LIMIT 1");
            $stmt->execute(['%' . $topicName . '%']);
            $topic = $stmt->fetch();
        }

        if ($topic) {
            $domainId = (int)$topic['domain_id'];
            $partNum = (int)$topic['display_order'];
            $pStmt = $this->db->prepare("SELECT id, title FROM psychology_parts WHERE domain_id = ? AND part_number = ?");
            $pStmt->execute([$domainId, $partNum]);
            $part = $pStmt->fetch();

            $dStmt = $this->db->prepare("SELECT name FROM psychology_domains WHERE id = ?");
            $dStmt->execute([$domainId]);
            $domainName = $dStmt->fetchColumn() ?: 'Psychiatric Domain';

            return [
                'domain_id'   => $domainId,
                'domain_name' => $domainName,
                'part_number' => $partNum,
                'part_title'  => $part ? $part['title'] : "Part {$partNum}",
                'study_url'   => "/study/{$domainId}/part/{$partNum}",
            ];
        }

        $domId = ($categoryId && $categoryId <= 19) ? $categoryId : 4; // default Depressive Disorders
        $dStmt = $this->db->prepare("SELECT name FROM psychology_domains WHERE id = ?");
        $dStmt->execute([$domId]);
        $domainName = $dStmt->fetchColumn() ?: 'Psychiatric Disorders';

        return [
            'domain_id'   => $domId,
            'domain_name' => $domainName,
            'part_number' => 1,
            'part_title'  => 'Diagnostic Foundations',
            'study_url'   => "/study/{$domId}/part/1",
        ];
    }

    private function updateCategoryPerformance(int $userId, int $categoryId, bool $isCorrect, int $responseTime): void {
        $stmt = $this->db->prepare("SELECT * FROM user_category_performance WHERE user_id = ? AND category_id = ?");
        $stmt->execute([$userId, $categoryId]);
        $perf = $stmt->fetch();

        if ($perf) {
            $answered = (int)$perf['questions_answered'] + 1;
            $correct = (int)$perf['questions_correct'] + ($isCorrect ? 1 : 0);
            $acc = round(($correct / $answered) * 100, 2);
            $totalTime = ((float)$perf['avg_response_time'] * (int)$perf['questions_answered']) + $responseTime;
            $avgTime = round($totalTime / $answered, 2);
            $mastery = round(($acc * 0.7) + min(30, $answered * 2), 2);

            $upd = $this->db->prepare("
                UPDATE user_category_performance 
                SET questions_answered = ?, questions_correct = ?, accuracy = ?, mastery_score = ?, avg_response_time = ?
                WHERE id = ?
            ");
            $upd->execute([$answered, $correct, $acc, $mastery, $avgTime, $perf['id']]);
        } else {
            $answered = 1;
            $correct = $isCorrect ? 1 : 0;
            $acc = $isCorrect ? 100.0 : 0.0;
            $mastery = $isCorrect ? 60.0 : 30.0;

            $ins = $this->db->prepare("
                INSERT INTO user_category_performance 
                (user_id, category_id, questions_answered, questions_correct, accuracy, mastery_score, avg_response_time)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $ins->execute([$userId, $categoryId, $answered, $correct, $acc, $mastery, $responseTime]);
        }
    }

    private function updateTopicPerformance(int $userId, int $topicId, bool $isCorrect, int $responseTime): void {
        $stmt = $this->db->prepare("SELECT * FROM user_topic_performance WHERE user_id = ? AND topic_id = ?");
        $stmt->execute([$userId, $topicId]);
        $perf = $stmt->fetch();

        if ($perf) {
            $answered = (int)$perf['questions_answered'] + 1;
            $correct = (int)$perf['questions_correct'] + ($isCorrect ? 1 : 0);
            $acc = round(($correct / $answered) * 100, 2);
            $totalTime = ((float)$perf['avg_response_time'] * (int)$perf['questions_answered']) + $responseTime;
            $avgTime = round($totalTime / $answered, 2);
            $mastery = round(($acc * 0.7) + min(30, $answered * 2), 2);

            $upd = $this->db->prepare("
                UPDATE user_topic_performance 
                SET questions_answered = ?, questions_correct = ?, accuracy = ?, mastery_score = ?, avg_response_time = ?
                WHERE id = ?
            ");
            $upd->execute([$answered, $correct, $acc, $mastery, $avgTime, $perf['id']]);
        } else {
            $answered = 1;
            $correct = $isCorrect ? 1 : 0;
            $acc = $isCorrect ? 100.0 : 0.0;
            $mastery = $isCorrect ? 60.0 : 30.0;

            $ins = $this->db->prepare("
                INSERT INTO user_topic_performance 
                (user_id, topic_id, questions_answered, questions_correct, accuracy, mastery_score, avg_response_time)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $ins->execute([$userId, $topicId, $answered, $correct, $acc, $mastery, $responseTime]);
        }
    }

    /**
     * Generate full adaptive psychology readiness summary for a user.
     */
    public function getFullReadinessProfile(int $userId): array {
        $lvlStmt = $this->db->prepare("SELECT level, username FROM users WHERE id = ?");
        $lvlStmt->execute([$userId]);
        $user = $lvlStmt->fetch() ?: ['level' => 1, 'username' => 'Student'];
        $userLevel = (int) $user['level'];

        $profile = $this->getUserProfile($userId);
        $recentAttempts = $this->getRecentAttempts($userId, 10);
        $recentPerf = $this->calculateRecentPerformance($recentAttempts);
        $diffDist = $this->determineDifficultyDistribution($userLevel, (float) $profile['mastery_score'], $recentPerf);
        $weakAreas = $this->detectWeakAreas($userId);

        $studyLink = null;
        $recommendation = "Complete personalized practice sessions to identify strengths and strengthen board-exam readiness!";
        if (!empty($weakAreas['weakest_topic'])) {
            $wt = &$weakAreas['weakest_topic'];
            $studyLink = $this->resolveStudyLinkForTopic((int)$wt['topic_id'], $wt['topic_name']);
            $wt['domain_id']   = $studyLink['domain_id'];
            $wt['domain_name'] = $studyLink['domain_name'];
            $wt['part_number'] = $studyLink['part_number'];
            $wt['part_title']  = $studyLink['part_title'];
            $wt['study_url']   = $studyLink['study_url'];
            $recommendation = "Focus on {$wt['topic_name']} — current accuracy is {$wt['accuracy']}%. Targeted review recommended in Study Hall!";
        } elseif (!empty($weakAreas['weakest_category'])) {
            $wc = &$weakAreas['weakest_category'];
            $studyLink = $this->resolveStudyLinkForTopic(null, null, (int)$wc['category_id']);
            $wc['domain_id']   = $studyLink['domain_id'];
            $wc['domain_name'] = $studyLink['domain_name'];
            $wc['part_number'] = $studyLink['part_number'];
            $wc['part_title']  = $studyLink['part_title'];
            $wc['study_url']   = $studyLink['study_url'];
            $recommendation = "Reinforce core concepts in {$wc['category_name']} ({$wc['accuracy']}% accuracy) to elevate overall board readiness!";
        }

        return [
            'user_id'             => $userId,
            'username'            => $user['username'],
            'user_level'          => $userLevel,
            'baseline_difficulty' => $this->getBaselineDifficulty($userLevel),
            'adaptive_difficulty' => $diffDist['primary_tier'],
            'adaptive_state'      => $profile['adaptive_state'],
            'mastery_score'       => (float) $profile['mastery_score'],
            'recent_accuracy'     => (float) $recentPerf['accuracy'],
            'current_streak'      => (int) $recentPerf['streak'],
            'avg_response_time'   => (float) $recentPerf['avg_response_time'],
            'user_explanation'   => $diffDist['user_explanation'],
            'recommendation'      => $recommendation,
            'study_url'           => $studyLink ? $studyLink['study_url'] : '/study',
            'weakest_domain_id'   => $studyLink ? $studyLink['domain_id'] : null,
            'weakest_domain_name' => $studyLink ? $studyLink['domain_name'] : null,
            'weakest_part_number' => $studyLink ? $studyLink['part_number'] : null,
            'weakest_part_title'  => $studyLink ? $studyLink['part_title'] : null,
            'weakest_category'    => $weakAreas['weakest_category'],
            'strongest_category'  => $weakAreas['strongest_category'],
            'weakest_topic'       => $weakAreas['weakest_topic'],
            'strongest_topic'     => $weakAreas['strongest_topic'],
            'distribution'        => $diffDist['distribution'],
            'categories'          => $weakAreas['all_categories'],
            'topics'              => $weakAreas['all_topics'],
        ];
    }
}
