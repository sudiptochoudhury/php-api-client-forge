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

        $this->sourceData = $json = $this->readJson($filePath);
        $data = [];
        $docs = [];
        $methods = [];
        $operations = [];

        if (!empty($json)) {

            $globalInfo = $this->readGlobalInfo($json);
            $globalTitle = $this->readGlobalTitle($globalInfo);

            $items = $json['item'];

            foreach ($items as $itemIndex => $apiGroup) {

                $apiGroupName = $this->applyFilter('GroupName', $apiGroup['name']);
                $apiGroupDescription = $this->applyFilter('GroupDescription', $apiGroup['description']);
                if (empty($docs[$apiGroupName])) {
                    $docs[$apiGroupName] = ['description' => $apiGroupDescription, 'items' => []];
                } else {
                    // group already exists
                }

                $apiGroupItems = $apiGroup['item'];
                foreach ($apiGroupItems as $apiIndex => $api) {

                    $commonParams = compact('api', 'apiIndex',
                        'apiGroupItems', 'apiGroup', 'operations') +
                            [
                                'groupIndex' => $itemIndex,
                                'info' => $globalInfo,
                                'allItems' => $items
                            ];

                    $operation = $this->readOperationItem($commonParams);
                    $commonParams['operation'] = $operation;

                    $commonParams['apiName'] = $apiName = $this->readApiName($commonParams);
                    $operations[$apiName] = $operation;

                    $commonParams['apiDocMethod'] = $methods[$apiName] = $this->readPhpDocMethodItem($commonParams);
                    $docs[$apiGroupName]['items'][$apiName] =$this->readMarkdownItem($commonParams);

                }
            }

            if (!empty($operations)) {
                $data = compact('operations');
                $data['models'] = $this->getOperationModels($operations);
            }

            $this->mdDocsData = ['title' => $globalTitle, 'groups' => $docs];
            $this->methodData = $methods;
        }

        return $data;
    }

    /**
     * @param array $params
     *
     * @return mixed|string
     */
    protected function readApiName($params = []) {
        /** @var $api */
        /** @var $apiGroup */
        /** @var $operations */
        extract($params);
        $apiName = $this->applyFilter('Name', $this->parseApiName($api), compact('api', 'apiGroup'));
        if (isset($operations[$apiName])) {
            var_dump("Exists...$apiName");
            $apiName = $this->applyFilter('Slug', $this->sluggify($api['name']), compact('api', 'apiGroup'));
            var_dump('      named...'. $apiName);
        }
        $apiName = $this->applyFilter('FinalName', $apiName, compact('api', 'apiGroup'));
        if (isset($operations[$apiName])) {
            var_dump("Still exists...replacing ... $apiName with {$apiName}_new ");
            $apiName .= '_new';
        }
        return $apiName;
    }

    /**
     * @param array $params
     *
     * @return array|mixed
     */
    protected function readOperationItem($params = []) {
        /** @var $api */
        extract($params);
        $request = $api['request'];
        $operation = $this->parseOperation($request);
        $operation = $this->applyFilter('Operation', $operation, $params);
        return $operation;
    }

    /**
     * @param array $params
     *
     * @return mixed|string
     */
    protected function readPhpDocMethodItem($params = []) {
        /** @var $api */
        /** @var $apiName */
        /** @var $operation */
        extract($params);
        $phpDocMethod = $this->generateMethod($apiName, $operation, $api);
        $phpDocMethod = $this->applyFilter('DocMethodItem', $phpDocMethod, $params);
        return $phpDocMethod;
    }

    /**
     * @param array $params
     *
     * @return array|mixed
     */
    protected function readMarkdownItem($params = []) {
        /** @var $apiName */
        /** @var $api */
        /** @var $operation */
        /** @var $apiDocMethod */
        extract($params);

        $md = $this->generateDocs($apiName, $operation, $api, $apiDocMethod);
        $md = $this->applyFilter('DocMDItem', $md, $params);
        return $md;
    }

    /**
     * @param array $params
     *
     * @return array
     */
    protected function getOperationModels($params = []) {
        return [
            'getResponse' => [
                'type' => 'object',
                'additionalProperties' => [
                    'location' => 'json',
                ],
            ],
        ];
    }

    /**
     * @param array $params
     *
     * @return mixed
     */
    protected function readGlobalInfo($params = []) {
        return $params['info'];
    }

    /**
     * @param array $params
     *
     * @return mixed
     */
    protected function readGlobalTitle($params = []) {
    return $this->applyFilter('Title', $params['name']);
    }


}