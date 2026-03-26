import { mkdir, readdir, readFile, rm, stat, writeFile } from "node:fs/promises";
import { createHash, randomBytes } from "node:crypto";
import { join } from "node:path";
import { NextRequest, NextResponse } from "next/server";

const MAX_FILE_BYTES = 8 * 1024 * 1024; // 8MB
const TTL_MS = 1000 * 60 * 60 * 24 * 2; // 48h
const SHARE_DIR = join(process.cwd(), "tmp", "share-cards");

interface ShareMeta {
  token: string;
  mimeType: string;
  fileName: string;
  expiresAt: number;
}

function getBasePath(): string {
  return (process.env.NEXT_PUBLIC_BASE_PATH ?? "/bnote-next-generation").replace(/\/$/, "");
}

function tokenFromBytes(buf: Uint8Array): string {
  return createHash("sha256").update(buf).digest("hex").slice(0, 32);
}

async function ensureDir() {
  await mkdir(SHARE_DIR, { recursive: true });
}

async function cleanupExpiredFiles(now: number) {
  let files: string[] = [];
  try {
    files = await readdir(SHARE_DIR);
  } catch {
    return;
  }
  const metaFiles = files.filter((file) => file.endsWith(".json"));
  await Promise.all(
    metaFiles.map(async (metaFile) => {
      try {
        const metaPath = join(SHARE_DIR, metaFile);
        const raw = await readFile(metaPath, "utf8");
        const parsed = JSON.parse(raw) as Partial<ShareMeta>;
        if (typeof parsed.expiresAt !== "number" || parsed.expiresAt >= now) return;
        const token = metaFile.replace(/\.json$/, "");
        await rm(join(SHARE_DIR, `${token}.png`), { force: true });
        await rm(metaPath, { force: true });
      } catch {
        // Keep request resilient; stale files can be cleaned up later.
      }
    })
  );
}

export async function POST(request: NextRequest) {
  try {
    const formData = await request.formData();
    const file = formData.get("file");
    if (!(file instanceof File)) {
      return NextResponse.json({ success: false, error: "No file provided" }, { status: 400 });
    }
    if (file.type !== "image/png") {
      return NextResponse.json({ success: false, error: "Only PNG is supported" }, { status: 400 });
    }
    if (file.size <= 0 || file.size > MAX_FILE_BYTES) {
      return NextResponse.json({ success: false, error: "Invalid file size" }, { status: 400 });
    }

    const bytes = new Uint8Array(await file.arrayBuffer());
    const token = tokenFromBytes(randomBytes(32));
    const fileName = typeof formData.get("name") === "string" ? String(formData.get("name")) : "share-card.png";
    const now = Date.now();
    const meta: ShareMeta = {
      token,
      mimeType: "image/png",
      fileName,
      expiresAt: now + TTL_MS,
    };

    await ensureDir();
    await cleanupExpiredFiles(now);
    await writeFile(join(SHARE_DIR, `${token}.png`), bytes);
    await writeFile(join(SHARE_DIR, `${token}.json`), JSON.stringify(meta), "utf8");

    const basePath = getBasePath();
    const url = `${request.nextUrl.origin}${basePath}/share-card/${token}`;
    return NextResponse.json({
      success: true,
      data: {
        url,
        token,
        expiresAt: meta.expiresAt,
      },
    });
  } catch {
    return NextResponse.json({ success: false, error: "Failed to create share URL" }, { status: 500 });
  }
}

export async function GET() {
  return NextResponse.json({ success: false, error: "Method not allowed" }, { status: 405 });
}
