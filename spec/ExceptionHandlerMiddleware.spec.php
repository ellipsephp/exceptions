<?php

use function Eloquent\Phony\Kahlan\mock;
use function Eloquent\Phony\Kahlan\stub;
use function Eloquent\Phony\Kahlan\onStatic;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use Ellipse\Exceptions\ExceptionHandlerMiddleware;

describe('ExceptionHandlerMiddleware', function () {

    beforeEach(function () {

        $this->callable = stub();

        $this->middleware = new ExceptionHandlerMiddleware(RuntimeException::class, $this->callable);

    });

    it('should implement MiddlewareInterface', function () {

        expect($this->middleware)->toBeAnInstanceOf(MiddlewareInterface::class);

    });

    describe('->process()', function () {

        beforeEach(function () {

            $this->request = mock(ServerRequestInterface::class)->get();
            $this->response = mock(ResponseInterface::class)->get();
            $this->handler = mock(RequestHandlerInterface::class);

        });

        context('when the request handler does not throw an exception', function () {

            it('should proxy the request handler ->handler() method', function () {

                $this->handler->handle->with($this->request)->returns($this->response);

                $test = $this->middleware->process($this->request, $this->handler->get());

                expect($test)->toBe($this->response);

            });

        });

        context('when the request handler throws an exception with a different class name than the specified one', function () {

            it('should propagate the exception', function () {

                $exception = mock(Throwable::class)->get();

                $this->handler->handle->with($this->request)->throws($exception);

                $test = function () {

                    $this->middleware->process($this->request, $this->handler->get());

                };

                expect($test)->toThrow($exception);

            });

        });

        context('when the request handler throws an exception with the specified class name', function () {

            it('should proxy the callable with the caught exception', function () {

                $exception = new RuntimeException;

                $response = mock(ResponseInterface::class)->get();

                $this->handler->handle->with($this->request)->throws($exception);

                $this->callable->with($this->request, $exception)->returns($response);

                $test = $this->middleware->process($this->request, $this->handler->get());

                expect($test)->toBe($response);

            });

        });

    });

});
