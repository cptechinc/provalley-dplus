<?php
	include_once($modules->get('Mvc')->controllersPath().'vendor/autoload.php');
	use Controllers\Mpo\PurchaseOrder\Received;
	Received::initHooks();

	$routes = [
		['GET',  '', Received::class, 'index'],
	];
	$router = new Mvc\Routers\Router();
	$router->setRoutes($routes);
	$router->setRoutePrefix($page->url);
	$page->body = $router->route();
	$page->show_breadcrumbs = false;

	if ($config->ajax) {
		echo $page->body;
	} else {
		if ($input->lastSegment() == 'print' || $input->get->offsetExists('print')) {
			$page->show_title = true;

			if ($input->get->offsetExists('pdf')) {
				$page->show_title = false;
			}
			include __DIR__ . "/blank-page.php";
		} else {
			include __DIR__ . "/basic-page.php";
		}
	}
