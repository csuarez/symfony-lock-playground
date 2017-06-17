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
use LockExamples\Resource\UnsafeSharedResource;
use Simple\SHM\Block;

$app = new LockApplication();

$app->addStore('flock', new FlockStore(sys_get_temp_dir()));

$app->command('simple:nolock', function (OutputInterface $output, Factory $factory) {
    $resource = new UnsafeSharedResource('simple:lock');
    $resource->reset();

    do {
        $counter = $resource->read();
        $output->writeln($counter);
        $resource->write(++$counter);
    } while(true);
});

$app->command('simple:lock', function (OutputInterface $output, Factory $factory) {
    $resource = new UnsafeSharedResource('simple:lock');
    $resource->reset();

    $lock = $factory->createLock('simple:lock');

    do {
        $lock->acquire(true);
        try {
            $counter = $resource->read();
            $output->writeln($counter);
            $resource->write(++$counter);
        } finally {
            $lock->release();
        }
    } while(true);
});


$app->run();