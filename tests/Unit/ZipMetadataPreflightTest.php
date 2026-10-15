<?php

declare(strict_types=1);

namespace OCA\Comicpreviews\Tests\Unit;

use OCA\Comicpreviews\Preview\ZipMetadataPreflight;
use PHPUnit\Framework\TestCase;

final class ZipMetadataPreflightTest extends TestCase {
	public function testAcceptsCentralDirectoryBeyondFourGiBOn64BitPlatforms(): void {
		if (PHP_INT_SIZE < 8) {
			self::markTestSkipped('Large local ZIP offsets require a 64-bit PHP build.');
		}

		$file = $this->temporaryFile();
		$stream = fopen($file, 'w+b');
		self::assertIsResource($stream);
		$directoryOffset = 0x100000000;
		$directory = "PK\x01\x02" . str_repeat("\0", 42);
		$zip64Offset = $directoryOffset + strlen($directory);
		$record = "PK\x06\x06" . $this->packU64(44) . pack('vvVV', 45, 45, 0, 0)
			. $this->packU64(1) . $this->packU64(1)
			. $this->packU64(strlen($directory)) . $this->packU64($directoryOffset);
		$locator = "PK\x06\x07" . pack('V', 0) . $this->packU64($zip64Offset) . pack('V', 1);
		$end = "PK\x05\x06" . pack('vvvvVVv', 0, 0, 0xffff, 0xffff, 0xffffffff, 0xffffffff, 0);

		try {
			self::assertSame(0, fseek($stream, $directoryOffset));
			self::assertSame(strlen($directory), fwrite($stream, $directory));
			self::assertSame(strlen($record . $locator . $end), fwrite($stream, $record . $locator . $end));
			fclose($stream);
			$stream = null;

			ZipMetadataPreflight::check(
				$file,
				maxEntryCount: 1,
				maxCentralDirectoryBytes: strlen($directory),
				archiveDescription: 'CBZ',
			);
		} finally {
			if (is_resource($stream)) {
				fclose($stream);
			}
			@unlink($file);
		}
	}

	public function testRejectsMetadataBeyondPlatformOffsetRange(): void {
		$file = $this->temporaryFile();
		$locator = "PK\x06\x07" . pack('V', 0) . pack('VV', 0, 0x80000000) . pack('V', 1);
		$end = "PK\x05\x06" . pack('vvvvVVv', 0, 0, 0xffff, 0xffff, 0xffffffff, 0xffffffff, 0);
		self::assertSame(strlen($locator . $end), file_put_contents($file, $locator . $end));

		try {
			$this->expectException(\UnexpectedValueException::class);
			$this->expectExceptionMessage('platform offset range');
			ZipMetadataPreflight::check(
				$file,
				maxEntryCount: 1,
				maxCentralDirectoryBytes: 1024,
				archiveDescription: 'CBZ',
			);
		} finally {
			@unlink($file);
		}
	}

	private function temporaryFile(): string {
		$file = tempnam(sys_get_temp_dir(), 'comicpreviews-zip64-');
		self::assertIsString($file);
		return $file;
	}

	private function packU64(int $value): string {
		return pack('VV', $value % 0x100000000, intdiv($value, 0x100000000));
	}
}
