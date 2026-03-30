/**
 * BNote Next Generation - Layout wrapper that adds chat for rehearsal, concert, vote (view mode)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import React, { useCallback, useEffect, useRef, useState } from "react";
import { useSearchParams } from "next/navigation";
import { useI18n } from "@/contexts/I18nContext";
import { commentsApi, type CommentOtype } from "@/lib/comments-api";
import { EventChatPanel } from "./EventChatPanel";

const SIDEBAR_BREAKPOINT_PX = 1408;
/** Below this width the app sidebar is hidden (Tailwind md). */
const MOBILE_BREAKPOINT_PX = 768;

function useMinWidth(px: number): boolean {
  const [matches, setMatches] = useState(false);
  useEffect(() => {
    const mql = window.matchMedia(`(min-width: ${px}px)`);
    setMatches(mql.matches);
    const handler = () => setMatches(mql.matches);
    mql.addEventListener("change", handler);
    return () => mql.removeEventListener("change", handler);
  }, [px]);
  return matches;
}

const ENTITY_TYPE_TO_OTYPE: Record<string, CommentOtype> = {
  rehearsal: "R",
  concert: "C",
  vote: "V",
};

export interface EntityChatLayoutProps {
  /** Entity type: rehearsal | concert | vote */
  entityType: string;
  /** Entity id (rehearsal id, concert id, or vote id) */
  entityId: number;
  /** Current user id (for "own" comment styling and delete) */
  currentUserId: number;
  /** Main content (EventDetail or VoteDetail) */
  children: React.ReactNode;
}

export function EntityChatLayout({
  entityType,
  entityId,
  currentUserId,
  children,
}: EntityChatLayoutProps) {
  const { t } = useI18n();
  const searchParams = useSearchParams();
  const commentsAnchorRef = useRef<HTMLDivElement | null>(null);
  const [discussionOn, setDiscussionOn] = useState<boolean | null>(null);
  const isSidebar = useMinWidth(SIDEBAR_BREAKPOINT_PX);
  const isMobile = !useMinWidth(MOBILE_BREAKPOINT_PX);

  const otype = ENTITY_TYPE_TO_OTYPE[entityType.toLowerCase()];
  const hasChat = otype != null;

  const checkDiscussion = useCallback(async () => {
    if (!otype || !entityId) return;
    try {
      await commentsApi.list(otype, entityId);
      setDiscussionOn(true);
    } catch (err) {
      const e = err as Error & { status?: number };
      if (e.status === 403) {
        setDiscussionOn(false);
      } else {
        setDiscussionOn(true);
      }
    }
  }, [otype, entityId]);

  useEffect(() => {
    if (hasChat) checkDiscussion();
  }, [hasChat, checkDiscussion]);

  useEffect(() => {
    if (searchParams?.get("focus") !== "comments") return;
    if (discussionOn !== true) return;
    const scrollTimer = window.setTimeout(() => {
      commentsAnchorRef.current?.scrollIntoView({ behavior: "smooth", block: "start" });
    }, 100);
    return () => window.clearTimeout(scrollTimer);
  }, [searchParams, discussionOn]);

  const commentsTitle = t("js.chat.commentsHeading");

  if (!hasChat) {
    return <>{children}</>;
  }

  const commentsSection = discussionOn === true && (
    <>
      <div id="entity-discussion-comments" ref={commentsAnchorRef} className="scroll-mt-4" />
      <h2 className="text-lg font-semibold text-base-content mb-2 px-0 md:px-6">
        {commentsTitle}
      </h2>
      <EventChatPanel
        otype={otype!}
        oid={entityId}
        currentUserId={currentUserId}
        discussionOn={true}
        hideTitle
      />
    </>
  );

  if (isSidebar) {
    return (
      <div className="flex flex-row gap-4 min-h-full flex-1 w-full">
        <div className="flex-1 min-w-0">{children}</div>
        <aside
          className="flex flex-col min-h-full w-[360px] shrink-0 border-l border-base-300 bg-base-200 px-3 lg:px-4 pt-6 -mr-3 md:-mr-4 lg:-mr-6 -mt-16 md:-mt-4 -mb-3 md:-mb-4"
          aria-label={commentsTitle}
        >
          {commentsSection}
        </aside>
      </div>
    );
  }

  const commentsCard =
    commentsSection &&
    (isMobile ? (
      <section aria-label={commentsTitle}>{commentsSection}</section>
    ) : (
      <section aria-label={commentsTitle}>
        <div className="rounded-xl border border-base-300 shadow-sm bg-base-200 py-4 md:py-6">
          {commentsSection}
        </div>
      </section>
    ));

  const canInject =
    commentsCard &&
    React.isValidElement(children) &&
    (children.type as React.ComponentType) !== React.Suspense;

  return (
    <div className="flex flex-col min-h-0 flex-1 w-full max-w-none md:max-w-7xl md:mx-auto">
      <div className="min-w-0">
        {canInject
          ? React.cloneElement(children as React.ReactElement<{ renderAfterContent?: React.ReactNode }>, {
              renderAfterContent: commentsCard,
            })
          : (
            <>
              {children}
              {commentsCard}
            </>
          )}
      </div>
    </div>
  );
}
