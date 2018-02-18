<?php

use function Eloquent\Phony\Kahlan\mock;
use function Eloquent\Phony\Kahlan\stub;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use Ellipse\Exceptions\ExceptionHandlerMiddleware;
use Ellipse\Exceptions\Exceptions\ExceptionRequestHandlerTypeException;

describe('ExceptionHandlerMiddleware', function () {

    beforeEach(function () {

        $this->factory = stub();

        $this->middleware = new ExceptionHandlerMiddleware(RuntimeException::class, $this->factory);

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

            beforeEach(function () {

                $this->exception = new RuntimeException;

            });

            context('when the factory returns an implementation of RequestHandlerInterface', function () {

                it('should proxy the produced request handler', function () {

                    $handler = mock(RequestHandlerInterface::class);
                    $response = mock(ResponseInterface::class)->get();

                    $this->handler->handle->with($this->request)->throws($this->exception);

                    $this->factory->with($this->exception)->returns($handler);
                    $handler->handle->with($this->request)->returns($response);

                    $test = $this->middleware->process($this->request, $this->handler->get());

                    expect($test)->toBe($response);

                });

            });

            context('when the factory does not return an implementation of RequestHandlerInterface', function () {

                it('should throw a ExceptionRequestHandlerTypeException', function () {

                    $this->handler->handle->with($this->request)->throws($this->exception);

                    $this->factory->with($this->exception)->returns('handler');

                    $test = function () {

                        $this->middleware->process($this->request, $this->handler->get());

                    };

                    $exception = new ExceptionRequestHandlerTypeException($this->exception, 'handler');

                    expect($test)->toThrow($exception);

                });

            });

        });

    });

});
