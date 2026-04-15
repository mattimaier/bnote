import fs from "node:fs/promises";
import path from "node:path";
import sharp from "sharp";

const rootDir = path.resolve(process.cwd());
const glyphPath = path.join(rootDir, "public", "BNote_Logo_blue.svg");
const iconTokensPath = path.join(rootDir, "config", "bnote-icon-tokens.json");
const outputPngPath = path.join(rootDir, "public", "BNote_Logo_prebuilt.png");
const outputIcoPath = path.join(rootDir, "app", "favicon.ico");
const outputIconSvgPath = path.join(rootDir, "app", "icon.svg");

function createIcoFromPng(png, width, height) {
  const header = Buffer.alloc(22);
  header.writeUInt16LE(0, 0); // Reserved
  header.writeUInt16LE(1, 2); // ICO type
  header.writeUInt16LE(1, 4); // Image count
  header.writeUInt8(width === 256 ? 0 : width, 6);
  header.writeUInt8(height === 256 ? 0 : height, 7);
  header.writeUInt8(0, 8); // Palette color count
  header.writeUInt8(0, 9); // Reserved
  header.writeUInt16LE(1, 10); // Color planes
  header.writeUInt16LE(32, 12); // Bits per pixel
  header.writeUInt32LE(png.length, 14); // Image size
  header.writeUInt32LE(22, 18); // Data offset
  return Buffer.concat([header, png]);
}

function superellipsePoint(theta, radius, exponent) {
  const c = Math.cos(theta);
  const s = Math.sin(theta);
  const cx = Math.sign(c) * Math.pow(Math.abs(c), 2 / exponent);
  const sy = Math.sign(s) * Math.pow(Math.abs(s), 2 / exponent);
  return [radius * cx, radius * sy];
}

function buildSquirclePath(size, inset, exponent, steps) {
  const center = size / 2;
  const radius = center - inset;
  const points = [];
  for (let i = 0; i < steps; i += 1) {
    const t = (Math.PI * 2 * i) / steps;
    const [x, y] = superellipsePoint(t, radius, exponent);
    points.push([center + x, center + y]);
  }
  const [x0, y0] = points[0];
  const segments = points.slice(1).map(([x, y]) => `L ${x.toFixed(3)} ${y.toFixed(3)}`);
  return [`M ${x0.toFixed(3)} ${y0.toFixed(3)}`, ...segments, "Z"].join(" ");
}

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

function resolvePalette(p) {
  if (p.bgStart && p.bgEnd && p.border && p.glyph) return p;
  return {
    glyph: p.glyph,
    bgStart: mixHex(p.glyph, "#ffffff", p.bgStartMixWhite),
    bgEnd: mixHex(p.glyph, "#ffffff", p.bgEndMixWhite),
    border: mixHex(p.glyph, "#ffffff", p.borderMixWhite),
  };
}

function buildIconSvg({ geometry, palette, glyphPathData }) {
  const outerPath = buildSquirclePath(geometry.canvasSize, 0, geometry.squircleExponent, geometry.pathSteps);
  const borderPath = buildSquirclePath(
    geometry.canvasSize,
    geometry.borderInset,
    geometry.squircleExponent,
    geometry.pathSteps
  );
  return [
    '<svg version="1.2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="512" height="512">',
    "  <defs>",
    '    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1" gradientUnits="objectBoundingBox">',
    `      <stop offset="0" stop-color="${palette.bgStart}"/>`,
    `      <stop offset="1" stop-color="${palette.bgEnd}"/>`,
    "    </linearGradient>",
    "  </defs>",
    `  <path d="${outerPath}" fill="url(#bg)"/>`,
    `  <path d="${borderPath}" fill="none" stroke="${palette.border}" stroke-width="${geometry.borderWidth}" stroke-linejoin="round"/>`,
    `  <g transform="translate(${geometry.glyphTranslate},${geometry.glyphTranslate}) scale(${geometry.glyphScale})">`,
    `    <path fill="${palette.glyph}" fill-rule="evenodd" d="${glyphPathData}"/>`,
    "  </g>",
    "</svg>",
    "",
  ].join("\n");
}

async function main() {
  const [glyphSvgRaw, iconTokensRaw] = await Promise.all([
    fs.readFile(glyphPath, "utf8"),
    fs.readFile(iconTokensPath, "utf8"),
  ]);
  const iconTokens = JSON.parse(iconTokensRaw);
  const glyphMatch = glyphSvgRaw.match(/d="([^"]+)"/s);
  if (!glyphMatch) {
    throw new Error("Unable to extract glyph path from BNote_Logo_blue.svg");
  }
  const glyphPathData = glyphMatch[1].replace(/\s+/g, " ").trim();

  const lightSvg = buildIconSvg({
    geometry: iconTokens.geometry,
    palette: resolvePalette(iconTokens.palette.light),
    glyphPathData,
  });

  await Promise.all([
    fs.mkdir(path.dirname(outputPngPath), { recursive: true }),
    fs.mkdir(path.dirname(outputIcoPath), { recursive: true }),
    fs.mkdir(path.dirname(outputIconSvgPath), { recursive: true }),
  ]);

  await fs.writeFile(outputIconSvgPath, lightSvg, "utf8");

  await sharp(Buffer.from(lightSvg), { density: 1200 })
    .resize(512, 512, {
      fit: "contain",
      background: { r: 0, g: 0, b: 0, alpha: 0 },
    })
    .png({ compressionLevel: 9 })
    .toFile(outputPngPath);

  const faviconPng = await sharp(Buffer.from(lightSvg), { density: 1200 })
    .resize(64, 64, {
      fit: "contain",
      background: { r: 0, g: 0, b: 0, alpha: 0 },
    })
    .png({ compressionLevel: 9 })
    .toBuffer();
  const icoBuffer = createIcoFromPng(faviconPng, 64, 64);
  await fs.writeFile(outputIcoPath, icoBuffer);

  console.log(`Generated icon SVG: ${path.relative(rootDir, outputIconSvgPath)}`);
  console.log(`Generated logo PNG: ${path.relative(rootDir, outputPngPath)}`);
  console.log(`Generated favicon ICO: ${path.relative(rootDir, outputIcoPath)}`);
}

main().catch((error) => {
  console.error("Failed to generate logo PNG", error);
  process.exit(1);
});
