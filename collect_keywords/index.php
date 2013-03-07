<?php
/**
 * 雅虎相关关键词采集
 * 
 * v1.0.1
 * author: Jason W.
 * url:https://github.com/jsonwong/collect_keywords_for_yahoo
 */

$action = ! empty ( $_GET ['a'] ) ? $_GET ['a'] : '_init';

require_once 'control.class.php';

$object = new control ();

$object->$action ();


?>