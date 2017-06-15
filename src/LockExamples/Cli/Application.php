<?php

namespace LockExamples\Cli;

use Silly\Application as SillyApplication;
use Symfony\Component\Lock\StoreInterface;
use Symfony\Component\Lock\Factory;


class Application extends SillyApplication
{

    private $stores = [];

    public function addStore($key, StoreInterface $store) {
        $this->stores[$key] = $store;
    }

    public function command($expression, $callable, array $aliases = [])
    {
        $expressionWithStores = $expression . " [store]";

        $command = parent::command($expressionWithStores, $callable, $aliases);

        $command->setCode(function ($input, $output) use ($callable) {
            $factory = $this->getSelectedFactory($input);
            return call_user_func($callable, $output, $factory, $input);
        });

        return $command;
    }

    private function getSelectedFactory($input)
    {
        foreach ($this->stores as $key => $store) {
            if ($input->hasParameterOption("${key}", true)) {
                return new Factory($store);
            }
        }
        return null;
    }
}