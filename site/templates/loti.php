<?php
	include_once($modules->get('Mvc')->controllersPath().'vendor/autoload.php');

	use Controllers\Mii\Loti;

	Loti::initHooks();

	$routes = [
		['GET',  '', Loti::class, 'index'],
		['GET',  'activity/', Loti::class, 'index'],
	];


	$router = new Mvc\Router();
	$router->setRoutes($routes);
	$router->setRoutePrefix($page->url);
	$page->body = $router->route();
	include __DIR__ . "/basic-page.php";
