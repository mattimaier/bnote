/**
 * BNote Next Generation - Display notes (EditorJS JSON or Markdown/plain)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useMemo } from "react";
import { isEditorJson, notesEditorJsonToHtml } from "@/lib/editorjs-notes";
import { MarkdownText } from "@/components/MarkdownText";

interface NotesContentProps {
  value: string;
  className?: string;
  inline?: boolean;
}

export function NotesContent({ value, className, inline = false }: NotesContentProps) {
  const html = useMemo(() => {
    if (!value || typeof value !== "string") return "";
    return notesEditorJsonToHtml(value);
  }, [value]);

  if (!value || typeof value !== "string" || !value.trim()) {
    return null;
  }

  if (isEditorJson(value) && html) {
    return (
      <div
        className={["rich-text-content", className].filter(Boolean).join(" ")}
        dangerouslySetInnerHTML={{ __html: html }}
      />
    );
  }

  return <MarkdownText value={value} className={className} inline={inline} />;
}
