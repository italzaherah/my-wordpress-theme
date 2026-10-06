#!/usr/bin/env python3
"""Run actual CSS checker against malformed and valid standalone stylesheets."""
import contextlib
import io
import pathlib
import runpy
import sys
import tempfile

if len(sys.argv) != 2:
    raise SystemExit("Usage: check-css-regressions.py <check-css.py>")
checker = pathlib.Path(sys.argv[1]).resolve()
cases = [
    ("malformed URL value", '.x { background: url(foo"bar); }', True),
    ("unclosed string value", '.x { color: "unterminated\n; }', True),
    ("unclosed function before rule end", '.x { transform: translate(1px; }', True),
    ("nested media/supports", '@media (min-width:40rem) { @supports (display:grid) { .x { display:grid; color:rgb(10,20,30); } } }', False),
    ("custom property with block/function tokens", '.x { --layout: { padding: 4px; gap: 10px; }; --tone: rgb(10,20,30); color:var(--tone); background:linear-gradient(red,var(--tone)); }', False),
    ("font-face data URL and nested selector", '@font-face { font-family:"x"; src:url(data:font/woff2;base64,d09GMg==) format("woff2"); } .x { color:red; & > .y { color:rgb(10,20,30); } }', False),
]
passed = 0
for label, css, invalid in cases:
    with tempfile.TemporaryDirectory(prefix="alz-css-regression-") as temp:
        (pathlib.Path(temp) / "fixture.css").write_text(css, encoding="utf-8")
        output = io.StringIO()
        original_argv = sys.argv
        sys.argv = [str(checker), temp]
        try:
            with contextlib.redirect_stdout(output):
                try:
                    runpy.run_path(str(checker), run_name="__main__")
                    status = 0
                except SystemExit as done:
                    status = int(done.code or 0)
        finally:
            sys.argv = original_argv
        ok = bool(status) == invalid
        passed += ok
        print(("Passed" if ok else "Failed") + ": " + label)
        if not ok:
            print(output.getvalue().rstrip())
print(f"Assertions: {passed} passed, {len(cases)-passed} failed")
raise SystemExit(0 if passed == len(cases) else 1)