<?php

namespace App\Providers;


interface FilterDataManager
{
    public function get($key, $default = null);
    public function has($key);
}