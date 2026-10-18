<?php
require_once __DIR__ . '/../config/Database.php';

class AchievementSystemMigration {
    public static function run(): void {
        $db = Database::getInstance();

        // 1. Table: achievement_cards (Exactly 30 cards)
        $db->exec("
            CREATE TABLE IF NOT EXISTS achievement_cards (
                id INT AUTO_INCREMENT PRIMARY KEY,
                card_key VARCHAR(64) NOT NULL UNIQUE,
                title VARCHAR(150) NOT NULL,
                subtitle VARCHAR(255) NOT NULL,
                category ENUM('adventure', 'studyhall', 'progression', 'multiplayer') NOT NULL,
                icon VARCHAR(32) NOT NULL DEFAULT 'trophy',
                display_order INT NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 2. Table: achievement_milestones (Exactly 32 claimable milestones)
        $db->exec("
            CREATE TABLE IF NOT EXISTS achievement_milestones (
                id INT AUTO_INCREMENT PRIMARY KEY,
                card_id INT NOT NULL,
                milestone_key VARCHAR(64) NOT NULL UNIQUE,
                title VARCHAR(150) NOT NULL,
                description TEXT NOT NULL,
                condition_type VARCHAR(50) NOT NULL,
                condition_value INT NOT NULL DEFAULT 1,
                xp_reward INT NOT NULL DEFAULT 100,
                coins_reward INT NOT NULL DEFAULT 50,
                display_order INT NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_card (card_id),
                INDEX idx_condition (condition_type, condition_value)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 3. Table: user_milestone_progress
        $db->exec("
            CREATE TABLE IF NOT EXISTS user_milestone_progress (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                milestone_id INT NOT NULL,
                is_unlocked TINYINT(1) NOT NULL DEFAULT 0,
                unlocked_at TIMESTAMP NULL DEFAULT NULL,
                is_claimed TINYINT(1) NOT NULL DEFAULT 0,
                claimed_at TIMESTAMP NULL DEFAULT NULL,
                share_token VARCHAR(64) NULL UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_user_milestone (user_id, milestone_id),
                INDEX idx_user_unlocked (user_id, is_unlocked),
                INDEX idx_user_claimed (user_id, is_claimed)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 4. Seed the 30 Cards and 32 Milestones
        self::seedDefinitions($db);
    }

    private static function seedDefinitions(PDO $db): void {
        // Definitions for exactly 30 Cards and 32 Milestones
        $cards = [
            // ── A. 12 ADVENTURE GATE CHAPTER CARDS ──
            [
                'key' => 'adv_ch_1',
                'title' => 'Chapter 1: The Cradle of Thought',
                'subtitle' => 'Neurodevelopmental Disorders',
                'category' => 'adventure',
                'icon' => 'sword',
                'order' => 1,
                'milestones' => [
                    [
                        'key' => 'adv_ch_1_complete',
                        'title' => 'Cradle of Thought Conquered',
                        'desc' => 'Vanquish The Mind In Discord and liberate Chapter 1 of the Adventure Gate.',
                        'type' => 'adventure_chapter',
                        'value' => 1,
                        'xp' => 250,
                        'coins' => 100,
                        'order' => 1
                    ]
                ]
            ],
            [
                'key' => 'adv_ch_2',
                'title' => 'Chapter 2: The City Behind the Veil',
                'subtitle' => 'Schizophrenia Spectrum & Psychotic Disorders',
                'category' => 'adventure',
                'icon' => 'sword',
                'order' => 2,
                'milestones' => [
                    [
                        'key' => 'adv_ch_2_complete',
                        'title' => 'City Behind the Veil Conquered',
                        'desc' => 'Dispel the phantoms of The Fragmented Sovereign and conquer Chapter 2.',
                        'type' => 'adventure_chapter',
                        'value' => 2,
                        'xp' => 250,
                        'coins' => 100,
                        'order' => 1
                    ]
                ]
            ],
            [
                'key' => 'adv_ch_3',
                'title' => 'Chapter 3: The Kingdom Between Two Moons',
                'subtitle' => 'Bipolar & Related Disorders',
                'category' => 'adventure',
                'icon' => 'sword',
                'order' => 3,
                'milestones' => [
                    [
                        'key' => 'adv_ch_3_complete',
                        'title' => 'Kingdom Between Two Moons Conquered',
                        'desc' => 'Restore equilibrium against The Twin Extremes and liberate Chapter 3.',
                        'type' => 'adventure_chapter',
                        'value' => 3,
                        'xp' => 300,
                        'coins' => 120,
                        'order' => 1
                    ]
                ]
            ],
            [
                'key' => 'adv_ch_4',
                'title' => 'Chapter 4: The City That Lost Its Light',
                'subtitle' => 'Depressive Disorders',
                'category' => 'adventure',
                'icon' => 'sword',
                'order' => 4,
                'milestones' => [
                    [
                        'key' => 'adv_ch_4_complete',
                        'title' => 'City of Vanished Light Conquered',
                        'desc' => 'Overcome the shadows of The Deep Abyss and rekindle Chapter 4.',
                        'type' => 'adventure_chapter',
                        'value' => 4,
                        'xp' => 300,
                        'coins' => 120,
                        'order' => 1
                    ]
                ]
            ],
            [
                'key' => 'adv_ch_5',
                'title' => 'Chapter 5: The City of Countless Warnings',
                'subtitle' => 'Anxiety Disorders',
                'category' => 'adventure',
                'icon' => 'sword',
                'order' => 5,
                'milestones' => [
                    [
                        'key' => 'adv_ch_5_complete',
                        'title' => 'City of Warnings Conquered',
                        'desc' => 'Quiet the alarms of The Panic Sentinel and secure Chapter 5.',
                        'type' => 'adventure_chapter',
                        'value' => 5,
                        'xp' => 350,
                        'coins' => 150,
                        'order' => 1
                    ]
                ]
            ],
            [
                'key' => 'adv_ch_6',
                'title' => 'Chapter 6: The Academy of Endless Rules',
                'subtitle' => 'Obsessive-Compulsive & Related Disorders',
                'category' => 'adventure',
                'icon' => 'sword',
                'order' => 6,
                'milestones' => [
                    [
                        'key' => 'adv_ch_6_complete',
                        'title' => 'Academy of Rules Conquered',
                        'desc' => 'Break the rigid compulsions of The Unyielding Warden in Chapter 6.',
                        'type' => 'adventure_chapter',
                        'value' => 6,
                        'xp' => 350,
                        'coins' => 150,
                        'order' => 1
                    ]
                ]
            ],
            [
                'key' => 'adv_ch_7',
                'title' => 'Chapter 7: The Broken Memory Realm',
                'subtitle' => 'Trauma- & Stressor-Related Disorders',
                'category' => 'adventure',
                'icon' => 'sword',
                'order' => 7,
                'milestones' => [
                    [
                        'key' => 'adv_ch_7_complete',
                        'title' => 'Broken Memory Realm Conquered',
                        'desc' => 'Integrate the fractured mirror of The Shattered Self and conquer Chapter 7.',
                        'type' => 'adventure_chapter',
                        'value' => 7,
                        'xp' => 400,
                        'coins' => 180,
                        'order' => 1
                    ]
                ]
            ],
            [
                'key' => 'adv_ch_8',
                'title' => 'Chapter 8: The Cathedral of Signals',
                'subtitle' => 'Somatic Symptom & Related Disorders',
                'category' => 'adventure',
                'icon' => 'sword',
                'order' => 8,
                'milestones' => [
                    [
                        'key' => 'adv_ch_8_complete',
                        'title' => 'Cathedral of Signals Conquered',
                        'desc' => 'Calm the autonomic tremors of The Body That Speaks in Chapter 8.',
                        'type' => 'adventure_chapter',
                        'value' => 8,
                        'xp' => 400,
                        'coins' => 180,
                        'order' => 1
                    ]
                ]
            ],
            [
                'key' => 'adv_ch_9',
                'title' => 'Chapter 9: The Kingdom of Impossible Rules',
                'subtitle' => 'Feeding & Eating Disorders',
                'category' => 'adventure',
                'icon' => 'sword',
                'order' => 9,
                'milestones' => [
                    [
                        'key' => 'adv_ch_9_complete',
                        'title' => 'Kingdom of Impossible Rules Conquered',
                        'desc' => 'Restore nourishment against The Forbidden Appetite and liberate Chapter 9.',
                        'type' => 'adventure_chapter',
                        'value' => 9,
                        'xp' => 450,
                        'coins' => 200,
                        'order' => 1
                    ]
                ]
            ],
            [
                'key' => 'adv_ch_10',
                'title' => 'Chapter 10: The World Between Dream and Waking',
                'subtitle' => 'Sleep-Wake & Sexual Dysfunctions',
                'category' => 'adventure',
                'icon' => 'sword',
                'order' => 10,
                'milestones' => [
                    [
                        'key' => 'adv_ch_10_complete',
                        'title' => 'World Between Dreams Conquered',
                        'desc' => 'Awaken from paradoxical shadows and defeat The Dreamer in Chapter 10.',
                        'type' => 'adventure_chapter',
                        'value' => 10,
                        'xp' => 450,
                        'coins' => 200,
                        'order' => 1
                    ]
                ]
            ],
            [
                'key' => 'adv_ch_11',
                'title' => 'Chapter 11: The City of Instant Reward',
                'subtitle' => 'Substance-Related & Addictive Disorders',
                'category' => 'adventure',
                'icon' => 'sword',
                'order' => 11,
                'milestones' => [
                    [
                        'key' => 'adv_ch_11_complete',
                        'title' => 'City of Instant Reward Conquered',
                        'desc' => 'Resist the gilded trap, uncoil The Hungering Mind, and liberate Chapter 11.',
                        'type' => 'adventure_chapter',
                        'value' => 11,
                        'xp' => 500,
                        'coins' => 250,
                        'order' => 1
                    ]
                ]
            ],
            [
                'key' => 'adv_ch_12',
                'title' => 'Chapter 12: The Palace of Identity',
                'subtitle' => 'Neurocognitive & Personality Disorders',
                'category' => 'adventure',
                'icon' => 'sword',
                'order' => 12,
                'milestones' => [
                    [
                        'key' => 'adv_ch_12_complete',
                        'title' => 'Palace of Identity Conquered',
                        'desc' => 'Cast down the porcelain masks of The Mask of a Thousand Selves in Chapter 12.',
                        'type' => 'adventure_chapter',
                        'value' => 12,
                        'xp' => 600,
                        'coins' => 300,
                        'order' => 1
                    ]
                ]
            ],

            // ── B. MASTER OF MINDREALM & ASCENDANT SCHOLAR (4 SEPARATE CARDS) ──
            [
                'key' => 'master_adventure_gate',
                'title' => 'Adventure Gate Completed',
                'subtitle' => 'Master of Mindrealm • Campaign Completion',
                'category' => 'progression',
                'icon' => 'crown',
                'order' => 13,
                'milestones' => [
                    [
                        'key' => 'master_adventure_gate',
                        'title' => 'Adventure Gate Completed',
                        'desc' => 'Conquer the entire Adventure Gate campaign across all 12 Chapters and complete the final encounter.',
                        'type' => 'adventure_campaign',
                        'value' => 12,
                        'xp' => 1500,
                        'coins' => 800,
                        'order' => 1
                    ]
                ]
            ],
            [
                'key' => 'master_study_hall',
                'title' => 'Study Hall Completed',
                'subtitle' => 'Master of Mindrealm • All 12 Subjects Mastered',
                'category' => 'progression',
                'icon' => 'book',
                'order' => 14,
                'milestones' => [
                    [
                        'key' => 'master_study_hall',
                        'title' => 'Study Hall Completed',
                        'desc' => 'Achieve 100% full curriculum completion across all 12 Study Hall subjects.',
                        'type' => 'studyhall_all',
                        'value' => 12,
                        'xp' => 1500,
                        'coins' => 800,
                        'order' => 1
                    ]
                ]
            ],
            [
                'key' => 'scholar_level_25',
                'title' => 'Ascendant Scholar: Level 25',
                'subtitle' => 'Progression • Character Level Milestone',
                'category' => 'progression',
                'icon' => 'star',
                'order' => 15,
                'milestones' => [
                    [
                        'key' => 'scholar_reach_level_25',
                        'title' => 'Reach Level 25',
                        'desc' => 'Advance character experience and academic mastery to attain Character Level 25.',
                        'type' => 'level',
                        'value' => 25,
                        'xp' => 750,
                        'coins' => 350,
                        'order' => 1
                    ]
                ]
            ],
            [
                'key' => 'scholar_level_50',
                'title' => 'Ascendant Scholar: Level 50',
                'subtitle' => 'Progression • Apex Level Milestone',
                'category' => 'progression',
                'icon' => 'crystal',
                'order' => 16,
                'milestones' => [
                    [
                        'key' => 'scholar_reach_level_50',
                        'title' => 'Reach Level 50',
                        'desc' => 'Attain the supreme rank of Character Level 50, reaching the apex of scholarly wisdom.',
                        'type' => 'level',
                        'value' => 50,
                        'xp' => 2000,
                        'coins' => 1000,
                        'order' => 1
                    ]
                ]
            ],

            // ── C. 12 STUDY HALL SUBJECT CARDS ──
            [
                'key' => 'sh_subject_1',
                'title' => 'Domain I: Neurodevelopmental Disorders',
                'subtitle' => 'Study Hall Subject Mastery',
                'category' => 'studyhall',
                'icon' => 'book',
                'order' => 17,
                'milestones' => [
                    [
                        'key' => 'sh_domain_1_complete',
                        'title' => 'Neurodevelopmental Mastery',
                        'desc' => 'Read and complete all 10 parts of the Neurodevelopmental Disorders Study Hall curriculum.',
                        'type' => 'studyhall_domain',
                        'value' => 1,
                        'xp' => 300,
                        'coins' => 100,
                        'order' => 1
                    ]
                ]
            ],
            [
                'key' => 'sh_subject_2',
                'title' => 'Domain II: Schizophrenia Spectrum',
                'subtitle' => 'Study Hall Subject Mastery',
                'category' => 'studyhall',
                'icon' => 'book',
                'order' => 18,
                'milestones' => [
                    [
                        'key' => 'sh_domain_2_complete',
                        'title' => 'Schizophrenia Spectrum Mastery',
                        'desc' => 'Read and complete all parts of the Schizophrenia Spectrum and Other Psychotic Disorders curriculum.',
                        'type' => 'studyhall_domain',
                        'value' => 2,
                        'xp' => 300,
                        'coins' => 100,
                        'order' => 1
                    ]
                ]
            ],
            [
                'key' => 'sh_subject_3',
                'title' => 'Domain III: Bipolar & Related Disorders',
                'subtitle' => 'Study Hall Subject Mastery',
                'category' => 'studyhall',
                'icon' => 'book',
                'order' => 19,
                'milestones' => [
                    [
                        'key' => 'sh_domain_3_complete',
                        'title' => 'Bipolar Disorders Mastery',
                        'desc' => 'Read and complete all parts of the Bipolar and Related Disorders curriculum.',
                        'type' => 'studyhall_domain',
                        'value' => 3,
                        'xp' => 300,
                        'coins' => 100,
                        'order' => 1
                    ]
                ]
            ],
            [
                'key' => 'sh_subject_4',
                'title' => 'Domain IV: Depressive Disorders',
                'subtitle' => 'Study Hall Subject Mastery',
                'category' => 'studyhall',
                'icon' => 'book',
                'order' => 20,
                'milestones' => [
                    [
                        'key' => 'sh_domain_4_complete',
                        'title' => 'Depressive Disorders Mastery',
                        'desc' => 'Read and complete all parts of the Depressive Disorders curriculum in the Study Hall.',
                        'type' => 'studyhall_domain',
                        'value' => 4,
                        'xp' => 300,
                        'coins' => 100,
                        'order' => 1
                    ]
                ]
            ],
            [
                'key' => 'sh_subject_5',
                'title' => 'Domain V: Anxiety Disorders',
                'subtitle' => 'Study Hall Subject Mastery',
                'category' => 'studyhall',
                'icon' => 'book',
                'order' => 21,
                'milestones' => [
                    [
                        'key' => 'sh_domain_5_complete',
                        'title' => 'Anxiety Disorders Mastery',
                        'desc' => 'Read and complete all parts of the Anxiety Disorders curriculum in the Study Hall.',
                        'type' => 'studyhall_domain',
                        'value' => 5,
                        'xp' => 350,
                        'coins' => 120,
                        'order' => 1
                    ]
                ]
            ],
            [
                'key' => 'sh_subject_6',
                'title' => 'Domain VI: Obsessive-Compulsive Disorders',
                'subtitle' => 'Study Hall Subject Mastery',
                'category' => 'studyhall',
                'icon' => 'book',
                'order' => 22,
                'milestones' => [
                    [
                        'key' => 'sh_domain_6_complete',
                        'title' => 'Obsessive-Compulsive Mastery',
                        'desc' => 'Read and complete all parts of the Obsessive-Compulsive and Related Disorders curriculum.',
                        'type' => 'studyhall_domain',
                        'value' => 6,
                        'xp' => 350,
                        'coins' => 120,
                        'order' => 1
                    ]
                ]
            ],
            [
                'key' => 'sh_subject_7',
                'title' => 'Domain VII: Trauma & Stressor Disorders',
                'subtitle' => 'Study Hall Subject Mastery',
                'category' => 'studyhall',
                'icon' => 'book',
                'order' => 23,
                'milestones' => [
                    [
                        'key' => 'sh_domain_7_complete',
                        'title' => 'Trauma & Dissociative Mastery',
                        'desc' => 'Read and complete all parts of Trauma-, Stressor-Related and Dissociative Disorders.',
                        'type' => 'studyhall_domain',
                        'value' => 7,
                        'xp' => 400,
                        'coins' => 150,
                        'order' => 1
                    ]
                ]
            ],
            [
                'key' => 'sh_subject_8',
                'title' => 'Domain VIII: Somatic Symptom Disorders',
                'subtitle' => 'Study Hall Subject Mastery',
                'category' => 'studyhall',
                'icon' => 'book',
                'order' => 24,
                'milestones' => [
                    [
                        'key' => 'sh_domain_8_complete',
                        'title' => 'Somatic Symptom Mastery',
                        'desc' => 'Read and complete all parts of Somatic Symptom and Related Disorders.',
                        'type' => 'studyhall_domain',
                        'value' => 8,
                        'xp' => 400,
                        'coins' => 150,
                        'order' => 1
                    ]
                ]
            ],
            [
                'key' => 'sh_subject_9',
                'title' => 'Domain IX: Feeding & Eating Disorders',
                'subtitle' => 'Study Hall Subject Mastery',
                'category' => 'studyhall',
                'icon' => 'book',
                'order' => 25,
                'milestones' => [
                    [
                        'key' => 'sh_domain_9_complete',
                        'title' => 'Feeding & Eating Mastery',
                        'desc' => 'Read and complete all parts of Feeding, Eating, and Elimination Disorders.',
                        'type' => 'studyhall_domain',
                        'value' => 9,
                        'xp' => 400,
                        'coins' => 150,
                        'order' => 1
                    ]
                ]
            ],
            [
                'key' => 'sh_subject_10',
                'title' => 'Domain X: Sleep-Wake & Sexual Dysfunctions',
                'subtitle' => 'Study Hall Subject Mastery',
                'category' => 'studyhall',
                'icon' => 'book',
                'order' => 26,
                'milestones' => [
                    [
                        'key' => 'sh_domain_10_complete',
                        'title' => 'Sleep & Dysfunctions Mastery',
                        'desc' => 'Read and complete all parts of Sleep-Wake, Sexual Dysfunctions, and Gender Dysphoria.',
                        'type' => 'studyhall_domain',
                        'value' => 10,
                        'xp' => 450,
                        'coins' => 180,
                        'order' => 1
                    ]
                ]
            ],
            [
                'key' => 'sh_subject_11',
                'title' => 'Domain XI: Substance & Addictive Disorders',
                'subtitle' => 'Study Hall Subject Mastery',
                'category' => 'studyhall',
                'icon' => 'book',
                'order' => 27,
                'milestones' => [
                    [
                        'key' => 'sh_domain_11_complete',
                        'title' => 'Substance & Addictive Mastery',
                        'desc' => 'Read and complete all parts of Substance-Related, Addictive, and Disruptive Disorders.',
                        'type' => 'studyhall_domain',
                        'value' => 11,
                        'xp' => 450,
                        'coins' => 180,
                        'order' => 1
                    ]
                ]
            ],
            [
                'key' => 'sh_subject_12',
                'title' => 'Domain XII: Neurocognitive & Personality Disorders',
                'subtitle' => 'Study Hall Subject Mastery',
                'category' => 'studyhall',
                'icon' => 'book',
                'order' => 28,
                'milestones' => [
                    [
                        'key' => 'sh_domain_12_complete',
                        'title' => 'Neurocognitive & Personality Mastery',
                        'desc' => 'Read and complete all parts of Neurocognitive, Personality, and Paraphilic Disorders.',
                        'type' => 'studyhall_domain',
                        'value' => 12,
                        'xp' => 500,
                        'coins' => 200,
                        'order' => 1
                    ]
                ]
            ],

            // ── D. 4 MULTIPLAYER VICTORY CARDS ──
            [
                'key' => 'mp_victories_10',
                'title' => 'Proven Challenger',
                'subtitle' => 'Multiplayer Arena Milestone',
                'category' => 'multiplayer',
                'icon' => 'trophy',
                'order' => 29,
                'milestones' => [
                    [
                        'key' => 'mp_wins_10',
                        'title' => '10 Arena Victories',
                        'desc' => 'Secure 10 verified competitive multiplayer arena battle victories.',
                        'type' => 'multiplayer_wins',
                        'value' => 10,
                        'xp' => 300,
                        'coins' => 150,
                        'order' => 1
                    ]
                ]
            ],
            [
                'key' => 'mp_victories_20',
                'title' => 'Seasoned Champion',
                'subtitle' => 'Multiplayer Arena Milestone',
                'category' => 'multiplayer',
                'icon' => 'trophy',
                'order' => 30,
                'milestones' => [
                    [
                        'key' => 'mp_wins_20',
                        'title' => '20 Arena Victories',
                        'desc' => 'Secure 20 verified competitive multiplayer arena battle victories.',
                        'type' => 'multiplayer_wins',
                        'value' => 20,
                        'xp' => 500,
                        'coins' => 250,
                        'order' => 1
                    ]
                ]
            ],
            [
                'key' => 'mp_victories_50',
                'title' => 'Elite Competitor',
                'subtitle' => 'Multiplayer Arena Milestone',
                'category' => 'multiplayer',
                'icon' => 'trophy',
                'order' => 31,
                'milestones' => [
                    [
                        'key' => 'mp_wins_50',
                        'title' => '50 Arena Victories',
                        'desc' => 'Secure 50 verified competitive multiplayer arena battle victories.',
                        'type' => 'multiplayer_wins',
                        'value' => 50,
                        'xp' => 1000,
                        'coins' => 500,
                        'order' => 1
                    ]
                ]
            ],
            [
                'key' => 'mp_victories_100',
                'title' => 'Multiplayer Legend',
                'subtitle' => 'Multiplayer Arena Milestone',
                'category' => 'multiplayer',
                'icon' => 'trophy',
                'order' => 32,
                'milestones' => [
                    [
                        'key' => 'mp_wins_100',
                        'title' => '100 Arena Victories',
                        'desc' => 'Secure 100 verified competitive multiplayer arena battle victories to achieve legendary status.',
                        'type' => 'multiplayer_wins',
                        'value' => 100,
                        'xp' => 2500,
                        'coins' => 1200,
                        'order' => 1
                    ]
                ]
            ]
        ];

        // Upsert cards and milestones
        $cardStmt = $db->prepare("
            INSERT INTO achievement_cards (card_key, title, subtitle, category, icon, display_order)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                title = VALUES(title),
                subtitle = VALUES(subtitle),
                category = VALUES(category),
                icon = VALUES(icon),
                display_order = VALUES(display_order)
        ");

        $milestoneStmt = $db->prepare("
            INSERT INTO achievement_milestones (card_id, milestone_key, title, description, condition_type, condition_value, xp_reward, coins_reward, display_order)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                card_id = VALUES(card_id),
                title = VALUES(title),
                description = VALUES(description),
                condition_type = VALUES(condition_type),
                condition_value = VALUES(condition_value),
                xp_reward = VALUES(xp_reward),
                coins_reward = VALUES(coins_reward),
                display_order = VALUES(display_order)
        ");

        $selectCardId = $db->prepare("SELECT id FROM achievement_cards WHERE card_key = ?");

        foreach ($cards as $c) {
            $cardStmt->execute([
                $c['key'],
                $c['title'],
                $c['subtitle'],
                $c['category'],
                $c['icon'],
                $c['order']
            ]);

            $selectCardId->execute([$c['key']]);
            $cardId = (int)$selectCardId->fetchColumn();

            foreach ($c['milestones'] as $m) {
                $milestoneStmt->execute([
                    $cardId,
                    $m['key'],
                    $m['title'],
                    $m['desc'],
                    $m['type'],
                    $m['value'],
                    $m['xp'],
                    $m['coins'],
                    $m['order']
                ]);
            }
        }

        // Clean up old cards no longer present in definition
        $cardKeys = array_column($cards, 'key');
        $placeholders = implode(',', array_fill(0, count($cardKeys), '?'));
        $db->prepare("DELETE FROM achievement_cards WHERE card_key NOT IN ($placeholders)")->execute($cardKeys);
    }
}
