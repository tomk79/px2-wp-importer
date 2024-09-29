<?php
/**
 * px2-wp-importer
 */
namespace picklesFramework2\px2WpImporter;

/**
 * WpImporter
 */
class WpImporter {

	private $conditions;

	/**
	 * constructor
	 */
	public function __construct( $conditions ){
		$this->conditions = (object) $conditions;
	}

	/**
	 * start
	 */
	public function start(){

	}
}
