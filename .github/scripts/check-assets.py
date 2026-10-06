#!/usr/bin/env python3
"""Static checks for CSS/JS/PHP asset references.

Fails (exit 1) on:
  * CSS files with unbalanced braces.
  * CSS url()/@import targets that point to a local file that does not exist
    (custom-property values are reported as warnings: they resolve where used).
  * PHP string literals naming a local asset/template path that exists in
    neither this repository nor the optional paired repository, unless the
    reference is guarded by file_exists()/is_readable() within a few lines.

Usage: check-assets.py <repo-root> [<paired-repo-root>]
"""
import os
import re
import sys

ROOT = os.path.abspath(sys.argv[1] if len(sys.argv) > 1 else ".")
PAIRED = os.path.abspath(sys.argv[2]) if len(sys.argv) > 2 and os.path.isdir(sys.argv[2]) else None
SKIP_DIRS = {".git", "node_modules", "vendor"}

errors = []
warnings = []


def walk(ext):
    for base, dirs, files in os.walk(ROOT):
        dirs[:] = [d for d in dirs if d not in SKIP_DIRS]
        for name in files:
            if name.endswith(ext):
                yield os.path.join(base, name)


def rel(path):
    return os.path.relpath(path, ROOT)


def strip_css(text):
    text = re.sub(r"/\*.*?\*/", "", text, flags=re.S)
    return re.sub(r'"(?:\\.|[^"\\])*"|\'(?:\\.|[^\'\\])*\'', '""', text)


for css in walk(".css"):
    text = open(css, encoding="utf-8", errors="replace").read()
    stripped = strip_css(text)
    if stripped.count("{") != stripped.count("}"):
        errors.append(f"{rel(css)}: unbalanced braces ({stripped.count('{')} '{{' vs {stripped.count('}')} '}}')")
    # Blank out comments but keep their newlines so reported line numbers stay accurate.
    no_comments = re.sub(r"/\*.*?\*/", lambda m: "\n" * m.group(0).count("\n"), text, flags=re.S)
    for line_no, line in enumerate(no_comments.splitlines(), 1):
        for match in re.finditer(r"(?:url\(\s*|@import\s+)['\"]?([^'\")\s;]+)", line):
            target = match.group(1)
            if target.startswith(("data:", "http:", "https:", "//", "#", "var(")):
                continue
            path = os.path.normpath(os.path.join(os.path.dirname(css), target.split("?")[0].split("#")[0]))
            if os.path.exists(path):
                continue
            message = f"{rel(css)}:{line_no}: url() target not found: {target}"
            if re.match(r"\s*--[\w-]+\s*:", line):
                warnings.append(message + " (custom property; resolved where used)")
            else:
                errors.append(message)

ASSET_RE = re.compile(
    r"['\"]/?((?:assets|inc|includes|template-parts|templates|system-templates|woocommerce)/"
    r"[A-Za-z0-9_./-]+\.(?:css|js|php|svg|png|jpe?g|webp|gif|woff2?|html|xlsx|m4a|mp3|json|txt))['\"]"
)
GUARD_RE = re.compile(r"file_exists|is_readable|is_file")

for php in walk(".php"):
    lines = open(php, encoding="utf-8", errors="replace").read().splitlines()
    for idx, line in enumerate(lines):
        for match in ASSET_RE.finditer(line):
            target = match.group(1)
            roots = [ROOT] + ([PAIRED] if PAIRED else [])
            if any(os.path.exists(os.path.join(r, target)) for r in roots):
                continue
            window = "\n".join(lines[max(0, idx - 3): idx + 4])
            message = f"{rel(php)}:{idx + 1}: referenced path not found: {target}"
            if GUARD_RE.search(window):
                warnings.append(message + " (guarded by an existence check)")
            elif re.match(r"\s*(?:(?:public|private|protected)\s+)?const\s+\w+\s*=", line):
                warnings.append(message + " (constant declaration; verify its users)")
            elif PAIRED is None and not os.path.exists(os.path.join(ROOT, target.split("/")[0])):
                warnings.append(message + " (may belong to the paired plugin/theme)")
            else:
                errors.append(message)

for message in warnings:
    print(f"::warning::{message}")
for message in errors:
    print(f"::error::{message}")
print(f"check-assets: {len(errors)} error(s), {len(warnings)} warning(s)")
sys.exit(1 if errors else 0)
