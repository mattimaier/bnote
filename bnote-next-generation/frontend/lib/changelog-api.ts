import { api } from "@/lib/api";

export interface ChangelogBuildInfo {
  version: string;
  buildId: string;
  commit: string;
  fullCommit?: string;
  buildTime: string;
}

export interface ChangelogEntry {
  bugId: string | null;
  changeType: "added" | "fixed" | "changed" | "removed";
  title: string;
  date: string;
}

export interface ChangelogResponse {
  releaseId: string;
  generatedAt: string;
  build: ChangelogBuildInfo;
  entries: ChangelogEntry[];
}

export const changelogApi = {
  get: () => api.get<ChangelogResponse>("changelog", "get"),
};
