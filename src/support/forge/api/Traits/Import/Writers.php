<?php

namespace SudiptoChoudhury\Support\Forge\Api\Traits\Import;

use SudiptoChoudhury\Support\Utils\Arrays;


trait Writers
{

    /**
     * @param string $path
     * @param array  $options
     *
     * @return bool|int
     */
    public function writeDefinition($path = '', $options = [])
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

        $data = $this->data;
        $filterParams = ['data' => $this->data, 'definition' => $this->data, 'source' =>
            $this->sourceData];
        $data = $this->applyFilter('APIDefinitionFinalJson', $data, $filterParams);

        $json = \GuzzleHttp\json_encode($data, JSON_PRETTY_PRINT | JSON_ERROR_NONE | JSON_UNESCAPED_SLASHES);

        //        $options['skipDocs'] = true;
        if (empty($options['skipDocs'])) {
            $pathWihtouExtension = preg_replace('/\.[^.]+?$/', '', $path);
            $this->writeMarkdownDocs($options['docsPath'] ?? ($pathWihtouExtension . '.md'));
            $this->writePHPDocMethod($options['methodsPath'] ?? ($pathWihtouExtension . '.php'));
        }

        return file_put_contents($path, $json);
    }

    /**
     * @param string $path
     *
     * @return bool|int
     */
    public function writePHPDocMethod($path = '')
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

        $data = $this->methodData;
        $filterParams = ['data' => $data, 'definition' => $this->data, 'source' =>
            $this->sourceData];

        $methods = array_merge(["<?php", "/** "], $data ? : [], [" */", ""]);
        $methods = $this->applyFilter('DocMethodFinalArray', $methods, $filterParams);
        $methodText = implode("\n", $methods);
        $methodText = $this->applyFilter('DocMethodFinalText', $methodText, $filterParams);

        return file_put_contents($path, $methodText);
    }

    /**
     * @param string $path
     *
     * @return bool|int
     */
    public function writeMarkdownDocs($path = '')
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

        $data = $this->mdDocsData;

        $layout = ['title', 'subtitle', 'header', 'menu', 'description', 'requirements', 'install',
            'getStarted', 'setup', 'examples', 'table', 'footer', 'notes', 'references', 'contact', 'donate'];

        $layoutsData = [];
        $filterParams = ['data' => $data, 'definition' => $this->data, 'source' => $this->sourceData];
        foreach ($layout as $layoutName) {
            $filterName = 'DocMDLayout' . ucfirst($layoutName);
            $layoutSectionDocs = $this->applyFilter($filterName, [], $filterParams);
            $layoutsData[$layoutName] = $layoutSectionDocs;
        }

        $layoutsData = $this->applyFilter('DocMDFinalLayoutArray', $layoutsData, $filterParams);

        $finalLayoutArray = Arrays::flatten($layoutsData);
        $mdText = $this->applyFilter('DocMDFinalText', implode("\n", $finalLayoutArray), $filterParams);

        return file_put_contents($path, $mdText);
    }
}