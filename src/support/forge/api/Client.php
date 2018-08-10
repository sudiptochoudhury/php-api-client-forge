<?php

namespace SudiptoChoudhury\Support\Forge\Api;

use GuzzleHttp\Client as GHClient;
use GuzzleHttp\Command\Guzzle\Description;
use GuzzleHttp\Command\Guzzle\GuzzleClient;
use SudiptoChoudhury\Support\Forge\Api\Traits\Client\Importable;
use SudiptoChoudhury\support\forge\api\Traits\Client\Stackables;
use SudiptoChoudhury\Support\Utils\Traits\Dirs;

/**
 * Class Client
 *
 * @package SudiptoChoudhury\Support\Forge\Api
 */
class Client
{
    use Dirs;
    use Importable;
    use Stackables;

    protected $DEFAULT_API_JSON_PATH = './config/api.json';

    protected $DEFAULTS = [
        'description' => [
            'jsonPath' => '',
            'options' => [],
        ],
        'client' => [
            'base_uri' => '',
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ],
    ];

    protected $client;
    protected $description;
    protected $consumer;
    protected $options;
    protected $rootPath;

    /**
     * Client constructor.
     *
     * @param array $config
     */
    public function __construct($config = [])
    {
        $this->setOptions($config);
        $mergedOptions = $this->options;
        /** @var $description */
        /** @var $client */
        extract($mergedOptions);

        $this->setDescription($description);

        $this->setClient($client);

    }

    /**
     * @param array $options
     *
     * @return $this
     */
    protected function setOptions($options = [])
    {
        $myProperties = get_class_vars(__CLASS__);
        $myDefaults = $myProperties['DEFAULTS'];
        $defaults = array_replace_recursive($myDefaults, $this->DEFAULTS);

        if (empty($this->rootPath)) {
            $this->rootPath = realpath($this->getChildDir()) . '/';
        }

        $defaults['description']['jsonPath'] = realpath($this->rootPath . $this->DEFAULT_API_JSON_PATH);
        $mergedOptions = array_replace_recursive($defaults, $options);
        $parsedOptions = $this->parseOptions($mergedOptions, $options);

        $handlers = $this->getStacks($parsedOptions);
        if (!empty($handlers)) {
            if (empty($parsedOptions['client'])) {
                $parsedOptions['client'] = [];
            }
            $parsedOptions['client']['handler'] = $handlers;
        }

        $this->options = $parsedOptions;

        return $this;
    }

    /**
     * @param       $options
     * @param array $rootOption
     *
     * @return array|mixed
     */
    protected function parseOptions($options, $rootOption = [])
    {

        // @todo: need to make it is_iterable
        if (is_array($options)) {
            foreach ($options as $key => $option) {
                $options[$key] = $this->parseOptions($option, $rootOption);
            }
        } elseif (is_scalar($options)) {
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

    /**
     * @param array $descriptionOptions
     *
     * @return $this
     */
    protected function setDescription($descriptionOptions = [])
    {

        $optionsJsonPath = $this->options['description']['jsonPath'];
        /** @var $jsonPath */
        /** @var $options */
        extract($descriptionOptions);

        if ($descriptionOptions instanceof Description) {
            $this->description = $descriptionOptions;
        } else {
            if (empty($jsonPath) && !empty($optionsJsonPath)) {
                $jsonPath = realpath($optionsJsonPath);
            }

            try {
                $description = [];

                if (!empty($jsonPath)) {
                    $description = json_decode(file_get_contents($jsonPath), true);
                }
                if (empty($description)) {
                    $description = [];
                }
                if (empty($description['baseUri'])) {
                    $description['baseUri'] = $this->options['client']['base_uri'];
                }

            } catch (\Exception $ex) {
            } finally {
                $this->description = new Description($description, $options) ? : [];
            }

        }

        return $this;
    }

    /**
     * @param array $clientOptions
     *
     * @return $this
     */
    protected function createClient($clientOptions = [])
    {
        $this->client = new GHClient($clientOptions);
        return $this;
    }

    /**
     * @param array $clientOptions
     *
     * @return $this
     */
    protected function setClient($clientOptions = [])
    {
        $this->createClient($clientOptions);
        $this->consumer = new GuzzleClient($this->client, $this->description);

        return $this;
    }

    /**
     * @param $name
     * @param $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $api = $this->consumer;
        if (is_callable([$api, $name])) {
            return call_user_func_array([$api, $name], $arguments);
        }

        throw new \BadMethodCallException(sprintf('Call to undefined method %s::%s().', get_called_class(), $name));
    }

}