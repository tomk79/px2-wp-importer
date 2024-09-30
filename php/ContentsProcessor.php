<?php
/**
 * px2-wp-importer
 */
namespace picklesFramework2\px2WpImporter;

/**
 * ContentsProcessor
 */
class ContentsProcessor {

	private $fs;
	private $conditions;

	private $realpath_assets;
	private $realpath_dist;

	/**
	 * constructor
	 */
	public function __construct( $conditions ){
		$this->fs = new \tomk79\filesystem();
		$this->conditions = (object) $conditions;
		$this->realpath_assets = $this->conditions->assets;
		$this->realpath_dist = $this->conditions->dist;
	}

	/**
	 * 記事中で参照されている画像のリンクを解決する
	 * 
	 * @param String $content 加工前のコンテンツHTML
	 * @param Object $options オプション
	 */
	public function resolve_images( $content, $options ){
		$options = (object) $options;

		require_once(__DIR__.'/simple_html_dom.php');
		$simple_html_dom = \picklesFramework2\px2WpImporter\str_get_html(
			$content,
			true, // $lowercase
			true, // $forceTagsClosed
			DEFAULT_TARGET_CHARSET, // $target_charset
			false, // $stripRN
			DEFAULT_BR_TEXT, // $defaultBRText
			DEFAULT_SPAN_TEXT // $defaultSpanText
		);

		// --------------------------------------
		// 画像のリストを整理する
		$image_list = array();
		if( is_dir($this->realpath_assets) ){
			$tmp_images = $this->fs->ls($this->realpath_assets);
			foreach($tmp_images as $tmp_image){
				$parsed_url = parse_url($tmp_image);
				$image_list[$parsed_url['path']] = array(
					"basename" => $parsed_url['path'],
					"full_filename" => $tmp_image,
				);
				unset($parsed_url);
			}
		}

		// --------------------------------------
		// filesディレクトリの名前を調べる
		$path_files = $options->path_files;
		$path_basename = basename($options->path);
		$path_basename_no_ext = preg_replace('/\.[a-zA-Z0-9]+$/', '', $path_basename);
		$path_files = preg_replace('/\{\$dirname\}/', dirname($options->path), $path_files);
		$path_files = preg_replace('/\{\$filename\}/', $path_basename_no_ext, $path_files);
		$realpath_files = $this->realpath_dist.'contents'.$path_files;

		// --------------------------------------
		// DOM中から画像を抽出して置き換え
		$imgs = $simple_html_dom->find('img');
		foreach($imgs as $img){
			$image_path = trim($img->src);
			if( preg_match('/^https?\:\/\//', $image_path) ){
				// --------------------
				// ネットワークからダウンロードする
				$parsed_image_path = parse_url($image_path);
				$basename = basename($parsed_image_path["path"]);
				$image_basename = $basename;

				$downloaded = file_get_contents($image_path);

				$this->fs->mkdir_r($realpath_files.'assets/');
				$this->fs->save_file(
					$realpath_files.'assets/'.$image_basename,
					$downloaded
				);
				$img->src = '<'.'?= $px->h($px->path_files('.json_encode('/assets/'.$image_basename, JSON_UNESCAPED_SLASHES).')) ?'.'>';

			}elseif( preg_match('/^\/assets\//', $image_path) ){
				// --------------------
				// assetsからコピーする
				$parsed_image_path = parse_url($image_path);
				$basename = basename($parsed_image_path["path"]);
				if( !isset($image_list[$basename]) ){
					continue;
				}
				$image_basename = $image_list[$basename]["basename"];
				$realpath_image = $this->realpath_assets.$image_list[$basename]["full_filename"];
				if( is_file($realpath_image) ){
					$this->fs->mkdir_r($realpath_files.'assets/');
					$this->fs->copy(
						$realpath_image,
						$realpath_files.'assets/'.$image_basename
					);
					$img->src = '<'.'?= $px->h($px->path_files('.json_encode('/assets/'.$image_basename, JSON_UNESCAPED_SLASHES).')) ?'.'>';
				}
			}
		}

		$content = $simple_html_dom->outertext;

		return $content;
	}
}
