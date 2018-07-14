<?php

namespace SudiptoChoudhury\Support\Forge\Api;

use GuzzleHttp\Client as GHClient;
use GuzzleHttp\Command\Guzzle\GuzzleClient;
use GuzzleHttp\Command\Guzzle\Description;

/**
 * Class Client
 *
 * @package SudiptoChoudhury\Support\Forge\Api
 */
class Client
{

    private $DEFAULTS = [
        'description' => [
            'jsonPath' => './config/api.json',
            'options' => [],
        ],
        'client' => [
            'base_uri' => '',
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ],
    ];

    private $client;
    private $description;
    private $consumer;
    private $options;
    private $rootPath;

    public function __construct($config = [])
    {
        $this->setOptions($config);
        $mergedOptions = $this->options;

        /** @var $description */
        /** @var $client */
        extract($mergedOptions);

        $this->setDescription($description);

        $this->setClient($client);

        return $this->consumer;

    }

    public function setOptions($options = [])
    {

        $defaults = $this->DEFAULTS;
        $this->rootPath = realpath(__DIR__) . '/';
        $defaults['description']['jsonPath'] = realpath($this->rootPath . $defaults['description']['jsonPath']);
        $mergedOptions = array_merge_recursive($defaults, $options);
        $parsedOptions = $this->parseOptions($mergedOptions, $options);
        $this->options = $parsedOptions;

        return $this;
    }

    private function parseOptions($options, $rootOption = [])
    {

        // @todo: need to make it is_iterable
        if (is_array($options)) {
            foreach ($options as $key => $option) {
                $options[$key] = $this->parseOptions($option, $rootOption);
            }
        } else {
            if (preg_match('/{{/', $options)) {
                foreach ($rootOption as $key => $value) {
                    if (is_scalar($value)) {
                        $options = str_replace('{{' . $key . '}}', $value, $options);
                    }
                }
                return $options;
            }
        }

        return $options;

    }

    public function setDescription($descriptionOptions = [])
    {

        /** @var $jsonPath */
        /** @var $options */
        extract($descriptionOptions);

        if ($descriptionOptions instanceof Description) {
            $this->description = $descriptionOptions;
        } else {
            if (empty($jsonPath)) {
                $jsonPath = realpath($this->options['description']['jsonPath']);
            }

            try {

                $description = json_decode(file_get_contents($jsonPath), true);
                if (empty($description['baseUri'])) {
                    $description['baseUri'] = $this->options['client']['base_uri'];
                }

                $this->description = new Description($description, $options);

            } catch (\Exception $ex) {
            }

        }

        return $this;
    }

    public function createClient($clientOptions = [])
    {
        $this->client = new GHClient($clientOptions);
        return $this;
    }

    private function setClient($clientOptions = [])
    {

        $this->createClient($clientOptions);
        $this->consumer = new GuzzleClient($this->client, $this->description);

        return $this;
    }

    public function __call($name, $arguments)
    {
        $api = $this->consumer;
        if (is_callable([$api, $name])) {
            return call_user_func_array([$api, $name], $arguments);
        }

        throw new \BadMethodCallException(sprintf('Call to undefined method %s::%s().', get_called_class(), $name));
    }

    public function importApi($source = '', $destination = '', $sourceType = 'postman')
    {
        if (empty($source)) {
            $source = realpath($this->rootPath . './config/postman.json');
        }
        if (empty($destination)) {
            $destination = realpath($this->rootPath . './config/api.json');
        }
        return (new Import($source))->writeData($destination);
    }
}