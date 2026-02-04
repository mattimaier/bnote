/**
 * BNote Next Generation - App route group layout
 *
 * The root layout already wraps non-public routes with AppShell via AppShellLayout.
 * This layout only passes through so we don't double-render the sidebar.
 *
 * Copyright (C) 2026 BNote Contributors
 */

export default function AppLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return <>{children}</>;
}
