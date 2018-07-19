<?php

namespace SudiptoChoudhury\Support\Forge\Api\Traits\Import;


trait Getters
{

    protected $sourceData = [];
    protected $mdDocsData = [];
    protected $methodData = [];

    /**
     * @return array
     */
    public function getApiDefinition()
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function getSourceJson()
    {
        return $this->sourceData;
    }

    /**
     * @return array
     */
    public function getMarkdownDocs()
    {
        return $this->mdDocsData;
    }

    /**
     * @return array
     */
    public function getPHPDocMethods()
    {
        return $this->methodData;
    }

}