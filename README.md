## Readline completion for yii2 console application
Features:
* command completion
* options completion

> For usage require to init yii2 console application, see below

```php
// console.php
<?php

require __DIR__ . '/../yours/yii2/project/vendor/autoload.php';
require __DIR__ . '/../yours/yii2/project/vendor/yiisoft/yii2/Yii.php';

// copypaste of init code yours yii2 app
$application = new yii\console\Application([
    'id' => 'readline-completer-yii2',
    'basePath' => __DIR__,
    //settings below helps to find custom controllers
//    'aliases' => [
//        '@User/Namespace' => __DIR__,
//    ],
//    'controllerNamespace' => 'User\Namespace\Controllers',
]);


$readline = new \Ridzhi\Readline\Readline();
$readline->setCompleter(new \Ridzhi\Readline\Completer\Yii2\Completer());

```