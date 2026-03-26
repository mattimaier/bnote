/**
 * BNote Next Generation - Shared action button component
 * Consistent sizing for Add, Delete, Save, Edit, Cancel across detail pages and tables.
 * Base size: inline-flex shrink-0 items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import Link from "next/link";
import type { ComponentPropsWithoutRef } from "react";

const BASE =
  "inline-flex shrink-0 items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-center text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed";

const VARIANTS = {
  primary:
    "bg-primary text-white hover:bg-primary/90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary",
  danger:
    "bg-error text-white hover:bg-error/90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-error",
  outline:
    "border border-base-300 bg-transparent text-base-content hover:bg-base-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-base-content/30",
  "outline-error":
    "border border-error bg-transparent text-error hover:bg-error/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-error",
} as const;

export type ActionButtonVariant = keyof typeof VARIANTS;

export interface ActionButtonProps extends Omit<ComponentPropsWithoutRef<"button">, "className"> {
  variant?: ActionButtonVariant;
  /** Optional: render as Link when href is provided */
  href?: string;
  className?: string;
}

export function ActionButton({
  variant = "primary",
  href,
  className = "",
  children,
  type = "button",
  ...rest
}: ActionButtonProps) {
  const classes = `${BASE} ${VARIANTS[variant]} ${className}`.trim();

  if (href) {
    return (
      <Link href={href} className={classes}>
        {children}
      </Link>
    );
  }

  return (
    <button type={type} className={classes} {...rest}>
      {children}
    </button>
  );
}

/** ClassNames only, for use with custom elements (e.g. form submit buttons that need type="submit") */
export function actionButtonClassNames(variant: ActionButtonVariant = "primary"): string {
  return `${BASE} ${VARIANTS[variant]}`;
}
