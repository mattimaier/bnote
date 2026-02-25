/**
 * BNote Next Generation - Rich text notes editor (EditorJS)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import { parseNotesInitialData } from "@/lib/editorjs-notes";

type EditorJSInstance = import("@editorjs/editorjs").default;
type OutputData = import("@editorjs/editorjs").OutputData;

export interface NotesEditorProps {
  value: string;
  placeholder?: string;
  onChange: (jsonOrPlain: string) => void;
  disabled?: boolean;
  minHeight?: string;
  id?: string;
}

const DEFAULT_PLACEHOLDER = "Type or paste content…";
const DEBOUNCE_MS = 500;

export function NotesEditor({
  value,
  placeholder = DEFAULT_PLACEHOLDER,
  onChange,
  disabled = false,
  minHeight = "120px",
  id = "notes-editor-holder",
}: NotesEditorProps) {
  const holderRef = useRef<HTMLDivElement>(null);
  const editorRef = useRef<EditorJSInstance | null>(null);
  const initialValueRef = useRef(value);
  const onChangeRef = useRef(onChange);
  const [ready, setReady] = useState(false);
  const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  onChangeRef.current = onChange;

  const notifyChange = useCallback((output: OutputData) => {
    const str = JSON.stringify(output);
    onChangeRef.current(str);
  }, []);

  useEffect(() => {
    if (disabled) return;

    let cancelled = false;
    const initialData = parseNotesInitialData(initialValueRef.current);

    (async () => {
      if (!holderRef.current) return;
      const [EditorJS, Header, List, Quote, Paragraph, SimpleImage] = await Promise.all([
        import("@editorjs/editorjs").then((m) => m.default),
        import("@editorjs/header").then((m) => m.default),
        import("@editorjs/list").then((m) => m.default),
        import("@editorjs/quote").then((m) => m.default),
        import("@editorjs/paragraph").then((m) => m.default),
        import("@editorjs/simple-image").then((m) => m.default),
      ]);

      if (cancelled || !holderRef.current) return;

      const editorConfig = {
        holder: holderRef.current,
        placeholder,
        data: initialData as OutputData | undefined,
        tools: {
          header: { class: Header, config: { placeholder: "Heading" }, inlineToolbar: true },
          list: { class: List, inlineToolbar: true },
          quote: {
            class: Quote,
            config: { quotePlaceholder: "Quote", captionPlaceholder: "Caption" },
            inlineToolbar: true,
          },
          paragraph: { class: Paragraph, inlineToolbar: true },
          image: { class: SimpleImage, inlineToolbar: true },
        },
        onChange: () => {
          if (debounceRef.current) clearTimeout(debounceRef.current);
          debounceRef.current = setTimeout(async () => {
            debounceRef.current = null;
            const editor = editorRef.current;
            if (editor) {
              try {
                const output = await editor.save();
                notifyChange(output);
              } catch {
                // ignore
              }
            }
          }, DEBOUNCE_MS);
        },
      };

      const editor = new EditorJS(editorConfig as unknown as ConstructorParameters<typeof EditorJS>[0]);
      await editor.isReady;
      if (cancelled) {
        if (editor.destroy) editor.destroy();
        return;
      }
      editorRef.current = editor;
      setReady(true);
    })();

    return () => {
      cancelled = true;
      if (debounceRef.current) clearTimeout(debounceRef.current);
      debounceRef.current = null;
      if (editorRef.current?.destroy) {
        editorRef.current.destroy();
        editorRef.current = null;
      }
      setReady(false);
    };
  }, [disabled, placeholder, notifyChange]);

  const handleBlur = useCallback(async () => {
    if (debounceRef.current) clearTimeout(debounceRef.current);
    debounceRef.current = null;
    const editor = editorRef.current;
    if (editor) {
      try {
        const output = await editor.save();
        notifyChange(output);
      } catch {
        // ignore
      }
    }
  }, [notifyChange]);

  if (disabled) {
    return (
      <textarea
        value={value}
        readOnly
        className="textarea textarea-sm w-full min-h-[120px]"
        style={{ minHeight }}
      />
    );
  }

  return (
    <div
      ref={holderRef}
      id={id}
      onBlur={handleBlur}
      className={ready ? "" : "min-h-[120px] flex items-center justify-center"}
      style={{ minHeight: ready ? undefined : minHeight }}
    >
      {!ready && (
        <div className="h-8 w-8 animate-spin rounded-full border-2 border-[var(--primary)] border-t-transparent" />
      )}
    </div>
  );
}
