<?php declare(strict_types=1);

namespace Ellipse\Exceptions;

use Throwable;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ExceptionHandlerMiddleware implements MiddlewareInterface
{
    /**
     * The class name of the exceptions to catch.
     *
     * @var string
     */
    public $class;

    /**
     * The callable to execute when an exception is caught. Takes the request
     * and the exception as parameter and should return a Psr-7 response.
     *
     * @var callable
     */
    public $callable;

    /**
     * Set up an exception handler widdleware with the given exception class
     * name and callable.
     *
     * @param string    $class
     * @param callable  $callable
     */
    public function __construct(string $class, callable $callable)
    {
        $this->class = $class;
        $this->callable = $callable;
    }

    /**
     * Handle the request with the given handler and produce a response with the
     * callable when an exception is thrown.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Server\RequestHandlerInterface $handler
     * @param \Psr\Http\Message\ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {

            return $handler->handle($request);

        }

        catch (Throwable $e) {

            if ($e instanceof $this->class) {

                return ($this->callable)($request, $e);

            }

            throw $e;

        }
    }
}
