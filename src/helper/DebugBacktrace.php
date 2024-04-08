<?php

namespace yiqiang3344\yii2_lib\helper;


/**
 * 追溯记录处理类
 */
class DebugBacktrace
{
    public static function getTraces($traces, $removeNo = 0, $isReverse = true, $extractVendor = true)
    {
        if (count($traces) > $removeNo) {
            $traces = array_slice($traces, $removeNo);
        }
        $traces = $isReverse ? array_reverse($traces) : $traces;
        $data = [];
        foreach ($traces as $k => $v) {
            if ($extractVendor && isset($v['file']) && strpos($v['file'], 'vendor') !== false) {
                continue;
            }

            $class = $v['class'] ?? '';
            $file = self::handleFileName($v['file'] ?? '');
            $line = $v['line'] ?? '';
            $func = $v['function'] ?? '';
            $args = $v['args'] ?? [];
            $type = $v['type'] ?? '';

            $traceLine = $file ?: '';
            $traceLine .= $line ? (':' . $line) : '';
            $traceLine .= ($class . $type . $func) ? (($traceLine ? ' ' : '') . $class . $type . $func) : '';
            $traceLine .= '(' . static::setArgs($args) . ')';
            array_push($data, $traceLine);
        }

        return $data;
    }

    public static function handleFileName($fileName)
    {
        $pos = defined('PROJECT_NAME') ? strrpos($fileName, PROJECT_NAME) : false;
        return $pos !== false ? substr($fileName, $pos) : $fileName;
    }

    protected static function setArgs(array $args)
    {
        foreach ($args as $key => $arg) {
            if (is_object($arg)) {
                $args[$key] = get_class($arg);
            } elseif (is_resource($arg)) {
                $args[$key] = '#Resource';
            } elseif (is_null($arg)) {
                $args[$key] = 'null';
            } elseif (false === $arg) {
                $args[$key] = 'false';
            } elseif (true === $arg) {
                $args[$key] = 'true';
            } elseif (is_callable($arg)) {
                $args[$key] = '#Closure';
            } elseif (is_array($arg)) {
                $args[$key] = static::setArrayArg($arg);
            } elseif (is_string($arg)) {
                $args[$key] = '"' . $arg . '"';
            }
        }

        return implode(',', $args);
    }

    protected static function setArrayArg(array $args)
    {
        foreach ($args as $key => $arg) {
            if (is_object($arg)) {
                $args[$key] = get_class($arg);
            } elseif (is_resource($arg)) {
                $args[$key] = '#Resource';
            } elseif (is_null($arg)) {
                $args[$key] = 'null';
            } elseif (false === $arg) {
                $args[$key] = 'false';
            } elseif (true === $arg) {
                $args[$key] = 'true';
            } elseif (is_callable($arg)) {
                $args[$key] = '#Closure';
            } elseif (is_array($arg)) {
                $args[$key] = 'array';
            } elseif (is_string($arg)) {
                $args[$key] = '"' . $arg . '"';
            }
        }

        return '[' . implode(',', $args) . ']';
    }
}