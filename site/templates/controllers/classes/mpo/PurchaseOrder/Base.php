<?php namespace Controllers\Mpo\PurchaseOrder;
// Purl URI Library
use Purl\Url as Purl;
// Dplus Model
use PurchaseOrder;
// ProcessWire Classes, Modules
use ProcessWire\Page, ProcessWire\PurchaseOrderEdit as EpoModel;
// Dplus Validators
use Dplus\CodeValidators\Mpo as MpoValidator;
// Mvc Controllers
use Mvc\Controllers\AbstractController;

abstract class Base extends AbstractController {
	private static $validate;


/* =============================================================
	URLs
============================================================= */
	public static function poUrl($ponbr = '') {
		$url = new Purl(self::pw('pages')->get('pw_template=purchase-order-view')->url);
		if ($ponbr) {
			$url->query->set('ponbr', $ponbr);
		}
		return $url->getUrl();
	}

	public static function receivedUrl($ponbr) {
		$url = new Purl(self::poUrl($ponbr));
		$url->path->add('received');
		return $url->getUrl();
	}

/* =============================================================
	Displays
============================================================= */
	protected static function invalidPo($data) {
		$config = self::pw('config');
		$html = '';
		$html .= $config->twig->render('util/alert.twig', ['type' => 'danger', 'title' => 'Purchase Order Not Found', 'iconclass' => 'fa fa-warning fa-2x', 'message' => "PO # $data->ponbr can not be found"]);
		$html .= '<div class="mb-3"></div>';
		$html .= self::lookupForm();
		return $html;
	}

	protected static function lookupForm() {
		$page = self::pw('page');
		$config = self::pw('config');
		$page->body .= $config->twig->render('purchase-orders/purchase-order/lookup-form.twig');
		return $page->body;
	}

/* =============================================================
	Supplemental
============================================================= */
	/**
	 * Return Mpo Validator
	 * @return MpoValidator
	 */
	protected static function validator() {
		if (empty(self::$validate)) {
			self::$validate = new MpoValidator();
		}
		return self::$validate;
	}

}
