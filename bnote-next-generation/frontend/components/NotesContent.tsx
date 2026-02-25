/**
 * BNote Next Generation - Display notes (EditorJS JSON or Markdown/plain)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import type React from "react";
import { useMemo } from "react";
import { isEditorJson, notesEditorJsonToHtml } from "@/lib/editorjs-notes";
import { MarkdownText } from "@/components/MarkdownText";

interface NotesContentProps {
  value: string;
  className?: string;
  inline?: boolean;
  /** Max number of lines to show; truncate with ellipsis when set (e.g. in table cells). */
  maxLines?: number;
}

const lineClampStyle = (n: number): React.CSSProperties => ({
  display: "-webkit-box",
  WebkitBoxOrient: "vertical",
  WebkitLineClamp: n,
  overflow: "hidden",
});

export function NotesContent({ value, className, inline = false, maxLines }: NotesContentProps) {
  const html = useMemo(() => {
    if (!value || typeof value !== "string") return "";
    return notesEditorJsonToHtml(value);
  }, [value]);

  if (!value || typeof value !== "string" || !value.trim()) {
    return null;
  }

  const wrap = (inner: React.ReactNode) =>
    maxLines != null ? (
      <div className="overflow-hidden" style={lineClampStyle(maxLines)}>
        {inner}
      </div>
    ) : (
      inner
    );

  if (isEditorJson(value) && html) {
    return wrap(
      <div
        className={["rich-text-content", className].filter(Boolean).join(" ")}
        dangerouslySetInnerHTML={{ __html: html }}
      />
    );
  }

  return wrap(<MarkdownText value={value} className={className} inline={inline} />);
}
