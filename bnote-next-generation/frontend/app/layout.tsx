import type { Metadata, Viewport } from "next";
import "./globals.css";
import AppShellLayout from "@/components/AppShellLayout";
import FlyonuiScript from "@/components/FlyonuiScript";

export const metadata: Metadata = {
  title: "BNote",
  description: "BNote Next Generation",
};

export const viewport: Viewport = {
  width: "device-width",
  initialScale: 1,
  themeColor: [
    { media: "(prefers-color-scheme: light)", color: "#fcfcfd" },
    { media: "(prefers-color-scheme: dark)", color: "#2d2e38" },
  ],
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en" suppressHydrationWarning>
      <head>
        <script
          dangerouslySetInnerHTML={{
            __html: `(function(){var t=localStorage.getItem("theme");var d=!t&&window.matchMedia("(prefers-color-scheme: dark)").matches;var dark=t==="dark"||d;document.documentElement.setAttribute("data-theme",dark?"bnotedark":"bnotelight");document.documentElement.classList.toggle("dark",dark);})();`,
          }}
        />
      </head>
      <body className="antialiased">
        <AppShellLayout>{children}</AppShellLayout>
        <FlyonuiScript />
      </body>
    </html>
  );
}
