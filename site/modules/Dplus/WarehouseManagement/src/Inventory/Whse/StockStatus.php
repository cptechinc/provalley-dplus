<?php namespace Dplus\Wm\Inventory\Whse;
// Dplus Model
use WhseLotserial as Model;
// ProcessWire
use ProcessWire\WireData;

use Dplus\Wm\Inventory\Whse\Lots\Lookup\ExcludePackBin as InvLots;

/**
 * StockStatus
 * Class for getting Stock Status
 */
class StockStatus extends WireData {
	protected $whseID;

	public function __construct() {
		$this->inventory = new InvLots();
	}

/* =============================================================
	Setter Functions
============================================================= */
	public function setWhseID($whseID) {
		$this->whseID = $whseID;
		$this->inventory->setWhseID($whseID);
	}

/* =============================================================
	Data Functions
============================================================= */
	/**
	 * Return Entire Stock Status
	 * @return array
	 */
	public function getData() {
		$items = $this->getItemsGroupedByBin();
		$data = [];

		foreach ($items as $item) {
			$data[$item['binid'] . '-' . $item['itemid']] = $this->getBinItemData($item);
		}
		return $data;
	}

	/**
	 * Return Data for Item and Bin
	 * @param  array  $item
	 * @return array
	 */
	protected function getBinItemData(array $item) {
		$lots = $this->getBinItemidLots($item['binid'], $item['itemid']);

		$data = [
			'binid'  => $item['binid'],
			'itemid' => $item['itemid'],
			'totals' => [
				'qty'      => $item['qty'],
				'lotcount' => sizeof($lots),
			],
			'lots' => $lots
		];
		return $data;
	}

	/**
	 * Return Lots for Item and Bin
	 * @param  string $binID   Bin ID
	 * @param  string $itemID  Item ID
	 * @return array
	 */
	protected function getBinItemidLots($binID, $itemID) {
		$q = $this->inventory->queryWhse();
		$q->filterByBinid($binID)->filterByItemid($itemID);
		$q->withColumn(Model::aliasproperty('binid'), 'binid');
		$q->withColumn(Model::aliasproperty('itemid'), 'itemid');
		$q->withColumn(Model::aliasproperty('lotserial'), 'lotserial');
		$q->withColumn(Model::aliasproperty('lotserial'), 'lotserial');
		$q->withColumn(Model::aliasproperty('qty'), 'qty');
		$q->withColumn(Model::aliasproperty('expiredate'), 'expiredate');
		$q->select(['binid', 'itemid', 'qty']);
		return $q->find()->toArray();
	}

	/**
	 * Return Summarized Item Data
	 * @return array
	 */
	public function getItemsGroupedByBin() {
		$q = $this->inventory->queryWhseBins();
		$q->withColumn('SUM(InltOnHand)', 'qty');
		$q->withColumn(Model::aliasproperty('binid'), 'binid');
		$q->withColumn(Model::aliasproperty('itemid'), 'itemid');
		$q->select(['binid', 'itemid', 'qty']);
		$q->groupBy(['binid', 'itemid']);
		return $q->find();
	}
}
