<?php

spl_autoload_register('autoload');

use Quark\Database\Query\Builder\Select;


$qb = new Select(array(
    'u.name'  => 'name',
    'u.email' => 'email'
));
$qb->where('u.name', '=', 'costam');

$query = $qb->compile();

echo $query;
echo "\n";










function autoload($className)
{
    $className = ltrim($className, '\\');
    $fileName  = '';
    $namespace = '';
    if ($lastNsPos = strrpos($className, '\\')) {
        $namespace = substr($className, 0, $lastNsPos);
        $className = substr($className, $lastNsPos + 1);
        $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
    }
    $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

    require $fileName;
}