# Comic Previews

A bounded Nextcloud preview provider for **CBZ** archives and, when the optional PHP RAR extension is installed, **CBR** archives.

The provider generates the normal Nextcloud file thumbnail from the first naturally ordered supported image in an archive. It does not schedule previews itself; Nextcloud or a preview-generation app remains responsible for invoking providers.

## Status

This fork replaces the original external `/usr/bin/unrar` integration with direct `ext-rar` streaming and adds adversarial tests and explicit resource limits. The original implementation was reported to work against 287 personal-library archives (285 CBR and 2 CBZ); representative RAR4 and RAR5 fixtures also succeeded here when tested with proprietary UnRAR. See [the implementation audit](docs/ORIGINAL_IMPLEMENTATION_AUDIT.md) for the portability and boundary failures found around that happy path.

## Requirements

- Nextcloud 34 or newer
- PHP 8.2 through 8.5
- PHP `zip` extension for CBZ previews
- Optional PHP `rar` extension for CBR previews
- A Nextcloud-supported image backend such as GD or Imagick

No `unrar`, `7z`, Node.js, JavaScript runtime, or subprocess execution is used by the app.

## Installation

The app is not packaged for the Nextcloud App Store. For a manual installation:

1. Copy the repository into the Nextcloud `apps/` directory as `comicpreviews`.
2. Install and enable PHP `ext-zip`.
3. Install and enable PECL `ext-rar` on every PHP runtime that should generate CBR previews.
4. Enable the app:

   ```bash
   sudo -u www-data php /var/www/nextcloud/occ app:enable comicpreviews
   ```

5. Generate a preview for a test comic, or let the existing preview workflow process it:

   ```bash
   sudo -u www-data php /var/www/nextcloud/occ preview:generate "/username/files/path/to/comic.cbz"
   ```

Adjust the Nextcloud path, PHP configuration, PHP user, and file path for the deployment.

## Supported formats and selection

- **CBZ** through `ZipArchive`.
- **CBR** through optional `ext-rar` for RAR4 and RAR5.

JPEG, PNG, GIF, and WebP entries are candidates regardless of extension case. The encoded bytes determine the actual MIME type. Paths are normalized and compared using deterministic case-insensitive natural ordering with a bytewise tie-break, so `page-2.png` precedes `page-10.png`.

Directories, hidden components, `__MACOSX`, absolute paths, drive paths, traversal components, NUL bytes, excessive nesting, unsupported image extensions, encrypted entries, and redirected RAR entries are rejected or ignored. If the selected first image is malformed or unsafe, preview generation fails closed rather than silently substituting a later page.

## Resource and archive boundaries

Already-local archives are opened in place. Only encrypted or non-local Nextcloud files are copied to a temporary file, with a 1 GiB plus one-byte sentinel bound.

| Boundary | Default |
|---|---:|
| Remote or encrypted temporary copy | 1 GiB |
| Archive entries | 10,000 |
| ZIP central directory | 16 MiB |
| Entry path | 1,024 bytes and 16 components |
| Selected encoded image | 32 MiB |
| Compression ratio | 200:1 for entries of at least 1 MiB |
| Solid-RAR prefix through the selected entry | 1 GiB declared unpacked |
| Image dimensions | 8,192 pixels per axis |
| Decoded image pixels | 16,777,216 |

ZIP metadata is checked before libzip opens the archive. RAR4 and RAR5 main headers are checked before UnRAR opens the archive so multi-volume and header-encrypted files cannot trigger implicit sibling-volume access. The selected ZIP member is opened by numeric index, and the selected RAR member is retained by object identity. Entry streams, archive handles, and temporary files close on failure paths.

The implementation scans bounded metadata and streams one selected entry rather than extracting an archive tree. See [the performance model and measured small-fixture results](docs/PERFORMANCE.md).

## WebAssembly fallback

A server-side WebAssembly fallback is deliberately not bundled. The available UnRAR WebAssembly builds target browser or Node.js JavaScript glue and an Emscripten virtual filesystem; PHP cannot invoke them directly. Mature PHP Wasm integration requires another native runtime extension, while the surveyed pure-PHP runtimes are new, incomplete for this workload, restricted to PHP 8.5, or orders of magnitude less appropriate for bounded preview generation.

Adding such a fallback would therefore replace one optional native extension with a larger and less portable runtime stack while duplicating archive bytes into a Wasm filesystem. See [the detailed evaluation](docs/WASM_FALLBACK_EVALUATION.md).

## Testing

```bash
composer install
vendor/bin/phpunit --configuration tests/phpunit.xml
```

The no-extension run verifies that CBR support fails closed and is not registered. Run the same suite with `ext-rar` loaded to exercise real RAR4, RAR5, solid-RAR, ratio, size, and prefix-limit cases.

The committed fixtures contain only repository-owned test images or explicitly attributed third-party test material. They are not copyrighted comics.

## Security model

Archive contents are untrusted input. This implementation does not spawn archive utilities, extract archive trees, write selected entries to disk, or parse `ComicInfo.xml`. Unsupported, encrypted, multi-volume, malformed, inconsistent, or over-limit archives retain Nextcloud's generic file icon and produce a warning in the Nextcloud log.

## Contributing

Include the Nextcloud version, PHP version, archive format, `ext-rar` version where relevant, and sanitized log messages in reports. Do not attach copyrighted comic archives.

## License

This fork incorporates code from the AGPL-licensed `devnoname120/epubviewer` preview pipeline and is therefore distributed under the GNU Affero General Public License v3.0 or later. See [LICENSE](LICENSE) and [NOTICE.md](NOTICE.md).
