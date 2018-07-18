<?php

namespace SudiptoChoudhury\Support\Forge\Api;

use SudiptoChoudhury\Support\Forge\Api\Traits\Import\AllTraits;

/**
 * Class Import
 *
 * @package SudiptoChoudhury\Support\Forge\Api
 */
class Import
{
    use AllTraits;

    protected $data = [];
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
     */
    public function __construct($filePath = '', $options = [])
    {

        foreach ($options as $key => $item) {
            if (property_exists($this, $key)) {
                $this->{$key} = $item;
            }
        }

        $moreFilters = $this->getImportFilters();
        if (!empty($moreFilters)) {
            $this->filters = array_merge($this->filters, $moreFilters);
        }
        $this->categorizeFilters();

        $data = '';
        if (empty($this->rootPath)) {
            $this->rootPath = realpath($this->getChildDir()) . '/';
        }
        $rootPath = $this->rootPath;

        if (!file_exists($filePath) || is_dir($filePath)) {
            $filePath = realpath($rootPath . '/' . $filePath);
        }
        if (!file_exists($filePath) || is_dir($filePath)) {
            $filePath = realpath($rootPath . $this->DEFAULT_SOURCE_JSON_PATH);
        }
        if (!file_exists($filePath) || is_dir($filePath)) {
            $filePath = $rootPath . $this->DEFAULT_SOURCE_JSON_PATH;
        }
        if (file_exists($filePath) && !is_dir($filePath)) {
            $data = $this->importFromJson($filePath);
        }

        $this->data = $data;
        $this->options = $options;
    }

    /**
     * @param $filePath string Path containing source (Postman generated) json file
     *
     * @return array
     */
    protected function importFromJson($filePath)
    {

        if (!file_exists($filePath) || is_dir($filePath)) {
            $filePath = realpath($this->rootPath . '/' . $filePath);
        }
        if (!file_exists($filePath) || is_dir($filePath)) {
            $filePath = $this->rootPath . '/' . $filePath;
        }

        $this->sourceData = $json = \GuzzleHttp\json_decode(file_get_contents($filePath), true);
        $data = [];
        $docs = [];
        $methods = [];

        if (!empty($json)) {
            $globalInfo = $json['info'];
            $globalInfoName = $this->applyFilter('Title', $globalInfo['name']);
            $items = $json['item'];

            $operations = [];
            foreach ($items as $itemIndex => $apiGroup) {

                $apiGroupName = $this->applyFilter('GroupName', $apiGroup['name']);
                $apiGroupDescription = $this->applyFilter('GroupDescription', $apiGroup['description']);
                if (empty($docs[$apiGroupName])) {
                    $docs[$apiGroupName] = ['description' => $apiGroupDescription, 'items' => []];
                }

                $apiGroupItems = $apiGroup['item'];
                foreach ($apiGroupItems as $apiIndex => $api) {
                    $apiName = $this->applyFilter('Name', $this->parseApiName($api), compact('api', 'apiGroup'));
                    $request = $api['request'];
                    $operation = $this->applyFilter('Operation', $this->parseOperation($request), compact('api', 'apiGroup'));
                    if (isset($operations[$apiName])) {
                        $apiName = $this->applyFilter('Slug', $this->sluggify($api['name']), compact('api', 'apiGroup'));
                    }
                    $apiName = $this->applyFilter('FinalName', $apiName, compact('api', 'apiGroup'));
                    $operations[$apiName] = $operation;

                    $methods[$apiName] = $this->applyFilter('DocMethodItem',
                        $this->generateMethod($apiName, $operation, $api));
                    $docs[$apiGroupName]['items'][$apiName] = $this->applyFilter('DocMDItem',
                        $this->generateDocs($apiName, $operation, $api, $methods[$apiName]));

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

            $this->mdDocsData = ['title' => $globalInfoName, 'groups' => $docs];
            $this->methodData = $methods;
        }

        return $data;
    }

    /**
     * @param        $apiName
     * @param        $operation
     * @param array  $api
     * @param string $method string Function Method for PHPDoc
     *
     * @return array
     */
    protected function generateDocs($apiName, $operation, $api = [], $method = '')
    {

        $row = [];
        $row['method'] = $this->getMDApiName($apiName, $operation, $api, $method);
        $row['endpoint'] = $this->getMDApiEndpoint($apiName, $operation, $api, $method);
        $row['parameters'] = $this->getMDApiParams($apiName, $operation, $api, $method);
        $row['description'] = $this->getMDDescription($apiName, $operation, $api, $method);

        return $row;

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
        $description = $this->applyFilter('DocMethodDescription',
            $this->sanitizeDescription($request['description'], ['noMarkdown' => true, 'shorten' => true]));
        $params = $this->applyFilter('DocMethodParams', $operation['parameters']);
        if (!empty($params)) {
            $data = $this->applyFilter('DocMethodData', 'array $parameters', $params);
        }

        $method[] = $this->applyFilter('DocMethodSignature', "$apiName($data)", compact('apiName', 'params'));
        $method[] = $description;

        return implode("\t", $method);

    }

}