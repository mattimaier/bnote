<?php
/**
 * BNote Next Generation - Rich notes storage helper
 *
 * Used by the richnotes API module and by other modules for orphan cleanup.
 * See docs/RICH_NOTES.md for architecture.
 *
 * Copyright (C) 2026 BNote Contributors
 */

if (!defined('BNOTE_ROOT')) {
    return;
}

/**
 * Ensure the rich_notes table exists (create on first use).
 * Call with global $system_data available.
 */
function rich_notes_ensure_table() {
    global $system_data;
    $db = $system_data->dbcon;
    $tables = $db->getSelection("SHOW TABLES LIKE 'rich_notes'");
    $colName = $tables[0][0] ?? null;
    $exists = $colName && count($tables) > 1;
    if (!$exists) {
        $sql = "CREATE TABLE IF NOT EXISTS `rich_notes` (
            `entity_type` VARCHAR(50) NOT NULL,
            `entity_id` VARCHAR(100) NOT NULL,
            `content` LONGTEXT NOT NULL,
            `updated_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`entity_type`, `entity_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        $db->execute($sql, []);
    }
}

/**
 * Delete rich_notes row(s) for an entity (orphan cleanup when entity is deleted).
 * Call from contacts, equipment, locations, repertoire, etc. when deleting an entity.
 *
 * @param string $entityType e.g. 'contact', 'rehearsal', 'song'
 * @param string $entityId   entity id (or composite e.g. '123_456' for rehearsal_song)
 */
function rich_notes_delete_for_entity($entityType, $entityId) {
    global $system_data;
    $entityType = preg_replace('/[^a-z0-9_]/', '', $entityType);
    $entityId = (string) $entityId;
    if ($entityType === '' || $entityId === '') {
        return;
    }
    $system_data->dbcon->execute(
        "DELETE FROM rich_notes WHERE entity_type = ? AND entity_id = ?",
        [['s', $entityType], ['s', $entityId]]
    );
}
