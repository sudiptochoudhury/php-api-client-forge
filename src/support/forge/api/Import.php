<?php

namespace SudiptoChoudhury\Support\Forge\Api;

use Doctrine\Common\Inflector\Inflector;

/**
 * Class Import
 *
 * @package SudiptoChoudhury\Support\Forge\Api
 */
class Import
{
    protected $data = [];
    protected $sourceData = [];
    protected $docsData = [];
    protected $methodData = [];
    protected $options = [];
    protected $clientOptions = [];
    protected $rootPath;

    protected $DEFAULT_API_JSON_PATH = './config/api.json';
    protected $DEFAULT_SOURCE_JSON_PATH = './config/postman.json';

    public function __construct($filePath = '', $options = [])
    {

        foreach($options as $key => $item) {
            if (property_exists($this, $key)) {
                $this->{$key} = $item;
            }
        }

        $data = '';
        if (empty($this->rootPath)) {
            $this->rootPath = realpath($this->getChildDir()). '/';
        }
        $rootPath = $this->rootPath;

        if (!file_exists($filePath)) {
            $filePath = realpath($rootPath . '/' . $filePath);
        }
        if (!file_exists($filePath)) {
            $filePath = realpath($rootPath . $this->DEFAULT_SOURCE_JSON_PATH);
        }
        if (file_exists($filePath)) {
            $data = $this->importFromJson($filePath);
        }
        $this->data = $data;
        $this->options = $options;
    }

    protected function importFromJson($filePath)
    {

        if (!file_exists($filePath)) {
            $filePath = realpath($this->rootPath . '/' . $filePath);
        }

        $this->sourceData = $json = \GuzzleHttp\json_decode(file_get_contents($filePath), true);
        $data = [];
        $docs = [];
        $methods = [];

        if (!empty($json)) {
            $globalInfo = $json['info'];
            $globalInfoName = $globalInfo['name'];
            $items = $json['item'];

            $operations = [];
            foreach ($items as $itemIndex => $apiGroup) {

                $apiGroupName = $apiGroup['name'];
                $apiGroupDescription = $apiGroup['description'];
                if (empty($docs[$apiGroupName])) {
                    $docs[$apiGroupName] = ['description' => $apiGroupDescription, 'items' => []];
                }

                $apiGroupItems = $apiGroup['item'];
                foreach ($apiGroupItems as $apiIndex => $api) {
                    $apiName = $this->parseApiName($api);
                    $request = $api['request'];
                    $operation = $this->parseOperation($request);
                    if (isset($operations[$apiName])) {
                        $apiName = $this->sluggify($api['name']);
                    }
                    $operations[$apiName] = $operation;

                    $methods[$apiName] = $this->generateMethod($apiName, $operation, $api);
                    $docs[$apiGroupName]['items'][$apiName] = $this->generateDocs($operation, $api, $methods[$apiName]);

                }
            }

            if (!empty($operations)) {
                $data = compact('operations');
                $data['models'] = [
                    'getResponse' => [
                        'type' => 'object',
                        'additionalProperties' => [
                            'location' => 'json',
                        ],
                    ],
                ];
            }

            $this->docsData = ['title' => $globalInfoName, 'groups' => $docs];
            $this->methodData = $methods;
        }

        return $data;
    }

    protected function parseOperation($request = [])
    {
        $meta = [$request];
        $method = $request['method'];
//        $header = $request['header'];
        $body = $request['body'];
        $url = $request['url'];
        $data = [
            'httpMethod' => $method,
            'uri' => $this->parseUri($url, $meta),
            'responseModel' => 'getResponse',
            'parameters' => $this->parseParams($url['path'], $body['raw'], $meta),
        ];
        return $data;

    }

    protected function parseParams($paths = [], $bodyRaw = '', $meta = [])
    {
        $params = [];
        foreach ($paths as $path) {
            if (!!preg_match('/^{/', $path) !== false) {
                $paramName = preg_replace('/^{|}$/', '', $path);
                $params[$paramName] = [
                    'location' => 'uri', //query
                    // 'type' => 'string',
                    // 'default' => '',
                    // 'required' => 'false',
                    // 'sentAs' => '',
                    // 'instanceOf' => '',
                    // 'filters' => [],
                ];

            }
        }
        $rawParams = json_decode($bodyRaw, true);
        if (!empty($rawParams)) {
            foreach ($rawParams as $paramName => $paramItem) {
                $params[$paramName] = [
                    'location' => 'json', //formParam//json//body
                    // 'type' => 'string',
                    // 'default' => '',
                    // 'required' => 'false',
                    // 'sentAs' => '',
                    // 'instanceOf' => '',
                    // 'filters' => [],
                ];
            }
        }
        return $params;

    }

    protected function parseUri($url, $meta = [])
    {
        return implode('/', array_slice($url['path'], 2));
    }

    protected function parseApiName($api)
    {

        $methodMap = [
            'get' => 'get',
            'post' => 'add',
            'put' => 'update',
            'delete' => 'delete',
        ];

        //        $slug = $this->sluggify($api['name']);
        $request = $api['request'];
        $method = $methodMap[strtolower($request['method'])] ?? '';
        $url = $request['url'];
        $path = array_slice($url['path'], 2);
        $countPaths = count($path);

        $parts = [$method];
        if ($countPaths == 1 && $method !== 'get') {
            $parts[] = Inflector::singularize($path[0]);
        } else {
            foreach ($path as $index => $value) {
                if ($index && preg_match('/^{/', $value)) {
                    $path[$index - 1] = Inflector::singularize($path[$index - 1]);
                }
            }
            foreach ($path as $index => $value) {
                if (empty(preg_match('/^{/', $value))) {
                    $parts[] = $value;
                }
            }
        }

        $names = implode(' ', $parts);
        return Inflector::camelize($names);

    }

    protected function generateDocs($operation, $api = [], $method = '') {

//        $baseUri  = $this->clientOptions['base_url'] ?: '';

//        $name = $api['name'];
//        $request = $api['request'];
//        $response = $api['response'];
        /** @var $method */
        /** @var $header */
        /** @var $body */
        /** @var $url */
        /** @var $description */
//        extract($request);

        return compact('operation', 'api', 'method');

    }

    /**
     * @param       $apiName
     * @param array $operation
     * @param array $api
     *
     * @return string
     */
    protected function generateMethod($apiName, $operation = [], $api = []) {

        $method = [' * @method static array'];

        $data = "";
        $request = $api['request'];
        $description =  preg_replace('/[\r\n]/', '', $request['description']);
        $params = $operation['parameters'];
        if (!empty($params)) {
            $data = 'array $parameters';
        }

        $method[] = "$apiName($data)";
        $method[] = $description;

        return implode("\t", $method);

    }

    protected function sluggify($string = '')
    {
        if (!empty($string)) {
            $regexs = ['/[^\w\s_.-]|\s+?/', '/-+?/'];
            $replacer = ['-', ' '];
            $string = Inflector::camelize((preg_replace($regexs, $replacer, strtolower($string))));
        }
        return $string;
    }


    public function getData()
    {
        return $this->data;
    }

    public function getDocs()
    {
        return $this->docsData;
    }

    public function getMethods()
    {
        return $this->methodData;
    }

    public function writeData($path = '', $options = [])
    {
        if (empty($path)) {
            $path = realpath($this->rootPath . $this->DEFAULT_API_JSON_PATH);
            if (empty($path)) {
                $path = $this->rootPath . $this->DEFAULT_API_JSON_PATH;
            }
        }
        if (file_exists($path)) {
            rename($path, $path . '.bak');
        }
        $json = \GuzzleHttp\json_encode($this->data, JSON_PRETTY_PRINT | JSON_ERROR_NONE | JSON_UNESCAPED_SLASHES);

//        $options['skipDocs'] = true;
        if (empty($options['skipDocs'])) {
            $this->writeMDDocs($options['docsPath'] ?? $path . '.md');
            $this->writeMethods($options['methodsPath'] ?? $path . '.php');
        }

        return file_put_contents($path, $json);
    }


    protected function writeMDDocs($path = '') {

        if (empty($path)) {
            $path = realpath($this->rootPath . $this->DEFAULT_API_JSON_PATH. '.md');
            if (empty($path)) {
                $path = $this->rootPath . $this->DEFAULT_API_JSON_PATH. '.md';
            }
        }
        if (file_exists($path)) {
            rename($path, $path . '.bak');
        }

        $data = $this->docsData;

        $md = ['##' . $data['title']];
        $md[] = "";
        $md[] = "### Available API Methods";
        $md[] = "";
        $md[] = "| Method             | [method]Endpoint     | Description    | Parameters |";
        $md[] = "|--------------------|----------------------|----------------|------------|";

        $groups = $data['groups'];
        foreach($groups as $groupName => $group) {
            $items = $group['items'];
            foreach ($items as $apiName => $item) {
                /** @var $api */
                /** @var $operation */
                /** @var $method */
                extract($item);
                $row = [];
                $row[] = $apiName . "(" . (empty($operation['parameters']) ? '' : 'Array') . ")";
                $row[] = " \[{$operation['httpMethod']}\] {$operation['uri']} ";
                $row[] = preg_replace('/[\r\n]/', '', $api['request']['description']);
                $row[] = implode("<br/>", array_keys($operation['parameters']));
                $row[] = '';

                $md[] = trim(implode(' | ', $row), ' ');
            }
        }

        $mdText = implode("\n", $md);

        return file_put_contents($path, $mdText);
    }

    protected function writeMethods($path = '') {

        if (empty($path)) {
            $path = realpath($this->rootPath . $this->DEFAULT_API_JSON_PATH. '.php');
            if (empty($path)) {
                $path = $this->rootPath . $this->DEFAULT_API_JSON_PATH. '.php';
            }
        }
        if (file_exists($path)) {
            rename($path, $path . '.bak');
        }

        $methods = $this->methodData ?: [];
        $methodText = implode("\n", $methods);

        return file_put_contents($path, $methodText);
    }

    private function getChildDir() {
        return dirname((new \ReflectionClass(static::class))->getFileName());
    }
    private function getDir() {
        return __DIR__;
    }

}