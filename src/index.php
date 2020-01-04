#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Application;
use EmzCli\Command\Plugin\PreparePluginCommand;
use EmzCli\Command\LDE\CreateLDECommand;

$app = new Application('emz', '1.0.0');
$app->add(new PreparePluginCommand());
$app->add(new CreateLDECommand());

$app->run();