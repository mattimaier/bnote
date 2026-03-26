<?php
/**
 * BNote Next Generation - Share API Module
 *
 * Copyright (C) 2026 BNote Contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * Share API module
 * File sharing with rights-based access (user groups, permissions)
 *
 * Note: This file is loaded after api/index.php has changed working directory to project root
 */
require_once BNOTE_ROOT . '/src/data/database.php';
require_once BNOTE_ROOT . '/src/data/modules/startdata.php';
require_once BNOTE_ROOT . '/src/data/abstractfile.php';
require_once __DIR__ . '/../response.php';
require_once __DIR__ . '/../auth.php';

class ShareModule {
    private $adp;
    private $sysdata;
    private $shareRoot;
    private $action;
    /** Path segments that must never appear (browse/create). */
    private $reservedPathSegments = ['.', '..', '.thumbnails', '.htaccess', '_temp'];
    /** Folder names not allowed at root (createFolder only). */
    private $reservedRootFolderNames = ['users', 'groups'];

    public function __construct() {
        $this->action = $_GET['action'] ?? $_POST['action'] ?? 'list';
        $this->shareRoot = $GLOBALS['DATA_PATHS']['share'];
        if ($this->action === 'shareCard') {
            return;
        }
        global $system_data;
        $this->sysdata = $system_data;
        $moduleId = $system_data->getModuleId('Share');
        if (!$moduleId || !$system_data->userHasPermission($moduleId)) {
            Response::error('Access denied to Share', 403);
        }
        $startData = new StartData();
        $this->adp = $startData->adp();
    }

    public function handle() {
        $action = $this->action;

        switch ($action) {
            case 'list':
                return $this->listRoots();
            case 'browse':
                return $this->browse();
            case 'permissions':
                return $this->getPermissions();
            case 'upload':
                return $this->upload();
            case 'uploadShareCard':
                return $this->uploadShareCard();
            case 'deleteShareCard':
                return $this->deleteShareCard();
            case 'delete':
                return $this->delete();
            case 'createFolder':
                return $this->createFolder();
            case 'download':
                return $this->download();
            case 'downloadZip':
                return $this->downloadZip();
            case 'shareCard':
                return $this->serveShareCard();
            default:
                Response::error('Unknown action: ' . $action, 400);
        }
    }

    private function getShareCardTempDir() {
        return rtrim($this->shareRoot, '/') . '/_temp/share-cards';
    }

    private function cleanupExpiredShareCards($dir) {
        if (!is_dir($dir)) {
            return;
        }
        $metaFiles = @glob($dir . '/*.json');
        if (!$metaFiles) {
            return;
        }
        $now = time();
        foreach ($metaFiles as $metaPath) {
            $raw = @file_get_contents($metaPath);
            $meta = $raw ? json_decode($raw, true) : null;
            $shareId = basename($metaPath, '.json');
            $imgPath = $dir . '/' . $shareId . '.png';
            $expiresAt = is_array($meta) ? intval($meta['expiresAt'] ?? 0) : 0;
            if ($expiresAt > 0 && $expiresAt <= $now) {
                @unlink($metaPath);
                @unlink($imgPath);
            }
        }
    }

    private function buildPublicApiUrl($params, $scriptFile = 'index.php') {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/api/index.php';
        $baseDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        if ($baseDir === '' || $baseDir === '.') {
            $baseDir = '/api';
        }
        $scriptPath = $baseDir . '/' . ltrim($scriptFile, '/');

        $publicBase = trim(getenv('BNOTE_PUBLIC_BASE_URL') ?: '');
        if ($publicBase !== '') {
            $publicBase = rtrim($publicBase, '/');
            // Allow base values with or without /api suffix.
            $basePath = parse_url($publicBase, PHP_URL_PATH) ?: '';
            if (substr($basePath, -4) === '/api') {
                $url = $publicBase . '/' . ltrim($scriptFile, '/');
            } else {
                $url = $publicBase . $scriptPath;
            }
            return $url . '?' . http_build_query($params);
        }

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || intval($_SERVER['SERVER_PORT'] ?? 0) === 443;
        $scheme = $isHttps ? 'https' : 'http';
        $serverName = trim($_SERVER['SERVER_NAME'] ?? '');
        $serverPort = intval($_SERVER['SERVER_PORT'] ?? 0);
        $host = $serverName !== '' ? $serverName : 'localhost';
        if ($serverPort > 0 && $serverPort !== 80 && $serverPort !== 443 && strpos($host, ':') === false) {
            $host .= ':' . strval($serverPort);
        }
        return $scheme . '://' . $host . $scriptPath . '?' . http_build_query($params);
    }

    private function uploadShareCard() {
        if (!isset($_FILES['file'])) {
            Response::error('No file uploaded', 400);
        }
        if (!is_uploaded_file($_FILES['file']['tmp_name'])) {
            Response::error('Invalid upload', 400);
        }
        $mime = getFileMimeType($_FILES['file']['tmp_name']) ?: ($_FILES['file']['type'] ?? '');
        if (strpos($mime, 'image/png') !== 0 && strpos($mime, 'image/jpeg') !== 0) {
            Response::error('Only PNG or JPEG is allowed', 400);
        }
        $tempDir = $this->getShareCardTempDir();
        if (!is_dir($tempDir) && !mkdir($tempDir, 0755, true)) {
            Response::error('Failed to create temp folder', 500);
        }
        $this->cleanupExpiredShareCards($tempDir);

        $shareId = bin2hex(random_bytes(16));
        $imgPath = $tempDir . '/' . $shareId . '.png';
        $fileName = trim($_POST['name'] ?? '');
        if ($fileName === '') {
            $fileName = trim($_FILES['file']['name'] ?? '');
        }
        if ($fileName === '') {
            $fileName = 'share-card.png';
        }
        $fileName = preg_replace('/[<>:"\/\\\\|?*\x00-\x1F]/', '', $fileName);
        if ($fileName === '') {
            $fileName = 'share-card.png';
        }

        if (!move_uploaded_file($_FILES['file']['tmp_name'], $imgPath)) {
            Response::error('Failed to save uploaded image', 500);
        }

        $expiresAt = time() + 24 * 60 * 60;
        $meta = [
            'fileName' => $fileName,
            'expiresAt' => $expiresAt,
            'createdAt' => time(),
        ];
        if (@file_put_contents($tempDir . '/' . $shareId . '.json', json_encode($meta, JSON_UNESCAPED_SLASHES)) === false) {
            @unlink($imgPath);
            Response::error('Failed to save metadata', 500);
        }

        $url = $this->buildPublicApiUrl([
            'shareId' => $shareId,
        ], 'participation-card.php');
        return [
            'url' => $url,
            'shareId' => $shareId,
            'expiresAt' => $expiresAt,
        ];
    }

    private function serveShareCard() {
        $shareId = strtolower(trim($_GET['shareId'] ?? ''));
        if (!preg_match('/^[a-f0-9]{32}$/', $shareId)) {
            http_response_code(404);
            echo 'Not found';
            exit;
        }
        $tempDir = $this->getShareCardTempDir();
        $metaPath = $tempDir . '/' . $shareId . '.json';
        $imgPath = $tempDir . '/' . $shareId . '.png';
        if (!is_file($metaPath) || !is_file($imgPath)) {
            http_response_code(404);
            echo 'Not found';
            exit;
        }
        $raw = @file_get_contents($metaPath);
        $meta = $raw ? json_decode($raw, true) : null;
        $expiresAt = is_array($meta) ? intval($meta['expiresAt'] ?? 0) : 0;
        if ($expiresAt <= 0 || $expiresAt <= time()) {
            @unlink($metaPath);
            @unlink($imgPath);
            http_response_code(410);
            echo 'Expired';
            exit;
        }
        $fileName = is_array($meta) ? trim($meta['fileName'] ?? 'share-card.png') : 'share-card.png';
        $fileName = str_replace('"', '', $fileName);
        $rawImageUrl = $this->buildPublicApiUrl([
            'module' => 'share',
            'action' => 'shareCard',
            'shareId' => $shareId,
            'raw' => '1',
        ]);
        $isRaw = ($_GET['raw'] ?? '') === '1';
        $isView = ($_GET['view'] ?? '') === '1';

        if ($isView && !$isRaw) {
            $title = preg_replace('/\.(png|jpe?g)$/i', '', $fileName);
            if (!$title) {
                $title = 'Participation card';
            }
            $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
            $safeImageUrl = htmlspecialchars($rawImageUrl, ENT_QUOTES, 'UTF-8');
            header('Content-Type: text/html; charset=utf-8');
            header('Cache-Control: public, max-age=300');
            echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
            echo '<title>' . $safeTitle . '</title>';
            echo '<meta property="og:title" content="' . $safeTitle . '">';
            echo '<meta property="og:image" content="' . $safeImageUrl . '">';
            echo '<meta name="twitter:card" content="summary_large_image">';
            echo '</head><body style="margin:0;background:#111;color:#fff;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif">';
            echo '<div style="max-width:960px;margin:0 auto;padding:16px;display:flex;flex-direction:column;gap:12px">';
            echo '<div style="font-size:14px;opacity:.9">' . $safeTitle . '</div>';
            echo '<img src="' . $safeImageUrl . '" alt="' . $safeTitle . '" style="width:100%;height:auto;border-radius:12px;display:block;background:#fff">';
            echo '<a href="' . $safeImageUrl . '" target="_blank" rel="noopener noreferrer" style="display:inline-block;padding:10px 14px;border-radius:10px;background:#2563eb;color:#fff;text-decoration:none;font-weight:600;width:max-content">Open image</a>';
            echo '</div></body></html>';
            exit;
        }

        header('Content-Type: image/png');
        header('Content-Disposition: inline; filename="' . $fileName . '"');
        header('Cache-Control: public, max-age=300');
        header('Content-Length: ' . filesize($imgPath));
        readfile($imgPath);
        exit;
    }

    private function deleteShareCard() {
        $shareId = strtolower(trim($_POST['shareId'] ?? $_GET['shareId'] ?? ''));
        if (!preg_match('/^[a-f0-9]{32}$/', $shareId)) {
            Response::error('Invalid shareId', 400);
        }
        $tempDir = $this->getShareCardTempDir();
        $metaPath = $tempDir . '/' . $shareId . '.json';
        $imgPath = $tempDir . '/' . $shareId . '.png';
        if (is_file($metaPath)) {
            @unlink($metaPath);
        }
        if (is_file($imgPath)) {
            @unlink($imgPath);
        }
        return ['deleted' => true];
    }

    /**
     * Normalize path: relative to share root, no ../
     */
    private function normalizePath($path) {
        if ($path === null || $path === '') {
            return '';
        }
        $path = str_replace('\\', '/', trim($path));
        $path = preg_replace('#/+#', '/', $path);
        if (strpos($path, '..') !== false) {
            return null;
        }
        $path = trim($path, '/');
        return $path;
    }

    /**
     * Check if path is allowed (no reserved path segments like ., .., .thumbnails)
     */
    private function isPathAllowed($path) {
        if ($path === null) {
            return false;
        }
        $parts = array_filter(explode('/', $path));
        foreach ($parts as $part) {
            if (in_array($part, $this->reservedPathSegments)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get full filesystem path
     */
    private function getFullPath($relativePath) {
        if ($relativePath === '') {
            return rtrim($this->shareRoot, '/');
        }
        return rtrim($this->shareRoot, '/') . '/' . $relativePath;
    }

    /**
     * Get relative path for permission checks (SecurityManager expects paths like "users/john/" or "groups/group_2/")
     */
    private function getRelativePathForPermission($relativePath) {
        if ($relativePath === '') {
            return '/';
        }
        return $relativePath . (strpos($relativePath, '.') === false && substr($relativePath, -1) !== '/' ? '/' : '');
    }

    private function listRoots() {
        $roots = [];
        $secManager = $this->adp->getSecurityManager();

        // Common Share (Tauschordner) – first and default
        if ($secManager->canUserAccessFile('/')) {
            $roots[] = [
                'id' => 'common',
                'name' => 'Common Share',
                'path' => '',
            ];
        }

        // My Files (user home)
        $userHome = $this->sysdata->getUsersHomeDir();
        $userHomeRelative = preg_replace('#^' . preg_quote($this->shareRoot, '#') . '#', '', $userHome);
        $userHomeRelative = trim($userHomeRelative, '/');
        if ($secManager->canUserAccessFile($userHomeRelative . '/')) {
            $roots[] = [
                'id' => 'myfiles',
                'name' => 'My Files',
                'path' => $userHomeRelative,
            ];
        }

        // Group folders
        $groups = $this->adp->getUsersGroups();
        if ($this->sysdata->isUserSuperUser()) {
            $allGroups = $this->adp->getGroups();
            $groups = [];
            if (is_array($allGroups)) {
                for ($i = 1; $i < count($allGroups); $i++) {
                    $groups[] = $allGroups[$i]['id'];
                }
            }
        }
        if ($groups) {
            foreach ($groups as $gid) {
                $groupPath = 'groups/group_' . $gid;
                if ($secManager->canUserAccessFile($groupPath . '/')) {
                    $groupName = $this->adp->getGroupName($gid);
                    $roots[] = [
                        'id' => 'group_' . $gid,
                        'name' => $groupName ?: 'Group ' . $gid,
                        'path' => $groupPath,
                    ];
                }
            }
        }

        // User folder (admin only)
        if ($secManager->isUserAdmin()) {
            $roots[] = [
                'id' => 'users',
                'name' => 'User folder',
                'path' => 'users',
            ];
        }

        return ['roots' => $roots];
    }

    private function browse() {
        $path = $this->normalizePath($_GET['path'] ?? '');
        if (!$this->isPathAllowed($path)) {
            Response::error('Invalid path', 400);
        }

        $relPath = $path === '' ? '/' : $path . '/';
        $secManager = $this->adp->getSecurityManager();
        if (!$secManager->canUserAccessFile($relPath)) {
            Response::error('Access denied', 403);
        }

        $fullPath = $this->getFullPath($path);
        if (!is_dir($fullPath)) {
            Response::error('Directory not found', 404);
        }

        $sort = $_GET['sort'] ?? 'name';
        $order = strtolower($_GET['order'] ?? 'asc');
        if (!in_array($order, ['asc', 'desc'])) {
            $order = 'asc';
        }

        $items = [];
        $entries = @scandir($fullPath);
        if ($entries === false) {
            Response::error('Cannot read directory', 500);
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            if ($entry[0] === '.') {
                continue; // hide hidden files/folders
            }
            $entryPath = $path === '' ? $entry : $path . '/' . $entry;
            $entryFullPath = $fullPath . '/' . $entry;
            if (in_array($entry, $this->reservedPathSegments)) {
                continue;
            }

            $itemPath = $path === '' ? $entry : $path . '/' . $entry;
            $itemRelPath = $itemPath . (is_dir($entryFullPath) ? '/' : '');
            if (!$secManager->canUserAccessFile($itemRelPath)) {
                continue;
            }

            $canDelete = false;
            if (is_file($entryFullPath) || (is_dir($entryFullPath) && !$this->isReservedDir($itemPath))) {
                $canDelete = $secManager->userFilePermission(SecurityManager::$FILE_ACTION_DELETE, $itemRelPath);
            }

            $mimeType = '';
            $icon = 'file-earmark';
            if (is_file($entryFullPath)) {
                $mimeType = getFileMimeType($entryFullPath) ?: 'application/octet-stream';
                $icon = $this->getIconForMime($mimeType, $entry);
            } else {
                $icon = 'folder-open';
            }

            $items[] = [
                'name' => $entry,
                'path' => $itemPath,
                'type' => is_dir($entryFullPath) ? 'folder' : 'file',
                'size' => is_file($entryFullPath) ? filesize($entryFullPath) : 0,
                'mimeType' => $mimeType,
                'icon' => $icon,
                'canDelete' => $canDelete,
                'modifiedAt' => date('c', filemtime($entryFullPath)),
            ];
        }

        // Sort
        usort($items, function ($a, $b) use ($sort, $order) {
            $cmp = 0;
            if ($a['type'] !== $b['type']) {
                $cmp = ($a['type'] === 'folder' ? -1 : 1);
            } else {
                switch ($sort) {
                    case 'name':
                        $cmp = strcasecmp($a['name'], $b['name']);
                        break;
                    case 'size':
                        $cmp = $a['size'] - $b['size'];
                        break;
                    case 'type':
                        $cmp = strcasecmp($a['mimeType'], $b['mimeType']);
                        break;
                    case 'modifiedAt':
                        $cmp = strtotime($a['modifiedAt']) - strtotime($b['modifiedAt']);
                        break;
                    default:
                        $cmp = strcasecmp($a['name'], $b['name']);
                }
            }
            return $order === 'desc' ? -$cmp : $cmp;
        });

        $breadcrumbs = $this->buildBreadcrumbs($path);
        $permissions = $this->computePermissions($path);

        return [
            'items' => $items,
            'breadcrumbs' => $breadcrumbs,
            'permissions' => $permissions,
        ];
    }

    private function isReservedDir($path) {
        return $path === 'users' || preg_match('#^groups/group_\d+$#', $path);
    }

    private function getIconForMime($mimeType, $filename) {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $mimeMap = [
            'application/pdf' => 'file-text',
            'image/' => 'file-image',
            'audio/' => 'file-audio',
            'video/' => 'file-video',
            'text/' => 'file-text',
            'application/zip' => 'file-archive',
        ];
        foreach ($mimeMap as $prefix => $icon) {
            if (strpos($mimeType, $prefix) === 0) {
                return $icon;
            }
        }
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            return 'file-image';
        }
        if (in_array($ext, ['mp3', 'ogg', 'wav', 'm4a'])) {
            return 'file-audio';
        }
        if ($ext === 'pdf') {
            return 'file-text';
        }
        return 'file-text';
    }

    private function buildBreadcrumbs($path) {
        $crumbs = [['name' => 'Root', 'path' => '']];
        if ($path === '') {
            return $crumbs;
        }
        $parts = explode('/', $path);
        $acc = '';
        foreach ($parts as $part) {
            $acc = $acc === '' ? $part : $acc . '/' . $part;
            $crumbs[] = ['name' => $part, 'path' => $acc];
        }
        return $crumbs;
    }

    private function computePermissions($path) {
        $relPath = $path === '' ? '/' : $path . '/';
        $secManager = $this->adp->getSecurityManager();
        $canRead = $secManager->canUserAccessFile($relPath);
        $fullPath = $this->getFullPath($path);
        $canWrite = $secManager->userFilePermission(SecurityManager::$FILE_ACTION_WRITE, $fullPath);
        $canDelete = $secManager->userFilePermission(SecurityManager::$FILE_ACTION_DELETE, $relPath);
        $canCreateFolder = $canWrite && $this->isPathAllowed($path);
        return [
            'canRead' => $canRead,
            'canWrite' => $canWrite,
            'canDelete' => $canDelete,
            'canCreateFolder' => $canCreateFolder,
        ];
    }

    private function getPermissions() {
        $path = $this->normalizePath($_GET['path'] ?? '');
        if (!$this->isPathAllowed($path)) {
            Response::error('Invalid path', 400);
        }
        return $this->computePermissions($path);
    }

    private function upload() {
        $path = $this->normalizePath($_POST['path'] ?? $_GET['path'] ?? '');
        if (!$this->isPathAllowed($path)) {
            Response::error('Invalid path', 400);
        }

        $fullPath = $this->getFullPath($path);
        $relPath = $path === '' ? '/' : $path . '/';
        $secManager = $this->adp->getSecurityManager();
        if (!$secManager->userFilePermission(SecurityManager::$FILE_ACTION_WRITE, $fullPath)) {
            Response::error('No write permission', 403);
        }

        if (!is_dir($fullPath)) {
            Response::error('Target directory not found', 404);
        }

        if (!isset($_FILES['file']) && !isset($_FILES['files'])) {
            Response::error('No file uploaded', 400);
        }

        $replaceChars = ['&', '+', '/', '\\', '#'];
        $uploaded = [];
        $errors = [];

        $fileInput = isset($_FILES['files']) && is_array($_FILES['files']['name']) ? 'files' : 'file';
        $names = is_array($_FILES[$fileInput]['name']) ? $_FILES[$fileInput]['name'] : [$_FILES[$fileInput]['name']];
        $tmpNames = is_array($_FILES[$fileInput]['tmp_name']) ? $_FILES[$fileInput]['tmp_name'] : [$_FILES[$fileInput]['tmp_name']];
        $errors_arr = is_array($_FILES[$fileInput]['error']) ? $_FILES[$fileInput]['error'] : [$_FILES[$fileInput]['error']];

        for ($i = 0; $i < count($names); $i++) {
            $name = $names[$i];
            $tmpName = $tmpNames[$i];
            $err = $errors_arr[$i];

            if ($err !== UPLOAD_ERR_OK) {
                $errors[] = $name . ': ' . $this->uploadErrorMessage($err);
                continue;
            }
            if (!is_uploaded_file($tmpName)) {
                $errors[] = $name . ': Invalid upload';
                continue;
            }

            $mime = getFileMimeType($tmpName);
            if ($mime && strpos($mime, 'php') !== false) {
                $errors[] = $name . ': File type not allowed';
                continue;
            }

            if ($name[0] === '.') {
                $errors[] = $name . ': Hidden files not allowed';
                continue;
            }
            $targetName = $name;
            foreach ($replaceChars as $c) {
                $targetName = str_replace($c, '', $targetName);
            }
            if ($targetName === '') {
                $errors[] = $name . ': Invalid filename';
                continue;
            }

            $targetPath = $fullPath . '/' . $targetName;
            if (copy($tmpName, $targetPath)) {
                $uploaded[] = $targetName;
            } else {
                $errors[] = $name . ': Failed to save';
            }
        }

        return [
            'uploaded' => $uploaded,
            'errors' => $errors,
            'success' => count($uploaded) > 0,
        ];
    }

    private function uploadErrorMessage($code) {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'File too large';
            case UPLOAD_ERR_PARTIAL:
                return 'Upload incomplete';
            case UPLOAD_ERR_NO_FILE:
                return 'No file';
            default:
                return 'Upload failed';
        }
    }

    private function delete() {
        $rawInput = file_get_contents('php://input');
        $data = $rawInput ? json_decode($rawInput, true) : null;
        if (!$data) {
            $data = $_POST;
        }
        $path = $this->normalizePath($data['path'] ?? $_GET['path'] ?? '');
        if (!$path || !$this->isPathAllowed($path)) {
            Response::error('Invalid path', 400);
        }

        $relPath = $path . '/';
        if (is_file($this->getFullPath($path))) {
            $relPath = $path;
        }
        $secManager = $this->adp->getSecurityManager();
        if (!$secManager->userFilePermission(SecurityManager::$FILE_ACTION_DELETE, $relPath)) {
            Response::error('No delete permission', 403);
        }

        if ($this->isReservedDir($path)) {
            Response::error('Cannot delete reserved directory', 400);
        }

        $fullPath = $this->getFullPath($path);
        if (!file_exists($fullPath)) {
            Response::error('Not found', 404);
        }

        if (is_dir($fullPath)) {
            $this->recursiveDelete($fullPath);
        } else {
            unlink($fullPath);
        }

        return ['success' => true, 'message' => 'Deleted'];
    }

    private function recursiveDelete($dir) {
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->recursiveDelete($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    private function createFolder() {
        $rawInput = file_get_contents('php://input');
        $data = $rawInput ? json_decode($rawInput, true) : null;
        if (!$data) {
            $data = $_POST;
        }
        $path = $this->normalizePath($data['path'] ?? $_GET['path'] ?? '');
        $folderName = trim($data['name'] ?? $data['folder'] ?? '');
        if ($folderName === '') {
            Response::error('Invalid folder name', 400);
        }
        if (in_array($folderName, $this->reservedRootFolderNames)) {
            Response::error('Reserved folder name', 400);
        }

        $parentPath = $path === '' ? $folderName : $path . '/' . $folderName;
        if (!$this->isPathAllowed($parentPath)) {
            Response::error('Invalid path', 400);
        }

        $fullParent = $this->getFullPath($path);
        $relParent = $path === '' ? '/' : $path . '/';
        $secManager = $this->adp->getSecurityManager();
        if (!$secManager->userFilePermission(SecurityManager::$FILE_ACTION_WRITE, $fullParent)) {
            Response::error('No write permission', 403);
        }

        $fullPath = $this->getFullPath($parentPath);
        if (file_exists($fullPath)) {
            Response::error('Folder already exists', 400);
        }

        if (!mkdir($fullPath, 0755, true)) {
            Response::error('Failed to create folder', 500);
        }

        return ['success' => true, 'path' => $parentPath, 'message' => 'Folder created'];
    }

    private function download() {
        $path = $this->normalizePath($_GET['path'] ?? '');
        if (!$path || !$this->isPathAllowed($path)) {
            Response::error('Invalid path', 400);
        }

        $fullPath = $this->getFullPath($path);
        if (!file_exists($fullPath) || !is_file($fullPath)) {
            Response::error('File not found', 404);
        }

        $relPath = $path;
        $secManager = $this->adp->getSecurityManager();
        if (!$secManager->canUserAccessFile($relPath)) {
            Response::error('Access denied', 403);
        }

        $mime = getFileMimeType($fullPath) ?: 'application/octet-stream';
        $filename = basename($path);
        $disposition = isset($_GET['inline']) ? 'inline' : 'attachment';
        header('Content-Type: ' . $mime);
        header('Content-Disposition: ' . $disposition . '; filename="' . addslashes($filename) . '"');
        header('Content-Length: ' . filesize($fullPath));
        readfile($fullPath);
        exit;
    }

    private function downloadZip() {
        $path = $this->normalizePath($_GET['path'] ?? '');
        if (!$this->isPathAllowed($path)) {
            Response::error('Invalid path', 400);
        }

        $fullPath = $this->getFullPath($path);
        $relPath = $path === '' ? '/' : $path . '/';
        $secManager = $this->adp->getSecurityManager();
        if (!$secManager->canUserAccessFile($relPath)) {
            Response::error('Access denied', 403);
        }

        if (!is_dir($fullPath)) {
            Response::error('Not a directory', 400);
        }

        $zipName = (basename($path) ?: 'share') . '.zip';
        $tempZip = $this->shareRoot . '_temp';
        if (!is_dir($tempZip)) {
            mkdir($tempZip, 0755);
        }
        $zipPath = $tempZip . '/' . uniqid('zip_') . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            Response::error('Failed to create zip', 500);
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($fullPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($files as $file) {
            $filePath = $file->getPathname();
            $relativePath = substr($filePath, strlen($fullPath) + 1);
            $zip->addFile($filePath, $relativePath);
        }
        $zip->close();

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . addslashes($zipName) . '"');
        header('Content-Length: ' . filesize($zipPath));
        readfile($zipPath);
        unlink($zipPath);
        exit;
    }
}
