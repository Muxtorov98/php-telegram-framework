<?php
namespace App\Core;

class MiddlewarePipeline
{
    private array $middlewares = [];

    public function add(MiddlewareInterface $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    public function handle(array $update, callable $coreHandler): void
    {
        $handler = array_reduce(
            array_reverse($this->middlewares),
            fn($next, $middleware) => fn($update) => $middleware->process($update, $next),
            $coreHandler
        );

        $handler($update);
    }
}
