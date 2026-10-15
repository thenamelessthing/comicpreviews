<?php

declare(strict_types=1);

namespace OCA\Comicpreviews\Preview;

use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\IImage;
use OCP\Image;
use OCP\ITempManager;
use OCP\Preview\IProviderV2;
use OCP\Server;
use Psr\Log\LoggerInterface;

abstract class ComicPreviewProvider implements IProviderV2 {
	/** @var list<string> */
	private array $temporaryFiles = [];

	protected function __construct(
		private LoggerInterface $logger,
		private ComicArchiveReader $archiveReader,
	) {
	}

	abstract public function getMimeType(): string;

	public function isAvailable(FileInfo $file): bool {
		return true;
	}

	public function getThumbnail(File $file, int $maxX, int $maxY): ?IImage {
		$internalPath = $file->getInternalPath();

		try {
			$temporaryCopy = $this->requiresTemporaryCopy($file);
			$copyLimit = $this->archiveReader->getMaxTemporaryArchiveBytes();
			if ($temporaryCopy && $file->getSize() > $copyLimit) {
				throw new \UnexpectedValueException('The comic exceeds the temporary preview copy limit.');
			}

			$localFile = $this->localFile($file, $temporaryCopy ? $copyLimit + 1 : null);
			if ($localFile === false) {
				throw new \UnexpectedValueException('The comic could not be copied for preview generation.');
			}
			if ($temporaryCopy) {
				clearstatcache(true, $localFile);
				$copiedSize = filesize($localFile);
				if ($copiedSize === false || $copiedSize > $copyLimit) {
					throw new \UnexpectedValueException('The comic exceeds the temporary preview copy limit.');
				}
			}

			$cover = $this->archiveReader->readCover($localFile);
			if ($cover === null) {
				$this->logger->debug('Comic archive has no previewable cover image.', ['app' => 'comicpreviews']);
				return null;
			}

			$image = $this->createImage();
			if ($image->loadFromData($cover['data']) === false || !$image->valid()) {
				throw new \UnexpectedValueException('The validated comic cover could not be decoded.');
			}
			$image->scaleDownToFit(max(1, $maxX), max(1, $maxY));
			return $image;
		} catch (\Throwable $e) {
			$this->logger->warning('Failed to generate comic archive thumbnail for {file}, error: {error}.', [
				'file' => $internalPath,
				'error' => $e->getMessage(),
				'exception' => $e,
				'app' => 'comicpreviews',
			]);
			return null;
		} finally {
			$this->cleanTemporaryFiles();
		}
	}

	protected function createImage(): IImage {
		return new Image();
	}

	protected function getTemporaryFile(): string|false {
		return Server::get(ITempManager::class)->getTemporaryFile();
	}

	private function requiresTemporaryCopy(File $file): bool {
		return $file->isEncrypted() || !$file->getStorage()->isLocal();
	}

	private function localFile(File $file, ?int $maxBytes): string|false {
		if (!$this->requiresTemporaryCopy($file)) {
			return $file->getStorage()->getLocalFile($file->getInternalPath());
		}

		$path = $this->getTemporaryFile();
		if ($path === false) {
			return false;
		}
		$this->temporaryFiles[] = $path;

		$source = $file->fopen('r');
		if ($source === false) {
			return false;
		}
		try {
			$target = fopen($path, 'wb');
			if ($target === false) {
				return false;
			}
			try {
				$copied = stream_copy_to_stream($source, $target, $maxBytes ?? -1);
				return $copied === false ? false : $path;
			} finally {
				fclose($target);
			}
		} finally {
			fclose($source);
		}
	}

	private function cleanTemporaryFiles(): void {
		foreach ($this->temporaryFiles as $path) {
			if (is_file($path)) {
				@unlink($path);
			}
		}
		$this->temporaryFiles = [];
	}
}
