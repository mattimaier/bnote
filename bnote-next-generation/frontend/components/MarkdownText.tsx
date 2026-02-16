/**
 * BNote Next Generation - Markdown renderer for free-text fields
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import React from "react";
import ReactMarkdown from "react-markdown";
import remarkGfm from "remark-gfm";
import remarkBreaks from "remark-breaks";

interface MarkdownTextProps {
  value: string;
  className?: string;
  inline?: boolean;
}

export function MarkdownText({ value, className, inline = false }: MarkdownTextProps) {
  const components = {
    p: ({ children }: { children?: React.ReactNode }) => (
      <p className={inline ? "inline" : "mb-1.5 last:mb-0 leading-relaxed"}>{children}</p>
    ),
    ul: ({ children }: { children?: React.ReactNode }) => (
      <ul className={inline ? "inline" : "list-disc pl-5 space-y-0.5 my-1"}>{children}</ul>
    ),
    ol: ({ children }: { children?: React.ReactNode }) => (
      <ol className={inline ? "inline" : "list-decimal pl-5 space-y-0.5 my-1"}>{children}</ol>
    ),
    li: ({ children }: { children?: React.ReactNode }) => <li className="leading-relaxed">{children}</li>,
    a: ({ href, children }: { href?: string; children?: React.ReactNode }) => (
      <a
        href={href}
        target="_blank"
        rel="noreferrer noopener"
        className="underline underline-offset-2 hover:text-primary"
      >
        {children}
      </a>
    ),
    strong: ({ children }: { children?: React.ReactNode }) => <strong className="font-semibold">{children}</strong>,
    em: ({ children }: { children?: React.ReactNode }) => <em className="italic">{children}</em>,
    code: ({ children }: { children?: React.ReactNode }) => (
      <code className="rounded bg-base-200 px-1 py-0.5 text-[0.95em]">{children}</code>
    ),
    pre: ({ children }: { children?: React.ReactNode }) => (
      <pre className="mt-2 overflow-x-auto rounded-md bg-base-200 p-3 text-xs">{children}</pre>
    ),
  };

  return (
    <div className={className}>
      <ReactMarkdown remarkPlugins={[remarkGfm, remarkBreaks]} components={components}>
        {value}
      </ReactMarkdown>
    </div>
  );
}
