<?php namespace ProcessWire;

use Propel\Runtime\ActiveQuery\Criteria;
use Purl\Url;

use PurchaseOrderDetailReceivingQuery, PurchaseOrderDetailReceiving;
use ApInvoiceDetailQuery, ApInvoiceDetail;
use WarehouseQuery, Warehouse;

use EditPoDetailQuery, EditPoDetail;

class PoAmendItems extends WireData {
	public function __construct() {
		$this->sessionID = session_id();
		$this->configs = $this->wire('modules')->get('PurchaseOrderEditConfigs');
	}

	/**
	 * Returns Query for Purchase Order Details Edit
	 * @param  string $ponbr Purchase Order Number
	 * @return EditPoDetailQuery
	 */
	public function query($ponbr) {
		$q = EditPoDetailQuery::create();
		$q->filterBySessionid($this->sessionID);
		$q->filterByPonbr($ponbr);
		return $q;
	}

	/**
	 * Return if PO items are available for editing
	 * @param  string $ponbr Purchase Order Number
	 * @return bool
	 */
	public function exist($ponbr) {
		$q = $this->query($ponbr);
		return boolval($q->count());
	}

	public function all($ponbr) {
		return $this->query($ponbr)->find();
	}

	public function all_array($ponbr) {
		$this->init_configs();
		$js = [];
		$items = $this->query($ponbr)->find();

		foreach ($items as $item) {
			$js[$item->linenbr] = array(
				'linenbr'      => $item->linenbr,
				'itemid'       => $item->itemid,
				'description'  => $item->description,
				'vendoritemid' => $item->vendoritemid,
				'whseid'       => $item->whse,
				'specialorder' => $item->specialorder,
				'uom'          => $item->uom,
				'qty' => array(
					'ordered'  => number_format($item->qty_ordered, $this->configs->decimal_places_qty()),
					'received' => $this->get_qty_received($ponbr, $item->linenbr),
					'invoiced' => $this->get_qty_invoiced($ponbr, $item->linenbr),
				),
				'cost'         => number_format($item->cost, $this->configs->decimal_places_cost()),
				'cost_total'   => number_format($item->cost_total, $this->configs->decimal_places_cost())
			);
		}
		return $js;
	}

/* =============================================================
	CRUD Processing Functions
============================================================= */
	/**
	 * Process Input Data and act on upon action
	 * @param  WireInput $input Input Data
	 * @return void
	 */
	public function process_input(WireInput $input) {
		$this->init_configs();
		$rm = strtolower($input->requestMethod());
		$values = $input->$rm;

		switch ($values->text('action')) {
			case 'add-item':
				$this->input_add_item($input);
				break;
			case 'update-item':
				$this->input_update_item($input);
				break;
		}
	}

	/**
	 * Add Item To Purchase Order
	 * @param WireInput $input Input data
	 * @return void
	 */
	public function input_add_item(WireInput $input) {
		$rm = strtolower($input->requestMethod());
		$values = $input->$rm;
		$q = $this->query($values->text('ponbr'));
		$count_before = $q->count();
		$this->request_add_item($values->text('ponbr'), $values->text('itemID'), $values->int('qty'));
		$count_after = $q->count();

		if ($count_after > $count_before) {
			$response = MpoResponse::response_success($values->text('ponbr'), $values->text('itemID') . ' was added to PO');
		} else {
			$response = MpoResponse::response_error($values->text('ponbr'), $values->text('itemID') . ' was not added to PO');
		}
		$this->wire('session')->response_epo = $response;
	}

	/**
	 * Update / Edit Purchase Order Item
	 * @param  WireInput $input Input Data
	 * @return void
	 */
	public function input_update_item(WireInput $input) {
		$rm = strtolower($input->requestMethod());
		$values = $input->$rm;
		$ponbr = $values->text('ponbr');
		$linenbr = $values->int('linenbr');
		$q = $this->query($ponbr);
		$item = $q->findOneByLinenbr($linenbr);
		$this->update_item($item, $input);

		if ($item->save()) {
			$this->request_update_item($ponbr, $linenbr);
			$response = MpoResponse::response_success($values->text('ponbr'), "Line #$linenbr was updated");
		} else {
			$response = MpoResponse::response_error($values->text('ponbr'), "Line #$linenbr was not updated");
		}
		$this->wire('session')->response_epo = $response;
	}

	/**
	 * Updates EditPoDetail Record
	 * @param  EditPoDetail $item  Purchase Order Item
	 * @param  WireInput    $input Input Data
	 * @return void
	 */
	protected function update_item(EditPoDetail $item, WireInput $input) {
		$rm = strtolower($input->requestMethod());
		$values = $input->$rm;
		$item->setItemid($values->text('itemID'));
		$item->setDescription($values->text('description'));
		$item->setVendoritemid($values->text('vendoritemID'));
		$item->setWhse($values->text('whse'));
		$item->setQty_ordered($values->text('qty_ordered'));
		$item->setCost($values->text('cost'));
		$item->setCost_total($values->text('cost_total'));
	}

/* =============================================================
	Dplus Request Functions
============================================================= */
	/**
	 * Send PO Add Item Request
	 * @param string $ponbr  Purchase Order Number
	 * @param string $itemID Item ID
	 * @param int    $qty    Qty
	 */
	public function request_add_item($ponbr, $itemID, int $qty = 1) {
		$config = $this->wire('config');
		$dplusdb = $this->wire('modules')->get('DplusOnlineDatabase')->db_name;
		$data = array("DBNAME=$dplusdb", 'ADDPURCHASEORDERLINE', "PONBR=$ponbr", "ITEMID=$itemID", "QTY=$qty");
		$requestor = $this->wire('modules')->get('DplusRequest');
		$requestor->write_dplusfile($data, session_id());
		$requestor->cgi_request($config->cgis['default'], $this->sessionID);
	}

	/**
	 * Send PO Update Item
	 * @param  string $ponbr   Purchase Order Number
	 * @param  int    $linenbr Line Number
	 * @return void
	 */
	public function request_update_item($ponbr, int $linenbr = 0) {
		$config = $this->wire('config');
		$dplusdb = $this->wire('modules')->get('DplusOnlineDatabase')->db_name;
		$data = array("DBNAME=$dplusdb", 'SAVEPURCHASEORDERLINE', "PONBR=$ponbr", "LINE=$linenbr");
		$requestor = $this->wire('modules')->get('DplusRequest');
		$requestor->write_dplusfile($data, session_id());
		$requestor->cgi_request($config->cgis['default'], $this->sessionID);
	}

/* =============================================================
	Supplemental Functions
============================================================= */
	/**
	 * Loads configs needed into $configs property
	 * @return void
	 */
	public function init_configs() {
		$this->configs->init_configs();
	}

	/**
	 * Returns Details for JS
	 * @param  string $ponbr Purchase Order Number
	 * @return array
	 */
	public function get_details_array($ponbr) {
		$array = array();
		$items = $this->query($ponbr);

		$this->init_configs();

		foreach ($items as $item) {
			$array[$item->linenbr] = array(
				'linenbr'      => $item->linenbr,
				'itemid'       => $item->itemid,
				'description'  => $item->description,
				'vendoritemid' => $item->vendoritemid,
				'whseid'       => $item->whse,
				'specialorder' => $item->specialorder,
				'uom'          => $item->uom,
				'qty' => array(
					'ordered'  => number_format($item->qty_ordered, $this->configs->decimal_places_qty()),
					'received' => $this->get_qty_received($ponbr, $item->linenbr),
					'invoiced' => $this->get_qty_invoiced($ponbr, $item->linenbr),
				),
				'cost'         => number_format($item->cost, $this->configs->decimal_places_cost()),
				'cost_total'   => number_format($item->cost_total, $this->configs->decimal_places_cost())
			);
		}
		return $array;
	}

	/**
	 * Return Purchase Order Item Qty Received
	 * @param  string $ponbr   Purchase Order Number
	 * @param  int    $linenbr Line Number
	 * @return float|int       Uses ConfigSo::decimal_places
	 */
	public function get_qty_received($ponbr, $linenbr) {
		$q = PurchaseOrderDetailReceivingQuery::create();
		$col = PurchaseOrderDetailReceiving::get_aliasproperty('qty_received');
		$q->withColumn("SUM($col)", 'qty');
		$q->select('qty');
		$q->filterByPonbr($ponbr);
		$q->filterByLinenbr($linenbr);
		return number_format($q->findOne(), $this->configs->decimal_places_qty());
	}

	/**
	 * Return Purchase Order Item Qty Invoiced
	 * @param  string $ponbr   Purchase Order Number
	 * @param  int    $linenbr Line Number
	 * @return float|int       Uses ConfigSo::decimal_places
	 */
	public function get_qty_invoiced($ponbr, $linenbr) {
		$q = ApInvoiceDetailQuery::create();
		$col = ApInvoiceDetail::get_aliasproperty('qty_received');
		$q->withColumn("SUM($col)", 'qty');
		$q->select('qty');
		$q->filterByPonbr($ponbr);
		$q->filterByLinenbr($linenbr);
		return number_format($q->findOne(), $this->configs->decimal_places_qty());
	}

	/**
	 * Return Warehouses
	 * @return Warehouse[]|ObjectCollection
	 */
	public function warehouses() {
		return WarehouseQuery::create()->find();
	}
}
