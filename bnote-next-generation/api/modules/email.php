<?php
/**
 * BNote Next Generation - Email Composer API Module
 *
 * Copyright (C) 2026 BNote Contributors
 */
declare(strict_types=1);

require_once __DIR__ . '/../response.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../text_normalizer.php';
require_once __DIR__ . '/../mail/MailRecipientDirectory.php';
require_once __DIR__ . '/../mail/GenericEmailComposerService.php';

class EmailModule {
    private const MODULE_ID = 7;

    public function __construct() {
        if (!Auth::check()) {
            Response::error('Authentication required', 401);
        }
        $this->requireEmailModulePermission();
    }

    public function handle() {
        $action = $_GET['action'] ?? $_POST['action'] ?? 'meta';
        switch ($action) {
            case 'meta':
                return $this->normalizeResponse($this->meta(), $action);
            case 'draft':
                return $this->normalizeResponse($this->draft(), $action);
            case 'preview':
                return $this->preview();
            case 'send':
                return $this->normalizeResponse($this->send(), $action);
            default:
                Response::error('Unknown action: ' . $action, 400);
        }
    }

    private function normalizeResponse($payload, string $action) {
        $stats = ['count' => 0, 'samples' => []];
        $normalized = TextNormalizer::normalizeAllStringsRecursive($payload, $stats, true);
        TextNormalizer::logStats('email', $action, $stats);
        return $normalized;
    }

    private function getPayload(): array {
        $rawInput = file_get_contents('php://input');
        $data = $rawInput ? json_decode($rawInput, true) : null;
        return is_array($data) ? $data : $_POST;
    }

    /**
     * @return list<int>
     */
    private function selectedRecipientIds(array $payload, array $groupMembers): array {
        $recipientIds = array_values(array_filter(array_map('intval', $payload['recipientIds'] ?? []), fn($id) => $id > 0));
        $groupIds = array_values(array_filter(array_map('intval', $payload['groupIds'] ?? []), fn($id) => $id > 0));
        foreach ($groupIds as $groupId) {
            $key = strval($groupId);
            if (!array_key_exists($key, $groupMembers)) {
                continue;
            }
            foreach ($groupMembers[$key] as $contactId) {
                $cid = intval($contactId);
                if ($cid > 0) {
                    $recipientIds[] = $cid;
                }
            }
        }
        $uniq = [];
        foreach ($recipientIds as $id) {
            $uniq[$id] = true;
        }
        return array_map('intval', array_keys($uniq));
    }

    private function meta(): array {
        global $system_data;
        $groups = MailRecipientDirectory::loadGroups();
        $contacts = MailRecipientDirectory::loadContacts($system_data);
        $groupMembers = MailRecipientDirectory::loadGroupMembers($system_data);

        $formattedContacts = [];
        foreach ($contacts as $contact) {
            $formattedContacts[] = [
                'id' => (int) $contact['id'],
                'name' => (string) $contact['name'],
                'email' => (string) $contact['email'],
                'subtitle' => (string) ($contact['instrument'] ?? ''),
                'instrument' => (string) ($contact['instrument'] ?? ''),
            ];
        }

        return [
            'groups' => $groups,
            'contacts' => $formattedContacts,
            'groupMembers' => $groupMembers,
        ];
    }

    private function draft(): array {
        global $system_data;
        $payload = $this->getPayload();
        $locale = trim((string) ($payload['locale'] ?? (method_exists($system_data, 'getLang') ? $system_data->getLang() : 'en')));
        return GenericEmailComposerService::draft($system_data, $locale !== '' ? $locale : 'en');
    }

    private function preview(): array {
        global $system_data;
        $payload = $this->getPayload();
        $locale = trim((string) ($payload['locale'] ?? (method_exists($system_data, 'getLang') ? $system_data->getLang() : 'en')));
        $subject = trim((string) ($payload['subject'] ?? ''));
        $body = (string) ($payload['body'] ?? '');
        $manualEmails = array_values(array_filter(array_map('strval', $payload['manualEmails'] ?? []), fn($value) => trim($value) !== ''));
        $directory = MailRecipientDirectory::loadContacts($system_data);
        $groupMembers = MailRecipientDirectory::loadGroupMembers($system_data);
        $recipientIds = $this->selectedRecipientIds($payload, $groupMembers);

        $user = Auth::getUserInfo();
        $senderName = trim((string) (($user['name'] ?? '') . ' ' . ($user['surname'] ?? '')));
        try {
            $html = GenericEmailComposerService::previewHtml(
                $system_data,
                $locale !== '' ? $locale : 'en',
                $directory,
                $recipientIds,
                $manualEmails,
                $subject,
                $body,
                $senderName
            );
            return ['html' => $html];
        } catch (Throwable $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    private function send(): array {
        global $system_data;
        $payload = $this->getPayload();
        $locale = trim((string) ($payload['locale'] ?? (method_exists($system_data, 'getLang') ? $system_data->getLang() : 'en')));
        $subject = trim((string) ($payload['subject'] ?? ''));
        $body = (string) ($payload['body'] ?? '');
        $manualEmails = array_values(array_filter(array_map('strval', $payload['manualEmails'] ?? []), fn($value) => trim($value) !== ''));
        $directory = MailRecipientDirectory::loadContacts($system_data);
        $groupMembers = MailRecipientDirectory::loadGroupMembers($system_data);
        $recipientIds = $this->selectedRecipientIds($payload, $groupMembers);

        $user = Auth::getUserInfo();
        $senderName = trim((string) (($user['name'] ?? '') . ' ' . ($user['surname'] ?? '')));

        try {
            return GenericEmailComposerService::send(
                $system_data,
                $locale !== '' ? $locale : 'en',
                $directory,
                $recipientIds,
                $manualEmails,
                $subject,
                $body,
                $senderName
            );
        } catch (InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (Throwable $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    private function requireEmailModulePermission(): void {
        global $system_data;
        $userId = Auth::getUserId();
        $uid = intval($userId);
        if ($system_data->isUserSuperUser($uid) || $system_data->isUserMemberGroup(1, $uid)) {
            return;
        }
        if ($system_data->userHasPermission(self::MODULE_ID)) {
            return;
        }
        Response::error('Access denied to Email', 403);
    }
}
