#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Application;
use EmzCli\Command\Plugin\PreparePluginCommand;

$app = new Application('emz', '1.0.0');
$app->add(new PreparePluginCommand());

$app->run();