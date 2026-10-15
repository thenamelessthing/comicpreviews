# Performance model and microbenchmarks

The provider performs one metadata scan to identify the naturally first candidate and then streams only that selected entry. It does not extract an archive tree. Already-local archives are opened in place; remote or encrypted Nextcloud files require one bounded sequential copy to temporary storage.

CBZ work is proportional to the bounded central-directory entry count plus the selected encoded image size. CBR metadata iteration is likewise bounded by entry count. Extracting a selected entry from a solid RAR may require native UnRAR to process preceding compressed data, so the provider also bounds the declared unpacked prefix through that entry.

A small-fixture microbenchmark was run on 15 October 2026 on macOS arm64 with PHP 8.5.9, warm local filesystem cache, and `ext-rar` 4.3.2-dev. It measured the archive-reader call only, excluding Nextcloud dispatch and raster scaling:

| Fixture | Iterations | Mean per call |
|---|---:|---:|
| two-entry CBZ | 1,000 | 0.0430 ms |
| one-entry RAR4 | 500 | 0.2306 ms |
| two-entry solid RAR5 | 200 | 0.2349 ms |

These values are not deployment latency guarantees; archive size, compression method, storage, page location, and image decoding dominate real requests. They establish that the reader itself has no subprocess startup cost and that representative metadata selection plus one small extraction is sub-millisecond on the measured machine.

The important operational bounds are deterministic rather than benchmark-dependent: at most 10,000 entries, 16 MiB of ZIP central-directory metadata, 32 MiB of selected encoded image data, 16,777,216 decoded pixels, and 1 GiB of declared solid-RAR prefix or temporary-copy traffic.
