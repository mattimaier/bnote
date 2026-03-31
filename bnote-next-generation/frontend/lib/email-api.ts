import { api } from "./api";

export interface EmailRecipientContact {
  id: number;
  name: string;
  email: string;
  subtitle?: string;
  instrument?: string;
}

export interface EmailGroup {
  id: number;
  name: string;
}

export interface EmailMetaResponse {
  groups: EmailGroup[];
  contacts: EmailRecipientContact[];
  groupMembers: Record<string, number[]>;
}

export interface EmailDraftResponse {
  fromEmail: string;
  toEmail: string;
  selectedRecipientIds: number[];
  subject: string;
  subjectPrefix?: string;
  body: string;
}

export interface EmailPreviewResponse {
  html: string;
}

export interface EmailSendResponse {
  sent: number;
  skipped: number;
}

export const emailApi = {
  meta: () => api.get<EmailMetaResponse>("email", "meta"),
  draft: (locale: string) => api.post<EmailDraftResponse>("email", "draft", { locale }),
  preview: (payload: {
    locale: string;
    groupIds: number[];
    recipientIds: number[];
    manualEmails: string[];
    subject: string;
    body: string;
  }) => api.post<EmailPreviewResponse>("email", "preview", payload as unknown as Record<string, unknown>),
  send: (payload: {
    locale: string;
    groupIds: number[];
    recipientIds: number[];
    manualEmails: string[];
    subject: string;
    body: string;
  }) => api.post<EmailSendResponse>("email", "send", payload as unknown as Record<string, unknown>),
};
