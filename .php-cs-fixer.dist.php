<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Nextcloud\CodingStandard\Config;

$config = new Config();
$config->getFinder()
	->ignoreVCSIgnored(true)
	->in([
		__DIR__ . '/lib',
		__DIR__ . '/tests',
	]);

return $config;
