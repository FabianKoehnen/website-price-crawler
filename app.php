#!/usr/local/bin/php
<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use App\Command\WebsiteSearchPriceCrawler;
use Composer\InstalledVersions;
use Symfony\Component\Console\Application;

$composerData = InstalledVersions::getRootPackage();

$application = new Application($composerData['name'] ?? "unknown", $composerData['version'] ?? "unknown");
$command = new WebsiteSearchPriceCrawler();

$application->add($command);

$application->setDefaultCommand($command->getName(), true);
$application->run();
