import fs from "node:fs/promises";
import path from "node:path";
import sharp from "sharp";

const rootDir = path.resolve(process.cwd());
const glyphPath = path.join(rootDir, "public", "BNote_Logo_blue.svg");
const outputPath = path.join(rootDir, "public", "BNote_Logo_prebuilt.png");

const backgroundSvg = `
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#c2e0ff"/>
      <stop offset="1" stop-color="#ebf5ff"/>
    </linearGradient>
  </defs>
  <rect x="0" y="0" width="512" height="512" rx="77" ry="77" fill="url(#bg)"/>
  <rect x="7" y="7" width="498" height="498" rx="70" ry="70" fill="none" stroke="#d6ebff" stroke-width="14"/>
</svg>
`;

async function main() {
  const glyphSvg = await fs.readFile(glyphPath);
  await fs.mkdir(path.dirname(outputPath), { recursive: true });

  const background = await sharp(Buffer.from(backgroundSvg), { density: 1200 })
    .resize(512, 512, {
      fit: "contain",
      background: { r: 0, g: 0, b: 0, alpha: 0 },
    })
    .png()
    .toBuffer();

  // Match the original icon.svg transform: scale(0.66) inside the box.
  const glyph = await sharp(glyphSvg, { density: 1200 })
    .resize(338, 338, {
      fit: "contain",
      background: { r: 0, g: 0, b: 0, alpha: 0 },
    })
    .png()
    .toBuffer();

  await sharp(background)
    .composite([{ input: glyph, gravity: "centre" }])
    .png({ compressionLevel: 9 })
    .toFile(outputPath);

  // eslint-disable-next-line no-console
  console.log(`Generated logo: ${path.relative(rootDir, outputPath)}`);
}

main().catch((error) => {
  // eslint-disable-next-line no-console
  console.error("Failed to generate logo PNG", error);
  process.exit(1);
});
