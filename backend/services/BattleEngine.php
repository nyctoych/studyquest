<?php
require_once __DIR__ . '/../config/Database.php';

/**
 * Class BattleEngine
 *
 * Centralized, server-authoritative combat resolution service for StudyQuest.
 * Enforces the universal combat arithmetic and targeting rules across:
 * - ALL Users
 * - ALL Characters (Mage, Melee, Range)
 * - ALL Character Levels (1-50)
 * - ALL Chapters (1-12)
 * - ALL Parts & Boss Encounters
 *
 * Core Rule:
 * 1. Answer Correctness determines DAMAGE TARGET:
 *    - CORRECT -> Damage Enemy
 *    - WRONG   -> Damage Player
 * 2. Critical Mode determines DAMAGE AMOUNT:
 *    - OFF -> Normal scaled Damage
 *    - ON  -> Scaled Crit Damage
 */
class BattleEngine {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::getInstance();
    }

    /**
     * Resolves a battle action attempt authoritatively.
     *
     * @param int $userId Authenticated user ID
     * @param int $battleId Active battle session ID
     * @param array $payload Request payload
     * @return array Standardized authoritative combat outcome
     */
    public function resolveBattleAttempt(int $userId, int $battleId, array $payload): array {
        if (!$battleId) {
            return ['status' => 400, 'data' => ['success' => false, 'message' => 'Missing battle session ID']];
        }

        $selectedChoice = strtoupper(trim((string)($payload['selected_choice'] ?? '')));
        if ($selectedChoice === '') {
            return ['status' => 400, 'data' => ['success' => false, 'message' => 'No action choice selected']];
        }

        $criticalActive = !empty($payload['critical_active']) 
            || !empty($payload['is_critical']) 
            || filter_var($payload['critical_active'] ?? false, FILTER_VALIDATE_BOOLEAN) 
            || filter_var($payload['is_critical'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $questionId = (int)($payload['question_id'] ?? 0);
        $submittedIndex = isset($payload['question_index']) 
            ? (int)$payload['question_index'] 
            : (isset($payload['current_question_index']) ? (int)$payload['current_question_index'] : null);

        // 1. Fetch authoritative active battle session
        $bStmt = $this->db->prepare("SELECT * FROM adventure_active_battles WHERE id = ? AND user_id = ?");
        $bStmt->execute([$battleId, $userId]);
        $battle = $bStmt->fetch(PDO::FETCH_ASSOC);

        if (!$battle) {
            return ['status' => 404, 'data' => ['success' => false, 'message' => 'Active battle session not found']];
        }

        if ($battle['status'] !== 'active') {
            return [
                'status' => 400,
                'data' => [
                    'success' => false,
                    'message' => 'Battle has already concluded',
                    'battle_status' => $battle['status']
                ]
            ];
        }

        $currentIndex = (int)($battle['current_question_index'] ?? 0);

        // 2. Prevent duplicate submissions / race conditions
        if ($submittedIndex !== null && $submittedIndex < $currentIndex) {
            return [
                'status' => 409,
                'data' => [
                    'success' => false,
                    'message' => 'This inquiry attempt has already been resolved.',
                    'battle_id' => $battleId,
                    'current_question_index' => $currentIndex,
                    'player_current_health' => (int)$battle['player_current_health'],
                    'enemy_current_health' => (int)$battle['enemy_current_health'],
                    'battle_status' => $battle['status']
                ]
            ];
        }

        // 3. Resolve Question and Correct Answer
        $sequence = !empty($battle['question_sequence']) ? json_decode($battle['question_sequence'], true) : [];
        $currentQuestionData = $sequence[$currentIndex] ?? null;

        $correctAnswer = '';
        $explanation = '';

        // Priority 1: Match inside the server-issued session sequence by question id
        if ($questionId > 0 && !empty($sequence)) {
            foreach ($sequence as $item) {
                if (isset($item['id']) && (int)$item['id'] === $questionId && !empty($item['correct_answer'])) {
                    $correctAnswer = strtoupper(trim($item['correct_answer']));
                    $explanation = $item['explanation'] ?? '';
                    break;
                }
            }
        }

        // Priority 2: Match directly from database question tables
        if ($correctAnswer === '' && $questionId > 0) {
            $qStmt = $this->db->prepare("
                SELECT correct_answer, explanation FROM questions WHERE id = ?
                UNION
                SELECT correct_answer, explanation FROM learning_questions WHERE id = ?
                LIMIT 1
            ");
            $qStmt->execute([$questionId, $questionId]);
            $qRow = $qStmt->fetch(PDO::FETCH_ASSOC);
            if ($qRow && !empty($qRow['correct_answer'])) {
                $correctAnswer = strtoupper(trim($qRow['correct_answer']));
                $explanation = $qRow['explanation'] ?? '';
            }
        }

        // Priority 3: Fall back to expected correct answer from payload
        // (never trusted for the Final Boss: server data only)
        if ($correctAnswer === '' && !empty($payload['expected_correct']) && ($battle['encounter_type'] ?? '') !== 'final_boss') {
            $correctAnswer = strtoupper(trim((string)$payload['expected_correct']));
            $explanation = (string)($payload['expected_explanation'] ?? '');
        }

        // Priority 4: Fall back to current index in stored sequence
        if ($correctAnswer === '' && $currentQuestionData) {
            $correctAnswer = strtoupper(trim($currentQuestionData['correct_answer'] ?? ''));
            $explanation = $currentQuestionData['explanation'] ?? '';
        }

        // Normalize correct answer
        $correctAnswer = strtoupper(trim($correctAnswer));

        // 4. Determine Answer Correctness (Strict case-insensitive comparison)
        $isCorrect = ($correctAnswer !== '' && $selectedChoice === $correctAnswer);

        // 5. Retrieve Authoritative Level-Scaled Character Stats from Battle Session
        $damage = (int)($battle['character_damage'] ?: 10);
        $critDamage = (int)($battle['character_crit_damage'] ?: 35);

        // 6. Universal Combat Arithmetic & Targeting
        $damageAmount = $criticalActive ? $critDamage : $damage;

        if ($isCorrect) {
            // CORRECT ANSWER -> DAMAGE ENEMY
            $damageTarget = 'enemy';
            $damageDealt = $damageAmount;
            $playerDamageTaken = 0;
            $damageType = $criticalActive ? 'critical' : 'normal';

            $enemyHpAfter = max(0, (int)$battle['enemy_current_health'] - $damageDealt);
            $playerHpAfter = (int)$battle['player_current_health'];
        } else {
            // WRONG ANSWER -> DAMAGE PLAYER
            $damageTarget = 'player';
            $damageDealt = 0;
            $playerDamageTaken = $damageAmount;
            $damageType = $criticalActive ? 'critical_backfire' : 'normal_damage';

            $playerHpAfter = max(0, (int)$battle['player_current_health'] - $playerDamageTaken);
            $enemyHpAfter = (int)$battle['enemy_current_health'];
        }

        $nextIndex = $currentIndex + 1;
        $battleStatus = 'active';

        // 7. Determine Terminal Conditions (Clamped at 0 HP)
        if ($playerHpAfter <= 0) {
            $battleStatus = 'defeat';
            $playerHpAfter = 0;
        } elseif ($enemyHpAfter <= 0) {
            $battleStatus = 'victory';
            $enemyHpAfter = 0;
        } elseif ($nextIndex >= 50) {
            // Deterministic terminal resolution on question 50
            $playerHpPct = $playerHpAfter / max(1, (int)$battle['player_max_health']);
            $enemyHpPct = $enemyHpAfter / max(1, (int)$battle['enemy_max_health']);
            if ($playerHpPct >= $enemyHpPct) {
                $battleStatus = 'victory';
                $enemyHpAfter = 0;
            } else {
                $battleStatus = 'defeat';
                $playerHpAfter = 0;
            }
        }

        // 8. Persist Battle State to Database
        $upStmt = $this->db->prepare("
            UPDATE adventure_active_battles
            SET player_current_health = ?,
                enemy_current_health = ?,
                status = ?,
                current_question_index = ?
            WHERE id = ?
        ");
        $upStmt->execute([$playerHpAfter, $enemyHpAfter, $battleStatus, $nextIndex, $battleId]);

        // 8b. Persistent Adventure HP Carry-Over State (Adventure Gate ONLY)
        // Persist authoritative player HP after each combat action in Adventure Gate.
        // On defeat ($battleStatus === 'defeat' or $playerHpAfter <= 0), persistent HP becomes 0.
        // Strictly isolated from Arena battles.
        $nextAdventureStartHp = null;
        if (($battle['battle_mode'] ?? 'adventure') === 'adventure') {
            require_once __DIR__ . '/AdventureHealthService.php';
            $healthOutcome = AdventureHealthService::getInstance()->recordBattleOutcome($userId, $playerHpAfter, $battleStatus);
            $nextAdventureStartHp = $healthOutcome['current_hp'];
        }

        // 9. Log Combat Event
        $evStmt = $this->db->prepare("
            INSERT INTO adventure_combat_events
            (battle_id, user_id, question_id, selected_choice, is_correct, critical_active,
             damage_type, damage_dealt, player_damage_taken, player_hp_after, enemy_hp_after)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $evStmt->execute([
            $battleId, $userId, $questionId, $selectedChoice, $isCorrect ? 1 : 0,
            $criticalActive ? 1 : 0, $damageType, $damageDealt, $playerDamageTaken,
            $playerHpAfter, $enemyHpAfter
        ]);

        // 10. Prepare next question if active
        $nextQuestion = null;
        if ($battleStatus === 'active' && isset($sequence[$nextIndex])) {
            $nq = $sequence[$nextIndex];
            $nextQuestion = [
                'id' => $nq['id'],
                'question' => $nq['question'],
                'choices' => [
                    'A' => $nq['choice_a'],
                    'B' => $nq['choice_b'],
                    'C' => $nq['choice_c'],
                    'D' => $nq['choice_d'],
                ],
                'difficulty' => $nq['difficulty'] ?? 'intermediate',
            ];
        }

        return [
            'status' => 200,
            'data' => [
                'success' => true,
                'battle_id' => $battleId,
                'battle_mode' => $battle['battle_mode'] ?? 'adventure',
                'correct' => $isCorrect,
                'is_correct' => $isCorrect,
                'critical' => $criticalActive,
                'critical_active' => $criticalActive,
                'damage' => $damageAmount,
                'character_damage' => (int)($battle['character_damage'] ?? 10),
                'character_crit_damage' => (int)($battle['character_crit_damage'] ?? 35),
                'attack_power' => (int)($battle['character_damage'] ?? 10),
                'critical_attack_power' => (int)($battle['character_crit_damage'] ?? 35),
                'damage_target' => $damageTarget,
                'damage_type' => $damageType,
                'damage_dealt' => $damageDealt,
                'player_damage_taken' => $playerDamageTaken,
                'player_hp' => $playerHpAfter,
                'player_current_health' => $playerHpAfter,
                'max_health' => (int)$battle['player_max_health'],
                'current_hp' => $playerHpAfter,
                'player_max_health' => (int)$battle['player_max_health'],
                'enemy_hp' => $enemyHpAfter,
                'enemy_current_health' => $enemyHpAfter,
                'enemy_max_health' => (int)$battle['enemy_max_health'],
                'battle_status' => $battleStatus,
                'battle_result' => ($battleStatus === 'victory' || $battleStatus === 'defeat') ? $battleStatus : null,
                'is_victory' => ($battleStatus === 'victory'),
                'is_defeat' => ($battleStatus === 'defeat'),
                'next_adventure_start_hp' => $nextAdventureStartHp,
                'correct_answer' => $correctAnswer,
                'explanation' => $explanation,
                'next_question' => $nextQuestion
            ]
        ];
    }
}
