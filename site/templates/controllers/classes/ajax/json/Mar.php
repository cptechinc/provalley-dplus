<?php namespace Controllers\Ajax\Json;
// Dplus Models
use CustomerQuery, CustomerShiptoQuery;
// ProcessWire Mlasses, Modules
use ProcessWire\Module, ProcessWire\ProcessWire;
// Dplus Validators
use Dplus\CodeValidators\Mar       as MarValidator;
use Dplus\CodeValidators\Mar\Cxm   as CxmValidator;
// Mvc Controllers
use Mvc\Controllers\AbstractController;

class Mar extends AbstractController {
	public static function test() {
		return 'test';
	}

	public static function validateSalesPersonId($data) {
		$valid = false;
		$fields = ['id|text', 'jqv|bool', 'new|bool'];
		$data = self::sanitizeParametersShort($data, $fields);
		$valid = self::_validateSalesPersonId($data);

		if ($data->new) {
			$canuse = $valid === false;

			if ($canuse === false && $data->jqv) {
				return "Sales Person $data->id already exists";
			}
			return $canuse;
		}

		if ($data->jqv && $valid === false) {
			return "Sales Person $data->id not found";
		}
		return $valid;
	}

	public static function _validateSalesPersonId($data) {
		$exists = false;
		$fields = ['id|text'];
		$data = self::sanitizeParametersShort($data, $fields);
		$validate = new MarValidator();

		if ($validate->salespersonid($data->id) === false) {
			return false;
		}
		return true;
	}

	public static function validateSalesGroupId($data) {
		$valid = false;
		$fields = ['id|text', 'jqv|bool', 'new|bool'];
		$data = self::sanitizeParametersShort($data, $fields);

		$valid = self::_validateSalesGroupId($data);

		if ($data->new) {
			$canuse = $valid === false;
			if ($canuse === false && $data->jqv) {
				return "Sales Person $data->id already exists";
			}
			return $canuse;
		}

		if ($valid === false && $data->jqv) {
			return "Sales Group $data->id not found";
		}
		return $valid;
	}

	public static function _validateSalesGroupId($data) {
		$exists = false;
		$fields = ['id|text'];
		$data = self::sanitizeParametersShort($data, $fields);
		$validate = new MarValidator();

		if ($validate->salesgroupid($data->id) === false) {
			return false;
		}
		return true;
	}

	public static function validateCustid($data) {
		$fields = ['custID|text', 'new|bool', 'jqv|bool'];
		$data = self::sanitizeParametersShort($data, $fields);
		$validate = new MarValidator();
		$exists = $validate->custid($data->custID);

		if ($data->new) {
			$valid = $exists === false;

			if ($valid === false && $data->jqv) {
				return "$data->custID already exists";
			}
			return $valid;
		}

		if ($exists === false && $data->jqv) {
			return "$data->custID not found";
		}
		return $exists;
	}

	public static function getCustomer($data) {
		self::sanitizeParametersShort($data, ['custID|text']);
		$validate = new MarValidator();

		if ($validate->custid($data->custID) === false) {
			return false;
		}
		$customer = CustomerQuery::create()->findOneByCustid($data->custID);
		return [
			'id'   => $customer->id,
			'name' => $customer->name
		];
	}

	public static function getCustomerShipto($data) {
		self::sanitizeParametersShort($data, ['custID|text']);
		$validate = new MarValidator();

		if ($validate->custShiptoid($data->custID, $data->shiptoID) === false) {
			return false;
		}
		$shipto = CustomerShiptoQuery::create()->filterByCustid($data->custID)->filterByShiptoid($data->shiptoID)->findOne();

		return [
			'custid' => $shipto->custid,
			'id'     => $shipto->id,
			'name'   => $shipto->name,
			'address' => [
				'address1' => $shipto->address,
				'address2' => $shipto->address2,
				'city'     => $shipto->city,
				'state'    => $shipto->state,
				'zip'      => $shipto->zip,
			]
		];
	}
}
