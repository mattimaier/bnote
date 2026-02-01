import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  output: "export",
  // If the app is served under a subpath (e.g. /bnote-next-generation/), set:
  // basePath: "/bnote-next-generation",
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
