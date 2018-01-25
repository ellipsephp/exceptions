<?php

use function Eloquent\Phony\Kahlan\mock;
use function Eloquent\Phony\Kahlan\stub;
use function Eloquent\Phony\Kahlan\onStatic;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use Ellipse\Exceptions\ExceptionHandlerMiddleware;
use Ellipse\Exceptions\TerminableException;

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

            context('when no previous exception is a TerminableException', function () {

                it('should proxy the callable with the caught exception', function () {

                    $exception = new RuntimeException('', 0, new Exception);

                    $response = mock(ResponseInterface::class)->get();

                    $this->handler->handle->with($this->request)->throws($exception);

                    $this->callable->with($this->request, $exception)->returns($response);

                    $test = $this->middleware->process($this->request, $this->handler->get());

                    expect($test)->toBe($response);

                });

            });

            context('when previous exceptions are TerminableException', function () {

                it('should proxy the callable with the exception then proxy all the terminable exception ->terminate() method', function () {

                    $terminate1 = stub();
                    $terminate2 = stub();
                    $terminate3 = stub();

                    $exception = new RuntimeException('', 0, new TerminableException(
                        new Exception('', 0, new TerminableException(
                            new Exception('', 0, new TerminableException(
                                new Exception, $terminate3
                            )),
                            $terminate2
                        )),
                        $terminate1
                    ));

                    $response1 = mock(ResponseInterface::class)->get();
                    $response2 = mock(ResponseInterface::class)->get();
                    $response3 = mock(ResponseInterface::class)->get();
                    $response4 = mock(ResponseInterface::class)->get();

                    $this->handler->handle->with($this->request)->throws($exception);

                    $this->callable->with($this->request, $exception)->returns($response1);
                    $terminate1->with($response1)->returns($response2);
                    $terminate2->with($response2)->returns($response3);
                    $terminate3->with($response3)->returns($response4);

                    $test = $this->middleware->process($this->request, $this->handler->get());

                    expect($test)->toBe($response4);

                });

            });

        });

        context('when the request handler throws a TerminableException', function () {

            context('when the inner exception does not have the specified class name', function () {

                it('should propagate the exception', function () {

                    $exception = new TerminableException(new Exception, stub());

                    $this->handler->handle->with($this->request)->throws($exception);

                    $test = function () {

                        $this->middleware->process($this->request, $this->handler->get());

                    };

                    expect($test)->toThrow($exception);

                });

            });

            context('when the inner exception has the specified class name', function () {

                it('should proxy the callable with the inner exception then proxy all the terminable exception ->terminate() method', function () {

                    $terminate1 = stub();
                    $terminate2 = stub();
                    $terminate3 = stub();

                    $inner = new RuntimeException('', 0, new TerminableException(new Exception, $terminate3));
                    $exception = new TerminableException(new TerminableException($inner, $terminate2), $terminate1);

                    $response1 = mock(ResponseInterface::class)->get();
                    $response2 = mock(ResponseInterface::class)->get();
                    $response3 = mock(ResponseInterface::class)->get();
                    $response4 = mock(ResponseInterface::class)->get();

                    $this->handler->handle->with($this->request)->throws($exception);

                    $this->callable->with($this->request, $inner)->returns($response1);
                    $terminate1->with($response1)->returns($response2);
                    $terminate2->with($response2)->returns($response3);
                    $terminate3->with($response3)->returns($response4);

                    $test = $this->middleware->process($this->request, $this->handler->get());

                    expect($test)->toBe($response4);

                });

            });

        });

    });

});
