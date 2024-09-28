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

	/** プラグイン専有の非公開キャッシュディレクトリの内部パスを取得する */
	public function realpath_private_cache( $localpath = null ){
		$rtn = $this->px->realpath_plugin_private_cache($localpath);
		return $rtn;
	}
}
