<?php

spl_autoload_register('autoload');

/*
$qb = new Select(array(
    array('users.id', 'id'),
    array('users.username', 'uname'),
    array('users.password', 'pass')
));

$select = $qb
    ->distinct(true)
    ->from(array('users', 'u'))
    ->join(array('posts', 'p'), 'LEFT')
        ->on('p.user_id', '=', 'u.id')
    ->join(array('venues', 'v'), 'RIGHT')
        ->on('v.user_id', '=', 'u.id')
    ->where('u.name', '=', 'test')
    ->having_open()
        ->having('u.age', '>', '10')
        ->or_having('u.age', '<', '14')
    ->having_close()
    ->order_by('u.age', 'DESC')
    ->limit(10)
    ->reset();
*/

/*
$union = new Quark\Database\Query\Builder\Select(array(
    array('u.id', 'id'),
    array('u.username', 'name'),
    array('COUNT(u.id)', 'amount')
));

$union
    ->from(array('users', 'u'))
    ->group_by('u.active');

$select = new Quark\Database\Query\Builder\Select();

$select
    ->from(array('other_users', 'ou'))
    ->union('users', false)
    ->compile();
*/

$qb = new \Quark\Database\Query\Builder\Select();

$query = $qb
    ->select('id', 'username', 'pass')
    ->from('users', 'u')
    ->where_open()
        ->where('u.age', '>', 18)
        ->or_where('u.adult', '=', 1)
    ->where_close()
    ->and_where_open()
    ->where_close_empty();

echo $query;
echo "\n";

/*
$query = new \Quark\Database\Query\Builder\Update(array('posts', 'p'));
$query
    ->value('p.views', '123')
    ->set(array(
        'p.views'  => '300',
        'p.active' => '1'
    ))

    ->where('posts.id', 'IN', array('1', '2', '3'))
    ->or_where_open()
        ->where('posts.title', 'LIKE', '%test%')
        ->or_where('posts.title', 'LIKE', '%qwer%')
    ->or_where_close();

echo $query;
*/
echo "\n";
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