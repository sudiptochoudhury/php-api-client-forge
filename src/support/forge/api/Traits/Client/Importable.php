<?php

namespace SudiptoChoudhury\Support\Forge\Api\Traits\Client;

use SudiptoChoudhury\Support\Forge\Api\Import;
use SudiptoChoudhury\Support\Forge\Api\Traits\Import\Filterable;

trait Importable
{

    use Filterable;
    protected $DEFAULT_SOURCE_JSON_PATH = './config/postman.json';
    protected $IMPORTER = Import::class;

    /**
     * @param string $source
     * @param string $destination
     * @param array  $options
     *
     * @return bool|int
     *
     * @throws \Exception
     */
    public function importApi($source = '', $destination = '', $options = [])
    {
        if (empty($source)) {
            $source = realpath($this->rootPath . $this->DEFAULT_SOURCE_JSON_PATH);
        }
        if (empty($destination)) {
            $destination = realpath($this->rootPath . $this->DEFAULT_API_JSON_PATH);
        }
        return (new $this->IMPORTER($source, $this->getAllOptions()))->writeDefinition($destination, $options);
    }

    /**
     * @return array
     */
    protected function getAllOptions()
    {
        return [
            'clientOptions' => $this->options,
            'rootPath' => $this->rootPath,
            'DEFAULT_SOURCE_JSON_PATH' => $this->DEFAULT_SOURCE_JSON_PATH,
            'DEFAULT_API_JSON_PATH' => $this->DEFAULT_API_JSON_PATH,
            'filters' => $this->getImportFilters('importFilter'),
        ];
    }

}