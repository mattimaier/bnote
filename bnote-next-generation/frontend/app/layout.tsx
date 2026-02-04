import type { Metadata, Viewport } from "next";
import "./globals.css";
import AppShellLayout from "@/components/AppShellLayout";

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
    <html lang="en">
      <body className="antialiased">
        <AppShellLayout>{children}</AppShellLayout>
      </body>
    </html>
  );
}
