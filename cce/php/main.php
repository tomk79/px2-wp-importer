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
		$realpath_cache = $this->main->realpath_private_cache();
		$realpath_chunks = $realpath_cache.'chunks/';
		$realpath_file_info = $realpath_cache.'chunks/fileInfo.json';
		$realpath_uploaded = $realpath_cache.'uploaded/';
		$realpath_tmp_dist = $realpath_cache.'dist/';

		switch($request->command){
			case 'upload_init':
				// --------------------------------------
				// ファイルのアップロードを初期化する
				if( is_dir($realpath_chunks) ){
					$this->px->fs()->rm($realpath_chunks);
				}
				if( is_dir($realpath_uploaded) ){
					$this->px->fs()->rm($realpath_uploaded);
				}
				$this->px->fs()->mkdir($realpath_chunks);
				$this->px->fs()->mkdir($realpath_uploaded);
				$this->px->fs()->save_file($realpath_file_info, json_encode($request->fileInfo));

				return array(
					"result" => true,
					"message" => "OK",
				);

			case 'upload_chunk':
				// --------------------------------------
				// ファイルの断片のアップロードを受け付ける

				$realpath_uploaded_file = $realpath_cache.'_chunks/_'.intval($request->num ?? 0).'.txt';
				$this->px->fs()->save_file($realpath_uploaded_file, trim($request->chunk ?? ''));

				return array(
					"result" => true,
					"message" => "OK",
				);

			case 'upload_finalize':
				// --------------------------------------
				// ファイルの断片を統合する
				$realpath_xml_file = $realpath_uploaded.'contents.xml';
				$realpath_assets_dir = $realpath_uploaded.'assets/';

				$fileInfo = json_decode( file_get_contents( $realpath_file_info) );

				$base64 = '';
				for($i = 0; $i < $fileInfo->chunkCount; $i++){
					$base64 .= file_get_contents($realpath_chunks.'_'.intval($i).'.txt');
				}

				$bin = base64_decode($base64);
				$ext = ($fileInfo->ext ?? 'bin');
				$realpath_uploaded_file = $realpath_chunks.'uploaded.'.$ext;
				$this->px->fs()->save_file($realpath_uploaded_file, $bin);

				if( $fileInfo->mime_type == 'text/xml' ){
					$this->px->fs()->rename($realpath_uploaded_file, $realpath_xml_file);
				}elseif( $fileInfo->mime_type == 'application/zip' ){
					$realpath_unzipped_dir = $realpath_chunks.'unzipped/';
					$this->px->fs()->mkdir($realpath_unzipped_dir);
					$zipArchive = new \ZipArchive();

					// ZIPファイルを開く
					if ($zipArchive->open($realpath_uploaded_file) === true) {
						// 指定したディレクトリに解凍
						$zipArchive->extractTo($realpath_unzipped_dir);
						// ZIPファイルを閉じる
						$zipArchive->close();

						$files = $this->px->fs()->ls($realpath_unzipped_dir);
						foreach( $files as $basename ){
							if( is_file($realpath_unzipped_dir.$basename) ){
								if( $this->px->fs()->get_extension($basename) == 'xml' ){
									$this->px->fs()->rename($realpath_unzipped_dir.$basename, $realpath_xml_file);
								}
							}elseif( is_dir($realpath_unzipped_dir.$basename) && $basename == 'assets' ){
								$this->px->fs()->rename($realpath_unzipped_dir.$basename, $realpath_assets_dir);
							}
						}
					}

				}

				return array(
					"result" => true,
					"message" => "OK",
				);

			case 'import':
				// --------------------------------------
				// インポートを実行する
				$WpImporter = new \picklesFramework2\px2WpImporter\WpImporter(array(
					"xml" => $realpath_uploaded."contents.xml",
					"assets" => $realpath_uploaded."assets/",
					"dist" => $realpath_tmp_dist,
				));
				$WpImporter->start();

				return array(
					"result" => true,
					"message" => "OK",
				);
		}
		return false;
	}
}