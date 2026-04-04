import { api } from "@/lib/api";

export interface SystemInformationBuildInfo {
  version: string;
  buildId: string;
  commit: string;
  fullCommit?: string;
  buildTime: string;
}

export interface SystemInformationOverview {
  company: string;
  bnote_version_legacy: string;
  lang: string;
  country: string | null;
  demo_mode: boolean;
  system_url: string;
  modules_count: number;
  wrapped_enabled: boolean;
  nextgen: SystemInformationBuildInfo;
  changelog: {
    releaseId: string;
    generatedAt: string;
    entryCount: number;
  };
}

export const systemInformationApi = {
  getOverview: () => api.get<SystemInformationOverview>("systeminformation", "getOverview"),
};
