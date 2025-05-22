<?php

class Kernel
{
    protected $middleware = [
        // ...
        \Fruitcake\Cors\HandleCors::class,
    ];
}
