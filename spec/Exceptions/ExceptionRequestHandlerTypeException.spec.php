<?php

use function Eloquent\Phony\Kahlan\mock;

use Ellipse\Exceptions\Exceptions\ExceptionHandlingExceptionInterface;
use Ellipse\Exceptions\Exceptions\ExceptionRequestHandlerTypeException;

describe('ExceptionRequestHandlerTypeException', function () {

    it('should implement ExceptionHandlingExceptionInterface', function () {

        $exception = mock(Throwable::class)->get();

        $test = new ExceptionRequestHandlerTypeException($exception, 'handler');

        expect($test)->toBeAnInstanceOf(ExceptionHandlingExceptionInterface::class);

    });

});
