<?php
/**
 * Test for tomk79/px2-wp-importer
 */
class importTest extends PHPUnit\Framework\TestCase {
	private $fs;

	/**
	 * setup
	 */
	public function setUp() : void{
		$this->fs = new \tomk79\filesystem();
	}

	/**
	 * インポートテスト
	 */
	public function testImport(){
		$wpImporter = new \picklesFramework2\px2WpImporter\WpImporter(array(
			"xml" => "./testdata/imports/pattern01/input/contents.xml",
			"assets" => "./testdata/imports/pattern01/input/assets/",
			"dist" => "./testdata/imports/pattern01/dist/",
		));
		$wpImporter->start();

		$this->assertTrue( is_object($wpImporter) );
	}

}
