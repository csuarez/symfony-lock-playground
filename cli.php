<?php

require 'bootstrap.php';

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\StoreInterface;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\Store\SemaphoreStore;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Lock\Store\RedisStore;
use Symfony\Component\Lock\Store\RetryTillSaveStore;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use LockExamples\Cli\Application as LockApplication;

$app = new LockApplication();

$app->addStore('flock', new FlockStore(sys_get_temp_dir()));

$app->command('simple:nolock [--test]', function (OutputInterface $output, Factory $factory, $input) {
    $output->writeln('Hello world');
});


$app->run();