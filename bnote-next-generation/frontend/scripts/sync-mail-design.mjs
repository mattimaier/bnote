import fs from "node:fs/promises";
import path from "node:path";

const rootDir = path.resolve(process.cwd());
const jsonPath = path.join(rootDir, "mail-design-tokens.json");
const outPath = path.join(rootDir, "..", "api", "mail", "MailDesignTokens.php");

function phpString(v) {
  return JSON.stringify(String(v));
}

async function main() {
  const raw = await fs.readFile(jsonPath, "utf8");
  const data = JSON.parse(raw);
  const entries = Object.entries(data).filter(([k]) => !k.startsWith("_"));
  const lines = entries.map(([k, v]) => `        '${k}' => ${phpString(v)},`);
  const body = lines.join("\n");

  const php = `<?php
/**
 * Auto-generated from frontend/mail-design-tokens.json — run: npm run sync:mail-design
 */
declare(strict_types=1);

final class MailDesignTokens {
    /** @return array<string, string> */
    public static function tokens(): array {
        return [
${body}
        ];
    }

    public static function get(string $key, string $default = ''): string {
        $t = self::tokens();
        return $t[$key] ?? $default;
    }
}
`;

  await fs.mkdir(path.dirname(outPath), { recursive: true });
  await fs.writeFile(outPath, php, "utf8");
  // eslint-disable-next-line no-console
  console.log(`Wrote ${path.relative(path.join(rootDir, ".."), outPath)}`);
}

main().catch((err) => {
  // eslint-disable-next-line no-console
  console.error(err);
  process.exit(1);
});
