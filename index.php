<?php
/**
 * Created by PhpStorm.
 * User: ben
 * Date: 12/7/14
 * Time: 11:11
 */
// web/index.php
require_once __DIR__.'/vendor/autoload.php';

$app = new Silex\Application();
$app['debug'] = true;

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/templates',
));

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'   => 'pdo_mysql',
        'user' => 'homestead',
        'password' => 'secret',
        'host' => 'localhost',
        'port' => 3306,
        'dbname' => 'SalesOrders'
     ),
));

$app->get('/', function () use ($app){
    $sql = 'select * from Invoice';
    /** @var \Doctrine\DBAL\Connection $conn */
    $conn = $app['db'];
    $orders = $conn->fetchAll($sql);
    return $app['twig']->render('orders.twig', array('orders'=>$orders));
});

$app->run();