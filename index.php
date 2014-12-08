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

function array_rekey($array, $callback){

    $ret = array();

    foreach($array as $item){
        $key = $callback($item);
        $ret[$key] = $item;
    }

    return $ret;
}

$app->get('/', function () use ($app){
    $sql = 'select * from Invoice join Customer on Invoice.CustNum = Customer.CustNum order by OrderNum';
    /** @var \Doctrine\DBAL\Connection $conn */
    $conn = $app['db'];
    $orders = $conn->fetchAll($sql);

    //re-key the array by order num
    $orders = array_rekey($orders, function($e){
       return $e['OrderNum'];
    });

    $sql = 'select * from InvoiceLineItem join Inventory on Inventory.SKU = InvoiceLineItem.SKU order by OrderNum';
    $items = $conn->fetchAll($sql);
    $summary = array('sales'=>0, 'weight'=>0, 'items'=>0, 'totalProfit'=>0, 'averageOrder'=>0);


    foreach ($items as $item){
        $summary['sales'] += $item['UnitPrice'] * $item['OrdQty'];
        $summary['weight'] += $item['UnitWeight'] * $item['OrdQty'];
        $summary['items'] += 1;
        $summary['averageOrder'] += $item['UnitPrice'] * $item['OrdQty'];
        $summary['totalProfit'] += ($item['UnitPrice'] - $item['Cost']) * $item['OrdQty'];
        if(array_key_exists($item['OrderNum'], $orders)){
            $orders[$item['OrderNum']]['items'][] = $item;
        }

    }

    $summary['averageOrder'] /= sizeof($orders);
    $summary['averageProfit'] = $summary['totalProfit'] / sizeof($orders);

    return $app['twig']->render('index.twig', array('orders'=>$orders, 'summary'=> $summary));
});

$app->get('/order/{id}', function ($id) use ($app){
    $sql = 'select * from Invoice join Customer on Invoice.CustNum = Customer.CustNum where OrderNum = ?';
    /** @var \Doctrine\DBAL\Connection $conn */
    $conn = $app['db'];
    $order = $conn->fetchAssoc($sql, array($id));

    $sql = 'select * from InvoiceLineItem join Inventory on Inventory.SKU = InvoiceLineItem.SKU where OrderNum = ?';
    $items = $conn->fetchAll($sql, array($id));
    $orderTotal = 0;
    $profit = 0;
    foreach($items as $item){
        $orderTotal += $item['OrdQty'] * $item['UnitPrice'];
        $profit += $item['OrdQty'] * $item['UnitPrice'] - $item['OrdQty'] * $item['Cost'];
    }
    return $app['twig']->render('order.twig',
        array('order'=>$order, 'items'=>$items, 'total'=> $orderTotal, 'profit'=>$profit));
});

$app->get('/customer/{id}', function ($id) use ($app){

    $sql = 'select * from Customer where CustNum = ?';
    /** @var \Doctrine\DBAL\Connection $conn */
    $conn = $app['db'];
    $customer = $conn->fetchAssoc($sql, array($id));

    $sql = 'select * from InvoiceLineItem
        join Inventory on Inventory.SKU = InvoiceLineItem.SKU
        where InvoiceLineItem.OrderNum in (select OrderNum from Invoice where Invoice.CustNum = ?)';
    $items = $conn->fetchAll($sql, array($id));

    return $app['twig']->render('customer.twig', array('customer'=>$customer, 'items'=>$items));

});

$app->run();