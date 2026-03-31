/**
 * BNote Next Generation - HTML sanitizer for untrusted rich text.
 */

const DISALLOWED_TAGS = new Set([
  "script",
  "style",
  "iframe",
  "object",
  "embed",
  "link",
  "meta",
  "base",
  "form",
  "input",
  "button",
  "textarea",
  "select",
]);

const URL_ATTRS = new Set(["href", "src", "xlink:href", "action", "formaction"]);

function isSafeUrl(raw: string): boolean {
  const value = raw.trim().replace(/[\u0000-\u001f\u007f\s]+/g, "").toLowerCase();
  if (!value) return true;
  if (
    value.startsWith("javascript:") ||
    value.startsWith("vbscript:") ||
    value.startsWith("data:") ||
    value.startsWith("file:")
  ) {
    return false;
  }
  return true;
}

function sanitizeWithoutDom(input: string): string {
  return input
    .replace(/<script[\s\S]*?>[\s\S]*?<\/script>/gi, "")
    .replace(/<style[\s\S]*?>[\s\S]*?<\/style>/gi, "")
    .replace(/\son[a-z]+\s*=\s*(".*?"|'.*?'|[^\s>]+)/gi, "")
    .replace(/\sstyle\s*=\s*(".*?"|'.*?'|[^\s>]+)/gi, "");
}

/**
 * Sanitize HTML before rendering into dangerouslySetInnerHTML.
 */
export function sanitizeUntrustedHtml(input: string): string {
  if (!input) return "";

  // Fallback for non-browser execution (SSR/tests).
  if (typeof window === "undefined") {
    return sanitizeWithoutDom(input);
  }

  const parser = new DOMParser();
  const doc = parser.parseFromString(input, "text/html");
  const all = doc.body.querySelectorAll("*");

  all.forEach((el) => {
    const tagName = el.tagName.toLowerCase();
    if (DISALLOWED_TAGS.has(tagName)) {
      el.remove();
      return;
    }

    for (const attrName of el.getAttributeNames()) {
      const name = attrName.toLowerCase();
      if (name.startsWith("on")) {
        el.removeAttribute(attrName);
        continue;
      }
      if (name === "style") {
        el.removeAttribute(attrName);
        continue;
      }
      if (URL_ATTRS.has(name)) {
        const rawValue = el.getAttribute(attrName) ?? "";
        if (!isSafeUrl(rawValue)) {
          el.removeAttribute(attrName);
        }
      }
    }
  });

  return doc.body.innerHTML;
}
