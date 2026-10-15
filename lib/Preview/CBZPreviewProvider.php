<?php

declare(strict_types=1);

namespace OCA\Comicpreviews\Preview;

use Psr\Log\LoggerInterface;

final class CBZPreviewProvider extends ComicPreviewProvider {
	public function __construct(LoggerInterface $logger) {
		parent::__construct($logger, new CBZArchiveReader());
	}

	public function getMimeType(): string {
		return '/^(?:application\/x-cbz|application\/comicbook\+zip|application\/vnd\.comicbook\+zip)$/';
	}
}
