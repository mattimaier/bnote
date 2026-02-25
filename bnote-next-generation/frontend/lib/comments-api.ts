/**
 * BNote Next Generation - Comments API client
 *
 * Copyright (C) 2026 BNote Contributors
 */

import { api } from "./api";

export type CommentOtype = "R" | "C" | "V";

export interface Comment {
  id: number;
  author: string;
  author_id: number;
  author_email?: string | null;
  message: string;
  created_at: string;
}

export const commentsApi = {
  list: (otype: CommentOtype, oid: number): Promise<Comment[]> =>
    api.get<Comment[]>("comments", "list", { otype: String(otype), oid: String(oid) }),

  add: (otype: CommentOtype, oid: number, message: string): Promise<Comment> =>
    api.post<Comment>("comments", "add", { otype, oid, message }),

  delete: (commentId: number): Promise<{ deleted: boolean }> =>
    api.post<{ deleted: boolean }>("comments", "delete", { id: commentId }),
};
