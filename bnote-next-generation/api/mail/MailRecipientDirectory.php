<?php
declare(strict_types=1);

require_once BNOTE_ROOT . '/src/data/modules/gruppendata.php';

final class MailRecipientDirectory {
    /**
     * @return list<array{id:int,name:string,email:string,instrument:string}>
     */
    public static function loadContacts($system_data): array {
        $rows = $system_data->dbcon->preparedQuery(
            "SELECT c.id,
                    TRIM(CONCAT(COALESCE(c.name,''), ' ', COALESCE(c.surname,''))) AS fullname,
                    c.email,
                    COALESCE(i.name, '') AS instrument_name
             FROM contact c
             LEFT JOIN instrument i ON i.id = c.instrument
             ORDER BY fullname ASC",
            []
        );

        $out = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            $email = trim((string) ($row['email'] ?? ''));
            if ($id <= 0 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $name = trim((string) ($row['fullname'] ?? ''));
            if ($name === '') {
                $name = $email;
            }
            $out[] = [
                'id' => $id,
                'name' => $name,
                'email' => $email,
                'instrument' => trim((string) ($row['instrument_name'] ?? '')),
            ];
        }

        return $out;
    }

    /**
     * @return list<array{id:int,name:string}>
     */
    public static function loadGroups(): array {
        $groupData = new GruppenData();
        $groupsSel = $groupData->findAllNoRef();
        $groups = [];
        for ($i = 1; $i < count($groupsSel); $i++) {
            $groups[] = [
                'id' => intval($groupsSel[$i]['id']),
                'name' => trim((string) ($groupsSel[$i]['name'] ?? '')),
            ];
        }
        return $groups;
    }

    /**
     * @return array<string,list<int>>
     */
    public static function loadGroupMembers($system_data): array {
        $groupMembers = [];
        $rows = $system_data->dbcon->getSelection(
            "SELECT `group` AS group_id, contact AS contact_id FROM contact_group",
            []
        );
        unset($rows[0]);
        foreach ($rows as $row) {
            $groupId = intval($row['group_id'] ?? 0);
            $contactId = intval($row['contact_id'] ?? 0);
            if ($groupId <= 0 || $contactId <= 0) {
                continue;
            }
            $key = strval($groupId);
            if (!array_key_exists($key, $groupMembers)) {
                $groupMembers[$key] = [];
            }
            $groupMembers[$key][] = $contactId;
        }

        return $groupMembers;
    }

    /**
     * @param list<array{id:int,name:string,email:string,instrument?:string}> $directory
     * @param list<int> $recipientIds
     * @param list<string> $manualEmails
     * @return list<string>
     */
    public static function resolveOutgoingEmails(array $directory, array $recipientIds, array $manualEmails): array {
        $knownById = [];
        foreach ($directory as $row) {
            $id = (int) ($row['id'] ?? 0);
            $email = trim((string) ($row['email'] ?? ''));
            if ($id <= 0 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $knownById[$id] = $email;
        }

        $emails = [];
        foreach ($recipientIds as $id) {
            $idInt = (int) $id;
            if ($idInt > 0 && isset($knownById[$idInt])) {
                $emails[] = $knownById[$idInt];
            }
        }
        foreach ($manualEmails as $email) {
            $mail = trim((string) $email);
            if ($mail !== '' && filter_var($mail, FILTER_VALIDATE_EMAIL)) {
                $emails[] = $mail;
            }
        }

        $uniq = [];
        foreach ($emails as $email) {
            $uniq[strtolower($email)] = $email;
        }
        return array_values($uniq);
    }
}
