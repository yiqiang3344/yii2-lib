# 简介
库中定义了基于yii2框架的配置文件、数据库、加密、OSS、Redis、参数校验、状态码，以及数组、字符串、金额、图片和时间等常用的工具类。

# 安装
```bash
composer 
```

# 功能
## config
### 配置类 `\yiqiang3344\lib\helper\config\Config`
可以以点连接的方式按数据类型读取`Yii::$app->params`中的参数。
比如：
```php
//params.php
return [
    'env' => [
        'name' => 'test'
    ],
];
```
```php
echo Config::getString('env.name'); //输出为 test
```

## db
### 数据库类 `\yiqiang3344\lib\helper\db\DB`
方便自定义的获取数据库实例使用；提供闭包形式的事务方法。

db中配置了credit_db数据库，则可以继承`DB`类，然后添加`credit_db()`方法来获取数据库连接实例。
`DB`中默认实现了获取db连接实例的方法`default()`。
```php
    /**
     * 默认DB
     * @return \yii\db\Connection
     */
    public static function default()
    {
        $db = \Yii::$app->db;
        return $db;
    }
```

闭包形式的事务方法。
```php
    return DB::transaction(function (){
        DB::default()->createCommand('select id from User where id=1 for update')->execute();
        //...
        return true;
    });
```

## encrypt
### 加密类 `\yiqiang3344\lib\helper\encrypt\Encrypt`
提供`aes-128-cbc`的加密、解密和加签方法。

### RSA加密类 `\yiqiang3344\lib\helper\encrypt\RSAUtil`
提供`OPENSSL_ALGO_SHA256`的加密、解密、加签和验签方法。

## oss
### 阿里云OSS类 `\yiqiang3344\lib\helper\oss\Oss`
统一配置的阿里云OSS封装类，提供获取oss实例、上传、下载、删除、获取访问地址方法。

## redis
### Redis类 `\yiqiang3344\lib\helper\redis\Redis`
统一配置的Redis封装类，需要使用其他配置，只需集成此类，并重写`$redisName`属性。

## validator
### 参数校验类 `\yiqiang3344\lib\helper\validator\Validator`
统一使用`checkParams(&$params, $needParams)`方法来校验输入参数，
支持的参数类型参见`$builtInValidators`列表，且可自行扩展。

## AmountHelper 金额工具类
获取标准格式化金额，单位元，两位小数点 `format($amount)`

数字转大写的金额 `num2rmb($number = 0, $intUnit = '圆', $isRound = true, $isExtraZero = false)`

## ArrayHelper 数组工具类
把数组转为key-value格式 `listMap(array $array, $key, $columns = null)`

复制一个数组中指定列的数组 `cpList(array $array, array $columns)`

复制数组中的指定列 `cp($item, array $columns)`

## CodeMessage 响应结果类
可以使用`success($data = [])`或`failed($code, $subMessage = '', $data = [])`来统一输出响应结果。  
只需要在controller中`return CodeMessage::success();`即可。

响应状态码在`$codeMap`中定义，
默认正常响应码为1，异常响应码-1，
各应用可继承此类来定义各自应用的响应状态码。

## Image 图片工具类
获取base64格式的图片 `base64EncodeImage($image_file)`

## StringHelper 字符工具类
驼峰转横杠式 `humpToBar($string)`

遮盖名称 `coverName($string)`

用星星这个指定范围 `coverWithStar($string, $start, $length)`

遮盖字符串的制定范围 `cover($string, $start, $length, $mask = '*', $isUtf8 = false)`

## Time
获取秒级时间戳 `time()`

获取日期 `now($time = null)`
  
获取带毫秒的日期 `nowWithMicros()`
  
获取毫秒时间戳 `getMicrotime()`
  
获取指定月份后的当前日期 `getNexMonthDay($num = 1, $date = null)`
  
获取当前日期星期几的中文名 `getDayOfWeek($time)`
  
当前时间与指定日期相差天数 `getSubDayFromToday($defaultDay = '2015-01-22')`
  
获取指定时间戳的当日的开始时间 `getDailyStartTime($time = null)`
  
获取是指定时间戳的当天的截止时间 `getDailyEndTime($time = null)`  


