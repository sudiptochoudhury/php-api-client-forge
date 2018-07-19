<?php

namespace SudiptoChoudhury\Support\Utils\Traits;

trait Dirs
{
    /**
     * @return string
     */
    private function getChildDir()
    {
        try {
            return dirname((new \ReflectionClass(static::class))->getFileName());
        } catch (\ReflectionException $ex) {
            return '/../';
        }
    }

}