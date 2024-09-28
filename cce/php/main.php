<?php
/**
 * px2-wp-importer
 */
namespace picklesFramework2\px2WpImporter\cce;

/**
 * main.php
 */
class main {

	/** $px */
	private $px;

	/** $options */
	private $options;

	/** $cceAgent */
	private $cceAgent;

	/** $main */
	private $main;

	/**
	 * コンストラクタ
	 * @param object $px Pickles 2 オブジェクト
	 * @param object $options 設定オプション
	 * @param object $cceAgent 管理画面拡張エージェントオブジェクト
	 */
	public function __construct($px, $options, $cceAgent){
		$this->px = $px;
		$this->options = $options;
		$this->cceAgent = $cceAgent;

		$this->main = new \picklesFramework2\px2WpImporter\main($px);
	}

	/**
	 * 管理機能名を取得する
	 */
	public function get_label(){
		return 'Wp Importer';
	}

	/**
	 * フロントエンド資材の格納ディレクトリを取得する
	 */
	public function get_client_resource_base_dir(){
		return __DIR__.'/../front/';
	}

	/**
	 * 管理画面にロードするフロント資材のファイル名を取得する
	 */
	public function get_client_resource_list(){
		$rtn = array();
		$rtn['css'] = array();
		array_push($rtn['css'], 'wpImporterCceFront.css');
		$rtn['js'] = array();
		array_push($rtn['js'], 'wpImporterCceFront.js');
		return $rtn;
	}

	/**
	 * 管理画面を初期化するためのJavaScript関数名を取得する
	 */
	public function get_client_initialize_function(){
		return 'window.wpImporterCceFront';
	}

	/**
	 * General Purpose Interface (汎用API)
	 */
	public function gpi($request){
		switch($request->command){
			case 'upload':

				$realpath_cache = $this->main->realpath_private_cache();

				if( is_dir($realpath_cache.'work/') ){
					$this->px->fs()->rm($realpath_cache.'work/');
				}

				$this->px->fs()->mkdir($realpath_cache.'work/');
				$bin = base64_decode($request->fileInfo->base64);
				$ext = ($request->fileInfo->ext ?? 'bin');
				$realpath_uploaded_file = $realpath_cache.'work/uploaded.'.$ext;
				$this->px->fs()->save_file($realpath_uploaded_file, $bin);

				if( $request->fileInfo->mime_type == 'application/zip' ){
					$realpath_unzipped_dir = $realpath_cache.'work/unzipped/';
					$this->px->fs()->mkdir($realpath_unzipped_dir);
					$zipArchive = new \ZipArchive();

					// ZIPファイルを開く
					if ($zipArchive->open($realpath_uploaded_file) === true) {
						// 指定したディレクトリに解凍
						$zipArchive->extractTo($realpath_unzipped_dir);
						// ZIPファイルを閉じる
						$zipArchive->close();
					}
				}

				return array(
					"result" => true,
					"message" => "OK",
				);

		}
		return false;
	}
}