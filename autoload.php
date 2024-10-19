<?php

/**
 * This file is part of the Weblog.
 *
 * Copyright (c) 2024  René Coignard <contact@renecoignard.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

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
