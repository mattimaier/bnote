import { api } from "@/lib/api";

export interface BugReportPayload {
  message: string;
  screenshotDataUrl?: string;
  clientContext?: unknown;
  networkEvents?: unknown[];
  logEvents?: unknown[];
}

export interface BugReportSendResponse {
  sent: boolean;
  reportId: string;
}

export const bugReportApi = {
  send: (payload: BugReportPayload) =>
    api.post<BugReportSendResponse>("bugreport", "send", payload as unknown as Record<string, unknown>),
};
