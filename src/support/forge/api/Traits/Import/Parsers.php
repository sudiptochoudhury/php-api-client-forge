<?php

namespace SudiptoChoudhury\Support\Forge\Api\Traits\Import;

use Doctrine\Common\Inflector\Inflector;

trait Parsers
{
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
                $paramData = ['location' => 'uri'];
                if (empty(preg_match('/^{/', $query['value']))) {
                    $paramData['default'] = $query['value'];
                }
                $params[$query['key']] = $paramData;
            }
        }

        $this->applyFilter('Params', $params, $meta + ['body' => $bodyRaw]);

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
                if (empty(preg_match('/^{/', $query['value']))) {
                    $query['value'] = '{' . $query['key'] . '}';
                }
                $queries[] = "{$query['key']}={$query['value']}";
            }
            if (!empty($queries)) {
                $queryString = '?' . implode('&', $queries);
            }
        }

        $uriFinal = $uri . $queryString;
        $this->applyFilter('Uri', $uriFinal, $meta);
        return $uriFinal;
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
            'e' => ['/\[[^]]+\]\(\)/',], 'r' => ['',],
        ];
        $post = [
            'e' => ['/\s+/', '/^[\s]+|[\s]+$/', '/(<br\/>)+/', '/^(<br\/>)+|(<br\/>)+$/m'], 'r' =>
                [' ', '', '<br/>', ''],
        ];

        $optionsReplaceMap = [
            'noMarkdown' => [
                'e' => ['/\[[^]]+\]\([^)]+\)/', '/[\r\n]+/'], 'r' => ['', ' '],
            ],
            'noMarkdown-' => [
                'e' => ['/[\r\n]+/', '/\|/'], 'r' => ['<br/>', '\|'],
            ],
            'noMarkdownshorten' => [
                'e' => ['/\*{2,}[\s\S]+$/', '/\*+/'], 'r' => ['', ''],
            ],
            'noMarkdownshorten-' => [
                'e' => ['/\*+/'], 'r' => [''],
            ],
            'noMarkdown-shorten' => [
                'e' => ['/\*{2,}[\s\S]+$/', '/<br\/>[\s\S]+$/'], 'r' => ['', ''],
            ],
        ];

        if (!empty($description)) {
            $regExps = array_merge([], $pre['e']);
            $replaces = array_merge([], $pre['r']);
            foreach ($options as $key => $value) {
                $value = $value ? '' : '-';
                $keyValue = $key . $value;
                if (!empty($map = $optionsReplaceMap[$keyValue] ?? null)) {
                    $regExps = array_merge($regExps, $map['e']);
                    $replaces = array_merge($replaces, $map['r']);
                }
                foreach ($options as $childKey => $childValue) {
                    if ($key != $childKey) {
                        $childValue = ($options[$childKey] ?? false) ? '' : '-';
                        $childKeyValue = $keyValue . $childKey . $childValue;
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
     * @param $apiName
     * @param $operation
     * @param $api
     * @param $method
     *
     * @return string
     */
    protected function getMDApiName($apiName, $operation, $api, $method)
    {
        $docApiName = $apiName . "(" . (empty($operation['parameters']) ? '' : 'array') . ")";
        $docApiName = $this->applyFilter('DocMDName', $docApiName, compact('apiName', 'operation',
            'api', 'method'));
        return $docApiName;
    }

    /**
     * @param $operation
     * @param $api
     * @param $method
     *
     * @return string
     */
    protected function getMDApiEndpoint($apiName, $operation, $api, $method)
    {
        $anchor = $this->applyFilter('DocMDApiExternalLink', 'API DOC');
        $description = $api['request']['description'];
        $matched = preg_match('/\[' . $anchor . '\]\([^)]+\)/', $description, $apiDocUrl);
        $docApiEndpoint = " \[{$operation['httpMethod']}\] /{$operation['uri']} ";
        if (!empty($matched) && isset($apiDocUrl[0])) {
            $docApiEndpoint = str_replace($anchor, $docApiEndpoint, $apiDocUrl[0]);
        }
        $docApiEndpoint = $this->applyFilter('DocMDEndpoint', $docApiEndpoint, compact('operation', 'api', 'method'));
        return $docApiEndpoint;
    }

    /**
     * @param $operation
     * @param $api
     * @param $method
     *
     * @return string
     */
    protected function getMDDescription($apiName, $operation, $api, $method)
    {
        $description = $this->sanitizeDescription($api['request']['description']);
        $description = $this->applyFilter('DocMDDescription', $description,
            compact('operation', 'api', 'method'));
        return $description;
    }



    /**
     * @param $operation
     * @param $api
     * @param $method
     *
     * @return array
     */
    protected function getMDApiParams($apiName, $operation, $api, $method)
    {
        $params = array_keys($operation['parameters']);
        $params = $this->applyFilter('DocMDParams', $params, compact('operation', 'api', 'method'));
        return $params;
    }


}