<?php declare(strict_types=1);

namespace Ellipse\Exceptions;

use Throwable;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use Ellipse\Http\Exceptions\Response\RequestBasedResponseFactory;

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
     * Handle the request with the given handler and execute the callable when
     * it fails. Update the response when the exception or a previous one is a
     * TerminableException.
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

            $inner = $e instanceof TerminableException ? $e->inner() : $e;

            if ($inner instanceof $this->class) {

                $response = ($this->callable)($request, $inner);

                return $this->terminate($response, $e);

            }

            throw $e;

        }
    }

    /**
     * Return the given response updated with the given exception when it is a
     * TerminableException. Recurse over the previous exceptions.
     *
     * Can be useful for example to attach session cookie to the response even
     * when the script fails.
     *
     * @param \Psr\Http\Message\ResponseInterface   $response
     * @param \Throwable                            $e
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function terminate(ResponseInterface $response, Throwable $e): ResponseInterface
    {
        if ($e instanceof TerminableException) {

            $response = $e->terminate($response);

        }

        $previous = $e->getPrevious();

        if (is_null($previous)) {

            return $response;

        }

        return $this->terminate($response, $previous);
    }
}
