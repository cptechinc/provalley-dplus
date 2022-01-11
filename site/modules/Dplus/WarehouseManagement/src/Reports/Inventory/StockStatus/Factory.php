<?php namespace Dplus\Wm\Reports\Inventory\StockStatus;
// ProcessWire
use ProcessWire\WireData;
// Dplus Warehouse Managment Reports
use Dplus\Wm\Reports\Inventory\StockStatus;
use Dplus\Wm\Reports\Inventory\StockStatus\Export\Spreadsheet;

/**
 * Factory
 * Factory to handle generating report data
 *
 * @property StockStatus $reporter   Report Gatherer
 */
class Factory extends WireData {
	protected $reporter;
	protected $reportData;

	public function __construct() {
		$this->initReporter();
	}

	/**
	 * Export Report as a Spreadsheet
	 * @return bool
	 */
	public function exportSpreadsheet() {
		$this->reporter->generate();
		$exporter = new Spreadsheet();
		$exporter->setColumns($this->reporter->getAllColumns());
		$exporter->setReportData($this->reporter->getReportData());
		return $exporter->export();
	}

	/**
	 * Initialize / Set Reporter
	 * @return bool
	 */
	private function initReporter() {
		switch ($this->wire('config')->company) {
			case 'provalley':
				$this->reporter = new StockStatus\Provalley;
				break;
			default:
				$this->reporter = new StockStatus();
				break;
		}
	}
}
