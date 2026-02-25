/**
 * BNote Next Generation - Chat panel for rehearsals, concerts, and votes
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import { useI18n } from "@/contexts/I18nContext";
import { useToast } from "@/contexts/ToastContext";
import { commentsApi, type Comment, type CommentOtype } from "@/lib/comments-api";
import { Avatar } from "@/components/Avatar";
import { Spinner } from "@/components/Spinner";

const POLL_INTERVAL_MS = 20000;
const TEXTAREA_MAX_HEIGHT_PX = 120;

const URL_REGEX = /(https?:\/\/[^\s]+)/g;

function linkify(message: string): React.ReactNode[] {
  const parts = message.split(URL_REGEX);
  const nodes: React.ReactNode[] = [];
  parts.forEach((part, i) => {
    const isUrl = part.startsWith("http://") || part.startsWith("https://");
    if (isUrl) {
      nodes.push(
        <a
          key={i}
          href={part}
          target="_blank"
          rel="noopener noreferrer"
          className="link link-primary underline break-all"
          onClick={(e) => e.stopPropagation()}
          onContextMenu={(e) => e.stopPropagation()}
        >
          {part}
        </a>
      );
    } else {
      nodes.push(part);
    }
  });
  return nodes;
}

export interface EventChatPanelProps {
  otype: CommentOtype;
  oid: number;
  currentUserId: number;
  discussionOn?: boolean;
  /** When true, do not render the panel title (e.g. when used inside EntityChatLayout desktop panel) */
  hideTitle?: boolean;
}

function mergeComments(existing: Comment[], incoming: Comment[]): Comment[] {
  const byId = new Map(existing.map((c) => [c.id, c]));
  for (const c of incoming) {
    byId.set(c.id, c);
  }
  return Array.from(byId.values()).sort((a, b) => {
    const ta = new Date(a.created_at).getTime();
    const tb = new Date(b.created_at).getTime();
    return ta - tb;
  });
}

export function EventChatPanel({
  otype,
  oid,
  currentUserId,
  discussionOn = true,
  hideTitle = false,
}: EventChatPanelProps) {
  const { t, formatDateTime } = useI18n();
  const { showToast } = useToast();
  const [comments, setComments] = useState<Comment[]>([]);
  const [loading, setLoading] = useState(true);
  const [listError, setListError] = useState<string | null>(null);
  const [input, setInput] = useState("");
  const [sending, setSending] = useState(false);
  const textareaRef = useRef<HTMLTextAreaElement>(null);

  const fetchComments = useCallback(async () => {
    if (!discussionOn) return;
    try {
      const list = await commentsApi.list(otype, oid);
      setComments((prev) => mergeComments(prev, list));
      setListError(null);
    } catch (err) {
      const msg = err instanceof Error ? err.message : String(err);
      setListError(msg);
      setComments((prev) => (prev.length > 0 ? prev : []));
    } finally {
      setLoading(false);
    }
  }, [otype, oid, discussionOn]);

  useEffect(() => {
    fetchComments();
  }, [fetchComments]);

  useEffect(() => {
    if (!discussionOn) return;
    const id = setInterval(fetchComments, POLL_INTERVAL_MS);
    return () => clearInterval(id);
  }, [fetchComments, discussionOn]);

  useEffect(() => {
    handleTextareaInput();
  }, [input]);

  const handleSend = async () => {
    const msg = input.trim();
    if (!msg || sending) return;
    setSending(true);
    try {
      const added = await commentsApi.add(otype, oid, msg);
      setComments((prev) => mergeComments(prev, [added]));
      setInput("");
      resetTextareaHeight();
    } catch (err) {
      const msg = err instanceof Error ? err.message : t("js.chat.sendError");
      showToast(msg, "error");
    } finally {
      setSending(false);
    }
  };

  const handleTextareaInput = () => {
    const el = textareaRef.current;
    if (!el) return;
    el.style.height = "auto";
    el.style.height = `${Math.min(el.scrollHeight, TEXTAREA_MAX_HEIGHT_PX)}px`;
  };

  const resetTextareaHeight = () => {
    const el = textareaRef.current;
    if (el) {
      el.style.height = "auto";
    }
  };

  if (!discussionOn) return null;

  const title = t("js.chat.title");
  const placeholder = t("js.chat.placeholder");
  const sendLabel = t("js.chat.send");
  const noComments = t("js.chat.noComments");

  return (
    <div className="flex flex-col h-full min-h-0 bg-base-200 overflow-hidden">
      {!hideTitle && (
        <div className="px-4 py-3 border-b border-base-300 bg-base-200/60 shrink-0">
          <h3 className="font-semibold text-base text-base-content">{title}</h3>
        </div>
      )}
      <div className="p-4 flex flex-col gap-4 min-h-0">
        {loading && comments.length === 0 ? (
          <div className="flex items-center justify-center py-8">
            <Spinner />
          </div>
        ) : listError && comments.length === 0 ? (
          <p className="text-xs text-error px-2">{listError}</p>
        ) : comments.length === 0 ? (
          <p className="text-xs text-base-content/60 px-2">{noComments}</p>
        ) : (
          comments.map((c) => {
            const isSelf = c.author_id === currentUserId;
            const bubbleContent = (
              <div
                className={
                  "chat-bubble text-sm whitespace-pre-wrap break-words" +
                  (isSelf ? " [&_a]:!text-white [&_a]:underline [&_a]:break-all" : "")
                }
              >
                {linkify(c.message)}
              </div>
            );
            return (
              <div
                key={c.id}
                className={`chat ${isSelf ? "chat-sender" : "chat-receiver"}`}
              >
                <div className="chat-avatar avatar shrink-0">
                  <Avatar
                    name={c.author || "?"}
                    email={c.author_email ?? undefined}
                    size={40}
                    variant="soft"
                  />
                </div>
                <div className="chat-header text-base-content text-sm flex items-center gap-2 min-w-0">
                  <span className="font-medium truncate min-w-0">
                    {c.author || "?"}
                  </span>
                  <time className="text-base-content/50 shrink-0 whitespace-nowrap">
                    {formatDateTime(new Date(c.created_at))}
                  </time>
                </div>
                {bubbleContent}
              </div>
            );
          })
        )}
      </div>
      <div className="p-3 border-t border-base-300 bg-base-200/40 shrink-0">
        <div className="join w-full flex items-end">
          <textarea
            ref={textareaRef}
            value={input}
            onChange={(e) => {
              setInput(e.target.value);
              handleTextareaInput();
            }}
            onKeyDown={(e) => {
              if (e.key === "Enter" && !e.shiftKey) {
                e.preventDefault();
                handleSend();
              }
            }}
            placeholder={placeholder}
            className="textarea textarea-bordered join-item flex-1 min-w-0 min-h-[2.5rem] max-h-[120px] resize-none overflow-hidden !bg-white focus:ring-inset"
            style={{ height: "auto" }}
            rows={1}
            maxLength={2000}
            aria-label={placeholder}
          />
          <button
            type="button"
            onClick={handleSend}
            disabled={!input.trim() || sending}
            className="btn btn-primary join-item"
          >
            {sending ? (
              <Spinner size="sm" />
            ) : (
              sendLabel
            )}
          </button>
        </div>
      </div>
    </div>
  );
}
