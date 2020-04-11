<?php
namespace yiqiang3344\yii2_lib\base;

use yiqiang3344\yii2_lib\helper\ArrayHelper;
use yii\base\Model;

/**
 * 通过反射机制来定义属性的参数结构体父类
 * 继承此类之后，在类的块注释中说明属性即可
 * Class BaseData
 * @package common\models\base
 * @since 1.0.19
 */
class BaseData extends Model
{
    protected $_data;
    protected $_properties = null;

    protected function getProperties()
    {
        if ($this->_properties === null) {
            $class = new \ReflectionClass(get_called_class());
            $doc = $class->getDocComment();
            preg_match_all('/@property (\S+) \$(\S+)/', $doc, $matches);
            $this->_properties = [];
            foreach ($matches[2] as $k => $v) {
                $this->_properties[$v] = ['type' => $matches[1][$k]];
            }
        }
        return $this->_properties;
    }

    public function __construct(array $config = [])
    {
        //只初始化定义过的数据
        $propertyMap = $this->getProperties();
        foreach ($config as $k => $v) {
            if (isset($propertyMap[$k])) {
                $this->$k = $v;
            }
        }
        parent::__construct([]);
    }

    /**
     * @param string $name
     * @return mixed
     * @throws \yii\base\UnknownPropertyException
     */
    public function __get($name)
    {
        if (isset($this->_properties[$name])) {
            return $this->_data[$name] ?? null;
        }

        return parent::__get($name);
    }

    public function __isset($name)
    {
        if (isset($this->_properties[$name]) && isset($this->_data[$name])) {
            return true;
        }
        return parent::__isset($name);
    }

    public function __unset($name)
    {
        if (isset($this->_properties[$name]) && isset($this->_data[$name])) {
            $this->_data[$name] = null;
        }
        parent::__unset($name);
        return;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @throws \yii\base\UnknownPropertyException
     */
    public function __set($name, $value)
    {
        if (isset($this->_properties[$name])) {
            $this->_data[$name] = $value;
            return;
        }
        parent::__set($name, $value);
        return;
    }

    public function toArray(array $fields = [], array $expand = [], $recursive = true)
    {
        $data = parent::toArray($fields, $expand, $recursive);
        $_data = ArrayHelper::toArray($this->_data);
        foreach ($this->_properties as $k => $v) {
            $data[$k] = $_data[$k] ?? null;
        }
        return $data;
    }

    /**
     * 获取数据模板
     * @return array
     */
    public function getDataTemplate()
    {
        $ret = [];
        foreach ($this->getProperties() as $k => $v) {
            $ret[$k] = $this->getPropertyDefaultValue($v['type']);
        }
        return $ret;
    }

    /**
     * 根据属性类型获取属性默认值
     * @param $type
     * @return mixed
     */
    private function getPropertyDefaultValue($type)
    {
        $ret = null;
        if ($type == 'int' || $type == 'float' || $type == 'double') {
            $ret = 0;
        } elseif ($type == 'string') {
            $ret = '';
        } elseif ($type == 'array' || strpos($type, '[]') !== false) {
            $ret = [];
        }
        return $ret;
    }
}