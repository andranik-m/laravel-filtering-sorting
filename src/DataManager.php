<?php

namespace FifthLLC\LaravelFilteringSorting;


interface DataManager
{
    public function get($key, $default = null);
    public function has($key);
}