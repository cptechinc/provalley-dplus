<?php namespace Dplus\Filters\Min;
// Dplus Model
use InvLot;
use WhseLotserialQuery, WhseLotserial;
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
			InvLot::aliasproperty('lotserial'),
			InvLot::aliasproperty('itemid'),
		];
		$this->query->search_filter($columns, strtoupper($q));
	}

/* =============================================================
	Base Filter Functions
============================================================= */
	/**
	 * Filter To In Stock Lots
	 */
	public function inStock() {
		$q = WhseLotserialQuery::create();
		$q->select(WhseLotserial::aliasproperty('lotserial'));
		$lotnbrs = $q->find()->toArray();
		$this->query->filterByLotnbr($lotnbrs);
	}


/* =============================================================
	Misc Query Functions
============================================================= */

}
