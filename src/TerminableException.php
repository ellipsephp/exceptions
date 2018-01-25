<?php declare(strict_types=1);

namespace Ellipse\Exceptions;

use Throwable;
use Exception;

use Psr\Http\Message\ResponseInterface;

class TerminableException extends Exception
{
    public function __construct(Throwable $previous, callable $callable)
    {
        $this->callable = $callable;

        parent::__construct('', 0, $previous);
    }

    public function inner(): Throwable
    {
        $previous = $this->getPrevious();

        if ($previous instanceof TerminableException) {

            return $previous->inner();

        }

        return $previous;
    }

    public function terminate(ResponseInterface $response): ResponseInterface
    {
        return ($this->callable)($response);
    }
}
