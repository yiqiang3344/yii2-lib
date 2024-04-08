<?php

namespace yiqiang3344\yii2_lib\helper\db;

/**
 * 自定义数据库连接类
 *
 * 支持慢sql处理
 *
 */
class Connection extends \yii\db\Connection
{
    public $slowSqlTime;//慢sql判断时间，单位秒
    public $commandClass = Command::class;
}