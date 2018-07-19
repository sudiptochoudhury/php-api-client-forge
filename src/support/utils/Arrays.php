<?php

namespace SudiptoChoudhury\Support\Utils;

class Arrays
{

    /**
     * @param array $array
     *
     * @return array
     */
    public static function flatten($array = [])
    {
        $items = [];
        foreach ($array as $value) {
            if (is_array($value)) {
                $items = array_merge($items, self::flatten($value));
            } else {
                $items[] = $value;
            }
        }
        return $items;
    }
}