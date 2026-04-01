import type { Metadata, Viewport } from "next";
import { Space_Grotesk } from "next/font/google";
import "./globals.css";
import AppShellLayout from "@/components/AppShellLayout";
import FlyonuiScript from "@/components/FlyonuiScript";
import { DARK_THEME_COLOR, DARK_THEME_NAME, LIGHT_THEME_COLOR, LIGHT_THEME_NAME } from "@/lib/theme";

const basePath = (process.env.NEXT_PUBLIC_BASE_PATH ?? "/bnote-next-generation").replace(/\/$/, "");
const withBasePath = (path: string): string => `${basePath}${path}`;

export const metadata: Metadata = {
  title: "BNote",
  description: "BNote Next Generation",
  icons: {
    icon: [
      { url: withBasePath("/favicon.ico"), type: "image/x-icon" },
      { url: withBasePath("/BNote_Logo_prebuilt.png"), type: "image/png", sizes: "512x512" },
    ],
    shortcut: [{ url: withBasePath("/favicon.ico"), type: "image/x-icon" }],
    apple: [{ url: withBasePath("/BNote_Logo_prebuilt.png"), sizes: "180x180", type: "image/png" }],
  },
};

export const viewport: Viewport = {
  width: "device-width",
  initialScale: 1,
  maximumScale: 1,
  userScalable: false,
  viewportFit: "cover",
};

const spaceGrotesk = Space_Grotesk({
  subsets: ["latin"],
  weight: ["500", "600", "700"],
  variable: "--font-wrapped-display",
});

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en" suppressHydrationWarning>
      <head>
        <meta id="app-theme-color" name="theme-color" content={LIGHT_THEME_COLOR} />
        <script
          dangerouslySetInnerHTML={{
            __html: `(function(){function readTheme(){var t=null;try{t=localStorage.getItem("theme");}catch(e){}if(t==="dark"||t==="light"){return t;}var m=document.cookie.match(/(?:^|;\\s*)theme=(dark|light)(?:;|$)/);return m?m[1]:null;}var pref=readTheme();var dark=pref?pref==="dark":window.matchMedia("(prefers-color-scheme: dark)").matches;var c=dark?"${DARK_THEME_COLOR}":"${LIGHT_THEME_COLOR}";var root=document.documentElement;root.setAttribute("data-theme",dark?"${DARK_THEME_NAME}":"${LIGHT_THEME_NAME}");root.classList.toggle("dark",dark);root.style.colorScheme=dark?"dark":"light";root.style.backgroundColor=c;var meta=document.getElementById("app-theme-color");if(meta){meta.setAttribute("content",c);}})();`,
          }}
        />
      </head>
      <body className={`${spaceGrotesk.variable} antialiased`}>
        <AppShellLayout>{children}</AppShellLayout>
        <FlyonuiScript />
      </body>
    </html>
  );
}
