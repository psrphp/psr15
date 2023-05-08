<?php

declare(strict_types=1);

namespace PsrPHP\Psr15;

use OutOfBoundsException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RequestHandler implements RequestHandlerInterface
{
    protected $middlewares = [];

    public function setHandler(callable $callback): self
    {
        $this->appendMiddleware(new class($callback) implements MiddlewareInterface
        {
            protected $callback;

            public function __construct(callable $callback)
            {
                $this->callback = $callback;
            }

            public function process(
                ServerRequestInterface $server_request,
                RequestHandlerInterface $request_handler
            ): ResponseInterface {
                return ($this->callback)($server_request);
            }
        });
        return $this;
    }

    public function appendMiddleware(MiddlewareInterface ...$middlewares): self
    {
        array_push($this->middlewares, ...$middlewares);
        return $this;
    }

    public function prependMiddleware(MiddlewareInterface ...$middlewares): self
    {
        array_unshift($this->middlewares, ...$middlewares);
        return $this;
    }

    public function shiftMiddleware(): MiddlewareInterface
    {
        $middleware = array_shift($this->middlewares);
        if ($middleware === null) {
            throw new OutOfBoundsException('Reached end of middleware stack. Does your return a response?');
        }
        return $middleware;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $middleware = $this->shiftMiddleware();
        return $middleware->process($request, $this);
    }
}
