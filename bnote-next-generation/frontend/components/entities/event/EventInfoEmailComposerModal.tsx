"use client";

import { useEffect, useMemo, useState } from "react";
import { MultiSelect } from "@/components/entities/event/MultiSelect";
import { NotesEditor } from "@/components/NotesEditor";
import { Spinner } from "@/components/Spinner";
import { EventEntityHeader } from "@/components/entities/event/EventEntityHeader";
import { Trash2 } from "@/components/icons";
import { CHECKBOX_ROW_INPUT_CLASS } from "@/components/CheckboxRow";
import { api } from "@/lib/api";
import { isEmptyEditorJson } from "@/lib/editorjs-notes";
import { useToast } from "@/contexts/ToastContext";
import type { SimpleOption } from "@/lib/entities/event/types";

type ComposerTab = "compose" | "preview";

interface DraftRecipient {
  id: number;
  name: string;
  email: string;
}

interface DraftResponse {
  recipients: DraftRecipient[];
  additionalContacts?: DraftRecipient[];
  declinedRecipientIds?: number[];
  fromEmail?: string;
  toEmail?: string;
  selectedRecipientIds: number[];
  subject: string;
  body: string;
}

interface PreviewResponse {
  html: string;
}

interface SendResponse {
  sent: number;
  skipped: number;
}

interface EventInfoEmailComposerModalProps {
  open?: boolean;
  embedded?: boolean;
  onClose: () => void;
  module: "rehearsals" | "concerts";
  eventId: number;
  locale: string;
  title: string;
  eventIconName?: string;
  eventBadgeLabel?: string;
  eventBadgeClassName?: string;
  eventMetaLine?: string;
  eventLocation?: string;
  t: (key: string) => string;
}

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

function isValidEmail(value: string): boolean {
  return EMAIL_RE.test(value.trim());
}

function uniqueEmails(values: string[]): string[] {
  const map = new Map<string, string>();
  values.forEach((value) => {
    const trimmed = value.trim();
    if (!trimmed) return;
    const key = trimmed.toLowerCase();
    if (!map.has(key)) map.set(key, trimmed);
  });
  return Array.from(map.values());
}

function uniqueIds(values: number[]): number[] {
  return Array.from(new Set(values.map((value) => Number(value)).filter((value) => Number.isInteger(value) && value > 0)));
}

export function EventInfoEmailComposerModal({
  open = true,
  embedded = false,
  onClose,
  module,
  eventId,
  locale,
  title,
  eventIconName,
  eventBadgeLabel,
  eventBadgeClassName,
  eventMetaLine,
  eventLocation,
  t,
}: EventInfoEmailComposerModalProps) {
  const { showToast } = useToast();
  const [tab, setTab] = useState<ComposerTab>("compose");
  const [loadingDraft, setLoadingDraft] = useState(false);
  const [draftError, setDraftError] = useState("");
  const [sending, setSending] = useState(false);

  const [recipientOptions, setRecipientOptions] = useState<SimpleOption[]>([]);
  const [additionalContactOptions, setAdditionalContactOptions] = useState<SimpleOption[]>([]);
  const [selectedRecipientIds, setSelectedRecipientIds] = useState<number[]>([]);
  const [declinedRecipientIds, setDeclinedRecipientIds] = useState<number[]>([]);
  const [includeDeclinedRecipients, setIncludeDeclinedRecipients] = useState(false);
  const [selectedAdditionalContactIds, setSelectedAdditionalContactIds] = useState<number[]>([]);
  const [manualEmailInput, setManualEmailInput] = useState("");
  const [manualEmails, setManualEmails] = useState<string[]>([]);
  const [fromEmail, setFromEmail] = useState("");
  const [toEmail, setToEmail] = useState("");
  const [subject, setSubject] = useState("");
  const [body, setBody] = useState("");

  const [previewLoading, setPreviewLoading] = useState(false);
  const [previewError, setPreviewError] = useState("");
  const [previewHtml, setPreviewHtml] = useState("");

  const validManualEmails = useMemo(() => manualEmails.filter((email) => isValidEmail(email)), [manualEmails]);
  const knownRecipientEmails = useMemo(() => {
    const byId = new Map<number, string>();
    recipientOptions.forEach((option) => {
      if (option.email) byId.set(option.id, option.email);
    });
    additionalContactOptions.forEach((option) => {
      if (option.email) byId.set(option.id, option.email);
    });
    return byId;
  }, [recipientOptions, additionalContactOptions]);
  const previewBccEmails = useMemo(
    () =>
      uniqueEmails([
        ...selectedRecipientIds.map((id) => knownRecipientEmails.get(id) ?? "").filter(Boolean),
        ...selectedAdditionalContactIds.map((id) => knownRecipientEmails.get(id) ?? "").filter(Boolean),
        ...validManualEmails,
      ]),
    [knownRecipientEmails, selectedRecipientIds, selectedAdditionalContactIds, validManualEmails]
  );
  const declinedSelectableIds = useMemo(
    () => declinedRecipientIds.filter((id) => recipientOptions.some((option) => option.id === id)),
    [declinedRecipientIds, recipientOptions]
  );
  const invalidManualCount = manualEmails.length - validManualEmails.length;
  const selectedParticipantsCount = selectedRecipientIds.length;
  const selectedContactsCount = selectedAdditionalContactIds.length;
  const selectedManualCount = validManualEmails.length;
  const totalRecipientsCount = selectedRecipientIds.length + selectedAdditionalContactIds.length + validManualEmails.length;
  const bodyEmpty = !body.trim() || isEmptyEditorJson(body);
  const sendDisabled = sending || subject.trim().length === 0 || bodyEmpty || totalRecipientsCount < 1 || invalidManualCount > 0;

  const labelSelectRecipients =
    t("js.event.emailInfo.selectRecipients") !== "js.event.emailInfo.selectRecipients"
      ? t("js.event.emailInfo.selectRecipients")
      : "Select recipients";
  const labelNoMatches = t("js.common.noMatches") !== "js.common.noMatches" ? t("js.common.noMatches") : "No matches";
  const labelClose = t("js.common.close") !== "js.common.close" ? t("js.common.close") : "Close";
  const labelNoSelection = t("js.common.noSelection") !== "js.common.noSelection" ? t("js.common.noSelection") : "No selection";
  const labelRemove = t("js.common.remove") !== "js.common.remove" ? t("js.common.remove") : "Remove";
  const labelSearch = t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search...";
  const customHintPlaceholder =
    t("mail.eventInfo.defaultCustomTextHint") !== "mail.eventInfo.defaultCustomTextHint"
      ? t("mail.eventInfo.defaultCustomTextHint")
      : (t("js.event.emailInfo.messagePlaceholder") !== "js.event.emailInfo.messagePlaceholder"
          ? t("js.event.emailInfo.messagePlaceholder")
          : "Type or paste content...");
  const active = embedded || open;

  useEffect(() => {
    if (!active || eventId <= 0) return;
    let cancelled = false;
    setTab("compose");
    setDraftError("");
    setPreviewError("");
    setPreviewHtml("");
    setLoadingDraft(true);
    api
      .post<DraftResponse>(module, "emailInfoDraft", { id: eventId, locale })
      .then((draft) => {
        if (cancelled) return;
        const options: SimpleOption[] = (draft.recipients ?? []).map((recipient) => ({
          id: recipient.id,
          name: recipient.name,
          email: recipient.email,
        }));
        const additionalOptions: SimpleOption[] = (draft.additionalContacts ?? []).map((recipient) => ({
          id: recipient.id,
          name: recipient.name,
          email: recipient.email,
        }));
        setRecipientOptions(options);
        setAdditionalContactOptions(additionalOptions);
        const declinedIds = uniqueIds(draft.declinedRecipientIds ?? []);
        const preselectedIds = uniqueIds(draft.selectedRecipientIds ?? options.map((option) => option.id));
        setDeclinedRecipientIds(declinedIds);
        setIncludeDeclinedRecipients(false);
        setSelectedRecipientIds(preselectedIds.filter((id) => !declinedIds.includes(id)));
        setSelectedAdditionalContactIds([]);
        setFromEmail((draft.fromEmail ?? "").trim());
        setToEmail((draft.toEmail ?? "").trim());
        setSubject(draft.subject ?? "");
        setBody(draft.body ?? "");
        setManualEmails([]);
        setManualEmailInput("");
      })
      .catch((error: unknown) => {
        if (cancelled) return;
        const fallback = t("js.event.emailInfo.loadDraftFailed");
        setDraftError(error instanceof Error && error.message ? error.message : fallback);
      })
      .finally(() => {
        if (!cancelled) setLoadingDraft(false);
      });
    return () => {
      cancelled = true;
    };
  }, [active, eventId, locale, module, t]);

  useEffect(() => {
    if (!active || tab !== "preview" || eventId <= 0) return;
    const timer = window.setTimeout(() => {
      setPreviewLoading(true);
      setPreviewError("");
      const payload = {
        id: eventId,
        locale,
        recipientIds: uniqueIds([...selectedRecipientIds, ...selectedAdditionalContactIds]),
        manualEmails: validManualEmails,
        subject,
        body,
      };
      api
        .post<PreviewResponse>(module, "emailInfoPreview", payload)
        .then((res) => setPreviewHtml(res.html ?? ""))
        .catch((error: unknown) => {
          const fallback = t("js.event.emailInfo.previewFailed");
          setPreviewError(error instanceof Error && error.message ? error.message : fallback);
          setPreviewHtml("");
        })
        .finally(() => setPreviewLoading(false));
    }, 350);
    return () => {
      window.clearTimeout(timer);
    };
  }, [active, tab, eventId, locale, module, selectedRecipientIds, selectedAdditionalContactIds, validManualEmails, subject, body, t]);

  useEffect(() => {
    if (declinedSelectableIds.length < 1) return;
    if (includeDeclinedRecipients) {
      setSelectedRecipientIds((prev) => uniqueIds([...prev, ...declinedSelectableIds]));
      return;
    }
    const declinedSet = new Set(declinedSelectableIds);
    setSelectedRecipientIds((prev) => prev.filter((id) => !declinedSet.has(id)));
  }, [declinedSelectableIds, includeDeclinedRecipients]);

  const addManualEmails = (rawInput: string) => {
    const parts = rawInput
      .split(/[\n,;]+/)
      .map((value) => value.trim())
      .filter(Boolean);
    if (parts.length === 0) return;
    setManualEmails((prev) => uniqueEmails([...prev, ...parts]));
    setManualEmailInput("");
  };

  const sendEmail = async () => {
    if (sendDisabled) return;
    setSending(true);
    try {
      const res = await api.post<SendResponse>(module, "emailInfoSend", {
        id: eventId,
        locale,
        recipientIds: uniqueIds([...selectedRecipientIds, ...selectedAdditionalContactIds]),
        manualEmails: validManualEmails,
        subject,
        body,
      });
      const sentLabel = t("js.event.emailInfo.sent") !== "js.event.emailInfo.sent" ? t("js.event.emailInfo.sent") : "Email sent";
      const skippedSuffix =
        res.skipped > 0
          ? ` (${res.sent} ${t("js.event.emailInfo.sentCount") !== "js.event.emailInfo.sentCount" ? t("js.event.emailInfo.sentCount") : "sent"}, ${res.skipped} ${t("js.event.emailInfo.skippedCount") !== "js.event.emailInfo.skippedCount" ? t("js.event.emailInfo.skippedCount") : "skipped"})`
          : "";
      showToast(`${sentLabel}${skippedSuffix}`, "success");
      onClose();
    } catch (error) {
      const fallback = t("js.event.emailInfo.sendFailed") !== "js.event.emailInfo.sendFailed" ? t("js.event.emailInfo.sendFailed") : "Failed to send email";
      showToast(error instanceof Error && error.message ? error.message : fallback, "error");
    } finally {
      setSending(false);
    }
  };

  const content = (
      <div className={`flex flex-col ${embedded ? "h-[100dvh]" : "max-h-[88dvh]"}`}>
        <div className="border-b border-base-300 px-4 pt-3">
          <div className="mb-3 rounded-lg border border-base-300 bg-base-200/40 p-3">
            <EventEntityHeader
              title={title}
              iconName={eventIconName ?? "calendar"}
              iconColor={module === "rehearsals" ? "var(--color-primary)" : "var(--color-accent)"}
              badgeLabel={eventBadgeLabel ?? ""}
              badgeClassName={eventBadgeClassName ?? ""}
              dateTimeLine={eventMetaLine ?? ""}
              locationLine={eventLocation ?? undefined}
            />
          </div>
          <div className="flex flex-wrap items-center justify-between gap-2">
            <h2 className="text-lg font-semibold text-base-content">
              {t("js.event.emailInfo.modalTitle") !== "js.event.emailInfo.modalTitle" ? t("js.event.emailInfo.modalTitle") : "Send event info"}
            </h2>
            <div className="flex items-center gap-2">
              <button type="button" className="btn btn-primary" onClick={() => void sendEmail()} disabled={sendDisabled}>
                {sending
                  ? t("js.event.emailInfo.sending") !== "js.event.emailInfo.sending"
                    ? t("js.event.emailInfo.sending")
                    : "Sending..."
                  : t("js.event.emailInfo.sendNow") !== "js.event.emailInfo.sendNow"
                    ? t("js.event.emailInfo.sendNow")
                    : "Send email"}
              </button>
            </div>
          </div>
          <div className="flex gap-2">
            <button
              type="button"
              className={`btn ${tab === "compose" ? "btn-soft btn-primary" : "btn-soft"}`}
              onClick={() => setTab("compose")}
            >
              {t("js.event.emailInfo.tabCompose") !== "js.event.emailInfo.tabCompose" ? t("js.event.emailInfo.tabCompose") : "Compose"}
            </button>
            <button
              type="button"
              className={`btn ${tab === "preview" ? "btn-soft btn-primary" : "btn-soft"}`}
              onClick={() => setTab("preview")}
            >
              {t("js.event.emailInfo.tabPreview") !== "js.event.emailInfo.tabPreview" ? t("js.event.emailInfo.tabPreview") : "Preview"}
            </button>
          </div>
          <div className="pb-3" />
        </div>

        <div className="flex-1 overflow-y-auto p-4">
          {loadingDraft ? (
            <div className="flex items-center justify-center py-10">
              <Spinner />
            </div>
          ) : draftError ? (
            <div className="rounded-lg border border-error bg-error/15 px-3 py-2 text-sm text-error">{draftError}</div>
          ) : tab === "compose" ? (
            <div className="space-y-4">
              <section className="space-y-3 rounded-lg border border-base-300 bg-base-200/30 p-3">
                <h3 className="text-sm font-semibold text-base-content">
                  {t("js.event.emailInfo.recipients") !== "js.event.emailInfo.recipients" ? t("js.event.emailInfo.recipients") : "Recipients"}
                </h3>
                <div className="flex flex-wrap items-center gap-2 text-xs text-base-content/70">
                  <span className="rounded-full bg-base-100 px-2 py-0.5">
                    {(t("js.event.emailInfo.recipients") !== "js.event.emailInfo.recipients" ? t("js.event.emailInfo.recipients") : "Recipients") +
                      `: ${selectedParticipantsCount}`}
                  </span>
                  <span className="rounded-full bg-base-100 px-2 py-0.5">
                    {(t("js.event.emailInfo.additionalContacts") !== "js.event.emailInfo.additionalContacts"
                      ? t("js.event.emailInfo.additionalContacts")
                      : "Additional contacts") + `: ${selectedContactsCount}`}
                  </span>
                  <span className="rounded-full bg-base-100 px-2 py-0.5">
                    {(t("js.event.emailInfo.extraEmails") !== "js.event.emailInfo.extraEmails" ? t("js.event.emailInfo.extraEmails") : "Extra emails") +
                      `: ${selectedManualCount}`}
                  </span>
                  <span className="rounded-full bg-base-100 px-2 py-0.5 font-medium">
                    {(
                      t("js.event.emailInfo.recipientCount") !== "js.event.emailInfo.recipientCount"
                        ? t("js.event.emailInfo.recipientCount")
                        : "{count} recipients selected"
                    ).replace("{count}", String(totalRecipientsCount))}
                  </span>
                </div>
                <label
                  className={`inline-flex items-center gap-2 pt-1 text-sm ${
                    declinedSelectableIds.length > 0 ? "text-base-content" : "text-base-content/50"
                  }`}
                >
                  <input
                    type="checkbox"
                    className={CHECKBOX_ROW_INPUT_CLASS}
                    checked={includeDeclinedRecipients}
                    disabled={declinedSelectableIds.length < 1}
                    onChange={(event) => setIncludeDeclinedRecipients(event.target.checked)}
                  />
                  <span>
                    {t("js.event.emailInfo.includeDeclined") !== "js.event.emailInfo.includeDeclined"
                      ? t("js.event.emailInfo.includeDeclined")
                      : "Include declined contacts"}
                  </span>
                </label>

                <div>
                  <label className="mb-1 block text-xs font-medium text-base-content/60">
                    {t("js.event.emailInfo.recipients") !== "js.event.emailInfo.recipients" ? t("js.event.emailInfo.recipients") : "Recipients"}
                  </label>
                  <MultiSelect
                    options={recipientOptions}
                    selected={selectedRecipientIds}
                    onChange={setSelectedRecipientIds}
                    placeholder={labelSearch}
                    showChips={false}
                    labelSelect={labelSelectRecipients}
                    labelNoMatches={labelNoMatches}
                    labelClose={labelClose}
                    labelNoSelection={labelNoSelection}
                    labelRemove={labelRemove}
                  />
                </div>

                <div>
                  <label className="mb-1 block text-xs font-medium text-base-content/60">
                    {t("js.event.emailInfo.additionalContacts") !== "js.event.emailInfo.additionalContacts"
                      ? t("js.event.emailInfo.additionalContacts")
                      : "Additional contacts"}
                  </label>
                  <MultiSelect
                    options={additionalContactOptions}
                    selected={selectedAdditionalContactIds}
                    onChange={setSelectedAdditionalContactIds}
                    placeholder={labelSearch}
                    showChips={false}
                    labelSelect={
                      t("js.event.emailInfo.selectAdditionalContacts") !== "js.event.emailInfo.selectAdditionalContacts"
                        ? t("js.event.emailInfo.selectAdditionalContacts")
                        : "Select additional contacts"
                    }
                    labelNoMatches={labelNoMatches}
                    labelClose={labelClose}
                    labelNoSelection={labelNoSelection}
                    labelRemove={labelRemove}
                  />
                </div>

                <div>
                  <label className="mb-1 block text-xs font-medium text-base-content/60">
                    {t("js.event.emailInfo.extraEmails") !== "js.event.emailInfo.extraEmails" ? t("js.event.emailInfo.extraEmails") : "Extra emails"}
                  </label>
                  <div className="input input-bordered w-full min-h-[3rem] h-auto flex flex-wrap items-center gap-2 py-2">
                    {manualEmails.map((email) => (
                      <span
                        key={email}
                        className={`inline-flex items-center gap-2 rounded-full border px-2 py-1 text-xs ${
                          isValidEmail(email) ? "border-base-300" : "border-error text-error"
                        }`}
                      >
                        {email}
                        <button
                          type="button"
                          className="inline-flex h-6 w-6 items-center justify-center rounded-md text-base-content/60 transition-colors hover:bg-base-200 hover:text-base-content"
                          onClick={() => setManualEmails((prev) => prev.filter((value) => value !== email))}
                          aria-label={labelRemove}
                        >
                          <Trash2 className="h-3.5 w-3.5" />
                        </button>
                      </span>
                    ))}
                    <input
                      type="text"
                      value={manualEmailInput}
                      onChange={(event) => setManualEmailInput(event.target.value)}
                      onBlur={() => addManualEmails(manualEmailInput)}
                      onKeyDown={(event) => {
                        if (event.key === "Enter" || event.key === "," || event.key === ";") {
                          event.preventDefault();
                          addManualEmails(manualEmailInput);
                        }
                      }}
                      className="flex-1 min-w-[200px] border-0 bg-transparent p-0 text-sm outline-none"
                      placeholder={
                        manualEmails.length < 1
                          ? (t("js.event.emailInfo.extraEmailsPlaceholder") !== "js.event.emailInfo.extraEmailsPlaceholder"
                              ? t("js.event.emailInfo.extraEmailsPlaceholder")
                              : "name@example.com, name2@example.com")
                          : ""
                      }
                    />
                  </div>
                  {invalidManualCount > 0 && (
                    <p className="mt-2 text-xs text-error">
                      {(
                        t("js.event.emailInfo.invalidEmailCount") !== "js.event.emailInfo.invalidEmailCount"
                          ? t("js.event.emailInfo.invalidEmailCount")
                          : "{count} invalid email(s)"
                      ).replace("{count}", String(invalidManualCount))}
                    </p>
                  )}
                </div>
              </section>

              <section className="space-y-4 rounded-lg border border-base-300 bg-base-100 p-3">
                <h3 className="text-sm font-semibold text-base-content">
                  {t("js.event.emailInfo.message") !== "js.event.emailInfo.message" ? t("js.event.emailInfo.message") : "Message"}
                </h3>
                <div>
                  <label className="mb-1 block text-xs font-medium text-base-content/60">
                    {t("js.event.emailInfo.subject") !== "js.event.emailInfo.subject" ? t("js.event.emailInfo.subject") : "Subject"}
                  </label>
                  <input
                    type="text"
                    value={subject}
                    onChange={(event) => setSubject(event.target.value)}
                    className="input input-sm input-bordered w-full"
                  />
                </div>

                <div>
                  <label className="mb-1 block text-xs font-medium text-base-content/60">
                    {t("js.event.emailInfo.message") !== "js.event.emailInfo.message" ? t("js.event.emailInfo.message") : "Message"}
                  </label>
                  <NotesEditor
                    value={body}
                    onChange={setBody}
                    placeholder={customHintPlaceholder}
                    id={`event-info-email-editor-${module}-${eventId}`}
                    enableImage={false}
                    allowChecklist={false}
                  />
                </div>
              </section>
            </div>
          ) : (
            <div className="space-y-3">
              {previewLoading && (
                <div className="flex items-center justify-center py-6">
                  <Spinner />
                </div>
              )}
              {previewError && <div className="rounded-lg border border-error bg-error/15 px-3 py-2 text-sm text-error">{previewError}</div>}
              {!previewLoading && !previewError && previewHtml && (
                <div className="overflow-hidden rounded-lg border border-base-300 bg-base-100">
                  <div className="border-b border-base-300 bg-base-200/40 px-4 py-3">
                    <div className="text-xs text-base-content/60">
                      <div className="grid gap-1">
                        <div>
                          <span className="font-medium">
                            {t("js.event.emailInfo.previewFrom") !== "js.event.emailInfo.previewFrom" ? t("js.event.emailInfo.previewFrom") : "From"}:
                          </span>{" "}
                          {fromEmail || "-"}
                        </div>
                        <div>
                          <span className="font-medium">
                            {t("js.event.emailInfo.previewTo") !== "js.event.emailInfo.previewTo" ? t("js.event.emailInfo.previewTo") : "To"}:
                          </span>{" "}
                          {toEmail || "-"}
                        </div>
                        <div>
                          <span className="font-medium">
                            {t("js.event.emailInfo.previewBcc") !== "js.event.emailInfo.previewBcc" ? t("js.event.emailInfo.previewBcc") : "BCC"}:
                          </span>{" "}
                          {previewBccEmails.length > 0 ? previewBccEmails.join(", ") : "-"}
                        </div>
                        <div>
                          <span className="font-medium">
                            {t("js.event.emailInfo.subject") !== "js.event.emailInfo.subject" ? t("js.event.emailInfo.subject") : "Subject"}:
                          </span>{" "}
                          {subject || "-"}
                        </div>
                      </div>
                    </div>
                  </div>
                  <iframe
                    title={t("js.event.emailInfo.previewFrameTitle") !== "js.event.emailInfo.previewFrameTitle" ? t("js.event.emailInfo.previewFrameTitle") : "Email preview"}
                    srcDoc={previewHtml}
                    className="h-[56vh] w-full bg-base-100"
                  />
                </div>
              )}
            </div>
          )}
        </div>

      </div>
  );
  if (embedded) {
    return <div className="h-[100dvh] w-full bg-base-100">{content}</div>;
  }
  if (!open) return null;
  return (
    <div className="fixed inset-0 z-50 bg-base-content/20 p-4">
      <div className="mx-auto w-full max-w-5xl rounded-box border border-base-300 bg-base-100 shadow-xl">
        {content}
      </div>
    </div>
  );
}
