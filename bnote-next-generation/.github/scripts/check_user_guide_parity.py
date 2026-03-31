#!/usr/bin/env python3

from pathlib import Path
import sys


ROOT = Path(__file__).resolve().parents[2]
GUIDE = ROOT / "user-guide" / "i18n"
LANGS = ["en", "de", "es", "fr"]
CRITICAL_FILES = [
    "index.md",
    "getting-started/quick-start.md",
    "migration/whats-new-and-migration-guide.md",
    "migration/old-vs-nextgen.md",
    "troubleshooting/index.md",
]


def main() -> int:
    missing = []
    for lang in LANGS:
        lang_root = GUIDE / lang
        for relative in CRITICAL_FILES:
            target = lang_root / relative
            if not target.exists():
                missing.append(str(target.relative_to(ROOT)))

    if missing:
        print("Missing required user-guide parity files:")
        for item in missing:
            print(f"- {item}")
        return 1

    print("User-guide parity check passed.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
