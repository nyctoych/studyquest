<?php
/**
 * StudyQuest — Adventure Story Migration
 * Creates tables and seeds rich 12-Chapter Dark Fantasy story campaign with 120 parts.
 */

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/AdventureStoryDataset.php';

class AdventureStoryMigration {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function run(): void {
        echo "=== Running Adventure Story Migration ===\n";

        $this->createAdventureChaptersTable();
        $this->alterAdventurePartStoryTable();
        $this->alterBossesTable();
        $this->createStoryFlagsTable();
        $this->seedChaptersAndBosses();
        $this->seedAdventureParts();

        echo "=== Adventure Story Migration Complete ===\n";
    }

    private function createAdventureChaptersTable(): void {
        $sql = "CREATE TABLE IF NOT EXISTS adventure_chapters (
            id INT AUTO_INCREMENT PRIMARY KEY,
            domain_id INT NOT NULL,
            chapter_number INT NOT NULL UNIQUE,
            act_number INT NOT NULL DEFAULT 1,
            act_title VARCHAR(100) NOT NULL,
            title VARCHAR(255) NOT NULL,
            story_title VARCHAR(255) NOT NULL,
            setting_description TEXT,
            story_intro TEXT,
            story_outro TEXT,
            boss_id INT NULL,
            boss_name VARCHAR(150),
            mind_seal_name VARCHAR(150),
            fracture_shard_name VARCHAR(150),
            display_order INT DEFAULT 1,
            status ENUM('active','coming_soon') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $this->db->exec($sql);
        echo "✓ Table `adventure_chapters` ready.\n";
    }

    private function alterAdventurePartStoryTable(): void {
        $cols = $this->db->query("SHOW COLUMNS FROM adventure_part_story")->fetchAll(PDO::FETCH_COLUMN);

        $addCols = [
            'story_title' => "VARCHAR(255) NULL AFTER part_id",
            'narrative_phase' => "VARCHAR(50) DEFAULT 'discovery' AFTER story_title",
            'speaker_title' => "VARCHAR(100) NULL AFTER dialogue_speaker",
            'academic_connection' => "TEXT NULL AFTER dialogue_text",
            'fracture_clue' => "TEXT NULL AFTER academic_connection",
        ];

        foreach ($addCols as $col => $def) {
            if (!in_array($col, $cols)) {
                $this->db->exec("ALTER TABLE adventure_part_story ADD COLUMN $col $def");
                echo "✓ Added column `$col` to `adventure_part_story`.\n";
            }
        }
    }

    private function alterBossesTable(): void {
        $cols = $this->db->query("SHOW COLUMNS FROM bosses")->fetchAll(PDO::FETCH_COLUMN);

        $addCols = [
            'story_title' => "VARCHAR(255) NULL AFTER name",
            'story_intro' => "TEXT NULL",
            'dialogue_intro' => "TEXT NULL",
            'dialogue_defeat' => "TEXT NULL",
            'restoration_text' => "TEXT NULL",
            'mind_seal_name' => "VARCHAR(150) NULL",
            'fracture_shard_name' => "VARCHAR(150) NULL",
        ];

        foreach ($addCols as $col => $def) {
            if (!in_array($col, $cols)) {
                $this->db->exec("ALTER TABLE bosses ADD COLUMN $col $def");
                echo "✓ Added column `$col` to `bosses`.\n";
            }
        }
    }

    private function createStoryFlagsTable(): void {
        $sql = "CREATE TABLE IF NOT EXISTS story_flags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            flag_key VARCHAR(100) NOT NULL,
            flag_value TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_user_flag (user_id, flag_key),
            INDEX idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $this->db->exec($sql);
        echo "✓ Table `story_flags` ready.\n";
    }

    private function seedChaptersAndBosses(): void {
        $chapters = AdventureStoryDataset::getChapters();

        foreach ($chapters as $chNum => $c) {
            // 1. Sync or insert boss in `bosses` table
            $stmtBoss = $this->db->prepare("SELECT id FROM bosses WHERE chapter_id = ? LIMIT 1");
            $stmtBoss->execute([$chNum]);
            $bossRow = $stmtBoss->fetch();

            if ($bossRow) {
                $bossId = (int)$bossRow['id'];
                $updBoss = $this->db->prepare("UPDATE bosses SET
                    name = ?,
                    story_title = ?,
                    description = ?,
                    portrait_asset = ?,
                    idle_asset = ?,
                    image = ?,
                    story_intro = ?,
                    dialogue_intro = ?,
                    dialogue_defeat = ?,
                    restoration_text = ?,
                    mind_seal_name = ?,
                    fracture_shard_name = ?,
                    max_hp = ?,
                    xp_reward = ?,
                    point_reward = ?
                    WHERE id = ?");
                $updBoss->execute([
                    $c['boss_name'],
                    $c['boss_title'],
                    $c['setting_description'],
                    $c['boss_portrait'],
                    $c['boss_animated'],
                    $c['boss_portrait'],
                    $c['story_intro'],
                    $c['boss_dialogue_intro'],
                    $c['boss_dialogue_defeat'],
                    $c['story_outro'],
                    $c['mind_seal_name'],
                    $c['fracture_shard_name'],
                    $c['boss_max_hp'],
                    $c['boss_xp'],
                    $c['boss_coins'],
                    $bossId
                ]);
            } else {
                $insBoss = $this->db->prepare("INSERT INTO bosses (
                    name, story_title, description, chapter_id, boss_order, difficulty, max_hp,
                    portrait_asset, idle_asset, image, story_intro, dialogue_intro, dialogue_defeat,
                    restoration_text, mind_seal_name, fracture_shard_name, xp_reward, point_reward
                ) VALUES (?, ?, ?, ?, ?, 'hard', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $insBoss->execute([
                    $c['boss_name'],
                    $c['boss_title'],
                    $c['setting_description'],
                    $chNum,
                    1,
                    $c['boss_max_hp'],
                    $c['boss_portrait'],
                    $c['boss_animated'],
                    $c['boss_portrait'],
                    $c['story_intro'],
                    $c['boss_dialogue_intro'],
                    $c['boss_dialogue_defeat'],
                    $c['story_outro'],
                    $c['mind_seal_name'],
                    $c['fracture_shard_name'],
                    $c['boss_xp'],
                    $c['boss_coins']
                ]);
                $bossId = (int)$this->db->lastInsertId();
            }

            // 2. Sync into `adventure_chapters`
            $checkCh = $this->db->prepare("SELECT id FROM adventure_chapters WHERE chapter_number = ?");
            $checkCh->execute([$chNum]);
            $chExists = $checkCh->fetch();

            if ($chExists) {
                $updCh = $this->db->prepare("UPDATE adventure_chapters SET
                    domain_id = ?,
                    act_number = ?,
                    act_title = ?,
                    title = ?,
                    story_title = ?,
                    setting_description = ?,
                    story_intro = ?,
                    story_outro = ?,
                    boss_id = ?,
                    boss_name = ?,
                    mind_seal_name = ?,
                    fracture_shard_name = ?,
                    display_order = ?
                    WHERE chapter_number = ?");
                $updCh->execute([
                    $c['domain_id'],
                    $c['act_number'],
                    $c['act_title'],
                    $c['domain_title'],
                    $c['story_title'],
                    $c['setting_description'],
                    $c['story_intro'],
                    $c['story_outro'],
                    $bossId,
                    $c['boss_name'],
                    $c['mind_seal_name'],
                    $c['fracture_shard_name'],
                    $chNum,
                    $chNum
                ]);
            } else {
                $insCh = $this->db->prepare("INSERT INTO adventure_chapters (
                    chapter_number, domain_id, act_number, act_title, title, story_title,
                    setting_description, story_intro, story_outro, boss_id, boss_name,
                    mind_seal_name, fracture_shard_name, display_order
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $insCh->execute([
                    $chNum,
                    $c['domain_id'],
                    $c['act_number'],
                    $c['act_title'],
                    $c['domain_title'],
                    $c['story_title'],
                    $c['setting_description'],
                    $c['story_intro'],
                    $c['story_outro'],
                    $bossId,
                    $c['boss_name'],
                    $c['mind_seal_name'],
                    $c['fracture_shard_name'],
                    $chNum
                ]);
            }
        }
        echo "✓ Seeded 12 Chapters and Bosses into `adventure_chapters` and `bosses`.\n";
    }

    private function seedAdventureParts(): void {
        $parts = AdventureStoryDataset::getParts();
        $synced = 0;

        foreach ($parts as $p) {
            // Find corresponding psychology_parts id
            $findPart = $this->db->prepare("SELECT id FROM psychology_parts WHERE domain_id = ? AND part_number = ? LIMIT 1");
            $findPart->execute([$p['chapter_id'], $p['part_number']]);
            $partRow = $findPart->fetch();

            if (!$partRow) {
                continue;
            }
            $partId = (int)$partRow['id'];

            // Upsert in adventure_part_story
            $checkStory = $this->db->prepare("SELECT id FROM adventure_part_story WHERE part_id = ?");
            $checkStory->execute([$partId]);
            $storyExists = $checkStory->fetch();

            $enemyHp = self::calculateEnemyHp((int)($p['chapter_id'] ?? 1), (int)$p['part_number']);

            if ($storyExists) {
                $updStory = $this->db->prepare("UPDATE adventure_part_story SET
                    story_title = ?,
                    narrative_phase = ?,
                    story_intro = ?,
                    dialogue_speaker = ?,
                    dialogue_text = ?,
                    academic_connection = ?,
                    story_after = ?,
                    enemy_name = ?,
                    enemy_flavor = ?,
                    enemy_max_hp = ?
                    WHERE part_id = ?");
                $updStory->execute([
                    $p['story_title'],
                    $p['phase'],
                    $p['intro'],
                    $p['speaker'],
                    $p['dialogue'],
                    $p['academic'],
                    $p['after'] ?? null,
                    $p['enemy'],
                    $p['flavor'],
                    $enemyHp,
                    $partId
                ]);
            } else {
                $insStory = $this->db->prepare("INSERT INTO adventure_part_story (
                    part_id, story_title, narrative_phase, story_intro, dialogue_speaker,
                    dialogue_text, academic_connection, story_after, enemy_name, enemy_flavor, enemy_max_hp
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $insStory->execute([
                    $partId,
                    $p['story_title'],
                    $p['phase'],
                    $p['intro'],
                    $p['speaker'],
                    $p['dialogue'],
                    $p['academic'],
                    $p['after'] ?? null,
                    $p['enemy'],
                    $p['flavor'],
                    $enemyHp
                ]);
            }
            $synced++;
        }
        echo "✓ Seeded $synced adventure parts into `adventure_part_story`.\n";
    }

    public static function calculateEnemyHp(int $chapterId, int $partNumber): int {
        $configs = [
            1  => ['base' => 30,  'step' => 5],   // Part 1: 30 HP, Part 10: 75 HP (easy for beginners dealing 10-15 dmg)
            2  => ['base' => 50,  'step' => 6],   // Part 1: 50 HP, Part 10: 104 HP
            3  => ['base' => 75,  'step' => 7],   // Part 1: 75 HP, Part 10: 138 HP
            4  => ['base' => 105, 'step' => 8],   // Part 1: 105 HP, Part 10: 177 HP
            5  => ['base' => 140, 'step' => 9],   // Part 1: 140 HP, Part 10: 221 HP
            6  => ['base' => 180, 'step' => 10],  // Part 1: 180 HP, Part 10: 270 HP
            7  => ['base' => 225, 'step' => 12],  // Part 1: 225 HP, Part 10: 333 HP
            8  => ['base' => 275, 'step' => 14],  // Part 1: 275 HP, Part 10: 401 HP
            9  => ['base' => 330, 'step' => 16],  // Part 1: 330 HP, Part 10: 474 HP
            10 => ['base' => 390, 'step' => 18],  // Part 1: 390 HP, Part 10: 552 HP
            11 => ['base' => 455, 'step' => 20],  // Part 1: 455 HP, Part 10: 635 HP
            12 => ['base' => 525, 'step' => 25],  // Part 1: 525 HP, Part 10: 750 HP
        ];
        $cfg = $configs[$chapterId] ?? ['base' => 30 + max(0, $chapterId - 1) * 45, 'step' => 5 + max(0, $chapterId - 1) * 2];
        return $cfg['base'] + (($partNumber - 1) * $cfg['step']);
    }
}

// Execute migration
$migration = new AdventureStoryMigration();
$migration->run();
