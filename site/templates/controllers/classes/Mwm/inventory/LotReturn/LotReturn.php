<?php namespace Controllers\Wm\Inventory;

use stdClass;
// Purl Library
use Purl\Url as Purl;
// ProcessWire Classes, Modules
use ProcessWire\Page, ProcessWire\Module, ProcessWire\WireData;
use Processwire\SearchInventory, Processwire\WarehouseManagement,ProcessWire\HtmlWriter;
// Dplus Configs
use Dplus\Configs;
// Dplus Validators
use Dplus\CodeValidators\Mpo as MpoValidator;
// Mvc Controllers
use Controllers\Wm\Base;

class LotReturn extends Base {
	const DPLUSPERMISSION = 'wm';
	const JSONCODE = 'whse-lotreturn';

	private static $jsonm;

/* =============================================================
	Indexes
============================================================= */
	static public function index($data) {
		$fields = ['scan|text'];
		self::sanitizeParametersShort($data, $fields);

		if (empty($data->action) === false) {
			return self::handleCRUD($data);
		}

		if (empty($data->scan) === false) {
			return self::scan($data);
		}
		return self::scanForm($data);
	}

	static public function handleCRUD($data) {
		self::sanitizeParametersShort($data, ['action|text', 'scan|text']);

		switch ($data->action) {
			case 'search-inventory':
				self::redirect(self::receivingScanUrl($data->ponbr, $data->scan, $data->binID), $http301 = false);
				break;
			default:
				self::redirect(self::receivingUrl($data->ponbr), $http301 = false);
				break;
		}
	}

	static private function scan($data) {
		self::requestSearch($data);
		$exists = self::verifyData($data);
		if ($exists === false) {
			return self::pw('config')->twig->render('util/alert.twig', ['type' => 'danger', 'title' => 'Error!', 'iconclass' => 'fa fa-warning fa-2x', 'message' => 'Could not find JSON']);
		}
		$json = self::getJsonModule()->getFile(self::JSONCODE);
		return self::scanResult($json);
	}

/* =============================================================
	Data Processing
============================================================= */
	static private function verifyData($data) {
		self::sanitizeParametersShort($data, ['scan|text']);

		$jsonm = self::getJsonModule();
		$json   = $jsonm->getFile(self::JSONCODE);
		$session = self::pw('session');

		if ($jsonm->exists(self::JSONCODE) === false) {
			$session->setFor('whse-lotreturn', $data->scan, ($session->getFor('whse-lotreturn', $data->scan) + 1));
			if ($session->getFor('whse-lotreturn', $data->scan) > 3) {
				return false;
			}
			$session->redirect(self::scanUrl($data->scan, $refresh = true));
		}

		if ($jsonm->exists(self::JSONCODE)) {
			if ($json['scan'] != $data->scan) {
				$jsonm->delete(self::JSONCODE);
				$session->redirect(self::scanUrl($data->scan, $refresh = true), $http301 = false);
			}
			$session->setFor('whse-lotreturn', $data->scan, 0);
			return true;
		}

		if ($session->getFor('whse-lotreturn', $data->scan) > 3) {
			return false;
		}
		$session->setFor('whse-lotreturn', $data->scan, ($session->getFor('whse-lotreturn', $data->scan) + 1));
		$session->redirect(self::scanUrl($data->scan, $refresh = true), $http301 = false);
	}



/* =============================================================
	URLs
============================================================= */
	static public function scanUrl($scan = '') {
		$url = new Purl(self::pw('pages')->get('pw_template=whse-lot-return')->url);
		if ($scan) {
			$url->query->set('scan', $scan);
		}
		return $url->getUrl();
	}

/* =============================================================
	Displays
============================================================= */
	static private function scanForm($data) {
		return self::pw('config')->twig->render('mii/loti/forms/scan.twig');
	}

	static private function scanResult($data, array $json) {
		$config = self::pw('config');

		if ($json['error']) {
			return $config->twig->render('util/alert.twig', ['type' => 'danger', 'title' => 'Error!', 'iconclass' => 'fa fa-warning fa-2x', 'message' => $json['message']]);
		}
		$html  = '';
		$html .= $config->twig->render('warehouse/inventory/lot-return/scanned-lot-form.twig');
		return $html;
	}

/* =============================================================
	Requests
============================================================= */
	static private function requestSearch($data) {
		self::sendRequest(['LOTRETURN', "QUERY=$data->scan"]);
	}

	static private function sendRequest(array $data, $sessionID = '') {
		$sessionID = $sessionID ? $sessionID : session_id();
		$db = self::pw('modules')->get('DplusOnlineDatabase')->db_name;
		$data = array_merge(["DBNAME=$db"], $data);
		$requestor = self::pw('modules')->get('DplusRequest');
		$requestor->write_dplusfile($data, $sessionID);
		$requestor->cgi_request(self::pw('config')->cgis['warehouse'], $sessionID);
	}

/* =============================================================
	Validator, Module Getters
============================================================= */
	static public function validateUserPermission(User $user = null) {
		if (empty($user)) {
			$user = self::pw('user');
		}
		return $user->has_function(self::DPLUSPERMISSION);
	}

	public static function getJsonModule() {
		if (empty(self::$jsonm)) {
			self::$jsonm = self::pw('modules')->get('JsonDataFilesSession');
		}
		return self::$jsonm;
	}

/* =============================================================
	Init
============================================================= */
	public static function initHooks() {
		$m = self::pw('modules')->get('WarehouseManagement');

	}
}