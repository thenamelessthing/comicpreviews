# Server-side WebAssembly RAR fallback evaluation

Evaluated on 15 October 2026 for a Nextcloud preview provider running inside PHP-FPM or the PHP CLI.

## Required properties

A useful fallback would need to:

- execute inside the PHP process without a Node.js or shell subprocess;
- support RAR4, RAR5, solid archives, and selective member extraction;
- accept bounded input and expose bounded output without extracting an archive tree;
- run on PHP 8.2 through 8.5 and common Linux `amd64`/`arm64` deployments;
- provide predictable memory, execution-time, and cleanup controls;
- have a maintainable license and active security posture.

## UnRAR WebAssembly modules

### `YuJianrong/node-unrar.js`

This is an Emscripten build of upstream UnRAR with a JavaScript adaptation layer. It supports selective extraction and has meaningful usage history, but its public API requires JavaScript, typed arrays, Emscripten glue, and an in-memory virtual filesystem. Its documentation warns that archive iterators must be exhausted to avoid leaking the underlying C++ objects. It is appropriate for Node.js or browsers, not directly callable from PHP.

### `niekvlessert/unrar-wasm-2026`

This newer browser/worker-oriented build exposes list and extract-all functions through Emscripten JavaScript. It is configured for growing Wasm memory with a 2 GiB maximum and writes through an Emscripten filesystem. It does not expose the narrow selective streaming interface required here, and running it from PHP would still require a JavaScript runtime.

### `DavidKk/unrar-browser-wasm`

This project explicitly targets browsers, ESM tooling, and browser isolation headers. It reads archives into JavaScript/Wasm memory and exposes browser-centric APIs. It is not a server-side PHP runtime.

## PHP WebAssembly runtimes

### `wasmerio/wasmer-php`

Wasmer PHP is itself a native PHP extension. Its published support table remains centered on PHP 8.0 and excludes Linux and Darwin `arm64`; its documented runtime also lacks WASI support. Open issues include current build and platform failures. Requiring it would be operationally harder than requiring `ext-rar`, while still needing an Emscripten-compatible JavaScript or WASI adaptation for the available UnRAR modules.

### Pure-PHP runtimes

The surveyed pure-PHP projects are not suitable production dependencies for this workload:

- `Aneesh495/zenith` was created in October 2026, targets PHP 8.5, and has no established adoption. Its own benchmark claims roughly two million interpreted Wasm operations per second, which is not an appropriate basis for latency-sensitive native decompression of multi-megabyte solid archives.
- `ryohey/php-wasm-runtime` is centered on WAT parsing and specification tests rather than running binary Emscripten/WASI applications with the required host surface.
- `ilyajalayi/PPwasm` contains only a minimal project description and no demonstrated RAR/WASI integration.

A pure-PHP interpreter would also duplicate the archive into PHP-managed Wasm linear memory and interpret a compute-heavy decompressor, increasing both peak memory and CPU exposure relative to native `ext-rar`.

## Decision

No WebAssembly fallback is bundled.

The available RAR Wasm artifacts are browser/Node.js applications, not self-contained modules that PHP can invoke. The available PHP runtimes either add another native extension, lack platform/PHP support, or are too immature and slow for untrusted archive decompression. Combining the two stacks would add more deployment dependencies than direct `ext-rar`, duplicate archive data, and make resource enforcement less reliable.

CBZ previews remain available everywhere through required `ext-zip`. CBR previews are registered only when `ext-rar` is loaded. This is a smaller, faster, and more auditable optional-capability model. A future fallback should be reconsidered only if a maintained PHP 8.2+ Wasm runtime provides broad `amd64`/`arm64` support, fuel or epoch interruption, bounded memory, the host API required by a selective RAR decoder, and production security maintenance.

## References

- https://github.com/YuJianrong/node-unrar.js
- https://github.com/niekvlessert/unrar-wasm-2026
- https://github.com/DavidKk/unrar-browser-wasm
- https://github.com/wasmerio/wasmer-php
- https://github.com/Aneesh495/zenith
- https://github.com/ryohey/php-wasm-runtime
- https://github.com/ilyajalayi/PPwasm
