<?php

namespace SudiptoChoudhury\Support\Forge\Api\Traits\Import;


trait DefaultFilters
{

    /**
     * @param $default
     * @param $details
     *
     * @return array
     */
    public function filterDocMDLayoutTitle($default, $details)
    {
        $md = ['##' . $details['data']['title']];
        $md[] = "";
        return $md;
    }

    /**
     * @param $default
     * @param $details
     *
     * @return array
     */
    protected function filterDocMDLayoutHeader($default, $details)
    {
        $md = [""];
        $md[] = "### Available API Methods";
        $md[] = "";
        return $md;
    }

    /**
     * @param $default
     * @param $details
     *
     * @return array
     */
    protected function filterDocMDLayoutTable($default, $details)
    {
        $md = [];
        /** @var $data */
        /** @var $definition */
        /** @var $source */
        extract($details);

        $md[] = "| Method & Endpoint | Parameters | Description |";
        $md[] = "|-------------------|------------|-------------|";
        $groups = $data['groups'];
        foreach ($groups as $groupName => $group) {
            $items = $group['items'];
            foreach ($items as $apiName => $item) {
                /** @var $method string the function name */
                /** @var $endpoint */
                /** @var $parameters */
                /** @var $description */
                extract($item);
                $row = [];
                $row[] = implode('<br/>', [$method, $endpoint]);
                $row[] = implode("<br/>", $parameters);
                $row[] = $description;
                $row[] = '';

                $md[] = trim(implode(' | ', $row), ' ');
            }
        }

        return $md;
    }

}