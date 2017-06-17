<?php

namespace LockExamples\Resource;

class UnsafeSharedResource
{
    private $file;

    public function __construct($key)
    {
        $this->file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $key;
    }

    public function write($value)
    {
        file_put_contents($this->file, $value);
    }

    public function read()
    {
        return intval(file_get_contents($this->file));
    }

    public function reset()
    {
        $this->write(0);
    }
}
