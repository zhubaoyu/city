<?php
spl_autoload_register(function($class_name){
    $class_name = str_replace('\\','/', $class_name);
    $file = __DIR__."/{$class_name}.php";
    require $file;
});

