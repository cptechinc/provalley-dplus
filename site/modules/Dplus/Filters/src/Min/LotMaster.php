<?php namespace Dplus\Filters\Min;
// Dplus Model
use InvLot, ItemMasterItem as Model;
// ProcessWire Classes
use ProcessWire\WireData, ProcessWire\WireInput, ProcessWire\Page;
// Dplus Filters
use Dplus\Filters\AbstractFilter;

/**
 * Wrapper Class for adding Filters to the InvLot class
 */
class LotMaster extends AbstractFilter {
	const MODEL = 'InvLot';

/* =============================================================
	1. Abstract Contract / Extensible Functions
============================================================= */
	public function _search($q) {
		$columns = [
			Model::aliasproperty('lotserial'),
			Model::aliasproperty('itemid'),
		];
		$this->query->search_filter($columns, strtoupper($q));
	}

/* =============================================================
	Base Filter Functions
============================================================= */


/* =============================================================
	Misc Query Functions
============================================================= */

}
