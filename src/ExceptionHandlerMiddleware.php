<?php declare(strict_types=1);

namespace Ellipse\Exceptions;

use Throwable;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use Ellipse\Exceptions\Exceptions\ExceptionRequestHandlerTypeException;

class ExceptionHandlerMiddleware implements MiddlewareInterface
{
    /**
     * The class name of the exceptions to catch.
     *
     * @var string
     */
    public $class;

    /**
     * The factory producing a request handler from the caught exception.
     *
     * @var callable
     */
    public $factory;

    /**
     * Set up an exception handler widdleware with the given exception class
     * name and request handler factory.
     *
     * @param string    $class
     * @param callable  $factory
     */
    public function __construct(string $class, callable $factory)
    {
        $this->class = $class;
        $this->factory = $factory;
    }

    /**
     * Handle the request with the given request handler. When an exception is
     * caught, handle the request with the request handler produced by the
     * factory.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Server\RequestHandlerInterface $handler
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Ellipse\Exceptions\Exceptions\ExceptionRequestHandlerTypeException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {

            return $handler->handle($request);

        }

        catch (Throwable $e) {

            if ($e instanceof $this->class) {

                $handler = ($this->factory)($e);

                if ($handler instanceof RequestHandlerInterface) {

                    return $handler->handle($request);

                }

                throw new ExceptionRequestHandlerTypeException($e, $handler);

            }

            throw $e;

        }
    }
}
