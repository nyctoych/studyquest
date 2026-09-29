<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT id, username, nickname, email, points, xp, level, streak, longest_streak, coins, battle_rating, wins, losses, draws, total_battles, last_quest_date, avatar_style, avatar_seed, avatar_options, is_new_player, created_at FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if ($user) {
            if (!empty($user['avatar_options']) && is_string($user['avatar_options'])) {
                $user['avatar_options'] = json_decode($user['avatar_options'], true);
            }
            $eqStmt = $this->db->prepare(
                "SELECT ue.slot, i.id, i.name, i.icon,
                        i.effect_key, i.preview_css, i.rarity, ic.slug as category_slug, ic.name as category_name
                 FROM user_equipped_items ue
                 JOIN items i ON ue.item_id = i.id
                 JOIN item_categories ic ON i.category_id = ic.id
                 WHERE ue.user_id = ?"
            );
            $eqStmt->execute([$id]);
            $user['equipped'] = $eqStmt->fetchAll() ?: [];
        }
        return $user ?: null;
    }

    public function findByNickname(string $nickname): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE nickname = ?");
        $stmt->execute([$nickname]);
        return $stmt->fetch() ?: null;
    }

    public function findByEmail(string $email): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public function findByUsername(string $username): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch() ?: null;
    }

    public function create(string $username, string $email, string $password, string $nickname = ''): int {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $nick = !empty($nickname) ? $nickname : $username;
        $stmt = $this->db->prepare("INSERT INTO users (username, nickname, email, password, is_new_player) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute([$username, $nick, $email, $hashed]);
        return (int) $this->db->lastInsertId();
    }

    public function addCoins(int $userId, int $coins): int {
        $stmt = $this->db->prepare("UPDATE users SET coins = COALESCE(coins, 0) + ? WHERE id = ?");
        $stmt->execute([$coins, $userId]);
        $user = $this->findById($userId);
        return (int)($user['coins'] ?? 0);
    }

    public function spendCoins(int $userId, int $cost): bool {
        $user = $this->findById($userId);
        if (($user['coins'] ?? 0) < $cost) return false;
        $stmt = $this->db->prepare("UPDATE users SET coins = coins - ? WHERE id = ?");
        return $stmt->execute([$cost, $userId]);
    }

    public function completeTutorial(int $userId): bool {
        $stmt = $this->db->prepare("UPDATE users SET is_new_player = 0 WHERE id = ?");
        return $stmt->execute([$userId]);
    }

    public function updateNickname(int $userId, string $nickname): bool {
        $stmt = $this->db->prepare("UPDATE users SET nickname = ? WHERE id = ?");
        return $stmt->execute([$nickname, $userId]);
    }

    public function verifyPassword(string $input, string $hashed): bool {
        return password_verify($input, $hashed);
    }

    public function addXP(int $userId, int $xp): array {
        $user = $this->findById($userId);
        $newXp = $user['xp'] + $xp;
        $newLevel = $this->calculateLevel($newXp);
        $leveledUp = $newLevel > $user['level'];

        $stmt = $this->db->prepare("UPDATE users SET xp = ?, level = ? WHERE id = ?");
        $stmt->execute([$newXp, $newLevel, $userId]);

        return [
            'xp_gained' => $xp,
            'new_xp' => $newXp,
            'new_level' => $newLevel,
            'leveled_up' => $leveledUp,
            'previous_level' => $user['level'],
        ];
    }

    public function addPoints(int $userId, int $points): int {
        $stmt = $this->db->prepare("UPDATE users SET points = points + ? WHERE id = ?");
        $stmt->execute([$points, $userId]);
        $user = $this->findById($userId);
        return $user['points'];
    }


    public function spendPoints(int $userId, int $cost): bool {
        $user = $this->findById($userId);
        if ($user['points'] < $cost) return false;
        $stmt = $this->db->prepare("UPDATE users SET points = points - ? WHERE id = ?");
        return $stmt->execute([$cost, $userId]);
    }

    public function updateStreak(int $userId): array {
        $user = $this->findById($userId);
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $lastDate = $user['last_quest_date'];

        $newStreak = $user['streak'];
        $streakBroken = false;

        if ($lastDate === $today) {
            // Already completed a quest today, streak unchanged
        } elseif ($lastDate === $yesterday) {
            // Consecutive day!
            $newStreak++;
        } else {
            // Streak broken
            $newStreak = 1;
            $streakBroken = ($user['streak'] > 0 && $lastDate !== null);
        }

        $newLongest = max($user['longest_streak'], $newStreak);

        $stmt = $this->db->prepare("UPDATE users SET streak = ?, longest_streak = ?, last_quest_date = ? WHERE id = ?");
        $stmt->execute([$newStreak, $newLongest, $today, $userId]);

        return [
            'streak' => $newStreak,
            'longest_streak' => $newLongest,
            'streak_broken' => $streakBroken,
        ];
    }

    public function calculateLevel(int $xp): int {
        // XP required per level: level * 1000
        $level = 1;
        $required = 1000;
        while ($xp >= $required) {
            $xp -= $required;
            $level++;
            $required = $level * 1000;
        }
        return $level;
    }

    public function getXpForNextLevel(int $level): int {
        return $level * 1000;
    }

    public function getXpInCurrentLevel(int $totalXp, int $level): int {
        $xpUsed = 0;
        for ($l = 1; $l < $level; $l++) {
            $xpUsed += $l * 1000;
        }
        return $totalXp - $xpUsed;
    }

    public function update(int $userId, array $data): bool {
        $allowed = ['username', 'email'];
        $fields = [];
        $values = [];
        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }
        if (empty($fields)) return false;
        $values[] = $userId;
        $stmt = $this->db->prepare("UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?");
        return $stmt->execute($values);
    }

    public function getStats(int $userId): array {
        $user = $this->findById($userId);
        $level = $user['level'];
        $totalXp = $user['xp'];
        $xpInLevel = $this->getXpInCurrentLevel($totalXp, $level);
        $xpForNext = $this->getXpForNextLevel($level);

        // Quest counts
        $stmt = $this->db->prepare("SELECT status, COUNT(*) as count FROM quests WHERE user_id = ? GROUP BY status");
        $stmt->execute([$userId]);
        $questCounts = ['pending' => 0, 'in_progress' => 0, 'completed' => 0, 'failed' => 0];
        foreach ($stmt->fetchAll() as $row) {
            $questCounts[$row['status']] = (int)$row['count'];
        }

        // Achievement count
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM user_achievements WHERE user_id = ?");
        $stmt->execute([$userId]);
        $achievementCount = (int) $stmt->fetchColumn();

        // Boss wins
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM boss_attempts WHERE user_id = ? AND status = 'won'");
        $stmt->execute([$userId]);
        $bossWins = (int) $stmt->fetchColumn();

        return [
            'level' => $level,
            'xp' => $totalXp,
            'xp_in_level' => $xpInLevel,
            'xp_for_next_level' => $xpForNext,
            'points' => $user['points'],
            'coins' => (int)($user['coins'] ?? 0),
            'battle_rating' => (int)($user['battle_rating'] ?? 1200),
            'wins' => (int)($user['wins'] ?? 0),
            'losses' => (int)($user['losses'] ?? 0),
            'streak' => $user['streak'],
            'longest_streak' => $user['longest_streak'],
            'quests' => $questCounts,
            'achievements' => $achievementCount,
            'boss_wins' => $bossWins,
            'nickname' => $user['nickname'] ?? $user['username'],
            'is_new_player' => (bool)($user['is_new_player'] ?? false),
        ];
    }
}
