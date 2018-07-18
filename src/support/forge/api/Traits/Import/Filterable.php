<?php

namespace SudiptoChoudhury\Support\Forge\Api\Traits\Import;


trait Filterable
{

    protected $filters = [];
    protected $filtersCategorized = [];

    /**
     * @param string $prefix
     *
     * @return array
     */
    private function getImportFilters($prefix = 'filter')
    {
        $filters = [];
        try {
            $methods = (new \ReflectionClass(static::class))->getMethods();
            foreach ($methods as $methodDetails) {
                $methodName = $methodDetails->name;
                if (stripos($methodName, $prefix) === 0) {
                    $filters[] = [$this, $methodName];
                }
            }

        } catch (\ReflectionException $ex) {

        }
        return $filters;
    }

    /**
     * @return array
     */
    private function categorizeFilters()
    {
        $categories = [];
        $filters = $this->filters;
        if (!empty($filters)) {
            foreach ($filters as $method) {
                $filterName = strtolower(preg_replace(['/^(import)?[Ff]ilter/', '/_.+$/'], ['', ''], $method[1]));
                if (!isset($categories[$filterName])) {
                    $categories[$filterName] = [];
                }
                $categories[$filterName][] = $method;
            }
        }
        $this->filtersCategorized = $categories;
        return $categories;
    }

    /**
     * @param       $filterName
     * @param       $value
     * @param array $helperData
     *
     * @return mixed
     */
    private function applyFilter($filterName, $value, $helperData = [])
    {
        $categories = $this->filtersCategorized;
        $filterName = strtolower($filterName);
        if (!empty($filters = $categories[$filterName] ?? null)) {
            foreach ($filters as $method) {
                $oldValue = $value;
                try {
                    $value = call_user_func($method, $value, $helperData);
                } catch (\Exception $ex) {
                    var_dump("Error: " . $ex->getMessage());
                    $value = $oldValue;
                }
            }
        }
        return $value;
    }
}