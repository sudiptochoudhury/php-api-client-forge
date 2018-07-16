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

    /**
     * Import constructor.
     *
     * @param string $filePath Path containing source (Postman generated) json file
     * @param array  $options
     *
     * @throws \ReflectionException
     */
    public function __construct($filePath = '', $options = [])
    {

        foreach ($options as $key => $item) {
            if (property_exists($this, $key)) {
                $this->{$key} = $item;
            }
        }

        $data = '';
        if (empty($this->rootPath)) {
            $this->rootPath = realpath($this->getChildDir()) . '/';
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

    /**
     * @param $filePath Path containing source (Postman generated) json file
     *
     * @return array
     */
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

    /**
     * @param array $request
     *
     * @return array
     */
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
            'parameters' => $this->parseParams($url, $body['raw'], $meta),
        ];
        return $data;

    }

    /**
     * @param array  $url
     * @param string $bodyRaw
     * @param array  $meta
     *
     * @return array
     */
    protected function parseParams($url = [], $bodyRaw = '', $meta = [])
    {
        $paths = $url['path'];
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

        if (!empty($queryParams = ($url['query'] ?? null))) {
            foreach ($queryParams as $query) {
                if (!empty(preg_match('/^{/', $query['value']))) {
                    $params[$query['key']] = ['location' => 'uri'];
                }
            }
        }

        return $params;

    }

    /**
     * @param       $url
     * @param array $meta
     *
     * @return string
     */
    protected function parseUri($url, $meta = [])
    {
        $uri = implode('/', array_slice($url['path'], 2));
        $queryString = '';

        if (!empty($queryParams = ($url['query'] ?? null))) {
            $queries = [];
            foreach ($queryParams as $query) {
                $queries[] = "{$query['key']}={$query['value']}";
            }
            if (!empty($queries)) {
                $queryString = '?' . implode('&', $queries);
            }
        }
        return $uri . $queryString;
    }

    /**
     * @param $api
     *
     * @return string
     */
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

    /**
     * @param        $operation
     * @param array  $api
     * @param string $method
     *
     * @return array
     */
    protected function generateDocs($operation, $api = [], $method = '')
    {

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
    protected function generateMethod($apiName, $operation = [], $api = [])
    {

        $method = [' * @method', 'array'];

        $data = "";
        $request = $api['request'];
        $description = $this->sanitizeDescription($request['description'], ['noMarkdown' => true, 'shorten' => true]);
        $params = $operation['parameters'];
        if (!empty($params)) {
            $data = 'array $parameters';
        }

        $method[] = "$apiName($data)";
        $method[] = $description;

        return implode("\t", $method);

    }

    /**
     * @param string $description
     * @param array  $options
     *
     * @return string
     */
    protected function sanitizeDescription($description = '', $options = [])
    {
        $defaults = ['noMarkdown' => false, 'shorten' => false];
        $options = array_merge($defaults, $options);

        $pre = [
            'e' => ['/\[[^]]+\]\(\)/', ], 'r' => ['',]
        ];
        $post = [
            'e' => ['/\s+/', '/^[\s]+|[\s]+$/', '/(<br\/>)+/', '/^(<br\/>)+|(<br\/>)+$/m'], 'r' =>
                [' ', '', '<br/>', '']
        ];

        $optionsReplaceMap = [
            'noMarkdown' => [
                'e' => ['/\[[^]]+\]\([^)]+\)/', '/[\r\n]+/'], 'r' => ['', ' ']
            ],
            'noMarkdown-' => [
                'e' => ['/[\r\n]+/'], 'r' => ['<br/>']
            ],
            'noMarkdownshorten' => [
                'e' => ['/\*{2,}[\s\S]+$/', '/\*+/'], 'r' => ['', '']
            ],
            'noMarkdownshorten-' => [
                'e' => ['/\*+/'], 'r' => ['']
            ],
            'noMarkdown-shorten' => [
                'e' => ['/\*{2,}[\s\S]+$/', '/<br\/>[\s\S]+$/'], 'r' => ['', '']
            ],
        ];

        if (!empty($description)) {
            $regExps = array_merge([], $pre['e']);
            $replaces = array_merge([], $pre['r']);
            foreach($options as $key => $value) {
                $value = $value ? '': '-';
                $keyValue = $key.$value;
                if (!empty($map = $optionsReplaceMap[$keyValue] ?? null)) {
                    $regExps = array_merge($regExps, $map['e']);
                    $replaces = array_merge($replaces, $map['r']);
                }
                foreach($options as $childKey => $childValue) {
                    if ($key != $childKey) {
                        $childValue = ($options[$childKey] ?? false) ? '': '-';
                        $childKeyValue = $keyValue.$childKey.$childValue;
                        if (!empty($childMap = $optionsReplaceMap[$childKeyValue] ?? null)) {
                            $regExps = array_merge($regExps, $childMap['e']);
                            $replaces = array_merge($replaces, $childMap['r']);
                        }
                    }
                }
            }
            $regExps = array_merge($regExps, $post['e']);
            $replaces = array_merge($replaces, $post['r']);

            $description = trim(preg_replace($regExps, $replaces, trim($description)));
        }

        return $description;
    }

    /**
     * @param string $string
     *
     * @return string
     */
    protected function sluggify($string = '')
    {
        if (!empty($string)) {
            $regexs = ['/[^\w\s_.-]|\s+?/', '/-+?/'];
            $replacer = ['-', ' '];
            $string = Inflector::camelize((preg_replace($regexs, $replacer, strtolower($string))));
        }
        return $string;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function getDocs()
    {
        return $this->docsData;
    }

    /**
     * @return array
     */
    public function getMethods()
    {
        return $this->methodData;
    }

    /**
     * @param string $path
     * @param array  $options
     *
     * @return bool|int
     */
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
            $pathWihtouExtension = preg_replace('/\.[^.]+?$/', '', $path);
            $this->writeMDDocs($options['docsPath'] ?? ($pathWihtouExtension . '.md'));
            $this->writeMethods($options['methodsPath'] ?? ($pathWihtouExtension . '.php'));
        }

        return file_put_contents($path, $json);
    }

    /**
     * @param string $path
     *
     * @return bool|int
     */
    protected function writeMDDocs($path = '')
    {

        if (empty($path)) {
            $path = realpath($this->rootPath . $this->DEFAULT_API_JSON_PATH);
            if (empty($path)) {
                $path = $this->rootPath . $this->DEFAULT_API_JSON_PATH;
            }
            $path = preg_replace('/\.[^.]+?$/', '.md', $path);
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
        foreach ($groups as $groupName => $group) {
            $items = $group['items'];
            foreach ($items as $apiName => $item) {
                /** @var $api */
                /** @var $operation */
                /** @var $method */
                extract($item);
                $row = [];
                $row[] = $apiName . "(" . (empty($operation['parameters']) ? '' : 'array') . ")";
                $row[] = " \[{$operation['httpMethod']}\] {$operation['uri']} ";
                $row[] = $this->sanitizeDescription($api['request']['description']);
                $row[] = implode("<br/>", array_keys($operation['parameters']));
                $row[] = '';

                $md[] = trim(implode(' | ', $row), ' ');
            }
        }

        $mdText = implode("\n", $md);

        return file_put_contents($path, $mdText);
    }

    /**
     * @param string $path
     *
     * @return bool|int
     */
    protected function writeMethods($path = '')
    {

        if (empty($path)) {
            $path = realpath($this->rootPath . $this->DEFAULT_API_JSON_PATH);
            if (empty($path)) {
                $path = $this->rootPath . $this->DEFAULT_API_JSON_PATH;
            }
            $path = preg_replace('/\.[^.]+?$/', '.php', $path);
        }
        if (file_exists($path)) {
            rename($path, $path . '.bak');
        }


        $methods = array_merge(["<?php", "/** "], $this->methodData ? : [], [" */", ""]);
        $methodText = implode("\n", $methods);

        return file_put_contents($path, $methodText);
    }

    /**
     * @return string
     * @throws \ReflectionException
     */
    private function getChildDir()
    {
        return dirname((new \ReflectionClass(static::class))->getFileName());
    }

    /**
     * @return string
     */
    private function getDir()
    {
        return __DIR__;
    }

}