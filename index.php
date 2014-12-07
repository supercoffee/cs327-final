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

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/templates',
));

$app->get('/hello/{name}', function ($name) use ($app) {
    return $app['twig']->render('index.twig', array('name'=>$name));
});

$app->run();