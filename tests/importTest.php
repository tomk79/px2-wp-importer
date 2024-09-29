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

		$this->fs->rm(__DIR__."/testdata/imports/pattern01/dist/");
		$this->fs->mkdir(__DIR__."/testdata/imports/pattern01/dist/");

		$wpImporter = new \picklesFramework2\px2WpImporter\WpImporter(array(
			"xml" => __DIR__."/testdata/imports/pattern01/input/contents.xml",
			"assets" => __DIR__."/testdata/imports/pattern01/input/assets/",
			"dist" => __DIR__."/testdata/imports/pattern01/dist/",
		));
		$wpImporter->start();

		$this->assertTrue( is_object($wpImporter) );
	}

}
