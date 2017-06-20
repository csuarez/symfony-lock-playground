<?php

require 'bootstrap.php';

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\StoreInterface;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\Store\SemaphoreStore;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Lock\Store\RedisStore;
use Symfony\Component\Lock\Store\MemcachedStore;
use Symfony\Component\Lock\Store\RetryTillSaveStore;
use Symfony\Component\Lock\Store\CombinedStore;
use Symfony\Component\Lock\Strategy\ConsensusStrategy;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use LockExamples\Cli\Application as LockApplication;
use LockExamples\Resource\UnsafeSharedResource;
use Simple\SHM\Block;

$app = new LockApplication();

$redisConns = [];
$redisStores = [];

$redisConn = new \Predis\Client('tcp://redis:6379');
try {
    $redisConn->connect();
    $redisStores[] = new RedisStore($redisConn);
} catch (\Exception $e) {}

$redisConn2 = new \Predis\Client('tcp://redis2:6379');
try {
    $redisConn2->connect();
    $redisStores[] = new RedisStore($redisConn2);
} catch (\Exception $e) {}

$redisConn3 = new \Predis\Client('tcp://redis3:6379');
try {
    $redisConn3->connect();
    $redisStores[] = new RedisStore($redisConn3);
} catch (\Exception $e) {}


$memcachedConn = new \Memcached;
$memcachedConn->addServer('memcached', 11211);

$app->addStore('flock', new FlockStore(sys_get_temp_dir()));
$app->addStore('semaphore', new SemaphoreStore());

$app->addStore('redis', new RetryTillSaveStore(
    new RedisStore($redisConn)
));

$app->addStore('memcached', new RetryTillSaveStore(new MemcachedStore($memcachedConn)));
$app->addStore('combined', new RetryTillSaveStore(new CombinedStore(
    $redisStores,
    new ConsensusStrategy())
));

$app->command('resource:reset [resource]', function ($output, $factory, $input) {
    $resourceName = $input->getArgument('resource');
    $resource = new UnsafeSharedResource($resourceName);
    $resource->reset();
});

$app->command('simple:nolock', function (OutputInterface $output, Factory $factory) {
    $resource = new UnsafeSharedResource('very-important-thing');

    do {
        $value = $resource->increase();
        $output->writeln($value);
    } while(true);
});

$app->command('simple:lock', function (OutputInterface $output, Factory $factory) {
    $resource = new UnsafeSharedResource('very-important-thing');

    $lock = $factory->createLock('simple:lock', 10);

    do {
        $lock->acquire(true);
        try {
            $value = $resource->increase();
            $output->writeln($value);
        } finally {
            $lock->release();
        }
    } while(true);
});



$app->command('simple:distlockerror', function (OutputInterface $output, Factory $factory) {
    $resource = new UnsafeSharedResource('very-important-thing');

    $lock = $factory->createLock('simple:lock', 10);

    do {
        $lock->acquire(true);

        if ((rand(0, 1000) % 1000) === 0) {
            throw new \Exception('bye bye!');
        }

        try {
            $value = $resource->increase();
            $output->writeln($value);
        } finally {
            $lock->release();
        }
    } while(true);
});


$app->command('barrier', function (OutputInterface $output, Factory $factory) {
    $processes = new UnsafeSharedResource('processes');
    $barrierFlag = new UnsafeSharedResource('barrier');

    $counterLock = $factory->createLock('processes-counter');
    $barrierLock = $factory->createLock('processes-barrier');

    $heavyWork = function () use ($output) {
        $output->writeln("Working");
        sleep(rand(1, 5));
    };


    do {
        $processesCount = 0;

        $counterLock->acquire(true);
        try {
            $output->writeln("Waiting!!");
            $processesCount = $processes->increase();
            if ($processesCount == 1) {
                $barrierLock->acquire(true);
                try {
                    $barrierFlag->write(0);
                } finally {
                    $barrierLock->release();
                }
            }
        } finally {
            $counterLock->release();
        }

        $readBarrierFlag = 0;
        do {
            $counterLock->acquire(true);
            try {
                $processesCount = $processes->read();
                if ($processesCount == 4) {
                    $barrierLock->acquire(true);
                    try {
                        $output->writeln("----------");
                        $barrierFlag->write(1);
                        $processes->write(0);
                    } finally {
                        $barrierLock->release();
                    }
                }
                $barrierLock->acquire(true);
                try {
                    $readBarrierFlag = $barrierFlag->read();
                } finally {
                    $barrierLock->release();
                }
            } finally {
                $counterLock->release();
            }
        } while ($readBarrierFlag === 0);

        $heavyWork();
    } while (true);
});

$app->command('rw:reader', function (OutputInterface $output, Factory $factory) {
    $resource = new UnsafeSharedResource('very-important-thing');
    $readersStore = new UnsafeSharedResource('readers');
    $readLock = $factory->createLock('reader');
    $writeLock = $factory->createLock('writer');
    do {
        $readLock = $factory->createLock('reader');
        $writeLock = $factory->createLock('writer');
        // begin read
        $readLock->acquire(true);
        $readers = $readersStore->increase();

        if ($readers == 1) {
            $writeLock->acquire(true);
        }
        $readLock->release();

        // read
        $value = $resource->read();
        $output->writeln(">> Read $value");

        // end read
        $readLock->acquire(true);
        $readers = $readersStore->decrease();

        if ($readers == 0) {
            $writeLock->release();
        }
        $readLock->release();
        sleep(rand(1,2));
    } while (true);
});


$app->command('rw:writer', function (OutputInterface $output, Factory $factory) {
    $resource = new UnsafeSharedResource('very-important-thing');
    $readLock = $factory->createLock('reader');
    $writeLock = $factory->createLock('writer');
    do {
        //begin write
        $writeLock->acquire(true);
        $output->writeln('Start to write...');

        //write
        $resource->increase();
        sleep(rand(1,5));

        //end write
        $output->writeln('Write finishes!');
        $writeLock->release();
    } while (true);
});


$app->run();