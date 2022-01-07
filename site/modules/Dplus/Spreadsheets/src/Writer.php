<?php namespace Dplus\Spreadsheets;
// PhpSpreadsheet Library
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as WriterXlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

abstract class Writer extends WireData {
	const EXTENSION = 'xlsx';

	public function __construct() {
		$this->directory = $this->wire('config')->directory_webdocs;;

		$this->filename   = 'spreadsheet';
		$this->fileprefix = session_id();
	}

	/**
	 * Return Spreadsheet File Writer
	 * @return BaseWriter
	 */
	abstract protected function getWriter(Spreadsheet $spreadsheet);

	/**
	 * Return Filepath for file
	 * @return string
	 */
	public function getFilepath() {
		return $this->directory.$this->fileprefix.'-'.$this->filename.'.'.static::EXTENSION;
	}

	/**
	 * Writes Spreadsheet to File
	 * @param  Spreadsheet $spreadsheet Spreadsheet
	 * @return bool
	 */
	public function write(Spreadsheet $spreadsheet) {
		$writer = $this->getWriter($spreadsheet);
		return $writer->save($this->getFilepath());
	}
}
