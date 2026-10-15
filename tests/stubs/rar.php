<?php

declare(strict_types=1);

/** @implements IteratorAggregate<int, RarEntry> */
final class RarArchive implements IteratorAggregate {
	public static function open(string $filename, ?string $password = null, ?callable $volumeCallback = null): self|false {
		throw new LogicException('Psalm stub.');
	}

	public function isSolid(): bool {
		throw new LogicException('Psalm stub.');
	}

	public function isBroken(): bool {
		throw new LogicException('Psalm stub.');
	}

	public function close(): bool {
		throw new LogicException('Psalm stub.');
	}

	/** @return Traversable<int, RarEntry> */
	public function getIterator(): Traversable {
		throw new LogicException('Psalm stub.');
	}
}

final class RarEntry {
	public function getName(): string {
		throw new LogicException('Psalm stub.');
	}

	public function getUnpackedSize(): int {
		throw new LogicException('Psalm stub.');
	}

	public function getPackedSize(): int {
		throw new LogicException('Psalm stub.');
	}

	/** @return resource|false */
	public function getStream(?string $password = null) {
		throw new LogicException('Psalm stub.');
	}

	public function isDirectory(): bool {
		throw new LogicException('Psalm stub.');
	}

	public function isEncrypted(): bool {
		throw new LogicException('Psalm stub.');
	}

	public function getRedirType(): ?int {
		throw new LogicException('Psalm stub.');
	}
}
