/**
 * Configure zxcvbn-ts for current UI language (de vs en fallback for es/fr).
 */

import { zxcvbnOptions } from "@zxcvbn-ts/core";
import * as zxcvbnCommonPackage from "@zxcvbn-ts/language-common";
import * as zxcvbnDePackage from "@zxcvbn-ts/language-de";
import * as zxcvbnEnPackage from "@zxcvbn-ts/language-en";

let lastLang = "";

export function ensureZxcvbnOptions(lang: string): void {
  const useDe = lang === "de";
  const key = useDe ? "de" : "en";
  if (key === lastLang) return;
  lastLang = key;
  const dictPkg = useDe ? zxcvbnDePackage : zxcvbnEnPackage;
  zxcvbnOptions.setOptions({
    dictionary: {
      ...zxcvbnCommonPackage.dictionary,
      ...dictPkg.dictionary,
    },
    graphs: zxcvbnCommonPackage.adjacencyGraphs,
    translations: dictPkg.translations,
  });
}
