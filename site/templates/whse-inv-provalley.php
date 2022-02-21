<?php
	include_once($modules->get('Mvc')->controllersPath().'vendor/autoload.php');

	use Controllers\Wm\Inventory\Provalley;

	Provalley\Inventory::initHooks();

	$routes = [
		['GET',  '', Provalley\Inventory::class, 'index'],
		['GET',  'print-gs1', Provalley\PrintGs1::class, 'index'],
		['POST',  'print-gs1', Provalley\PrintGs1::class, 'handleCRUD'],
	];

	$router = new Mvc\Routers\Router();
	$router->setRoutes($routes);
	$router->setRoutePrefix($page->url);
	$page->body = $router->route();
	$page->show_breadcrumbs = false;
	include __DIR__ . "/basic-page.php";
