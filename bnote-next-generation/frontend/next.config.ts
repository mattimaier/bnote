import type { NextConfig } from "next";

/** Default base path for subfolder deployment. Set NEXT_PUBLIC_BASE_PATH="" for local dev at root. */
const basePath = (process.env.NEXT_PUBLIC_BASE_PATH ?? "/bnote-next-generation").replace(/\/$/, "");

const nextConfig: NextConfig = {
  output: "export",
  basePath: basePath || undefined,
  trailingSlash: true,
  // In dev, proxy /api to the PHP backend so login works without CORS (same origin).
  async rewrites() {
    const base =
      process.env.NEXT_PUBLIC_API_BASE ||
      "http://localhost:8888/Bnote/bnote-next-generation";
    const target = base.endsWith("/") ? base.slice(0, -1) : base;
    return [{ source: "/api/:path*", destination: `${target}/api/:path*` }];
  },
};

export default nextConfig;
