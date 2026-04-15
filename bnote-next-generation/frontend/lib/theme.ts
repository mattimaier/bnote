export type ThemeMode = "light" | "dark";

export const LIGHT_THEME_COLOR = "#fcfcfd";
export const DARK_THEME_COLOR = "#2d2e38";
export const LIGHT_THEME_NAME = "bnotelight";
export const DARK_THEME_NAME = "bnotedark";

export function readStoredTheme(): ThemeMode | null {
  let storedTheme: string | null = null;
  try {
    storedTheme = localStorage.getItem("theme");
  } catch {
    storedTheme = null;
  }
  if (storedTheme === "dark" || storedTheme === "light") return storedTheme;
  const cookieMatch = document.cookie.match(/(?:^|;\s*)theme=(dark|light)(?:;|$)/);
  if (cookieMatch?.[1] === "dark" || cookieMatch?.[1] === "light") return cookieMatch[1];
  return null;
}

export function resolveTheme(): ThemeMode {
  const storedTheme = readStoredTheme();
  if (storedTheme) return storedTheme;
  return window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
}

export function themeToMetaColor(theme: ThemeMode): string {
  return theme === "dark" ? DARK_THEME_COLOR : LIGHT_THEME_COLOR;
}

export function applyTheme(theme: ThemeMode) {
  const isDark = theme === "dark";
  const html = document.documentElement;
  html.classList.toggle("dark", isDark);
  html.setAttribute("data-theme", isDark ? DARK_THEME_NAME : LIGHT_THEME_NAME);
  html.style.colorScheme = isDark ? "dark" : "light";
  const themeMeta = document.getElementById("app-theme-color");
  if (themeMeta) {
    themeMeta.setAttribute("content", themeToMetaColor(theme));
  }
}

export function persistTheme(theme: ThemeMode) {
  try {
    localStorage.setItem("theme", theme);
  } catch {
    // Ignore storage failures (private mode / blocked storage).
  }
  document.cookie = `theme=${theme}; path=/; max-age=31536000; SameSite=Lax`;
}

export function isThemeApplied(theme: ThemeMode): boolean {
  const html = document.documentElement;
  const expectedThemeName = theme === "dark" ? DARK_THEME_NAME : LIGHT_THEME_NAME;
  return (
    html.classList.contains("dark") === (theme === "dark") && html.getAttribute("data-theme") === expectedThemeName
  );
}
