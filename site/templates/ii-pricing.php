<?php
	include_once('./ii-include.php');

	use ItemsearchQuery, Itemsearch;
	use WarehouseQuery, Warehouse;
	use CustomerQuery, Customer;

	$module_ii = $modules->get('DpagesMii');
	$module_ii->init_iipage();

	$page->show_breadcrumbs = false;
	$page->body .= $config->twig->render('items/ii/bread-crumbs.twig', ['page' => $page, 'item' => $item]);

	if ($itemquery->count()) {
		$page->title = "$itemID Pricing";

		if ($input->get->custID) {
			$custID = $input->get->text('custID');
			$customer = CustomerQuery::create()->findOneByCustid($custID);
			$page->title .= " for $customer->name";

			$module_json = $modules->get('JsonDataFiles');
			$json = $module_json->get_file(session_id(), $page->jsoncode);

			if ($module_json->file_exists(session_id(), $page->jsoncode)) {
				if ($json['itemid'] != $itemID) {
					$module_json->remove_file(session_id(), $page->jsoncode);
					$session->redirect($page->get_itempricingURL($itemID, $custID));
				}
				$session->pricingtry = 0;

				$refreshurl = $page->get_itempricingURL($itemID, $custID);
				$page->body .= $config->twig->render('items/ii/ii-links.twig', ['page' => $page, 'itemID' => $itemID, 'lastmodified' => $module_json->file_modified(session_id(), $page->jsoncode), 'refreshurl' => $refreshurl]);

				if ($json['error']) {
					$page->body .= $config->twig->render('util/alert.twig', ['type' => 'danger', 'title' => 'Error!', 'iconclass' => 'fa fa-warning fa-2x', 'message' => $json['errormsg']]);
				} else {
					$page->body .= $config->twig->render('items/ii/pricing/customer-item.twig', ['page' => $page, 'customer' => $customer, 'json' => $json]);
					$page->body .= $config->twig->render('items/ii/pricing/screen.twig', ['page' => $page, 'itemID' => $itemID, 'json' => $json]);
				}
			} else {
				if ($session->pricingtry > 3) {
					$page->headline = $page->title = "Pricing File could not be loaded";
					$page->body .= $config->twig->render('util/error-page.twig', ['title' => $page->title, 'msg' => $module_json->get_error()]);
				} else {
					$session->pricingtry++;
					$session->redirect($page->get_itempricingURL($itemID, $custID));
				}
			}
		} else {
			$query = CustomerQuery::create();

			if ($input->get->q) {
				$q = $input->get->text('q');
				$page->title = "II Pricing: Searching for '$q'";
				$col_custid = Customer::get_aliasproperty('custid');
				$col_name = Customer::get_aliasproperty('name');
				$columns = array($col_custid, $col_name);
				$query->search_filter($columns, strtoupper($q));
			}
			$customers = $query->paginate($input->pageNum, 10);

			$page->searchURL = $page->url;
			$page->body .= $config->twig->render('items/ii/pricing/customer/customer-search.twig', ['page' => $page, 'customers' => $customers, 'itemID' => $itemID]);
			$page->body .= $config->twig->render('util/paginator.twig', ['page' => $page, 'resultscount'=> $customers->getNbResults()]);
		}
	}

	if ($page->print) {
		$page->show_title = true;
		include __DIR__ . "/blank-page.php";
	} else {
		include __DIR__ . "/basic-page.php";
	}
