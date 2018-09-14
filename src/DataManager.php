<?php

namespace App\Providers;


interface DataManager
{
    public function get($key, $default = null);
    public function has($key);
}