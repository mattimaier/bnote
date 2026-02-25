/**
 * BNote Next Generation - Share Upload Zone
 * Drag-and-drop + file picker for file uploads
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useCallback, useState } from "react";
import { useI18n } from "@/contexts/I18nContext";
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
  const { t } = useI18n();
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
        isDragging && !disabled ? "border-primary bg-primary/5" : "border-base-300 bg-base-200/50"
      } ${disabled ? "opacity-50 cursor-not-allowed" : "cursor-pointer hover:border-base-300"}`}
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
          className="h-10 w-10 shrink-0 text-base-content/60"
        />
        <p className="text-sm font-medium text-base-content">
          {uploading ? t("js.share.uploading") : t("js.share.dragOrClick")}
        </p>
        <p className="text-xs text-base-content/60">
          {t("js.share.multipleFilesSupported")}
        </p>
      </div>
    </div>
  );
}
