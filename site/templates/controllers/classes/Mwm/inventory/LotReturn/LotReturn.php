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
		self::requestJson($data);
		self::getData($data);
	}

/* =============================================================
	Data Processing
============================================================= */
	private static function getData($data) {
		self::sanitizeParametersShort($data, ['scan|text']);
		self::setupData($data);

		$jsonm = IIActivity::getJsonModule();
		$json   = $jsonm->getFile(self::JSONCODE);
		$session = self::pw('session');

		if ($jsonm->exists(self::JSONCODE) === false) {
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
		$session->setFor('whse-lotreturn', $data->scan, ($session->getFor('loti', $data->scan) + 1));
		$session->redirect(self::scanUrl($data->scan, $refresh = true), $http301 = false);
	}



/* =============================================================
	URLs
============================================================= */

/* =============================================================
	Displays
============================================================= */
	static public function scanForm($data) {
		return self::pw('config')->twig->render('mii/loti/forms/scan.twig');
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
