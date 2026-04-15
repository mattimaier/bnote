import fs from "node:fs/promises";
import path from "node:path";
import { execFileSync } from "node:child_process";
import sharp from "sharp";

const frontendRoot = path.resolve(process.cwd());
const repoRoot = path.resolve(frontendRoot, "..");
const entityConfigPath = path.join(frontendRoot, "config", "entity-config.json");
const mailIconsPhpPath = path.join(repoRoot, "api", "mail", "MailEntityIcons.php");
const mailEntityColorsPhpPath = path.join(repoRoot, "api", "mail", "MailEntityColors.php");
const apiOutputDir = path.join(repoRoot, "api", "mail", "generated-icons");
const publicOutputDir = path.join(frontendRoot, "public", "generated-mail-icons");

const ICON_SIZE = 22;
const BADGE_SIZE = 40;

function mixHex(hexA, hexB, t) {
  const a = hexA.replace("#", "");
  const b = hexB.replace("#", "");
  if (a.length !== 6 || b.length !== 6) return hexA;
  const ar = parseInt(a.slice(0, 2), 16);
  const ag = parseInt(a.slice(2, 4), 16);
  const ab = parseInt(a.slice(4, 6), 16);
  const br = parseInt(b.slice(0, 2), 16);
  const bg = parseInt(b.slice(2, 4), 16);
  const bb = parseInt(b.slice(4, 6), 16);
  const r = Math.round(ar + (br - ar) * t);
  const g = Math.round(ag + (bg - ag) * t);
  const bch = Math.round(ab + (bb - ab) * t);
  return `#${[r, g, bch].map((v) => v.toString(16).padStart(2, "0")).join("")}`;
}

function mixWithWhiteByColorShare(hex, colorShare) {
  const clamped = Math.max(0, Math.min(1, colorShare));
  return mixHex(hex, "#ffffff", 1 - clamped);
}

function renderMailSvgByIconName(iconName) {
  const phpCode = [
    "$path = getenv('MAIL_ENTITY_ICONS_PATH');",
    "if (!$path) { fwrite(STDERR, 'MAIL_ENTITY_ICONS_PATH missing'); exit(1); }",
    "require_once $path;",
    "$name = getenv('MAIL_ICON_NAME') ?: 'music';",
    "echo MailEntityIcons::svgForIconName($name);",
  ].join(" ");

  return execFileSync("php", ["-r", phpCode], {
    cwd: repoRoot,
    env: {
      ...process.env,
      MAIL_ENTITY_ICONS_PATH: mailIconsPhpPath,
      MAIL_ICON_NAME: iconName,
    },
    encoding: "utf8",
  });
}

function resolveEntityHexColor(entityKey, fallbackHex) {
  const phpCode = [
    "$path = getenv('MAIL_ENTITY_COLORS_PATH');",
    "if (!$path) { fwrite(STDERR, 'MAIL_ENTITY_COLORS_PATH missing'); exit(1); }",
    "require_once $path;",
    "$k = getenv('MAIL_ENTITY_KEY') ?: 'rehearsal';",
    "echo MailEntityColors::solidHex($k);",
  ].join(" ");

  try {
    const out = execFileSync("php", ["-r", phpCode], {
      cwd: repoRoot,
      env: {
        ...process.env,
        MAIL_ENTITY_COLORS_PATH: mailEntityColorsPhpPath,
        MAIL_ENTITY_KEY: entityKey,
      },
      encoding: "utf8",
    }).trim();
    return /^#[0-9a-fA-F]{6}$/.test(out) ? out.toLowerCase() : fallbackHex;
  } catch {
    return fallbackHex;
  }
}

function colorizeSvg(svg, color) {
  return svg.replaceAll("currentColor", color);
}

async function main() {
  const configRaw = await fs.readFile(entityConfigPath, "utf8");
  const config = JSON.parse(configRaw);
  const entities = config?.entities && typeof config.entities === "object" ? config.entities : {};

  await Promise.all([fs.mkdir(apiOutputDir, { recursive: true }), fs.mkdir(publicOutputDir, { recursive: true })]);
  const [existingApi, existingPublic] = await Promise.all([fs.readdir(apiOutputDir), fs.readdir(publicOutputDir)]);
  await Promise.all([
    ...existingApi
      .filter((name) => name.toLowerCase().endsWith(".png"))
      .map((name) => fs.unlink(path.join(apiOutputDir, name))),
    ...existingPublic
      .filter((name) => name.toLowerCase().endsWith(".png"))
      .map((name) => fs.unlink(path.join(publicOutputDir, name))),
  ]);

  const entries = Object.entries(entities).filter(([, value]) => {
    return Boolean(
      value && typeof value === "object" && typeof value.icon === "string" && typeof value.color === "string"
    );
  });

  for (const [entityKey, value] of entries) {
    const iconName = String(value.icon).trim();
    const configuredColor = String(value.color ?? "").trim();
    if (!iconName) continue;
    const fallbackHex = /^#[0-9a-fA-F]{6}$/.test(configuredColor) ? configuredColor.toLowerCase() : "#3399ff";
    const color = resolveEntityHexColor(entityKey, fallbackHex);

    const baseSvg = renderMailSvgByIconName(iconName);
    const coloredSvg = colorizeSvg(baseSvg, color);
    const outputPathApi = path.join(apiOutputDir, `${entityKey}.png`);
    const outputPathPublic = path.join(publicOutputDir, `${entityKey}.png`);
    const badgePathApi = path.join(apiOutputDir, `${entityKey}-badge.png`);
    const badgePathPublic = path.join(publicOutputDir, `${entityKey}-badge.png`);

    const iconBuffer = await sharp(Buffer.from(coloredSvg), { density: 800 })
      .resize(ICON_SIZE, ICON_SIZE, {
        fit: "contain",
        background: { r: 0, g: 0, b: 0, alpha: 0 },
      })
      .png({ compressionLevel: 9 })
      .toBuffer();
    await Promise.all([fs.writeFile(outputPathApi, iconBuffer), fs.writeFile(outputPathPublic, iconBuffer)]);

    const badgeFill = mixWithWhiteByColorShare(color, 0.2);
    const badgeBorder = mixWithWhiteByColorShare(color, 0.42);
    const badgeSvg = [
      `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${BADGE_SIZE} ${BADGE_SIZE}">`,
      `<rect x="0.5" y="0.5" width="${BADGE_SIZE - 1}" height="${BADGE_SIZE - 1}" rx="12" ry="12" fill="${badgeFill}" stroke="${badgeBorder}" stroke-width="1" />`,
      "</svg>",
    ].join("");
    const glyphPng = await sharp(Buffer.from(coloredSvg), { density: 800 })
      .resize(18, 18, {
        fit: "contain",
        background: { r: 0, g: 0, b: 0, alpha: 0 },
      })
      .png({ compressionLevel: 9 })
      .toBuffer();
    const badgeBuffer = await sharp(Buffer.from(badgeSvg))
      .resize(BADGE_SIZE, BADGE_SIZE, {
        fit: "fill",
      })
      .composite([{ input: glyphPng, left: 11, top: 11 }])
      .png({ compressionLevel: 9 })
      .toBuffer();
    await Promise.all([fs.writeFile(badgePathApi, badgeBuffer), fs.writeFile(badgePathPublic, badgeBuffer)]);
  }

  console.log(`Generated mail icon PNGs: ${path.relative(repoRoot, apiOutputDir)}`);
  console.log(`Mirrored mail icon PNGs: ${path.relative(frontendRoot, publicOutputDir)}`);
}

main().catch((error) => {
  console.error("Failed to generate mail icon PNGs", error);
  process.exit(1);
});
