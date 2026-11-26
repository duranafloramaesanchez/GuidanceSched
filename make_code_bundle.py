#!/usr/bin/env python3
"""
MIS 215 Final Term Project
Builds the single-file source code bundle required for submission.

HOW TO USE
    1. Save this file in the top folder of your project (the folder that holds
       your README and your docker compose file).
    2. Open a terminal in that folder and run:

           python3 make_code_bundle.py "Dela Cruz"

       On Windows, use:  python make_code_bundle.py "Dela Cruz"

    3. The script writes MIS215_TP_DelaCruz_CodeBundle.txt beside itself.
       Open it, check that your files are there, then upload it.

WHAT IT DOES
    It walks your project folder, skips dependency and build folders, skips
    binaries and secrets, and writes every remaining source file into one
    plain text file with a clear header before each file.

Nothing is uploaded or sent anywhere. The script only reads and writes on
your own computer.
"""

import os
import sys
from datetime import datetime

# Folders that are never included.
SKIP_DIRS = {
    ".git", ".svn", ".hg", ".idea", ".vscode", ".pytest_cache", ".mypy_cache",
    "node_modules", "vendor", "bower_components",
    "venv", ".venv", "env", "__pycache__", ".tox",
    "dist", "build", "out", "target", "bin", "obj",
    "coverage", ".next", ".nuxt", ".cache", "tmp", "temp",
    "migrations_backup", "uploads", "media", "static_collected",
}

# File names that are never included, because they hold secrets.
SKIP_FILES = {
    ".env", ".env.local", ".env.production", ".env.development",
    "id_rsa", "id_ed25519", "credentials.json", "secrets.json",
}

# Extensions that are included. Add your own if your stack is not listed.
KEEP_EXT = {
    ".py", ".js", ".mjs", ".cjs", ".ts", ".tsx", ".jsx", ".vue", ".svelte",
    ".php", ".java", ".kt", ".go", ".rb", ".cs", ".rs",
    ".html", ".htm", ".css", ".scss", ".sass",
    ".sql", ".sh", ".bash", ".ps1",
    ".json", ".yml", ".yaml", ".toml", ".ini", ".cfg", ".conf", ".env.example",
    ".md", ".txt", ".dockerfile", ".gitignore",
}

# Files kept regardless of extension.
KEEP_NAMES = {
    "Dockerfile", "docker-compose.yml", "docker-compose.yaml", "Makefile",
    "README", "README.md", "LICENSE", ".env.example", "requirements.txt",
    "package.json", "composer.json", "pom.xml", "build.gradle", "go.mod",
}

MAX_FILE_BYTES = 400_000          # a single source file larger than this is noted, not inlined
MAX_BUNDLE_BYTES = 500_000        # warn if the finished bundle goes past this


def surname_slug(raw):
    return "".join(ch for ch in raw if ch.isalnum())


def wanted(path, name):
    if name in SKIP_FILES:
        return False
    if name in KEEP_NAMES:
        return True
    ext = os.path.splitext(name)[1].lower()
    return ext in KEEP_EXT


def main():
    if len(sys.argv) < 2:
        print('Usage: python3 make_code_bundle.py "Your Surname"')
        sys.exit(1)

    surname = surname_slug(sys.argv[1])
    root = os.path.abspath(os.path.dirname(__file__))
    out_name = f"MIS215_TP_{surname}_CodeBundle.txt"
    out_path = os.path.join(root, out_name)

    collected = []
    for folder, dirs, files in os.walk(root):
        dirs[:] = sorted(d for d in dirs if d not in SKIP_DIRS and not d.startswith("."))
        for name in sorted(files):
            full = os.path.join(folder, name)
            if os.path.abspath(full) == out_path or name == os.path.basename(__file__):
                continue
            if wanted(full, name):
                collected.append(full)

    if not collected:
        print("No source files found. Run this from your project's top folder.")
        sys.exit(1)

    with open(out_path, "w", encoding="utf-8", errors="replace") as out:
        out.write("=" * 78 + "\n")
        out.write("MIS 215 CLIENT-SERVER COMPUTING\n")
        out.write("Final Term Project: source code bundle\n")
        out.write(f"Surname: {surname}\n")
        out.write(f"Generated: {datetime.now().strftime('%Y-%m-%d %H:%M')}\n")
        out.write(f"Files included: {len(collected)}\n")
        out.write("=" * 78 + "\n\n")

        out.write("FILE INDEX\n")
        for full in collected:
            out.write("  " + os.path.relpath(full, root).replace("\\", "/") + "\n")
        out.write("\n")

        for full in collected:
            rel = os.path.relpath(full, root).replace("\\", "/")
            size = os.path.getsize(full)
            out.write("\n" + "=" * 78 + "\n")
            out.write(f"FILE: {rel}\n")
            out.write(f"SIZE: {size} bytes\n")
            out.write("=" * 78 + "\n")
            if size > MAX_FILE_BYTES:
                out.write("[This file was too large to include in full. "
                          "See it in the repository.]\n")
                continue
            try:
                with open(full, "r", encoding="utf-8", errors="replace") as f:
                    out.write(f.read())
            except OSError as exc:
                out.write(f"[Could not read this file: {exc}]\n")
            out.write("\n")

    total = os.path.getsize(out_path)
    print(f"Wrote {out_name}")
    print(f"  files included: {len(collected)}")
    print(f"  bundle size:    {total / 1_000_000:.2f} MB")
    if total > MAX_BUNDLE_BYTES:
        print("  NOTE: this bundle is larger than the 500 KB target. Check the FILE")
        print("        INDEX for data dumps, log files or generated code, remove them")
        print("        from your project folder, then run this again.")
    print("Open the file and check it before you upload it.")


if __name__ == "__main__":
    main()
