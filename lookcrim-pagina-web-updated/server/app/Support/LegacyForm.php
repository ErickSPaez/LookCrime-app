<?php

namespace App\Support;

use Illuminate\Support\Str;

class LegacyForm
{
    protected static $model = null;

    public static function open(array $options = []) : string
    {
        $method = strtoupper($options['method'] ?? 'POST');
        $action = $options['url'] ?? ($options['action'] ?? url()->current());
        $enctype = isset($options['files']) && $options['files'] ? ' enctype="multipart/form-data"' : '';
        $formMethod = $method === 'GET' || $method === 'POST' ? $method : 'POST';

        $html = '<form method="' . e(strtolower($formMethod)) . '" action="' . e($action) . '"' . $enctype . '>' . PHP_EOL;
        if ($formMethod === 'POST') {
            $html .= csrf_field() . PHP_EOL;
        }
        if ($method !== $formMethod) {
            $html .= method_field($method) . PHP_EOL;
        }
        return $html;
    }

    public static function close() : string
    {
        // clear any bound model after closing
        self::$model = null;
        return '</form>';
    }

    public static function submit($value = 'Submit', $options = []) : string
    {
        $attrs = '';
        foreach ($options as $k => $v) {
            $attrs .= ' ' . $k . '="' . e($v) . '"';
        }
        return '<button type="submit"' . $attrs . '>' . e($value) . '</button>';
    }

    public static function label($name, $value = null, $options = []) : string
    {
        $attrs = '';
        foreach ($options as $k => $v) {
            $attrs .= ' ' . $k . '="' . e($v) . '"';
        }
        return '<label for="' . e($name) . '"' . $attrs . '>' . e($value ?? Str::title($name)) . '</label>';
    }

    public static function text($name, $value = null, $options = []) : string
    {
        $attrs = '';
        foreach ($options as $k => $v) {
            $attrs .= ' ' . $k . '="' . e($v) . '"';
        }
        // model binding: if value not provided, try bound model, then old input
        if ($value === null) {
            if (self::$model && isset(self::$model->{$name})) {
                $value = self::$model->{$name};
            } else {
                $value = old($name);
            }
        }
        return '<input type="text" name="' . e($name) . '" value="' . e($value) . '"' . $attrs . ' />';
    }

    public static function textarea($name, $value = null, $options = []) : string
    {
        $attrs = '';
        foreach ($options as $k => $v) {
            $attrs .= ' ' . $k . '="' . e($v) . '"';
        }
        if ($value === null) {
            if (self::$model && isset(self::$model->{$name})) {
                $value = self::$model->{$name};
            } else {
                $value = old($name);
            }
        }
        return '<textarea name="' . e($name) . '"' . $attrs . '>' . e($value) . '</textarea>';
    }

    public static function model($model, array $options = []) : string
    {
        self::$model = $model;
        return self::open($options);
    }

    public static function hidden($name, $value = null) : string
    {
        if ($value === null) {
            if (self::$model && isset(self::$model->{$name})) {
                $value = self::$model->{$name};
            } else {
                $value = old($name);
            }
        }
        return '<input type="hidden" name="' . e($name) . '" value="' . e($value) . '" />';
    }

    public static function file($name, $options = []) : string
    {
        $attrs = '';
        foreach ($options as $k => $v) {
            $attrs .= ' ' . $k . '="' . e($v) . '"';
        }
        return '<input type="file" name="' . e($name) . '"' . $attrs . ' />';
    }
}
