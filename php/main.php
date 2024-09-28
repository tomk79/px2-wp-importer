<?php
/**
 * px2-site-search
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
	 * プラグイン設定オブジェクト
	 */
	private $plugin_conf;

	/**
	 * constructor
	 * @param object $px Picklesオブジェクト
	 * @param object $plugin_conf プラグイン設定
	 */
	public function __construct( $px, $plugin_conf ){
		$this->px = $px;
		$this->plugin_conf = $plugin_conf;
	}

	public function px(){
		return $this->px;
	}

	public function plugin_conf(){
		return $this->plugin_conf;
	}
}
