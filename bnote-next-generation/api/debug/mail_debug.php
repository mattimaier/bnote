<?php
/**
 * Local index of mail preview templates. Loopback only.
 */
declare(strict_types=1);

require_once __DIR__ . "/mail_loopback_guard.php";
mail_loopback_guard();

require_once __DIR__ . "/../mail/MailPreviewRegistry.php";

$templates = MailPreviewRegistry::templates();
$locales = ["en", "de", "es", "fr"];
$previewScript = "mail_preview.php";
$theme = isset($_GET["theme"]) && is_string($_GET["theme"]) ? strtolower(trim($_GET["theme"])) : "auto";
if (!in_array($theme, ["auto", "light", "dark"], true)) {
  $theme = "auto";
}

header("Content-Type: text/html; charset=UTF-8");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>BNote — Mail previews (local)</title>
  <style>
    :root {
      --bg: #f4f4f5;
      --card: #ffffff;
      --text: #111827;
      --muted: #6b7280;
      --border: #e5e7eb;
      --primary: #3399ff;
      --primary-content: #fff;
      --radius: 12px;
      --font: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }
    @media (prefers-color-scheme: dark) {
      :root {
        --bg: #12151c;
        --card: #1e232c;
        --text: #eceff4;
        --muted: #8b95a5;
        --border: #3d4654;
      }
    }
    * { box-sizing: border-box; }
    body {
      font-family: var(--font);
      margin: 0;
      min-height: 100vh;
      background: var(--bg);
      color: var(--text);
      line-height: 1.5;
      padding: 2rem 1.25rem 3rem;
    }
    .wrap {
      max-width: 56rem;
      margin: 0 auto;
    }
    header {
      margin-bottom: 1.75rem;
    }
    h1 {
      font-size: 1.5rem;
      font-weight: 700;
      letter-spacing: -0.02em;
      margin: 0 0 0.5rem;
    }
    .subtitle {
      color: var(--muted);
      font-size: 0.9375rem;
      margin: 0;
      max-width: 42rem;
    }
    .theme-switch {
      margin-top: 0.9rem;
      display: inline-flex;
      gap: 0.4rem;
      padding: 0.25rem;
      border: 1px solid var(--border);
      border-radius: 999px;
      background: var(--card);
    }
    .theme-switch a {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 66px;
      padding: 0.3rem 0.7rem;
      border-radius: 999px;
      font-size: 0.8125rem;
      color: var(--muted);
      font-weight: 600;
    }
    .theme-switch a.active {
      background: var(--primary);
      color: var(--primary-content);
    }
    .card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      box-shadow: 0 1px 3px rgba(0,0,0,0.06);
      overflow: hidden;
    }
    @media (prefers-color-scheme: dark) {
      .card { box-shadow: 0 1px 3px rgba(0,0,0,0.35); }
    }
    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.875rem;
    }
    thead th {
      text-align: left;
      padding: 0.75rem 1rem;
      font-weight: 600;
      font-size: 0.6875rem;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      color: var(--muted);
      background: color-mix(in srgb, var(--border) 35%, transparent);
      border-bottom: 1px solid var(--border);
    }
    tbody td {
      padding: 0.75rem 1rem;
      border-bottom: 1px solid var(--border);
      vertical-align: middle;
    }
    tbody tr:last-child td { border-bottom: none; }
    tbody tr:hover td { background: color-mix(in srgb, var(--primary) 6%, transparent); }
    td:first-child { font-weight: 500; }
    a {
      color: var(--primary);
      text-decoration: none;
      font-weight: 500;
    }
    a:hover { text-decoration: underline; }
    .btn {
      display: inline-flex;
      align-items: center;
      margin-top: 1.25rem;
      padding: 0.5rem 1rem;
      font-size: 0.8125rem;
      font-weight: 600;
      border-radius: 8px;
      background: var(--primary);
      color: var(--primary-content) !important;
    }
    .btn:hover { filter: brightness(1.06); text-decoration: none; }
    footer {
      margin-top: 1.5rem;
      font-size: 0.8125rem;
      color: var(--muted);
    }
  </style>
</head>
<body>
  <div class="wrap">
    <header>
      <h1>Transactional mail — previews</h1>
      <p class="subtitle">Loopback only. Opens the same HTML as production sends; the logo uses a data URL in the browser.</p>
      <div class="theme-switch" role="tablist" aria-label="Preview theme">
        <?php foreach (["auto" => "Auto", "light" => "Light", "dark" => "Dark"] as $key => $label) {
          $href = "mail_debug.php?theme=" . rawurlencode($key);
          $active = $theme === $key ? " active" : "";
          echo '<a class="' .
            ltrim($active) .
            '" href="' .
            htmlspecialchars($href, ENT_QUOTES, "UTF-8") .
            '">' .
            htmlspecialchars($label, ENT_QUOTES, "UTF-8") .
            "</a>";
        } ?>
      </div>
    </header>
    <div class="card">
      <table>
        <thead>
          <tr>
            <th>Template</th>
            <?php foreach ($locales as $loc) {
              echo "<th>" . htmlspecialchars($loc, ENT_QUOTES, "UTF-8") . "</th>";
            } ?>
          </tr>
        </thead>
        <tbody>
<?php foreach ($templates as $t) {
  $lab = htmlspecialchars($t["label"], ENT_QUOTES, "UTF-8");
  echo "<tr><td>" . $lab . "</td>";
  foreach ($locales as $loc) {
    $href =
      htmlspecialchars($previewScript, ENT_QUOTES, "UTF-8") .
      "?template=" .
      rawurlencode($t["id"]) .
      "&locale=" .
      rawurlencode($loc) .
      "&theme=" .
      rawurlencode($theme);
    echo '<td><a href="' . $href . '">Preview</a></td>';
  }
  echo "</tr>";
} ?>
        </tbody>
      </table>
    </div>
    <a class="btn" href="<?php echo htmlspecialchars(
      $previewScript,
      ENT_QUOTES,
      "UTF-8",
    ); ?>?template=password_reset&amp;locale=en&amp;theme=<?php echo rawurlencode(
  $theme,
); ?>">Sample: password reset (en)</a>
    <footer>BNote Next Gen · api/debug/</footer>
  </div>
</body>
</html>
