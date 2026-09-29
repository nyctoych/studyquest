<?php
require_once __DIR__ . '/../config/database.php';

class SpecialEventQuestion {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function find(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT seq.*, pd.name as domain_name, sp.title as part_title, sp.part_number
            FROM special_event_questions seq
            LEFT JOIN psychiatric_domains pd ON seq.domain_id = pd.id
            LEFT JOIN study_parts sp ON seq.study_part_id = sp.id
            WHERE seq.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getByEventId(int $eventId, bool $approvedOnly = false): array {
        $sql = "
            SELECT seq.*, pd.name as domain_name, sp.title as part_title, sp.part_number
            FROM special_event_questions seq
            LEFT JOIN psychiatric_domains pd ON seq.domain_id = pd.id
            LEFT JOIN study_parts sp ON seq.study_part_id = sp.id
            WHERE seq.special_event_id = ?
        ";
        if ($approvedOnly) {
            $sql .= " AND seq.review_status = 'approved'";
        }
        $sql .= " ORDER BY seq.display_order ASC, seq.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$eventId]);
        return $stmt->fetchAll();
    }

    /**
     * Client-safe view for ACTIVE exam (excludes correct_choice and explanation to prevent cheating)
     */
    public function getClientSafeQuestion(array $q): array {
        return [
            'id' => (int) $q['id'],
            'special_event_id' => (int) $q['special_event_id'],
            'domain_id' => (int) $q['domain_id'],
            'domain_name' => $q['domain_name'] ?? '',
            'study_part_id' => (int) $q['study_part_id'],
            'part_title' => $q['part_title'] ?? '',
            'part_number' => isset($q['part_number']) ? (int) $q['part_number'] : null,
            'question_text' => $q['question_text'],
            'choice_a' => $q['choice_a'],
            'choice_b' => $q['choice_b'],
            'choice_c' => $q['choice_c'],
            'choice_d' => $q['choice_d'],
            'difficulty' => $q['difficulty'],
            'question_type' => $q['question_type'],
            'display_order' => (int) ($q['display_order'] ?? 1)
        ];
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO special_event_questions (
                special_event_id, study_part_id, domain_id, question_text,
                choice_a, choice_b, choice_c, choice_d, correct_choice,
                explanation, difficulty, question_type, bloom_level, source_hash,
                question_hash, concept_key, generation_version, review_status, display_order
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['special_event_id'],
            $data['study_part_id'],
            $data['domain_id'],
            $data['question_text'],
            $data['choice_a'],
            $data['choice_b'],
            $data['choice_c'],
            $data['choice_d'],
            strtoupper($data['correct_choice']),
            $data['explanation'],
            $data['difficulty'] ?? 'difficult',
            $data['question_type'] ?? 'clinical_application',
            $data['bloom_level'] ?? 'apply',
            $data['source_hash'],
            $data['question_hash'],
            $data['concept_key'] ?? '',
            $data['generation_version'] ?? 'v1.0',
            $data['review_status'] ?? 'approved',
            $data['display_order'] ?? 1
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $fields = [];
        $params = [];
        foreach ($data as $key => $value) {
            $fields[] = "`$key` = ?";
            $params[] = $value;
        }
        $params[] = $id;
        $sql = "UPDATE special_event_questions SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function updateReviewStatus(int $id, string $status): bool {
        $stmt = $this->db->prepare("UPDATE special_event_questions SET review_status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public function existsHash(int $eventId, string $questionHash): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM special_event_questions 
            WHERE special_event_id = ? AND question_hash = ?
        ");
        $stmt->execute([$eventId, $questionHash]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM special_event_questions WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
