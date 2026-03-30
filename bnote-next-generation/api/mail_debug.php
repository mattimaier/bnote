<?php
/**
 * Local index of mail preview templates. Loopback only.
 */
declare(strict_types=1);

require_once __DIR__ . '/mail_loopback_guard.php';
mail_loopback_guard();

require_once __DIR__ . '/mail/MailPreviewRegistry.php';

$templates = MailPreviewRegistry::templates();
$locales = ['en', 'de', 'es', 'fr'];
$previewScript = 'mail_preview.php';

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>BNote mail previews (local)</title>
  <style>
    body { font-family: system-ui, sans-serif; max-width: 48rem; margin: 2rem auto; padding: 0 1rem; }
    h1 { font-size: 1.25rem; }
    table { border-collapse: collapse; width: 100%; }
    th, td { text-align: left; padding: 0.5rem 0.75rem; border-bottom: 1px solid #e5e7eb; }
    a { color: #3399ff; }
    p.note { color: #6b7280; font-size: 0.875rem; }
  </style>
</head>
<body>
  <h1>BNote transactional mail (preview)</h1>
  <p class="note">Loopback only. Opens the same HTML as production sends; logo uses a data URL in the browser.</p>
  <table>
    <thead>
      <tr><th>Template</th><?php foreach ($locales as $loc) { echo '<th>' . htmlspecialchars($loc, ENT_QUOTES, 'UTF-8') . '</th>'; } ?></tr>
    </thead>
    <tbody>
<?php foreach ($templates as $t) {
    $tid = htmlspecialchars($t['id'], ENT_QUOTES, 'UTF-8');
    $lab = htmlspecialchars($t['label'], ENT_QUOTES, 'UTF-8');
    echo '<tr><td>' . $lab . '</td>';
    foreach ($locales as $loc) {
        $l = htmlspecialchars($loc, ENT_QUOTES, 'UTF-8');
        $href = htmlspecialchars($previewScript, ENT_QUOTES, 'UTF-8') . '?template=' . rawurlencode($t['id']) . '&locale=' . rawurlencode($loc);
        echo '<td><a href="' . $href . '">Preview</a></td>';
    }
    echo '</tr>';
} ?>
    </tbody>
  </table>
  <p><a href="<?php echo htmlspecialchars($previewScript, ENT_QUOTES, 'UTF-8'); ?>?template=password_reset&amp;locale=en">Password reset (en)</a></p>
</body>
</html>
