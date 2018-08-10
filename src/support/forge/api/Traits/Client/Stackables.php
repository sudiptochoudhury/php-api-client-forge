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

    protected function getStacks($config)
    {

        $stack = $config['client']['handler'] ?? HandlerStack::create();

        if (!empty($config['settings']['requestHandler'])) {
            $request = $this->getRequestStack($config['settings']['requestHandler']);
            $stack->push($request);
        }
        if (method_exists($this, 'requestHandler')) {
            $function = [$this, 'requestHandler'];
            $stack->push($this->getRequestStack($function));
        }

        if (!empty($config['settings']['responseHandler'])) {
            $response = $this->getResponseStack($config['settings']['responseHandler']);
            $stack->push($response);
        }
        if (method_exists($this, 'responseHandler')) {
            $function = [$this, 'responseHandler'];
            $stack->push($this->getResponseStack($function));
        }

        if ($config['log'] ?? true !== false) {
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
                $this->loggerFile ?? ($this->getChildDir() . '/webapi-logs.log')));
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