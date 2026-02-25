/**
 * BNote Next Generation - Share Upload Proxy
 * Proxies multipart/form-data to PHP backend. Uses /share-upload path so it
 * is NOT caught by the /api/:path* rewrite (which would forward to PHP and
 * drop the multipart body).
 *
 * Streams the raw request body to PHP to avoid parsing/re-encoding issues
 * that can corrupt multipart uploads.
 *
 * Copyright (C) 2026 BNote Contributors
 */

import { NextRequest, NextResponse } from "next/server";

const PHP_BACKEND =
  process.env.NEXT_PUBLIC_API_BASE ||
  "http://localhost:8888/Bnote/bnote-next-generation";

export async function POST(request: NextRequest) {
  try {
    const contentType = request.headers.get("content-type");
    if (!contentType?.includes("multipart/form-data")) {
      return NextResponse.json(
        { success: false, error: "Expected multipart/form-data" },
        { status: 400 }
      );
    }

    const backendUrl = `${PHP_BACKEND.replace(/\/$/, "")}/api/index.php?module=share&action=upload`;
    const body = await request.arrayBuffer();

    const res = await fetch(backendUrl, {
      method: "POST",
      body,
      headers: {
        "Content-Type": contentType,
        Cookie: request.headers.get("cookie") ?? "",
      },
    });

    const json = await res.json();
    return NextResponse.json(json, { status: res.status });
  } catch (err) {
    if (process.env.NODE_ENV === "development") {
      console.error("Share upload proxy error:", err);
    }
    return NextResponse.json(
      { success: false, error: "Upload proxy failed" },
      { status: 500 }
    );
  }
}
