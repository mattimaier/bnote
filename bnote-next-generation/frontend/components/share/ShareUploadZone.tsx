/**
 * BNote Next Generation - Share Upload Zone
 * Drag-and-drop + file picker for file uploads
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useCallback, useState } from "react";
import { Upload } from "@/components/icons";

export interface ShareUploadZoneProps {
  onUpload: (files: File[]) => Promise<void>;
  disabled?: boolean;
  className?: string;
}

export function ShareUploadZone({
  onUpload,
  disabled = false,
  className = "",
}: ShareUploadZoneProps) {
  const [isDragging, setIsDragging] = useState(false);
  const [uploading, setUploading] = useState(false);

  const handleDrop = useCallback(
    async (e: React.DragEvent) => {
      e.preventDefault();
      setIsDragging(false);
      if (disabled || uploading) return;
      const files = Array.from(e.dataTransfer.files).filter((f) => !f.name.startsWith("."));
      if (files.length === 0) return;
      setUploading(true);
      try {
        await onUpload(files);
      } finally {
        setUploading(false);
      }
    },
    [onUpload, disabled, uploading]
  );

  const handleDragOver = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    setIsDragging(true);
  }, []);

  const handleDragLeave = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    setIsDragging(false);
  }, []);

  const handleFileSelect = useCallback(
    async (e: React.ChangeEvent<HTMLInputElement>) => {
      const files = e.target.files ? Array.from(e.target.files) : [];
      e.target.value = "";
      if (files.length === 0 || disabled || uploading) return;
      setUploading(true);
      try {
        await onUpload(files);
      } finally {
        setUploading(false);
      }
    },
    [onUpload, disabled, uploading]
  );

  return (
    <div
      onDrop={handleDrop}
      onDragOver={handleDragOver}
      onDragLeave={handleDragLeave}
      className={`relative rounded-lg border-2 border-dashed p-6 transition-colors ${
        isDragging && !disabled ? "border-[var(--primary)] bg-[var(--primary)]/5" : ""
      } ${disabled ? "opacity-50 cursor-not-allowed" : "cursor-pointer hover:border-[var(--border)]"}`}
      style={{
        borderColor: isDragging && !disabled ? "var(--primary)" : "var(--border)",
        background: isDragging && !disabled ? "color-mix(in oklch, var(--primary) 5%, transparent)" : "var(--muted)/30",
      }}
    >
      <input
        type="file"
        multiple
        className="absolute inset-0 w-full h-full opacity-0 cursor-pointer disabled:cursor-not-allowed"
        onChange={handleFileSelect}
        disabled={disabled || uploading}
      />
      <div className="flex flex-col items-center justify-center gap-2 text-center">
        <Upload
          className="h-10 w-10 shrink-0"
          style={{ color: "var(--muted-foreground)" }}
        />
        <p className="text-sm font-medium" style={{ color: "var(--foreground)" }}>
          {uploading ? "Uploading…" : "Drag files here or click to select"}
        </p>
        <p className="text-xs" style={{ color: "var(--muted-foreground)" }}>
          Multiple files supported
        </p>
      </div>
    </div>
  );
}
