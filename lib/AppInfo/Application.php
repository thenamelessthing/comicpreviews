<?php

declare(strict_types=1);

namespace OCA\Comicpreviews\AppInfo;

use OCA\Comicpreviews\Preview\CBRPreviewProvider;
use OCA\Comicpreviews\Preview\CBZPreviewProvider;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

final class Application extends App implements IBootstrap {
	public const APP_ID = 'comicpreviews';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerPreviewProvider(
			CBZPreviewProvider::class,
			'/^(?:application\/x-cbz|application\/comicbook\+zip|application\/vnd\.comicbook\+zip)$/'
		);
		if (extension_loaded('rar')) {
			$context->registerPreviewProvider(
				CBRPreviewProvider::class,
				'/^(?:application\/x-cbr|application\/comicbook\+rar|application\/vnd\.comicbook\+rar)$/'
			);
		}
	}

	public function boot(IBootContext $context): void {
	}
}
