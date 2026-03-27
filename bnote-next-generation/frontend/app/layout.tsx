import type { Metadata, Viewport } from "next";
import "./globals.css";
import AppShellLayout from "@/components/AppShellLayout";
import FlyonuiScript from "@/components/FlyonuiScript";
import { DARK_THEME_COLOR, DARK_THEME_NAME, LIGHT_THEME_COLOR, LIGHT_THEME_NAME } from "@/lib/theme";

export const metadata: Metadata = {
  title: "BNote",
  description: "BNote Next Generation",
};

export const viewport: Viewport = {
  width: "device-width",
  initialScale: 1,
  maximumScale: 1,
  userScalable: false,
  viewportFit: "cover",
};

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
      <body className="antialiased">
        <AppShellLayout>{children}</AppShellLayout>
        <FlyonuiScript />
      </body>
    </html>
  );
}
