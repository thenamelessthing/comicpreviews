<?php

declare(strict_types=1);

namespace OCA\Comicpreviews\Preview;

use OCP\Files\FileInfo;
use Psr\Log\LoggerInterface;

final class CBRPreviewProvider extends ComicPreviewProvider {
	public function __construct(LoggerInterface $logger) {
		parent::__construct($logger, new CBRArchiveReader());
	}

	public function getMimeType(): string {
		return '/^(?:application\/x-cbr|application\/comicbook\+rar|application\/vnd\.comicbook\+rar)$/';
	}

	public function isAvailable(FileInfo $file): bool {
		return extension_loaded('rar');
	}
}
