<?php

namespace alina\utils;

class Str
{
    static public function startsWith($haystack, $needle)
    {
        $length = mb_strlen($needle);

        return (mb_substr($haystack, 0, $length) === (string)$needle);
    }

    static public function endsWith($haystack, $needle)
    {
        $length = mb_strlen($needle);
        if ($length == 0) {
            return TRUE;
        }

        return (mb_substr($haystack, -$length) === (string)$needle);
    }

    static public function ifContains($haystack, $needle)
    {
        return mb_stripos($haystack, $needle);
    }

    static public function removeEnters($haystack, $needle = '')
    {
        return $string = str_replace(["\n", "\r"], $needle, $haystack);
    }
}
