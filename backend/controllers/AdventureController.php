<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Question.php';
require_once __DIR__ . '/../services/CurriculumService.php';
require_once __DIR__ . '/../services/AdventureProgressService.php';
require_once __DIR__ . '/../services/AdaptiveDifficultyService.php';
require_once __DIR__ . '/../database/PsychiatricCurriculumMigration.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class AdventureController {
    private User $userModel;
    private PDO $db;
    private AdventureProgressService $progressService;

    public function __construct() {
        PsychiatricCurriculumMigration::run();
        $this->userModel = new User();
        $this->db = Database::getInstance();
        $this->progressService = new AdventureProgressService();
    }

    // ── GET /adventure/chapters ───────────────────────────────
    public function chapters(): void {
        $userId = AuthMiddleware::userId();
        $result = $this->progressService->getAdventureMap($userId);
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
}
