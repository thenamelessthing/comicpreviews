<?php

declare(strict_types=1);

namespace OCA\Comicpreviews\Tests\Unit;

use OCA\Comicpreviews\Preview\CBRPreviewProvider;
use OCA\Comicpreviews\Preview\CBZPreviewProvider;
use OCP\Files\FileInfo;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class PreviewRegistrationTest extends TestCase {
	public function testProvidersExposeNarrowComicMimePatterns(): void {
		$logger = $this->createMock(LoggerInterface::class);

		self::assertSame(
			'/^(?:application\/x-cbz|application\/comicbook\+zip|application\/vnd\.comicbook\+zip)$/',
			(new CBZPreviewProvider($logger))->getMimeType(),
		);
		self::assertSame(
			'/^(?:application\/x-cbr|application\/comicbook\+rar|application\/vnd\.comicbook\+rar)$/',
			(new CBRPreviewProvider($logger))->getMimeType(),
		);
	}

	public function testCbrAvailabilityMatchesRarExtension(): void {
		$logger = $this->createMock(LoggerInterface::class);
		$file = $this->createMock(FileInfo::class);

		self::assertSame(extension_loaded('rar'), (new CBRPreviewProvider($logger))->isAvailable($file));
	}

	public function testApplicationRegistersCbrOnlyWhenRarExtensionIsLoaded(): void {
		$contents = file_get_contents(dirname(__DIR__, 2) . '/lib/AppInfo/Application.php');
		self::assertIsString($contents);

		self::assertSame(1, substr_count($contents, 'CBZPreviewProvider::class'));
		self::assertSame(1, substr_count($contents, 'CBRPreviewProvider::class'));
		self::assertMatchesRegularExpression(
			'/if \(extension_loaded\(\'rar\'\)\) \{\s*\$context->registerPreviewProvider\(\s*CBRPreviewProvider::class,/s',
			$contents,
		);
	}

	public function testProductionCodeDoesNotInvokeArchiveSubprocesses(): void {
		$directory = dirname(__DIR__, 2) . '/lib';
		$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
		foreach ($iterator as $file) {
			if (!$file->isFile() || $file->getExtension() !== 'php') {
				continue;
			}
			$contents = file_get_contents($file->getPathname());
			self::assertIsString($contents);
			self::assertDoesNotMatchRegularExpression(
				'/\b(?:exec|shell_exec|system|passthru|proc_open|popen)\s*\(/',
				$contents,
				$file->getPathname(),
			);
			self::assertStringNotContainsString('/usr/bin/unrar', $contents, $file->getPathname());
		}
	}
}
