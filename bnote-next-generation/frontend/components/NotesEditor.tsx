/**
 * BNote Next Generation - Rich text notes editor (EditorJS)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import { parseNotesInitialData } from "@/lib/editorjs-notes";
import { Spinner } from "@/components/Spinner";

type EditorJSInstance = import("@editorjs/editorjs").default;
type OutputData = import("@editorjs/editorjs").OutputData;

export interface NotesEditorProps {
  value: string;
  placeholder?: string;
  onChange: (jsonOrPlain: string) => void;
  disabled?: boolean;
  minHeight?: string;
  id?: string;
  enableImage?: boolean;
  allowChecklist?: boolean;
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
  enableImage = true,
  allowChecklist = true,
}: NotesEditorProps) {
  const holderRef = useRef<HTMLDivElement>(null);
  const editorRef = useRef<EditorJSInstance | null>(null);
  const initialValueRef = useRef(value);
  const onChangeRef = useRef(onChange);
  const [ready, setReady] = useState(false);
  const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  onChangeRef.current = onChange;

  const notifyChange = useCallback((output: OutputData) => {
    const normalizedOutput = allowChecklist ? output : normalizeChecklistBlocks(output);
    const blocks = Array.isArray(normalizedOutput?.blocks) ? normalizedOutput.blocks : [];
    if (blocks.length === 0) {
      onChangeRef.current("");
      return;
    }
    const str = JSON.stringify(normalizedOutput);
    onChangeRef.current(str);
  }, [allowChecklist]);

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

      const ListTool = allowChecklist ? List : createListToolWithoutChecklist(List);
      const tools: Record<string, unknown> = {
        header: { class: Header, config: { placeholder: "Heading" }, inlineToolbar: true },
        list: { class: ListTool, inlineToolbar: true, config: { defaultStyle: "unordered" } },
        quote: {
          class: Quote,
          config: { quotePlaceholder: "Quote", captionPlaceholder: "Caption" },
          inlineToolbar: true,
        },
        paragraph: { class: Paragraph, inlineToolbar: true },
      };
      if (enableImage) {
        tools.image = { class: SimpleImage, inlineToolbar: true };
      }

      const editorConfig = {
        holder: holderRef.current,
        placeholder,
        data: initialData as OutputData | undefined,
        tools,
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
  }, [disabled, placeholder, notifyChange, enableImage, allowChecklist]);

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
      className={`${ready ? "w-full" : "w-full min-h-[120px] flex items-center justify-center"} ${allowChecklist ? "" : "notes-editor-no-checklist"}`}
      style={{ minHeight: ready ? undefined : minHeight }}
    >
      {!ready && (
        <Spinner />
      )}
    </div>
  );
}

function normalizeChecklistBlocks(output: OutputData): OutputData {
  const blocks = Array.isArray(output.blocks) ? output.blocks : [];
  const normalizedBlocks = blocks.map((block) => {
    if (!block || block.type !== "list" || typeof block.data !== "object" || !block.data) return block;
    const data = block.data as { style?: string; items?: unknown[] };
    if (data.style !== "checklist") return block;
    return {
      ...block,
      data: {
        ...data,
        style: "unordered",
        items: normalizeChecklistItems(data.items ?? []),
      },
    };
  });
  return { ...output, blocks: normalizedBlocks };
}

function normalizeChecklistItems(items: unknown[]): unknown[] {
  return items.map((item) => {
    if (typeof item === "string") return item;
    if (!item || typeof item !== "object") return item;
    const typed = item as { content?: string; text?: string; items?: unknown[] };
    return {
      content: typed.content ?? typed.text ?? "",
      items: Array.isArray(typed.items) ? normalizeChecklistItems(typed.items) : [],
    };
  });
}

function createListToolWithoutChecklist(BaseListTool: new (...args: unknown[]) => { renderSettings?: () => unknown[]; api?: { i18n?: { t?: (k: string) => string } } }) {
  class ListWithoutChecklist extends BaseListTool {
    static get toolbox(): unknown {
      const toolbox = (BaseListTool as unknown as { toolbox?: unknown }).toolbox;
      return removeChecklistToolboxEntries(toolbox);
    }

    renderSettings(): unknown[] {
      // Force any stale checklist style back to unordered in mail editors.
      if ((this as { listStyle?: string }).listStyle === "checklist") {
        (this as { listStyle?: string }).listStyle = "unordered";
      }
      const settings = typeof super.renderSettings === "function" ? super.renderSettings() : [];
      const checklistLabel = this.api?.i18n?.t?.("Checklist") ?? "Checklist";
      return removeChecklistTuneItems(settings, checklistLabel);
    }
  }
  return ListWithoutChecklist;
}

function removeChecklistTuneItems(items: unknown[], checklistLabel: string): unknown[] {
  const normalizedChecklistLabel = checklistLabel.trim().toLowerCase();
  return items
    .map((item) => {
      if (!item || typeof item !== "object") return item;
      const tune = item as { label?: string; title?: string; onActivate?: unknown; children?: { items?: unknown[] } };
      const label = String(tune.label ?? tune.title ?? "").trim().toLowerCase();
      const onActivateSource =
        typeof tune.onActivate === "function" ? Function.prototype.toString.call(tune.onActivate).toLowerCase() : "";
      const isChecklistByLabel = label === normalizedChecklistLabel || label === "checklist" || label.includes("checklist");
      const isChecklistByHandler = onActivateSource.includes("checklist");
      if (isChecklistByLabel || isChecklistByHandler) return null;
      if (tune.children?.items && Array.isArray(tune.children.items)) {
        tune.children.items = removeChecklistTuneItems(tune.children.items, checklistLabel);
      }
      return tune;
    })
    .filter((item) => item !== null);
}

function removeChecklistToolboxEntries(toolbox: unknown): unknown {
  if (!Array.isArray(toolbox)) return toolbox;
  return toolbox.filter((entry) => {
    if (!entry || typeof entry !== "object") return true;
    const item = entry as { title?: string; data?: { style?: string } };
    const title = String(item.title ?? "").trim().toLowerCase();
    const style = String(item.data?.style ?? "").trim().toLowerCase();
    return style !== "checklist" && title !== "checklist";
  });
}
