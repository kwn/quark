<?php

spl_autoload_register('autoload');

use Quark\Database\Query\Builder\Select;


$qb = new Select(array(
    'u.name'  => 'name',
    'u.email' => 'email'
));

$query = $qb
    ->from(array('users', 'u'))
    ->join(array('posts', 'p'), 'LEFT')
        ->on('p.user_id', '=', 'u.id')
    ->where('u.name', '=', 'costam')
    ->having_open()
        ->having('u.age', '>', '10')
        ->or_having('u.age', '<', '14')
    ->having_close()
    ->order_by('u.age', 'DESC')
    ->compile();

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