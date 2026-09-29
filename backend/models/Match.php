<?php
require_once __DIR__ . '/../config/database.php';

class BattleMatch {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create(string $roomId, string $mode = 'ranked', int $questionsCount = 10): int {
        $stmt = $this->db->prepare(
            "INSERT INTO matches (room_id, status, battle_mode, questions_count) VALUES (?, 'waiting', ?, ?)"
        );
        $stmt->execute([$roomId, $mode, $questionsCount]);
        return (int) $this->db->lastInsertId();
    }

    public function addPlayer(int $matchId, int $userId, string $slot, int $ratingBefore): int {
        $stmt = $this->db->prepare(
            "INSERT INTO match_players (match_id, user_id, player_slot, rating_before) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$matchId, $userId, $slot, $ratingBefore]);
        return (int) $this->db->lastInsertId();
    }

    public function addQuestion(int $matchId, int $questionId, int $order): void {
        $stmt = $this->db->prepare(
            "INSERT INTO match_questions (match_id, question_id, question_order) VALUES (?, ?, ?)"
        );
        $stmt->execute([$matchId, $questionId, $order]);
    }

    public function start(int $matchId): void {
        $stmt = $this->db->prepare(
            "UPDATE matches SET status = 'in_progress', started_at = NOW() WHERE id = ?"
        );
        $stmt->execute([$matchId]);
    }

    public function complete(int $matchId): void {
        $stmt = $this->db->prepare(
            "UPDATE matches SET status = 'completed', ended_at = NOW() WHERE id = ?"
        );
        $stmt->execute([$matchId]);
    }

    public function updatePlayerResult(int $matchId, int $userId, array $data): void {
        $stmt = $this->db->prepare(
            "UPDATE match_players 
             SET hp_end = ?, correct_answers = ?, wrong_answers = ?,
                 outcome = ?, xp_earned = ?, coins_earned = ?,
                 rating_after = ?, rating_change = ?
             WHERE match_id = ? AND user_id = ?"
        );
        $stmt->execute([
            $data['hp_end'],
            $data['correct_answers'],
            $data['wrong_answers'],
            $data['outcome'],
            $data['xp_earned'],
            $data['coins_earned'],
            $data['rating_after'],
            $data['rating_change'],
            $matchId,
            $userId,
        ]);
    }

    public function getByRoomId(string $roomId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM matches WHERE room_id = ?");
        $stmt->execute([$roomId]);
        $match = $stmt->fetch();
        if (!$match) return null;

        // Load players
        $pStmt = $this->db->prepare(
            "SELECT mp.*, u.username, u.nickname
             FROM match_players mp
             JOIN users u ON mp.user_id = u.id
             WHERE mp.match_id = ?"
        );
        $pStmt->execute([$match['id']]);
        $match['players'] = $pStmt->fetchAll();

        return $match;
    }

    public function getHistory(int $userId, int $limit = 20): array {
        $stmt = $this->db->prepare(
            "SELECT m.id, m.room_id, m.status, m.battle_mode, m.started_at, m.ended_at,
                    mp.outcome, mp.hp_end, mp.correct_answers, mp.wrong_answers,
                    mp.xp_earned, mp.coins_earned, mp.rating_before, mp.rating_after, mp.rating_change,
                    opp.username AS opponent_username
             FROM matches m
             JOIN match_players mp ON m.id = mp.match_id AND mp.user_id = ?
             LEFT JOIN match_players opp_mp ON m.id = opp_mp.match_id AND opp_mp.user_id != ?
             LEFT JOIN users opp ON opp_mp.user_id = opp.id
             WHERE m.status = 'completed'
             ORDER BY m.ended_at DESC
             LIMIT ?"
        );
        $stmt->execute([$userId, $userId, $limit]);
        return $stmt->fetchAll();
    }

    public function getStats(int $userId): array {
        $stmt = $this->db->prepare(
            "SELECT 
                COUNT(*) AS total_battles,
                SUM(CASE WHEN outcome = 'won' THEN 1 ELSE 0 END) AS wins,
                SUM(CASE WHEN outcome = 'lost' THEN 1 ELSE 0 END) AS losses,
                SUM(CASE WHEN outcome = 'draw' THEN 1 ELSE 0 END) AS draws,
                SUM(xp_earned) AS total_xp_earned,
                SUM(coins_earned) AS total_coins_earned,
                AVG(correct_answers) AS avg_correct
             FROM match_players mp
             JOIN matches m ON mp.match_id = m.id
             WHERE mp.user_id = ? AND m.status = 'completed'"
        );
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: [];
    }
}
