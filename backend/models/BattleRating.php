<?php
require_once __DIR__ . '/../config/database.php';

class BattleRating {
    private PDO $db;
    const DEFAULT_RATING = 1200;
    const K_FACTOR = 32;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getByUserId(int $userId): int {
        $stmt = $this->db->prepare("SELECT battle_rating FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row ? (int)$row['battle_rating'] : self::DEFAULT_RATING;
    }

    /**
     * Calculate ELO-based rating changes for a match.
     * Returns ['player_change' => int, 'opponent_change' => int]
     */
    public function calculateElo(int $playerRating, int $opponentRating, string $outcome): array {
        // Expected scores
        $expectedPlayer = 1 / (1 + pow(10, ($opponentRating - $playerRating) / 400));
        $expectedOpponent = 1 - $expectedPlayer;

        // Actual scores
        if ($outcome === 'won') {
            $actualPlayer = 1;
            $actualOpponent = 0;
        } elseif ($outcome === 'lost') {
            $actualPlayer = 0;
            $actualOpponent = 1;
        } else { // draw
            $actualPlayer = 0.5;
            $actualOpponent = 0.5;
        }

        $playerChange   = (int) round(self::K_FACTOR * ($actualPlayer - $expectedPlayer));
        $opponentChange = (int) round(self::K_FACTOR * ($actualOpponent - $expectedOpponent));

        return [
            'player_change'   => $playerChange,
            'opponent_change' => $opponentChange,
            'new_player_rating'   => max(800, $playerRating + $playerChange),
            'new_opponent_rating' => max(800, $opponentRating + $opponentChange),
        ];
    }

    public function updateRating(int $userId, int $newRating): void {
        $stmt = $this->db->prepare("UPDATE users SET battle_rating = ? WHERE id = ?");
        $stmt->execute([$newRating, $userId]);
    }

    public function recordHistory(int $userId, int $matchId, int $before, int $after, string $outcome): void {
        $stmt = $this->db->prepare(
            "INSERT INTO rating_history (user_id, match_id, rating_before, rating_after, rating_change, outcome)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$userId, $matchId, $before, $after, $after - $before, $outcome]);
    }

    public function updateBattleStats(int $userId, string $outcome): void {
        $field = match ($outcome) {
            'won'  => 'wins',
            'lost' => 'losses',
            'draw' => 'draws',
            default => null,
        };
        if ($field) {
            $stmt = $this->db->prepare("UPDATE users SET {$field} = {$field} + 1, total_battles = total_battles + 1 WHERE id = ?");
            $stmt->execute([$userId]);
        }
    }

    public function getLeaderboard(string $type = 'rating', int $limit = 50): array {
        if ($type === 'rating') {
            $sql = "SELECT u.id, u.username, u.nickname, u.avatar_style, u.avatar_seed, u.avatar_options,
                           u.battle_rating, u.wins, u.losses, u.draws, u.total_battles, u.level, u.xp, u.created_at
                    FROM users u
                    WHERE u.total_battles > 0
                    ORDER BY u.battle_rating DESC, u.wins DESC
                    LIMIT ?";
        } elseif ($type === 'level') {
            $sql = "SELECT u.id, u.username, u.nickname, u.avatar_style, u.avatar_seed, u.avatar_options,
                           u.level, u.xp, u.battle_rating, u.wins, u.losses, u.draws, u.total_battles, u.created_at
                    FROM users u
                    ORDER BY u.level DESC, u.xp DESC
                    LIMIT ?";
        } elseif ($type === 'weekly') {
            $sql = "SELECT u.id, u.username, u.avatar_style, u.avatar_seed, u.avatar_options,
                           u.battle_rating, u.level,
                           COUNT(mp.id) AS weekly_wins
                    FROM users u
                    JOIN match_players mp ON mp.user_id = u.id AND mp.outcome = 'won'
                    JOIN matches m ON mp.match_id = m.id AND m.ended_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    GROUP BY u.id
                    ORDER BY weekly_wins DESC, u.battle_rating DESC
                    LIMIT ?";
        } else {
            return [];
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            if (!empty($row['avatar_options'])) {
                $row['avatar_options'] = json_decode($row['avatar_options'], true);
            }
        }
        return $rows;
    }
}
