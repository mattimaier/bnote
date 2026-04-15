"use client";

import { useEffect, useMemo, useState } from "react";
import { AppPageHeader } from "@/components/AppPageHeader";
import { MultiSelect } from "@/components/entities/event/MultiSelect";
import { NotesEditor } from "@/components/NotesEditor";
import { PageContent } from "@/components/PageContent";
import { Spinner } from "@/components/Spinner";
import { Trash2 } from "@/components/icons";
import { useI18n } from "@/contexts/I18nContext";
import { useToast } from "@/contexts/ToastContext";
import { isEmptyEditorJson } from "@/lib/editorjs-notes";
import { isValidEmailSyntax } from "@/lib/email-syntax";
import { emailApi, type EmailRecipientContact } from "@/lib/email-api";
import type { SimpleOption } from "@/lib/entities/event/types";

type ComposerTab = "compose" | "preview";

function uniqueIds(values: number[]): number[] {
  return Array.from(
    new Set(values.map((value) => Number(value)).filter((value) => Number.isInteger(value) && value > 0))
  );
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

export default function EmailModulePage() {
  const { t, ready, lang } = useI18n();
  const { showToast } = useToast();

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [tab, setTab] = useState<ComposerTab>("compose");
  const [sending, setSending] = useState(false);

  const [groupOptions, setGroupOptions] = useState<SimpleOption[]>([]);
  const [contactOptions, setContactOptions] = useState<SimpleOption[]>([]);
  const [groupMembers, setGroupMembers] = useState<Record<string, number[]>>({});
  const [selectedGroupIds, setSelectedGroupIds] = useState<number[]>([]);
  const [selectedAdditionalContactIds, setSelectedAdditionalContactIds] = useState<number[]>([]);
  const [manualEmailInput, setManualEmailInput] = useState("");
  const [manualEmails, setManualEmails] = useState<string[]>([]);

  const [fromEmail, setFromEmail] = useState("");
  const [toEmail, setToEmail] = useState("");
  const [subjectPrefix, setSubjectPrefix] = useState("");
  const [subject, setSubject] = useState("");
  const [body, setBody] = useState("");

  const [previewLoading, setPreviewLoading] = useState(false);
  const [previewError, setPreviewError] = useState("");
  const [previewHtml, setPreviewHtml] = useState("");

  const validManualEmails = useMemo(() => manualEmails.filter((mail) => isValidEmailSyntax(mail)), [manualEmails]);
  const invalidManualCount = manualEmails.length - validManualEmails.length;

  const selectedGroupContactIds = useMemo(() => {
    const ids: number[] = [];
    selectedGroupIds.forEach((groupId) => {
      const members = groupMembers[String(groupId)] ?? [];
      ids.push(...members);
    });
    return uniqueIds(ids);
  }, [groupMembers, selectedGroupIds]);

  const selectedRecipientIds = useMemo(
    () => uniqueIds([...selectedGroupContactIds, ...selectedAdditionalContactIds]),
    [selectedAdditionalContactIds, selectedGroupContactIds]
  );
  const selectedGroupsCount = selectedGroupIds.length;
  const selectedContactsCount = selectedAdditionalContactIds.length;
  const selectedManualCount = validManualEmails.length;
  const totalRecipientsCount = selectedRecipientIds.length + validManualEmails.length;

  const contactsById = useMemo(() => {
    const map = new Map<number, EmailRecipientContact>();
    contactOptions.forEach((contact) => {
      if (contact.id > 0 && contact.email) {
        map.set(contact.id, {
          id: contact.id,
          name: contact.name ?? "",
          email: contact.email,
          subtitle: contact.subtitle ?? undefined,
          instrument: contact.instrument ?? undefined,
        });
      }
    });
    return map;
  }, [contactOptions]);

  const previewBccEmails = useMemo(() => {
    const selectedContactEmails = selectedRecipientIds.map((id) => contactsById.get(id)?.email ?? "").filter(Boolean);
    return uniqueEmails([...selectedContactEmails, ...validManualEmails]);
  }, [contactsById, selectedRecipientIds, validManualEmails]);
  const composedSubject = useMemo(() => {
    const prefix = subjectPrefix.trim();
    const suffix = subject.trim();
    if (!prefix) return suffix;
    if (!suffix) return prefix;
    return `${prefix} - ${suffix}`;
  }, [subject, subjectPrefix]);

  const bodyEmpty = !body.trim() || isEmptyEditorJson(body);
  const sendDisabled =
    sending || bodyEmpty || selectedRecipientIds.length + validManualEmails.length < 1 || invalidManualCount > 0;

  const selectLabel = t("js.common.select") !== "js.common.select" ? t("js.common.select") : "Select...";
  const labelNoMatches = t("js.common.noMatches") !== "js.common.noMatches" ? t("js.common.noMatches") : "No matches";
  const labelClose = t("js.common.close") !== "js.common.close" ? t("js.common.close") : "Close";
  const labelNoSelection =
    t("js.common.noSelection") !== "js.common.noSelection" ? t("js.common.noSelection") : "No selection";
  const labelRemove = t("js.common.remove") !== "js.common.remove" ? t("js.common.remove") : "Remove";

  useEffect(() => {
    if (!ready) return;
    let cancelled = false;
    setLoading(true);
    setError("");

    Promise.all([emailApi.meta(), emailApi.draft(lang || "en")])
      .then(([meta, draft]) => {
        if (cancelled) return;
        setGroupOptions((meta.groups ?? []).map((group) => ({ id: group.id, name: group.name })));
        setContactOptions(
          (meta.contacts ?? []).map((contact) => ({
            id: contact.id,
            name: contact.name,
            email: contact.email,
            subtitle: contact.subtitle ?? "",
            instrument: contact.instrument ?? "",
          }))
        );
        setGroupMembers(meta.groupMembers ?? {});
        setFromEmail((draft.fromEmail ?? "").trim());
        setToEmail((draft.toEmail ?? "").trim());
        const fixedPrefix = (draft.subjectPrefix ?? "").trim();
        const draftedSubject = (draft.subject ?? "").trim();
        setSubjectPrefix(fixedPrefix);
        if (fixedPrefix !== "" && draftedSubject.toLowerCase().startsWith(fixedPrefix.toLowerCase())) {
          const remainder = draftedSubject.slice(fixedPrefix.length).replace(/^\s*-\s*/, "");
          setSubject(remainder);
        } else {
          setSubject(draftedSubject);
        }
        setBody(draft.body ?? "");
      })
      .catch((err: unknown) => {
        const fallback =
          t("js.email.loadFailed") !== "js.email.loadFailed"
            ? t("js.email.loadFailed")
            : "Failed to load email composer.";
        setError(err instanceof Error && err.message ? err.message : fallback);
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });

    return () => {
      cancelled = true;
    };
  }, [lang, ready, t]);

  useEffect(() => {
    if (!ready || tab !== "preview" || loading || error) return;
    const timer = window.setTimeout(() => {
      setPreviewLoading(true);
      setPreviewError("");
      emailApi
        .preview({
          locale: lang || "en",
          groupIds: selectedGroupIds,
          recipientIds: selectedAdditionalContactIds,
          manualEmails: validManualEmails,
          subject: composedSubject,
          body,
        })
        .then((res) => setPreviewHtml(res.html ?? ""))
        .catch((err: unknown) => {
          const fallback =
            t("js.email.previewFailed") !== "js.email.previewFailed"
              ? t("js.email.previewFailed")
              : "Failed to build preview.";
          setPreviewError(err instanceof Error && err.message ? err.message : fallback);
          setPreviewHtml("");
        })
        .finally(() => setPreviewLoading(false));
    }, 300);

    return () => {
      window.clearTimeout(timer);
    };
  }, [
    body,
    composedSubject,
    error,
    lang,
    loading,
    ready,
    selectedAdditionalContactIds,
    selectedGroupIds,
    tab,
    t,
    validManualEmails,
  ]);

  const addManualEmails = (rawInput: string) => {
    const parts = rawInput
      .split(/[\n,;]+/)
      .map((value) => value.trim())
      .filter(Boolean);
    if (parts.length < 1) return;
    setManualEmails((prev) => uniqueEmails([...prev, ...parts]));
    setManualEmailInput("");
  };

  const handleSend = async () => {
    if (sendDisabled) return;
    setSending(true);
    try {
      const res = await emailApi.send({
        locale: lang || "en",
        groupIds: selectedGroupIds,
        recipientIds: selectedAdditionalContactIds,
        manualEmails: validManualEmails,
        subject: composedSubject,
        body,
      });
      const sentLabel = t("js.email.sent") !== "js.email.sent" ? t("js.email.sent") : "Email sent";
      const sentCountLabel =
        t("js.event.emailInfo.sentCount") !== "js.event.emailInfo.sentCount"
          ? t("js.event.emailInfo.sentCount")
          : "sent";
      const skippedCountLabel =
        t("js.event.emailInfo.skippedCount") !== "js.event.emailInfo.skippedCount"
          ? t("js.event.emailInfo.skippedCount")
          : "skipped";
      const suffix = ` (${res.sent} ${sentCountLabel}, ${res.skipped} ${skippedCountLabel})`;
      showToast(`${sentLabel}${suffix}`, "success");
      setManualEmails([]);
      setManualEmailInput("");
      setSelectedAdditionalContactIds([]);
      setSelectedGroupIds([]);
      setPreviewHtml("");
      setTab("compose");
    } catch (err) {
      const fallback =
        t("js.email.sendFailed") !== "js.email.sendFailed" ? t("js.email.sendFailed") : "Failed to send email";
      showToast(err instanceof Error && err.message ? err.message : fallback, "error");
    } finally {
      setSending(false);
    }
  };

  if (!ready || loading) {
    return (
      <div className="flex justify-center py-12">
        <Spinner />
      </div>
    );
  }

  return (
    <PageContent className="px-1 md:px-4">
      <AppPageHeader
        moduleKey="email"
        title={t("js.sidebar.email") !== "js.sidebar.email" ? t("js.sidebar.email") : "Email"}
        subtitle={
          t("js.email.subtitle") !== "js.email.subtitle"
            ? t("js.email.subtitle")
            : "Compose and send emails to groups and contacts"
        }
      />

      {error && (
        <div
          className="rounded-lg border px-4 py-3 text-sm"
          style={{ borderColor: "var(--destructive)", color: "var(--destructive-foreground)" }}
        >
          {error}
        </div>
      )}

      {!error && (
        <div className="space-y-4">
          <div className="flex flex-wrap items-center justify-between gap-2">
            <div className="flex gap-2">
              <button
                type="button"
                className={`btn ${tab === "compose" ? "btn-soft btn-primary" : "btn-soft"}`}
                onClick={() => setTab("compose")}
              >
                {t("js.event.emailInfo.tabCompose") !== "js.event.emailInfo.tabCompose"
                  ? t("js.event.emailInfo.tabCompose")
                  : "Compose"}
              </button>
              <button
                type="button"
                className={`btn ${tab === "preview" ? "btn-soft btn-primary" : "btn-soft"}`}
                onClick={() => setTab("preview")}
              >
                {t("js.event.emailInfo.tabPreview") !== "js.event.emailInfo.tabPreview"
                  ? t("js.event.emailInfo.tabPreview")
                  : "Preview"}
              </button>
            </div>
            <button type="button" className="btn btn-primary" disabled={sendDisabled} onClick={() => void handleSend()}>
              {sending
                ? t("js.event.emailInfo.sending") !== "js.event.emailInfo.sending"
                  ? t("js.event.emailInfo.sending")
                  : "Sending..."
                : t("js.email.sendNow") !== "js.email.sendNow"
                  ? t("js.email.sendNow")
                  : "Send email"}
            </button>
          </div>

          {tab === "compose" ? (
            <div className="space-y-4">
              <section className="space-y-3 rounded-lg border border-base-300 bg-base-200/30 p-3">
                <h3 className="text-sm font-semibold text-base-content">
                  {t("js.event.emailInfo.recipients") !== "js.event.emailInfo.recipients"
                    ? t("js.event.emailInfo.recipients")
                    : "Recipients"}
                </h3>
                <div className="flex flex-wrap items-center gap-2 text-xs text-base-content/70">
                  <span className="rounded-full bg-base-100 px-2 py-0.5">
                    {(t("js.email.groups") !== "js.email.groups" ? t("js.email.groups") : "Groups") +
                      `: ${selectedGroupsCount}`}
                  </span>
                  <span className="rounded-full bg-base-100 px-2 py-0.5">
                    {(t("js.event.emailInfo.additionalContacts") !== "js.event.emailInfo.additionalContacts"
                      ? t("js.event.emailInfo.additionalContacts")
                      : "Additional contacts") + `: ${selectedContactsCount}`}
                  </span>
                  <span className="rounded-full bg-base-100 px-2 py-0.5">
                    {(t("js.event.emailInfo.extraEmails") !== "js.event.emailInfo.extraEmails"
                      ? t("js.event.emailInfo.extraEmails")
                      : "Extra emails") + `: ${selectedManualCount}`}
                  </span>
                  <span className="rounded-full bg-base-100 px-2 py-0.5 font-medium">
                    {(t("js.event.emailInfo.recipientCount") !== "js.event.emailInfo.recipientCount"
                      ? t("js.event.emailInfo.recipientCount")
                      : "{count} recipients selected"
                    ).replace("{count}", String(totalRecipientsCount))}
                  </span>
                </div>

                <div>
                  <label className="mb-1 block text-xs font-medium text-base-content/60">
                    {t("js.email.groups") !== "js.email.groups" ? t("js.email.groups") : "Groups"}
                  </label>
                  <MultiSelect
                    options={groupOptions}
                    selected={selectedGroupIds}
                    onChange={setSelectedGroupIds}
                    placeholder={selectLabel}
                    showChips={false}
                    labelSelect={
                      t("js.email.selectGroups") !== "js.email.selectGroups"
                        ? t("js.email.selectGroups")
                        : "Select groups"
                    }
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
                    options={contactOptions}
                    selected={selectedAdditionalContactIds}
                    onChange={setSelectedAdditionalContactIds}
                    placeholder={selectLabel}
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
                    {t("js.event.emailInfo.extraEmails") !== "js.event.emailInfo.extraEmails"
                      ? t("js.event.emailInfo.extraEmails")
                      : "Extra emails"}
                  </label>
                  <div className="input input-bordered w-full min-h-[3rem] h-auto flex flex-wrap items-center gap-2 py-2">
                    {manualEmails.map((email) => (
                      <span
                        key={email}
                        className={`inline-flex items-center gap-2 rounded-full border px-2 py-1 text-xs ${
                          isValidEmailSyntax(email) ? "border-base-300" : "border-error text-error"
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
                        t("js.event.emailInfo.extraEmailsPlaceholder") !== "js.event.emailInfo.extraEmailsPlaceholder"
                          ? t("js.event.emailInfo.extraEmailsPlaceholder")
                          : "name@example.com, name2@example.com"
                      }
                    />
                  </div>
                  {invalidManualCount > 0 && (
                    <p className="mt-2 text-xs text-error">
                      {(t("js.event.emailInfo.invalidEmailCount") !== "js.event.emailInfo.invalidEmailCount"
                        ? t("js.event.emailInfo.invalidEmailCount")
                        : "{count} invalid email(s)"
                      ).replace("{count}", String(invalidManualCount))}
                    </p>
                  )}
                </div>
              </section>

              <section className="space-y-4 rounded-lg border border-base-300 bg-base-100 p-3">
                <h3 className="text-sm font-semibold text-base-content">
                  {t("js.event.emailInfo.message") !== "js.event.emailInfo.message"
                    ? t("js.event.emailInfo.message")
                    : "Message"}
                </h3>
                <div>
                  <label className="mb-1 block text-xs font-medium text-base-content/60">
                    {t("js.event.emailInfo.subject") !== "js.event.emailInfo.subject"
                      ? t("js.event.emailInfo.subject")
                      : "Subject"}
                  </label>
                  <div className="input input-sm input-bordered w-full flex items-center gap-2">
                    <span className="text-base-content/60 whitespace-nowrap">{subjectPrefix || "BNote"}</span>
                    <span className="text-base-content/40">-</span>
                    <input
                      type="text"
                      className="flex-1 border-0 bg-transparent p-0 text-sm outline-none"
                      value={subject}
                      onChange={(event) => setSubject(event.target.value)}
                      placeholder={
                        t("js.email.subjectRestPlaceholder") !== "js.email.subjectRestPlaceholder"
                          ? t("js.email.subjectRestPlaceholder")
                          : "Your subject"
                      }
                    />
                  </div>
                </div>
                <div>
                  <label className="mb-1 block text-xs font-medium text-base-content/60">
                    {t("js.event.emailInfo.message") !== "js.event.emailInfo.message"
                      ? t("js.event.emailInfo.message")
                      : "Message"}
                  </label>
                  <NotesEditor
                    value={body}
                    onChange={setBody}
                    placeholder={
                      t("js.event.emailInfo.messagePlaceholder") !== "js.event.emailInfo.messagePlaceholder"
                        ? t("js.event.emailInfo.messagePlaceholder")
                        : "Type or paste content..."
                    }
                    id="email-module-editor"
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
              {previewError && (
                <div className="rounded-lg border border-error bg-error/15 px-3 py-2 text-sm text-error">
                  {previewError}
                </div>
              )}
              {!previewLoading && !previewError && previewHtml && (
                <div className="overflow-hidden rounded-lg border border-base-300 bg-base-100">
                  <div className="border-b border-base-300 bg-base-200/40 px-4 py-3">
                    <div className="text-xs text-base-content/70">
                      <div>
                        <span className="font-medium">
                          {t("js.event.emailInfo.previewFrom") !== "js.event.emailInfo.previewFrom"
                            ? t("js.event.emailInfo.previewFrom")
                            : "From"}
                          :
                        </span>{" "}
                        {fromEmail || "-"}
                      </div>
                      <div>
                        <span className="font-medium">
                          {t("js.event.emailInfo.previewTo") !== "js.event.emailInfo.previewTo"
                            ? t("js.event.emailInfo.previewTo")
                            : "To"}
                          :
                        </span>{" "}
                        {toEmail || "-"}
                      </div>
                      <div>
                        <span className="font-medium">
                          {t("js.event.emailInfo.previewBcc") !== "js.event.emailInfo.previewBcc"
                            ? t("js.event.emailInfo.previewBcc")
                            : "BCC"}
                          :
                        </span>{" "}
                        {previewBccEmails.length > 0 ? previewBccEmails.join(", ") : "-"}
                      </div>
                      <div>
                        <span className="font-medium">
                          {t("js.event.emailInfo.subject") !== "js.event.emailInfo.subject"
                            ? t("js.event.emailInfo.subject")
                            : "Subject"}
                          :
                        </span>{" "}
                        {composedSubject || "-"}
                      </div>
                    </div>
                  </div>
                  <iframe
                    title={
                      t("js.event.emailInfo.previewFrameTitle") !== "js.event.emailInfo.previewFrameTitle"
                        ? t("js.event.emailInfo.previewFrameTitle")
                        : "Email preview"
                    }
                    srcDoc={previewHtml}
                    className="h-[60vh] w-full bg-base-100"
                  />
                </div>
              )}
            </div>
          )}
        </div>
      )}
    </PageContent>
  );
}
