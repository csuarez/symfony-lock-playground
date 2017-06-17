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

$app->command('resource:reset [resource]', function ($output, $factory, $input) {
    $resourceName = $input->getArgument('resource');
    $resource = new UnsafeSharedResource($resourceName);
    $resource->reset();
});

$app->command('simple:nolock', function (OutputInterface $output, Factory $factory) {
    $resource = new UnsafeSharedResource('very-important-thing');

    do {
        $counter = $resource->read();
        $output->writeln($counter);
        $resource->write(++$counter);
    } while(true);
});

$app->command('simple:lock', function (OutputInterface $output, Factory $factory) {
    $resource = new UnsafeSharedResource('very-important-thing');

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