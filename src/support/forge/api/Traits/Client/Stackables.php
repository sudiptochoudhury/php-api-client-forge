<?php


namespace SudiptoChoudhury\support\forge\api\Traits\Client;

use GuzzleHttp\MessageFormatter;
use Monolog\Logger;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;
use Monolog\Handler\RotatingFileHandler;

trait Stackables
{

    private $loggerFile = 'webapi-logs.log';

    protected function getStacks($config)
    {

        $stack = HandlerStack::create();

        if (!empty($config['settings']['requestHandler'])) {
            $request = $this->getRequestStack($config['settings']['requestHandler']);
            $stack->push($request);
        }
        if (is_callable(static::requestHandler)) {
            $stack->push($this->getRequestStack(static::requestHandler));
        }

        if (!empty($config['settings']['responseHandler'])) {
            $response = $this->getResponseStack($config['settings']['responseHandler']);
            $stack->push($response);
        }
        if (is_callable(static::responseHandler)) {
            $stack->push($this->getResponseStack(static::responseHandler));
        }

        if ($config['log'] !== false) {
            $logger = $this->getLoggerStack($config['log'] ?? null);
            $stack->push($logger);
        }

        return $stack;

    }

    protected function getLoggerStack($loggerSettings)
    {
        $logger = $loggerSettings['logger'] ?? null;
        $loggerFormat = $loggerSettings['format'] ?? "\r\n[{method} {uri} HTTP/{version}] \r\n{req_body} " .
            "\r\nRESPONSE: \r\nCODE: {code} \r\n-----------\r\n{res_body}\r\n------------";

        if (!($logger instanceof Logger)) {
            $logger = new Logger($loggerSettings['name'] ?? $this->loggerName ?? 'API');
            $logger->pushHandler(new RotatingFileHandler($loggerSettings['file'] ??
                $this->loggerFile ?? (__DIR__ . '/webapi-logs.log')));
        }

        return Middleware::log($logger, new MessageFormatter($loggerFormat));

    }

    protected function getRequestStack($stackFunction)
    {
        return Middleware::mapRequest(function (RequestInterface $request) use ($stackFunction) {
            if (is_callable($stackFunction)) {
                $request = call_user_func($stackFunction, $request);
            }
            return $request;
        });
    }

    protected function getResponseStack($stackFunction)
    {
        return Middleware::mapResponse(function (ResponseInterface $response) use ($stackFunction) {
            if (is_callable($stackFunction)) {
                $response = call_user_func($stackFunction, $response);
            }
            return $response;
        });
    }
}