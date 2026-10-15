# Original implementation audit

This audit covers `thenamelessthing/comicpreviews` commit `0914e264039e182c500ecf76a016083b50c88a6e`, the implementation linked from `devnoname120/epubviewer` issue 47 on 15 October 2026.

## Compatibility evidence

The author reports testing against a personal library of 287 archives: 285 CBR and 2 CBZ. That is useful evidence that the basic provider wiring, filename selection, and proprietary UnRAR command line work against a varied real-world corpus.

Independent representative checks reproduced the happy path:

- a two-page CBZ selected `page-2.gif` before `page-10.png`;
- a RAR4 comic generated from a repository-owned PNG produced the expected PNG when `/usr/bin/unrar` was proprietary UnRAR 6.2.6;
- a solid RAR5 fixture likewise produced the expected PNG with proprietary UnRAR 6.2.6.

## Portability findings

The original provider hardcodes `/usr/bin/unrar` and checks only that the path is executable. This creates materially different behavior across systems:

- a loaded PHP `ext-rar` is ignored when `/usr/bin/unrar` is absent;
- Debian `unrar-free` satisfies the executable check but failed to extract the representative RAR4 and RAR5 image fixtures with the command-line contract used by the provider;
- distributions and containers commonly place proprietary UnRAR elsewhere or omit it entirely.

The fork replaces this deployment-specific command with direct `ext-rar` iteration and streaming.

## Reliability and boundary findings

The original CBR backend has no subprocess deadline. Replacing `/usr/bin/unrar` with a test executable that never returns caused preview generation to remain blocked beyond a two-second external watchdog. A malformed archive or stuck utility can therefore occupy a PHP worker indefinitely.

Other relevant findings from code inspection and adversarial fixtures:

- every archive is copied to a temporary file, including already-local Nextcloud files;
- all archives above 1 GiB are rejected even when already local and readable by path;
- CBZ entries are accumulated and sorted as a complete array, without an entry-count or central-directory bound;
- CBR listing output is bounded, but archive metadata, compression ratio, solid-prefix work, and decoded image pixels are not;
- selected ZIP entries are reopened by filename rather than numeric identity; a duplicate-name archive with an invalid first `cover.png` and valid second `cover.png` returned no preview because both lookups resolved ambiguously;
- malformed earlier candidates are skipped in favor of later pages, potentially repeating decompression and hiding corruption in the nominal cover;
- generic `application/zip` is registered, broadening the provider beyond comic archives;
- exceptions are swallowed without structured logging;
- there are no automated tests or CI checks in the original commit.

## Changes in this fork

The fork keeps the useful narrow product concept while replacing the archive pipeline:

- direct `ZipArchive` and optional `ext-rar`, with no archive subprocesses;
- local-file passthrough and bounded remote/encrypted copying;
- pre-open ZIP and RAR metadata checks;
- deterministic candidate identity by ZIP index or retained RAR entry object;
- entry-count, metadata, path, encoded-size, compression-ratio, solid-prefix, dimension, and pixel limits;
- exact-size bounded stream reads and content-based image validation;
- conditional CBR registration when `ext-rar` is loaded;
- automated unit, boundary, real RAR4/RAR5, and provider-lifecycle tests.
