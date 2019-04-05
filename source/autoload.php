<?php



spl_autoload_register(function ($calledClassName) {

    $normalizedClassName = preg_replace('`^\\\\`', '', $calledClassName);


    if(strpos($normalizedClassName, 'Phi\Routing') === 0) {

        $relativeClassName = str_replace('Phi\Routing', '', $normalizedClassName);
        $relativePath = str_replace('\\', '/', $relativeClassName);


        $definitionClass = __DIR__.'/class'.$relativePath.'.php';
        if(is_file($definitionClass)) {
            include($definitionClass);
        }
    }



});


