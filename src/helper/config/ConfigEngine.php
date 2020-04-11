<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/4/2
 * Time: 9:32 AM
 */

namespace yiqiang3344\yii2_lib\helper\config;


use yii\base\Exception;
use yii\base\Model;

/**
 * 配置文件引擎
 * User: sidney
 * Date: 2019/8/29
 * @since 1.0.0
 */
class ConfigEngine extends Model
{
    private $data = [];

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function get($name, $default = null)
    {
        $segments = explode('.', $name);
        $node =& $this->data;
        foreach ($segments as $segment) {
            if (is_array($node) && isset($node[$segment])) {
                $node =& $node[$segment];
            } else {
                return $default;
            }
        }
        return $node;
    }

    /**
     * @param $name
     * @param null $default
     * @return mixed|null|string
     * @throws Exception
     */
    public function getString($name, $default = null)
    {
        $result = $this->get($name);
        if ($result === null) {
            return $default;
        }
        if (is_string($result)) {
            return $result;
        }
        if (is_scalar($result) || is_resource($result)) {
            return (string)$result;
        }
        if (is_object($result)) {
            if (method_exists($result, '__toString')) {
                return (string)$result;
            }
            throw new Exception(
                "Config '$name' requires a string, object of class "
                . get_class($result) . " could not be converted to string."
            );
        }
        throw new Exception(
            "Config '$name' requires a string, "
            . gettype($result) . ' could not be converted to string.'
        );
    }

    /**
     * @param string $name
     * @param bool $default
     * @return bool
     */
    public function getBool($name, $default = null)
    {
        $result = $this->get($name);
        if ($result === null) {
            return $default;
        }
        return (bool)$result;
    }

    /**
     * @param $name
     * @param null $default
     * @return int|null
     * @throws Exception
     */
    public function getInt($name, $default = null)
    {
        $result = $this->get($name);
        if ($result === null) {
            return $default;
        }
        if (is_object($result)) {
            throw new Exception(
                "Config '$name' requires an integer, object of class '"
                . get_class($result)
                . "' could not be converted to integer."
            );
        }
        return (int)$result;
    }

    /**
     * @param $name
     * @param null $default
     * @return float|null
     * @throws Exception
     */
    public function getFloat($name, $default = null)
    {
        $result = $this->get($name);
        if ($result === null) {
            return $default;
        }
        if (is_object($result)) {
            throw new Exception(
                "Config '$name' requires a float, object of class '"
                . get_class($result) . "' could not be converted to float."
            );
        }
        return (float)$result;
    }

    /**
     * @param $name
     * @param null $default
     * @return mixed|null
     * @throws Exception
     */
    public function getArray($name, $default = null)
    {
        $result = $this->get($name);
        if ($result === null) {
            return $default;
        }
        if (is_array($result) === false) {
            throw new Exception(
                "Config '$name' requires an array, "
                . gettype($result) . " given."
            );
        }
        return $result;
    }

    /**
     * @param $name
     * @param null $default
     * @return mixed|null|string
     * @throws Exception
     */
    public function getClass($name, $default = null)
    {
        $result = $this->getString($name);
        if ($result === null) {
            return $default;
        }
        if ($result === '') {
            throw new Exception(
                "The class name cannot be empty, set using config '$name'."
            );
        }
        if (class_exists($result) === false) {
            throw new Exception(
                "Class '$result' does not exist, set using config '$name'."
            );
        }
        return $result;
    }

    /**
     * @param $name
     * @param null $default
     * @return mixed|null
     * @throws Exception
     */
    public function getCallable($name, $default = null)
    {
        $result = $this->get($name);
        if ($result === null) {
            return $default;
        }
        if (is_callable($result) === false) {
            throw new Exception(
                "The value of config '$name' is not callable."
            );
        }
        return $result;
    }

    /**
     * @return array
     */
    public function getAll()
    {
        return $this->data;
    }

    /**
     * @param $name
     * @param $value
     * @throws Exception
     */
    public function set($name, $value)
    {
        $this->import([$name => $value]);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        $segments = explode('.', $name);
        $node =& $this->data;
        foreach ($segments as $segment) {
            if (is_array($node) && isset($node[$segment])) {
                $node =& $node[$segment];
            } else {
                return false;
            }
        }
        return true;
    }

    /**
     * @param string $name
     * @return void
     */
    public function remove($name)
    {
        $segments = explode('.', $name);
        $parent = null;
        $node =& $this->data;
        $segment = null;
        foreach ($segments as $segment) {
            if (is_array($node) && isset($node[$segment])) {
                $parent =& $node;
                $node =& $node[$segment];
            } else {
                return;
            }
        }
        unset($parent[$segment]);
    }

    /**
     * @param $data
     * @throws Exception
     */
    public function import($data)
    {
        $this->build($this->data, $data);
    }

    /**
     * @param $node
     * @param $data
     * @throws Exception
     */
    private function build(&$node, $data)
    {
        foreach ($data as $name => $value) {
            $segments = explode('.', $name);
            $currentNode =& $node;
            foreach ($segments as $segment) {
                if (is_array($currentNode) === false) {
                    $currentNode = [];
                }
                if (isset($currentNode[$segment]) === false) {
                    if ($segment === '') {
                        throw new Exception(
                            "The config name cannot be empty."
                        );
                    }
                    $currentNode[$segment] = [];
                }
                $currentNode =& $currentNode[$segment];
            }
            if (is_array($value)) {
                $this->build($currentNode, $value);
            } else {
                $currentNode = $value;
            }
        }
    }
}