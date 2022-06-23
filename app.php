#!/usr/local/bin/php
<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use App\Command\WebsiteSearchPriceCrawler;
use Symfony\Component\Console\Application;


$application = new Application("Website Price Scripts", '1.2.0');
$command = new WebsiteSearchPriceCrawler();

$application->add($command);

$application->setDefaultCommand($command->getName(), true);
$application->run();
