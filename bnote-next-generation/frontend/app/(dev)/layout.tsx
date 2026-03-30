/**
 * Debug routes — admin-only (with developer tools build unlock).
 */

import { DeveloperSurfacesGuard } from "@/components/debug/DeveloperSurfacesGuard";

export default function DevDebugLayout({ children }: { children: React.ReactNode }) {
  return <DeveloperSurfacesGuard>{children}</DeveloperSurfacesGuard>;
}
