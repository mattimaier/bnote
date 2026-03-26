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
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en" suppressHydrationWarning>
      <head>
        <meta id="app-theme-color" name="theme-color" content="#fcfcfd" />
        <script
          dangerouslySetInnerHTML={{
            __html: `(function(){function readTheme(){var t=null;try{t=localStorage.getItem("theme");}catch(e){}if(t==="dark"||t==="light"){return t;}var m=document.cookie.match(/(?:^|;\\s*)theme=(dark|light)(?:;|$)/);return m?m[1]:null;}var pref=readTheme();var dark=pref?pref==="dark":window.matchMedia("(prefers-color-scheme: dark)").matches;var c=dark?"#2d2e38":"#fcfcfd";document.documentElement.setAttribute("data-theme",dark?"bnotedark":"bnotelight");document.documentElement.classList.toggle("dark",dark);document.documentElement.style.colorScheme=dark?"dark":"light";var meta=document.getElementById("app-theme-color");if(meta){meta.setAttribute("content",c);}})();`,
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
