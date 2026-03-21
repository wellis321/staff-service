<?php
/**
 * PendingProfileChange Model
 *
 * Manages the approval queue for staff self-service profile edits.
 * Staff submit changes; their line manager (or HR at the top of the chain)
 * approves or rejects each field individually.
 */

class PendingProfileChange {

    /**
     * Fields that staff are permitted to edit on their own record,
     * and whether each one requires manager approval before going live.
     *
     * 'table'    — which DB table holds this field
     * 'label'    — human-readable name shown in the approval UI
     * 'type'     — how to interpret/display the value
     * 'approval' — true = held for manager sign-off; false = applied immediately
     */
    public const STAFF_EDITABLE_FIELDS = [
        // Personal identity
        'first_name'               => ['table' => 'people',          'label' => 'First name',             'type' => 'text',      'approval' => true],
        'last_name'                => ['table' => 'people',          'label' => 'Last name',              'type' => 'text',      'approval' => true],
        'date_of_birth'            => ['table' => 'people',          'label' => 'Date of birth',          'type' => 'date',      'approval' => true],
        'phone'                    => ['table' => 'people',          'label' => 'Phone number',           'type' => 'text',      'approval' => false],

        // Home address
        'address_line1'            => ['table' => 'staff_profiles',  'label' => 'Address line 1',         'type' => 'text',      'approval' => true],
        'address_line2'            => ['table' => 'staff_profiles',  'label' => 'Address line 2',         'type' => 'text',      'approval' => true],
        'address_city'             => ['table' => 'staff_profiles',  'label' => 'City / Town',            'type' => 'text',      'approval' => true],
        'address_county'           => ['table' => 'staff_profiles',  'label' => 'County',                 'type' => 'text',      'approval' => true],
        'address_postcode'         => ['table' => 'staff_profiles',  'label' => 'Postcode',               'type' => 'text',      'approval' => true],
        'address_country'          => ['table' => 'staff_profiles',  'label' => 'Country',                'type' => 'text',      'approval' => true],

        // Emergency contact — no approval delay (could be needed urgently)
        'emergency_contact_name'   => ['table' => 'staff_profiles',  'label' => 'Emergency contact name', 'type' => 'text',      'approval' => false],
        'emergency_contact_phone'  => ['table' => 'staff_profiles',  'label' => 'Emergency contact phone','type' => 'text',      'approval' => false],

        // Financial / sensitive identifiers — always require approval
        'ni_number'                => ['table' => 'staff_profiles',  'label' => 'National Insurance number', 'type' => 'text',   'approval' => true],
        'bank_sort_code'           => ['table' => 'staff_profiles',  'label' => 'Bank sort code',         'type' => 'text',      'approval' => true],
        'bank_account_number'      => ['table' => 'staff_profiles',  'label' => 'Bank account number',    'type' => 'text',      'approval' => true],
        'bank_account_name'        => ['table' => 'staff_profiles',  'label' => 'Bank account name',      'type' => 'text',      'approval' => true],

        // Photo and signature — files, always require approval
        'photo_path'               => ['table' => 'people',          'label' => 'Profile photo',          'type' => 'file_path', 'approval' => true],
        'signature_path'           => ['table' => 'staff_profiles',  'label' => 'Signature',              'type' => 'file_path', 'approval' => true],
    ];

    // -------------------------------------------------------------------------
    // Submitting changes
    // -------------------------------------------------------------------------

    /**
     * Submit one or more field changes for a person.
     * For fields marked approval=false the change is applied immediately.
     * For all others a pending row is inserted for manager review.
     *
     * @param int    $personId
     * @param int    $organisationId
     * @param int    $submittedByUserId  User ID of the staff member
     * @param array  $changes            Associative array: field_name => new_value
     * @param array  $currentValues      Current live values (keyed by field_name)
     * @return array ['applied' => [...], 'pending' => [...], 'errors' => [...]]
     */
    public static function submit(int $personId, int $organisationId, int $submittedByUserId, array $changes, array $currentValues): array {
        $db = getDbConnection();
        $applied = [];
        $pending = [];
        $errors  = [];

        foreach ($changes as $fieldName => $newValue) {
            if (!isset(self::STAFF_EDITABLE_FIELDS[$fieldName])) {
                $errors[] = $fieldName;
                continue;
            }

            $meta = self::STAFF_EDITABLE_FIELDS[$fieldName];

            if (!$meta['approval']) {
                // Apply immediately — write straight to the live record
                self::applyChange($db, $personId, $organisationId, $meta['table'], $fieldName, $newValue);
                $applied[] = $fieldName;
                continue;
            }

            // Cancel any existing pending change for this field (staff re-submitted)
            $db->prepare("
                DELETE FROM pending_profile_changes
                WHERE person_id = ? AND field_name = ? AND status = 'pending'
            ")->execute([$personId, $fieldName]);

            $stmt = $db->prepare("
                INSERT INTO pending_profile_changes
                    (organisation_id, person_id, submitted_by_user_id,
                     table_name, field_name, field_label, field_type,
                     current_value, proposed_value, status, submitted_at)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            $stmt->execute([
                $organisationId,
                $personId,
                $submittedByUserId,
                $meta['table'],
                $fieldName,
                $meta['label'],
                $meta['type'],
                $currentValues[$fieldName] ?? null,
                $newValue,
            ]);

            $pending[] = $fieldName;
        }

        return compact('applied', 'pending', 'errors');
    }

    // -------------------------------------------------------------------------
    // Approval / rejection
    // -------------------------------------------------------------------------

    /**
     * Approve a pending change and write it to the live record.
     *
     * @param int $changeId
     * @param int $reviewerUserId
     * @return bool
     */
    public static function approve(int $changeId, int $reviewerUserId): bool {
        $db = getDbConnection();

        $change = self::findById($changeId);
        if (!$change || $change['status'] !== 'pending') {
            return false;
        }

        // Write to live record
        self::applyChange(
            $db,
            (int) $change['person_id'],
            (int) $change['organisation_id'],
            $change['table_name'],
            $change['field_name'],
            $change['proposed_value']
        );

        $db->prepare("
            UPDATE pending_profile_changes
            SET status = 'approved', reviewer_id = ?, reviewed_at = NOW()
            WHERE id = ?
        ")->execute([$reviewerUserId, $changeId]);

        return true;
    }

    /**
     * Reject a pending change (live record is untouched).
     *
     * @param int    $changeId
     * @param int    $reviewerUserId
     * @param string $reason
     * @return bool
     */
    public static function reject(int $changeId, int $reviewerUserId, string $reason = ''): bool {
        $db = getDbConnection();

        $change = self::findById($changeId);
        if (!$change || $change['status'] !== 'pending') {
            return false;
        }

        $db->prepare("
            UPDATE pending_profile_changes
            SET status = 'rejected', reviewer_id = ?, reviewed_at = NOW(), rejection_reason = ?
            WHERE id = ?
        ")->execute([$reviewerUserId, $reason, $changeId]);

        return true;
    }

    // -------------------------------------------------------------------------
    // Queries
    // -------------------------------------------------------------------------

    /**
     * Pending changes awaiting review for a given person.
     */
    public static function getPendingForPerson(int $personId): array {
        $db = getDbConnection();
        $stmt = $db->prepare("
            SELECT ppc.*,
                   CONCAT(u.first_name, ' ', u.last_name) AS submitted_by_name
            FROM pending_profile_changes ppc
            JOIN users u ON ppc.submitted_by_user_id = u.id
            WHERE ppc.person_id = ? AND ppc.status = 'pending'
            ORDER BY ppc.submitted_at ASC
        ");
        $stmt->execute([$personId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * All pending changes that a given manager should review.
     * Finds everyone whose line_manager_id resolves to this manager's person record.
     *
     * @param int $managerPersonId  The person_id (not user_id) of the manager
     * @param int $organisationId
     */
    public static function getPendingForManager(int $managerPersonId, int $organisationId): array {
        $db = getDbConnection();
        $stmt = $db->prepare("
            SELECT ppc.*,
                   CONCAT(p.first_name, ' ', p.last_name) AS person_name,
                   CONCAT(u.first_name, ' ', u.last_name) AS submitted_by_name
            FROM pending_profile_changes ppc
            JOIN people p ON ppc.person_id = p.id
            JOIN staff_profiles sp ON p.id = sp.person_id
            JOIN users u ON ppc.submitted_by_user_id = u.id
            WHERE sp.line_manager_id = ?
              AND ppc.organisation_id = ?
              AND ppc.status = 'pending'
            ORDER BY ppc.submitted_at ASC
        ");
        $stmt->execute([$managerPersonId, $organisationId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * All pending changes in an organisation with no line manager set
     * (i.e. top of chain — reviewed by HR / admin).
     */
    public static function getPendingTopOfChain(int $organisationId): array {
        $db = getDbConnection();
        $stmt = $db->prepare("
            SELECT ppc.*,
                   CONCAT(p.first_name, ' ', p.last_name) AS person_name,
                   CONCAT(u.first_name, ' ', u.last_name) AS submitted_by_name
            FROM pending_profile_changes ppc
            JOIN people p ON ppc.person_id = p.id
            JOIN staff_profiles sp ON p.id = sp.person_id
            JOIN users u ON ppc.submitted_by_user_id = u.id
            WHERE sp.line_manager_id IS NULL
              AND ppc.organisation_id = ?
              AND ppc.status = 'pending'
            ORDER BY ppc.submitted_at ASC
        ");
        $stmt->execute([$organisationId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Change history for a person (all statuses).
     */
    public static function getHistoryForPerson(int $personId, int $limit = 50): array {
        $db = getDbConnection();
        $stmt = $db->prepare("
            SELECT ppc.*,
                   CONCAT(u.first_name, ' ', u.last_name) AS submitted_by_name,
                   CONCAT(r.first_name, ' ', r.last_name) AS reviewer_name
            FROM pending_profile_changes ppc
            JOIN users u ON ppc.submitted_by_user_id = u.id
            LEFT JOIN users r ON ppc.reviewer_id = r.id
            WHERE ppc.person_id = ?
            ORDER BY ppc.submitted_at DESC
            LIMIT ?
        ");
        $stmt->execute([$personId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find a single change by ID.
     */
    public static function findById(int $id): ?array {
        $db = getDbConnection();
        $stmt = $db->prepare("SELECT * FROM pending_profile_changes WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Write an approved value directly to the live record.
     * Only fields in STAFF_EDITABLE_FIELDS can be written this way.
     */
    private static function applyChange(PDO $db, int $personId, int $organisationId, string $table, string $fieldName, $value): void {
        // Validate field is in the allowed list (defence in depth)
        $allowedFields = array_keys(self::STAFF_EDITABLE_FIELDS);
        if (!in_array($fieldName, $allowedFields, true)) {
            return;
        }

        if ($table === 'people') {
            $stmt = $db->prepare("
                UPDATE people SET `{$fieldName}` = ?, updated_at = NOW()
                WHERE id = ? AND organisation_id = ?
            ");
            $stmt->execute([$value, $personId, $organisationId]);
        } elseif ($table === 'staff_profiles') {
            $stmt = $db->prepare("
                UPDATE staff_profiles SET `{$fieldName}` = ?, updated_at = NOW()
                WHERE person_id = ?
                  AND person_id IN (SELECT id FROM people WHERE organisation_id = ?)
            ");
            $stmt->execute([$value, $personId, $organisationId]);
        }
    }
}
