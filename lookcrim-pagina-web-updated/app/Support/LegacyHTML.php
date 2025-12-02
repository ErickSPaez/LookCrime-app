<?php

namespace App\Support;

class LegacyHTML
{
    public static function style(string $path, array $attributes = []) : string
    {
        $attrs = '';
        foreach ($attributes as $k => $v) {
            $attrs .= ' ' . $k . '="' . e($v) . '"';
        }
        return '<link rel="stylesheet" href="' . asset($path) . '"' . $attrs . '>';
    }

    public static function script(string $path, array $attributes = []) : string
    {
        $attrs = '';
        foreach ($attributes as $k => $v) {
            $attrs .= ' ' . $k . '="' . e($v) . '"';
        }
        return '<script src="' . asset($path) . '"' . $attrs . '></script>';
    }

    public static function image(string $path, string $alt = '', array $attributes = []) : string
    {
        $attrs = '';
        foreach ($attributes as $k => $v) {
            $attrs .= ' ' . $k . '="' . e($v) . '"';
        }
        return '<img src="' . asset($path) . '" alt="' . e($alt) . '"' . $attrs . ' />';
    }

    public static function link(string $url, string $title = null, array $attributes = []) : string
    {
        $title = $title ?? $url;
        $attrs = '';
        foreach ($attributes as $k => $v) {
            $attrs .= ' ' . $k . '="' . e($v) . '"';
        }
        return '<a href="' . e($url) . '"' . $attrs . '>' . e($title) . '</a>';
    }
}
