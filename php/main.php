<?php
/**
 * px2-wp-importer
 */
namespace picklesFramework2\px2WpImporter;

/**
 * main.php
 */
class main {

	/**
	 * Picklesオブジェクト
	 */
	private $px;

	/**
	 * constructor
	 * @param object $px Picklesオブジェクト
	 */
	public function __construct( $px ){
		$this->px = $px;
	}

	public function px(){
		return $this->px;
	}
}
