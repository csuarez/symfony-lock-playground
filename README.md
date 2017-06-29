# Examples of [symfony/lock](https://github.com/symfony/lock)

This repo includes several examples for [symfony/lock](https://github.com/symfony/lock) that could be run with different configurations in an easy way.

> **NOTE:** All this code has been written for educational purposes, so some implementations are very naive. Created for a talk about symfony/lock (slides not avialable yet).

## Requirements
- Docker
- docker-compose
- Composer

## How to execute

Open a terminal and execute the following in the root folder:
```sh
$ docker-compose up
```

There are several `composer` scripts that execute some presetted scenarios inside the docker container:

- `composer run:simple-lock`: Runs 4 processes using a Redis lock that increment a stored number in an unsafe shared resource.

- `composer run:faulty-lock`: Runs 4 processes using one Redis lock that increment a stored number in an unsafe shared resource, but they something fails (0,1% of the times).

- `composer run:faulty-halock`: Runs 4 processes using three Redis locks that increment a number stored in an unsafe shared resource, but they something fails (0,1% of the times).

- `composer run:barrier`: Runs 4 processes which implement a [barrier](https://en.wikipedia.org/wiki/Barrier_(computer_science)).

- `composer run:rw`: Runs a writer process which increments a number stored in an unsafe shared resource and 4 reader process that read the stored value. They implement a [readers-write block](https://en.wikipedia.org/wiki/Readers%E2%80%93writer_lock).

- `composer stop:all`: Stops all running processes.

You can modify the number of processes or the used stores modifying the [PM2 process files](http://pm2.keymetrics.io/docs/usage/application-declaration/) included in `./pm2/`.

## cli.php

All the explained processes are implemented at `./cli.php`.

It can be used as follows:

```sh
$ php ./cli.php <process-name> <store>
```

### Available processes

- `simple:nolock`: A process which increments the value of an unsafe shared resource without using any lock.

- `simple:lock`: Like `simple:nolock` but using a lock who writes and reads atomically.

- `simple:distlockerror`: Like `simple:lock` but fails sometimes. Useful to check the behaviour of stores when a process fails.

- `barrier`: A [barrier](https://en.wikipedia.org/wiki/Barrier_(computer_science)) which waits for 4 processes. You have to execute at least 4 processes to make this command work (more details later).

- `rw:reader`: A reader process for a [readers-writer lock](https://en.wikipedia.org/wiki/Readers%E2%80%93writer_lock).

- `rw:writer`: A writer process for a [readers-writer lock](https://en.wikipedia.org/wiki/Readers%E2%80%93writer_lock).

### Available stores

The following stores are supported:
- `semaphore`: Uses a `SemaphoreStore` instance.

- `flock`: Uses a `FlockStore` instance.

- `redis`: Uses a `RedisStore` instance.

- `memcache`: Uses a `MemcachedStore`.

- `combined`: Uses a `CombinedStore` with three `RedisStore` instances.

There are some stores that need external services like Redis or Memcached. It is recommended to run the examples in the provided Docker environment, which includes all the needed dependencies.

Some of the stores need some cleaning before each execution (to clean locks of previous executions). The `composer.json` handle this for you, check that file if you want to run the processes manually.