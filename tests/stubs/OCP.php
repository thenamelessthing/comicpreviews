<?php

declare(strict_types=1);

namespace Psr\Log {
	interface LoggerInterface {
		/** @param array<string, mixed> $context */
		public function debug(string|\Stringable $message, array $context = []): void;

		/** @param array<string, mixed> $context */
		public function warning(string|\Stringable $message, array $context = []): void;
	}
}

namespace OCP\AppFramework {
	class App {
		/** @param array<string, mixed> $urlParams */
		public function __construct(string $appName, array $urlParams = []) {
		}
	}
}

namespace OCP\AppFramework\Bootstrap {
	interface IBootContext {
	}

	interface IRegistrationContext {
		/** @param class-string $class */
		public function registerPreviewProvider(string $class, string $mimeTypeRegex): void;
	}

	interface IBootstrap {
		public function register(IRegistrationContext $context): void;

		public function boot(IBootContext $context): void;
	}
}

namespace OCP\Files\Storage {
	interface IStorage {
		public function isLocal(): bool;

		public function getLocalFile(string $path): string|false;
	}
}

namespace OCP\Files {
	use OCP\Files\Storage\IStorage;

	interface FileInfo {
		public function getName(): string;
	}

	interface File extends FileInfo {
		public function getInternalPath(): string;

		public function getPath(): string;

		public function getSize(): int|float;

		public function isEncrypted(): bool;

		public function getStorage(): IStorage;

		/** @return resource|false */
		public function fopen(string $mode);
	}
}

namespace OCP {
	interface IImage {
		public function loadFromData(string $data): \GdImage|false;

		public function valid(): bool;

		public function scaleDownToFit(int $maxWidth, int $maxHeight): bool;
	}

	class Image implements IImage {
		public function loadFromData(string $data): \GdImage|false {
			return false;
		}

		public function valid(): bool {
			return false;
		}

		public function scaleDownToFit(int $maxWidth, int $maxHeight): bool {
			return false;
		}
	}

	interface ITempManager {
		public function getTemporaryFile(string $postfix = ''): string|false;
	}

	final class Server {
		public static function get(string $class): object {
			throw new \LogicException('Tests must override service-backed methods.');
		}
	}
}

namespace OCP\Preview {
	use OCP\Files\File;
	use OCP\Files\FileInfo;
	use OCP\IImage;

	interface IProviderV2 {
		public function getMimeType(): string;

		public function isAvailable(FileInfo $file): bool;

		public function getThumbnail(File $file, int $maxX, int $maxY): ?IImage;
	}
}
