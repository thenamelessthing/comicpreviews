# Changelog

## 0.2.0 — 2026-10-15

- Replace the hardcoded `/usr/bin/unrar` subprocess with optional PHP `ext-rar` streaming.
- Register CBR previews only when `ext-rar` is loaded; CBZ previews remain available through `ext-zip`.
- Add deterministic natural cover selection for JPEG, PNG, GIF, and WebP entries.
- Add bounded temporary copies, archive metadata, path, selected-entry, compression-ratio, solid-RAR-prefix, dimension, and decoded-pixel checks.
- Reject encrypted entries, redirected RAR entries, multi-volume RAR archives, and header-encrypted RAR archives.
- Open ZIP entries by numeric identity and retain selected RAR entry objects.
- Add real RAR4/RAR5 fixtures, adversarial unit tests, PHP 8.2–8.5 CI, coding-standard checks, and Psalm.
- Document the original implementation audit and the server-side WebAssembly fallback evaluation.

## 0.1.0

- Initial CBZ preview provider with external `/usr/bin/unrar` CBR support.
