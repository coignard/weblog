<?php

declare(strict_types=1);

/**
 * Autoloads PHP classes by searching for class files in 'src/'.
 *
 * @param mixed $className The name of the class to load
 */
function customAutoloader(mixed $className): void
{
    if (str_starts_with($className, 'Weblog\\')) {
        $className = substr($className, 6);
    }

    $baseDir = __DIR__.'/src';
    $classPath = str_replace('\\', '/', $className).'.php';
    $filePath = $baseDir.$classPath;

    if (file_exists($filePath) && 'php' === pathinfo($filePath, \PATHINFO_EXTENSION)) {
        require_once $filePath;
    }
}

spl_autoload_register('customAutoloader');
