<?php
namespace App\Core;

interface MiddlewareInterface
{
    public function process(array $update, callable $next): void;
}
