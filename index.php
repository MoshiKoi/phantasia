#!/usr/bin/env php
<?php

namespace Phantasia\Application;

require __DIR__ . '/vendor/autoload.php';

use Phantasia\Command\ReplCommand;
use Phantasia\Command\RunCommand;

use Symfony\Component\Console\Application;

$application = new Application('Phantasia', '0.0.1');
$application->addCommands([
    new RunCommand,
    new ReplCommand,
]);
$application->run();