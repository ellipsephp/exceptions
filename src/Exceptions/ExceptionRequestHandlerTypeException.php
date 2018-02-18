<?php

namespace Ellipse\Exceptions\Exceptions;

use Throwable;
use TypeError;

use Psr\Http\Server\RequestHandlerInterface;

class ExceptionRequestHandlerTypeException extends TypeError implements ExceptionHandlingExceptionInterface
{
    public function __construct(Throwable $e, $value)
    {
        $template = "Trying to use a value of type %s to handle an exception of type %s - object implementing %s expected";

        $type = is_object($value) ? get_class($value) : gettype($value);

        $msg = sprintf($template, $type, get_class($e), RequestHandlerInterface::class);

        parent::__construct($msg);
    }
}
