<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Question.php';
require_once __DIR__ . '/../services/CurriculumService.php';
require_once __DIR__ . '/../services/AdventureProgressService.php';
require_once __DIR__ . '/../services/AdaptiveDifficultyService.php';
require_once __DIR__ . '/../database/PsychiatricCurriculumMigration.php';
require_once __DIR__ . '/../database/AdventureBattleMigration.php';
require_once __DIR__ . '/../models/Character.php';
require_once __DIR__ . '/../services/BattleEngine.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class AdventureController {
    private User $userModel;
    private Character $charModel;
    private PDO $db;
    private AdventureProgressService $progressService;
    private BattleEngine $battleEngine;

    public function __construct() {
        PsychiatricCurriculumMigration::run();
        AdventureBattleMigration::run();
        $this->userModel = new User();
        $this->charModel = new Character();
        $this->db = Database::getInstance();
        $this->progressService = new AdventureProgressService();
        $this->battleEngine = new BattleEngine($this->db);
    }

    // ── GET /adventure/chapters ───────────────────────────────
    public function chapters(): void {
        $userId = AuthMiddleware::userId();
        $result = $this->progressService->getAdventureMap($userId);
        echo json_encode($result);
    }

    // ── GET /adventure/chapters/{id}/final-boss or /adventure/final-boss ──
    public function finalBoss(): void {
        $userId = AuthMiddleware::userId();
        $result = $this->progressService->getFinalBossBattle($userId);

        if (!$result['success']) {
            http_response_code($result['status_code'] ?? 400);
            echo json_encode($result);
            return;
        }

        echo json_encode($result);
    }

    // ── POST /adventure/chapters/{id}/final-boss/complete or /adventure/final-boss/complete ──
    public function completeFinalBoss(): void {
        $userId = AuthMiddleware::userId();
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        // Client HP/answers are ignored: victory is verified from the server battle session.
        $battleData = [
            'battle_id' => (int)($input['battle_id'] ?? 0),
        ];

        $result = $this->progressService->completeFinalBoss($userId, $battleData);

        if (!$result['success']) {
            http_response_code($result['status_code'] ?? 400);
            echo json_encode($result);
            return;
        }

        echo json_encode($result);
    }

    // ── POST /adventure/final-boss/story-stage ────────────────
    public function finalBossStoryStage(): void {
        $userId = AuthMiddleware::userId();
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $result = $this->progressService->advanceFinalStory($userId, (string)($input['stage'] ?? ''));

        if (!$result['success']) {
            http_response_code($result['status_code'] ?? 400);
        }
        echo json_encode($result);
    }

    // ── GET /adventure/chapters/{id} ─────────────────────────
    public function chapter(int $domainId): void {
        $userId = AuthMiddleware::userId();
        $result = $this->progressService->getChapterProgress($userId, $domainId);

        if (!$result['success']) {
            http_response_code($result['status_code'] ?? 400);
            echo json_encode($result);
            return;
        }

        echo json_encode($result);
    }

    // ── GET /adventure/chapters/{id}/parts/{num} ─────────────
    public function part(int $domainId, int $partNum): void {
        $userId = AuthMiddleware::userId();

        // 1. Verify chapter is unlocked
        $chStmt = $this->db->prepare("
            SELECT pd.*, COALESCE(acp.status, 'locked') as chapter_status
            FROM psychology_domains pd
            LEFT JOIN adventure_chapter_progress acp ON acp.domain_id = pd.id AND acp.user_id = ?
            WHERE pd.id = ?
        ");
        $chStmt->execute([$userId, $domainId]);
        $chapter = $chStmt->fetch();

        if (!$chapter) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Chapter not found']);
            return;
        }

        $order = (int)$chapter['display_order'];
        $chStatus = $chapter['chapter_status'];
        if ($order > 1 && $chStatus === 'locked') {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'locked' => true,
                'message' => "You have not unlocked Chapter {$order} yet.",
                'required_chapter_order' => $order - 1
            ]);
            return;
        }

        // 2. Get part data with story integration
        $stmt = $this->db->prepare("
            SELECT pp.*, pd.name as domain_name, pd.icon as domain_icon, pd.color as domain_color,
                   COALESCE(upp.status, 'locked') as part_status,
                   COALESCE(upp.score, 0) as best_score, COALESCE(upp.attempts, 0) as attempts,
                   ac.act_number, ac.act_title, ac.story_title as chapter_story_title,
                   ac.setting_description as chapter_setting, ac.mind_seal_name as chapter_mind_seal,
                   ac.fracture_shard_name as chapter_fracture_shard,
                   aps.story_title, aps.narrative_phase, aps.speaker_title,
                   aps.story_intro, aps.dialogue_speaker, aps.dialogue_text,
                   aps.academic_connection, aps.fracture_clue, aps.story_after,
                   aps.enemy_name, aps.enemy_flavor, aps.enemy_max_hp,
                   b.name as boss_name, b.story_title as boss_story_title, b.portrait_asset as boss_portrait
            FROM psychology_parts pp
            JOIN psychology_domains pd ON pd.id = pp.domain_id
            LEFT JOIN adventure_chapters ac ON ac.domain_id = pd.id
            LEFT JOIN bosses b ON b.chapter_id = pd.id AND b.boss_order = 1
            LEFT JOIN user_part_progress upp ON upp.part_id = pp.id AND upp.user_id = ?
            LEFT JOIN adventure_part_story aps ON aps.part_id = pp.id
            WHERE pp.domain_id = ? AND pp.part_number = ? AND pp.is_published = 1
        ");
        $stmt->execute([$userId, $domainId, $partNum]);
        $part = $stmt->fetch();

        if (!$part) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Part not found']);
            return;
        }

        // 3. Check sequential access: part 1 always available, otherwise previous must be completed
        if ($partNum > 1) {
            $prevStmt = $this->db->prepare("
                SELECT upp.status FROM psychology_parts pp
                JOIN user_part_progress upp ON upp.part_id = pp.id AND upp.user_id = ?
                WHERE pp.domain_id = ? AND pp.part_number = ?
            ");
            $prevStmt->execute([$userId, $domainId, $partNum - 1]);
            $prev = $prevStmt->fetch();
            if (!$prev || !in_array($prev['status'], ['completed', 'mastered'])) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'locked' => true,
                    'message' => 'Complete the previous part first'
                ]);
                return;
            }
        }

        // Decode JSON fields if encoded as strings
        foreach (['key_concepts', 'important_terms', 'diagnostic_criteria', 'specifiers', 'differential_diagnosis'] as $col) {
            if (!empty($part[$col]) && is_string($part[$col])) {
                $decoded = json_decode($part[$col], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $part[$col] = $decoded;
                }
            }
        }

        // Fetch structured study content sections from Study Hall curriculum
        $secStmt = $this->db->prepare("
            SELECT scs.section_type, scs.title, scs.content, scs.source_reference
            FROM study_content_sections scs
            JOIN study_parts sp ON scs.part_id = sp.id
            WHERE sp.domain_id = ? AND sp.part_number = ?
            ORDER BY scs.display_order ASC
        ");
        $secStmt->execute([$domainId, $partNum]);
        $part['sections'] = $secStmt->fetchAll();

        // Requirement 14, 15, 17:
        // Adventure Part difficulty progression within each chapter:
        // Part 1: Foundational (beginner)
        // Part 2–4: Foundational / Intermediate (beginner, intermediate)
        // Part 5–7: Intermediate / Advanced (intermediate, advanced)
        // Part 8–9: Advanced / Application (advanced, board_exam)
        // Part 10: Mastery / Boss preparation (advanced, board_exam, intermediate)
        $progressionTiers = match(true) {
            $partNum === 1 => ['beginner'],
            $partNum >= 2 && $partNum <= 4 => ['beginner', 'intermediate'],
            $partNum >= 5 && $partNum <= 7 => ['intermediate', 'advanced'],
            $partNum >= 8 && $partNum <= 9 => ['advanced', 'board_exam'],
            default => ['advanced', 'board_exam', 'intermediate'],
        };
        $placeholders = implode(',', array_fill(0, count($progressionTiers), '?'));

        // Query 1: Approved questions strictly from this domain and part matching difficulty progression
        $qQuery = "
            SELECT id, question, choice_a, choice_b, choice_c, choice_d,
                   correct_answer, explanation, difficulty
            FROM questions
            WHERE domain_id = ? AND part_id = ? AND status = 'approved' AND difficulty IN ($placeholders)
            ORDER BY RAND() LIMIT 5
        ";
        $qParams = array_merge([$domainId, $part['id']], $progressionTiers);
        $qStmt = $this->db->prepare($qQuery);
        $qStmt->execute($qParams);
        $questions = $qStmt->fetchAll();

        // Query 2: If fewer than 5, widen difficulty while STRICTLY maintaining this domain and part
        if (count($questions) < 5) {
            $needed = 5 - count($questions);
            $existingIds = array_column($questions, 'id');
            $exclClause = !empty($existingIds) ? "AND id NOT IN (" . implode(',', array_fill(0, count($existingIds), '?')) . ")" : "";
            $stmt2 = $this->db->prepare("
                SELECT id, question, choice_a, choice_b, choice_c, choice_d,
                       correct_answer, explanation, difficulty
                FROM questions
                WHERE domain_id = ? AND part_id = ? AND status = 'approved' $exclClause
                ORDER BY RAND() LIMIT ?
            ");
            $params2 = array_merge([$domainId, $part['id']], $existingIds, [$needed]);
            $stmt2->execute($params2);
            $questions = array_merge($questions, $stmt2->fetchAll());
        }

        // Query 3: If still fewer than 5, pull from learning_questions for this exact part
        if (count($questions) < 5) {
            $needed = 5 - count($questions);
            $lqStmt = $this->db->prepare("
                SELECT id, question, choice_a, choice_b, choice_c, choice_d,
                       correct_answer, explanation, difficulty
                FROM learning_questions
                WHERE part_id = ? AND status = 'active'
                ORDER BY RAND() LIMIT ?
            ");
            $lqStmt->execute([$part['id'], $needed]);
            $questions = array_merge($questions, $lqStmt->fetchAll());
        }

        // Query 4: If still fewer than 5, pull from approved questions strictly within CURRENT domain
        if (count($questions) < 5) {
            $needed = 5 - count($questions);
            $existingIds = array_column($questions, 'id');
            $exclClause = !empty($existingIds) ? "AND id NOT IN (" . implode(',', array_fill(0, count($existingIds), '?')) . ")" : "";
            $domStmt = $this->db->prepare("
                SELECT id, question, choice_a, choice_b, choice_c, choice_d,
                       correct_answer, explanation, difficulty
                FROM questions
                WHERE domain_id = ? AND status = 'approved' $exclClause
                ORDER BY RAND() LIMIT ?
            ");
            $paramsDom = array_merge([$domainId], $existingIds, [$needed]);
            $domStmt->execute($paramsDom);
            $questions = array_merge($questions, $domStmt->fetchAll());
        }

        // Format questions for client-side battle
        $safeQuestions = array_map(function($q) {
            return [
                'id' => $q['id'],
                'question' => $q['question'],
                'choices' => [
                    'A' => $q['choice_a'],
                    'B' => $q['choice_b'],
                    'C' => $q['choice_c'],
                    'D' => $q['choice_d'],
                ],
                'difficulty' => $q['difficulty'] ?? 'intermediate',
                'correct_answer' => strtoupper($q['correct_answer']),
                'explanation' => $q['explanation'] ?? '',
            ];
        }, $questions);

        echo json_encode([
            'success'   => true,
            'part'      => $part,
            'questions' => $safeQuestions,
        ]);
    }

    // ── GET /adventure/chapters/{id}/boss ────────────────────
    public function boss(int $chapterId): void {
        $userId = AuthMiddleware::userId();

        $chRes = $this->progressService->getChapterDetails($userId, $chapterId);
        if (!$chRes['success']) {
            http_response_code($chRes['status_code'] ?? 404);
            echo json_encode($chRes);
            return;
        }

        $boss = $chRes['boss'];
        if (!$boss) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Boss not found for this chapter']);
            return;
        }

        if (!$chRes['can_fight_boss'] && empty($chRes['chapter']['boss_defeated'])) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'locked' => true,
                'message' => 'You must conquer all 10 preliminary episodes of this chapter before the Aberration awakens.',
                'parts_completed' => (int)($chRes['chapter']['parts_completed'] ?? 0),
            ]);
            return;
        }

        $questions = $boss['questions'] ?? $this->progressService->getBossQuestionsForDomain($chapterId, 10);

        echo json_encode([
            'success'   => true,
            'chapter'   => $chRes['chapter'],
            'boss'      => $boss,
            'questions' => $questions,
        ]);
    }

    // ── POST /adventure/parts/{id}/submit ────────────────────
    public function submitPart(int $partId): void {
        $userId = AuthMiddleware::userId();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $answers   = $data['answers'] ?? [];
        $timeSpent = (int)($data['time_spent'] ?? 0);

        if (empty($answers)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No answers provided']);
            return;
        }

        $result = $this->progressService->completePart($userId, $partId, $answers, $timeSpent);

        if (!$result['success']) {
            http_response_code($result['status_code'] ?? 400);
            echo json_encode($result);
            return;
        }

        echo json_encode($result);
    }

    // ── POST /adventure/chapters/{id}/boss/complete ──────────
    public function completeBoss(int $chapterId): void {
        $userId = AuthMiddleware::userId();
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $bossId = (int)($input['boss_id'] ?? 0);
        $battleData = [
            'player_hp' => (int)($input['player_hp'] ?? 100),
            'boss_hp'   => (int)($input['boss_hp'] ?? 0),
            'answers'   => $input['answers'] ?? [],
        ];

        $result = $this->progressService->completeBoss($userId, $chapterId, $bossId, $battleData);

        if (!$result['success']) {
            http_response_code($result['status_code'] ?? 400);
            echo json_encode($result);
            return;
        }

        echo json_encode($result);
    }

    // ── GET /adventure/story-state ───────────────────────────
    public function storyState(): void {
        $userId = AuthMiddleware::userId();
        $result = $this->progressService->getStoryState($userId);
        echo json_encode($result);
    }

    // ── GET /adventure/black-archive ──────────────────────────
    public function blackArchive(): void {
        $userId = AuthMiddleware::userId();
        $result = $this->progressService->getBlackArchiveData($userId);
        echo json_encode($result);
    }

    // ── POST /adventure/black-archive/complete-phase ──────────
    public function completeBlackArchivePhase(): void {
        $userId = AuthMiddleware::userId();
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $phase = (int)($input['phase'] ?? 1);
        $battleData = [
            'player_hp' => (int)($input['player_hp'] ?? 100),
            'boss_hp'   => (int)($input['boss_hp'] ?? 0),
        ];

        $result = $this->progressService->completeBlackArchivePhase($userId, $phase, $battleData);
        if (!$result['success']) {
            http_response_code(400);
        }
        echo json_encode($result);
    }

    // ── POST /adventure/tutorial/complete ────────────────────
    public function completeTutorial(): void {
        $userId = AuthMiddleware::userId();
        $this->userModel->completeTutorial($userId);
        $this->userModel->addXP($userId, 100);
        $this->userModel->addCoins($userId, 50);
        $user = $this->userModel->findById($userId);
        echo json_encode([
            'success' => true,
            'message' => 'Tutorial complete! Welcome to StudyQuest!',
            'xp_earned' => 100,
            'coins_earned' => 50,
            'new_xp' => (int)$user['xp'],
            'new_coins' => (int)($user['coins'] ?? 0),
        ]);
    }

    // ── POST /adventure/battle/start ──────────────────────────
    public function startBattle(): void {
        $userId = AuthMiddleware::userId();
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $mode = $input['mode'] ?? $input['encounter_type'] ?? 'part'; // 'part', 'boss', 'tutorial', 'final_boss'
        $battleMode = strtolower(trim((string)($input['battle_mode'] ?? 'adventure')));
        $partId = isset($input['part_id']) ? (int)$input['part_id'] : null;
        $chapterId = isset($input['chapter_id']) ? (int)$input['chapter_id'] : null;
        $bossId = isset($input['boss_id']) ? (int)$input['boss_id'] : (isset($input['encounter_id']) ? (int)$input['encounter_id'] : null);

        // The Creator is a separate final encounter: the server verifies Chapter 12 completion.
        if ($mode === 'final_boss' && !$this->progressService->isFinalBossUnlocked($userId)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'locked' => true,
                'message' => 'The Black Archive remains sealed. Defeat The Mask of a Thousand Selves first.'
            ]);
            return;
        }
        if ($mode === 'final_boss') {
            $chapterId = null;
            $partId = null;
            $bossId = null;
        }

        // Fetch User and Selected Character
        $user = $this->userModel->findById($userId);
        $userLevel = (int)($user['level'] ?? 1);
        $char = $this->charModel->getUserSelectedCharacter($userId);
        if (!$char) {
            $char = $this->charModel->findByKey('mage') ?: $this->charModel->findById(1);
        }

        // Apply Centralized Scaling (Level 1-50, 10% non-compounding base per level)
        $scaled = Character::calculateScaledStats($char, $userLevel);
        $playerMaxHp = (int)($scaled['max_health'] ?? $scaled['scaled_health'] ?? $scaled['health'] ?? 150);
        $charDmg = (int)($scaled['damage'] ?? $scaled['scaled_damage'] ?? 10);
        $charCritDmg = (int)($scaled['crit_damage'] ?? $scaled['scaled_crit_damage'] ?? 35);

        // Persistent Adventure HP carry-over rule:
        // Persistent HP carry-over applies ONLY when battle_mode = 'adventure'.
        // Applies pending passive health recovery (+50 HP/hour) clamped to current scaled Max HP.
        // DO NOT apply to Arena battles (battle_mode = 'arena').
        if ($battleMode === 'adventure') {
            require_once __DIR__ . '/../services/AdventureHealthService.php';
            $healthState = AdventureHealthService::getInstance()->syncAndGetHealth($userId);
            $playerCurrentHp = (int)$healthState['current_hp'];
            $playerMaxHp = (int)$healthState['max_hp'];
            $charDmg = (int)$healthState['attack_power'];
            $charCritDmg = (int)$healthState['critical_attack_power'];

            // Server-Side Health Gating (Rule 1, 3, 7, 8, 23, 24, 25):
            // Minimum health required to enter ANY Adventure battle is strictly 1 HP.
            // If HP = 0, reject battle start immediately.
            if ($playerCurrentHp < 1) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'allowed' => false,
                    'reason' => 'insufficient_health',
                    'current_hp' => 0,
                    'required_hp' => 1,
                    'max_hp' => $playerMaxHp,
                    'health_regeneration' => AdventureHealthService::REGEN_HP_PER_HOUR,
                    'seconds_until_next_regen' => $healthState['seconds_until_next_regen'] ?? 0,
                    'last_regen_at' => $healthState['last_regen_at'] ?? date('Y-m-d H:i:s'),
                    'message' => 'Your strength has been exhausted. Recover at least 1 HP before returning to battle.'
                ]);
                return;
            }
        } else {
            $playerCurrentHp = $playerMaxHp;
        }

        // Determine enemy details based on mode
        $enemyName = 'Corrupted Specter';
        $enemyTitle = 'Manifestation of Anxiety';
        $enemyMaxHp = 100;

        if ($mode === 'final_boss') {
            $enemyName = 'The Creator';
            $enemyTitle = 'Author of Mindrealm';
            $enemyMaxHp = AdventureProgressService::FINAL_BOSS_MAX_HP;
        } elseif ($mode === 'boss' || $bossId) {
            if ($bossId) {
                $bStmt = $this->db->prepare("SELECT name, story_title, max_hp FROM bosses WHERE id = ?");
                $bStmt->execute([$bossId]);
                $bRow = $bStmt->fetch();
                if ($bRow) {
                    $enemyName = $bRow['name'];
                    $enemyTitle = $bRow['story_title'] ?: 'Chapter Climax Guardian';
                    $enemyMaxHp = (int)($bRow['max_hp'] ?: 300);
                }
            } elseif ($chapterId) {
                $bStmt = $this->db->prepare("SELECT name, story_title, max_hp FROM bosses WHERE chapter_id = ?");
                $bStmt->execute([$chapterId]);
                $bRow = $bStmt->fetch();
                if ($bRow) {
                    $enemyName = $bRow['name'];
                    $enemyTitle = $bRow['story_title'] ?: 'Chapter Climax Guardian';
                    $enemyMaxHp = (int)($bRow['max_hp'] ?: 300);
                }
            }
        } elseif ($mode === 'tutorial') {
            $enemyName = 'Cognito Sentinel';
            $enemyTitle = 'Automaton of the Threshold';
            $enemyMaxHp = 50;
        } elseif ($partId) {
            $pStmt = $this->db->prepare("
                SELECT aps.enemy_name, aps.enemy_flavor, aps.enemy_max_hp, pp.title
                FROM psychology_parts pp
                LEFT JOIN adventure_part_story aps ON aps.part_id = pp.id
                WHERE pp.id = ?
            ");
            $pStmt->execute([$partId]);
            $pRow = $pStmt->fetch();
            if ($pRow) {
                $enemyName = $pRow['enemy_name'] ?: 'Fracture Specter';
                $enemyTitle = $pRow['enemy_flavor'] ?: ($pRow['title'] ? "Aberration of {$pRow['title']}" : 'Cognitive Aberration');
                $enemyMaxHp = (int)($pRow['enemy_max_hp'] ?: 100);
            }
        }

        $enemyCurrentHp = $enemyMaxHp;

        // Fetch exactly 50 questions internally (Section 7)
        $questions = [];
        if ($mode === 'final_boss') {
            $fStmt = $this->db->query("
                SELECT id, question, choice_a, choice_b, choice_c, choice_d, correct_answer, explanation, difficulty
                FROM learning_questions
                ORDER BY RAND()
                LIMIT 50
            ");
            $questions = $fStmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($partId) {
            $dStmt = $this->db->prepare("SELECT domain_id FROM psychology_parts WHERE id = ?");
            $dStmt->execute([$partId]);
            $domainId = $dStmt->fetchColumn();
            if ($domainId) {
                $qStmt = $this->db->prepare("
                    SELECT id, question, choice_a, choice_b, choice_c, choice_d, correct_answer, explanation, difficulty
                    FROM learning_questions
                    WHERE domain_id = ?
                    ORDER BY RAND()
                    LIMIT 50
                ");
                $qStmt->execute([$domainId]);
                $questions = $qStmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        if (count($questions) < 50) {
            $needed = 50 - count($questions);
            $existingIds = !empty($questions) ? array_column($questions, 'id') : [0];
            $inClause = implode(',', array_fill(0, count($existingIds), '?'));
            $fallbackStmt = $this->db->prepare("
                SELECT id, question, choice_a, choice_b, choice_c, choice_d, correct_answer, explanation, difficulty
                FROM (
                    SELECT id, question, choice_a, choice_b, choice_c, choice_d, correct_answer, explanation, difficulty FROM learning_questions WHERE id NOT IN ($inClause)
                    UNION
                    SELECT id, question, choice_a, choice_b, choice_c, choice_d, correct_answer, explanation, difficulty FROM questions WHERE id NOT IN ($inClause)
                ) combined
                ORDER BY RAND()
                LIMIT $needed
            ");
            $fallbackStmt->execute(array_merge($existingIds, $existingIds));
            $questions = array_merge($questions, $fallbackStmt->fetchAll(PDO::FETCH_ASSOC));
        }

        $questionSequenceJson = json_encode($questions);

        // Insert new active battle session
        $insStmt = $this->db->prepare("
            INSERT INTO adventure_active_battles
            (user_id, encounter_type, battle_mode, part_id, chapter_id, boss_id, character_id,
             player_max_health, player_current_health, enemy_name, enemy_title,
             enemy_max_health, enemy_current_health, character_damage, character_crit_damage,
             question_sequence, current_question_index, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 'active')
        ");
        $insStmt->execute([
            $userId, $mode, $battleMode, $partId, $chapterId, $bossId, (int)$char['id'],
            $playerMaxHp, $playerCurrentHp, $enemyName, $enemyTitle,
            $enemyMaxHp, $enemyCurrentHp, $charDmg, $charCritDmg,
            $questionSequenceJson
        ]);
        $battleId = (int)$this->db->lastInsertId();

        // First question to display (NO total question count revealed to client!)
        $firstQ = null;
        if (!empty($questions[0])) {
            $firstQ = [
                'id' => $questions[0]['id'],
                'question' => $questions[0]['question'],
                'choices' => [
                    'A' => $questions[0]['choice_a'],
                    'B' => $questions[0]['choice_b'],
                    'C' => $questions[0]['choice_c'],
                    'D' => $questions[0]['choice_d'],
                ],
                'difficulty' => $questions[0]['difficulty'] ?? 'intermediate'
            ];
        }

        echo json_encode([
            'success' => true,
            'battle_id' => $battleId,
            'battle_mode' => $battleMode,
            'character' => $char,
            'character_level' => $scaled['level'],
            'level' => $scaled['level'],
            'player_level' => $scaled['level'],
            'player_max_health' => $playerMaxHp,
            'player_current_health' => $playerCurrentHp,
            'player_hp' => $playerCurrentHp,
            'max_health' => $playerMaxHp,
            'current_hp' => $playerCurrentHp,
            'character_damage' => $charDmg,
            'character_crit_damage' => $charCritDmg,
            'damage' => $charDmg,
            'crit_damage' => $charCritDmg,
            'attack_power' => $charDmg,
            'critical_attack_power' => $charCritDmg,
            'enemy_name' => $enemyName,
            'enemy_title' => $enemyTitle,
            'enemy_max_health' => $enemyMaxHp,
            'enemy_current_health' => $enemyCurrentHp,
            'first_question' => $firstQ,
            'questions' => ($mode === 'final_boss') ? array_map(function ($q) {
                return [
                    'id' => $q['id'],
                    'question' => $q['question'],
                    'choices' => ['A' => $q['choice_a'], 'B' => $q['choice_b'], 'C' => $q['choice_c'], 'D' => $q['choice_d']],
                    'difficulty' => $q['difficulty'] ?? 'intermediate',
                ];
            }, $questions) : null,
            'status' => 'active',
            'battle_status' => 'active'
        ]);
    }

    // ── POST /adventure/battle/action ─────────────────────────
    public function battleAction(): void {
        $userId = AuthMiddleware::userId();
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $battleId = (int)($input['battle_id'] ?? 0);

        $result = $this->battleEngine->resolveBattleAttempt($userId, $battleId, $input);
        http_response_code($result['status']);
        echo json_encode($result['data']);
    }

    // ── GET /adventure/health ─────────────────────────────────
    public function health(): void {
        $userId = AuthMiddleware::userId();
        require_once __DIR__ . '/../services/AdventureHealthService.php';
        $healthState = AdventureHealthService::getInstance()->syncAndGetHealth($userId);
        echo json_encode(['success' => true, 'health' => $healthState]);
    }
}
