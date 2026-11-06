#!/usr/bin/env python3
"""Replace the company phone number everywhere in the project.

Usage (run from the project folder):
    python3 replace_phone.py            # preview only: shows what WOULD change
    python3 replace_phone.py --apply    # make the changes

Edit the two numbers below if they ever change again.
Windows: use  py replace_phone.py  instead of  python3 replace_phone.py
"""
import os, sys

# (old text, new text) — every way the number is written on the site
REPLACEMENTS = [
    ("+234 810 370 2504", "+234 813 964 5973"),   # display format
    ("+2348103702504",    "+2348139645973"),      # tel: links
    ("08103702504",       "08139645973"),          # local format
    ("234810372504",      "2348139645973"),        # variant without the extra 0
]
SKIP_DIRS = {".git", "node_modules", "data", "__pycache__"}
EXTS = {".html", ".php", ".js", ".css", ".py", ".md", ".txt", ".json", ".xml", ".toml"}

apply = "--apply" in sys.argv
total = 0
for root, dirs, files in os.walk("."):
    dirs[:] = [d for d in dirs if d not in SKIP_DIRS]
    for f in files:
        if os.path.splitext(f)[1].lower() not in EXTS or f == os.path.basename(__file__):
            continue
        p = os.path.join(root, f)
        try:
            s = open(p, encoding="utf-8").read()
        except (UnicodeDecodeError, OSError):
            continue
        n, new = 0, s
        for old, rep in REPLACEMENTS:
            c = new.count(old)
            if c:
                n += c
                new = new.replace(old, rep)
        if n:
            total += n
            print(f"{'UPDATED' if apply else 'would change'}  {p}  ({n} place{'s' if n != 1 else ''})")
            if apply:
                open(p, "w", encoding="utf-8", newline="").write(new)
print(f"\n{total} occurrence(s) {'replaced' if apply else 'found'}.")
if not apply and total:
    print("Run again with --apply to make the changes.")
if apply:
    print("Reminder: also update Admin > Settings > Phone (stored in the database).")
