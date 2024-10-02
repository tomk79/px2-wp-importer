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
		$asset_file_list = array();
		if( is_dir($this->realpath_assets) ){
			$tmp_asset_files = $this->fs->ls($this->realpath_assets);
			foreach($tmp_asset_files as $tmp_asset_file){
				$tmp_parsed_url = parse_url($tmp_asset_file);
				$asset_file_list[$tmp_parsed_url['path']] = array(
					"basename" => $tmp_parsed_url['path'],
					"full_filename" => $tmp_asset_file,
				);
			}
			unset($tmp_parsed_url);
			unset($tmp_asset_files);
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
				if( !isset($asset_file_list[$basename]) ){
					continue;
				}
				$image_basename = $asset_file_list[$basename]["basename"];
				$realpath_image = $this->realpath_assets.$asset_file_list[$basename]["full_filename"];
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

		// --------------------------------------
		// DOM中からp要素を抽出して、添付ファイルを置き換え
		$paragraphs = $simple_html_dom->find('p');
		foreach($paragraphs as $paragraph){
			$innerHTML = trim($paragraph->innertext);
			if( preg_match('/^\/assets\/[^\<\>\"]+$/', $innerHTML) ){
				// --------------------
				// assetsからコピーする
				$parsed_asset_path = parse_url($innerHTML);
				$basename = basename($parsed_asset_path["path"]);
				if( !isset($asset_file_list[$basename]) ){
					continue;
				}
				$asset_basename = $asset_file_list[$basename]["basename"];
				$realpath_asset = $this->realpath_assets.$asset_file_list[$basename]["full_filename"];
				if( is_file($realpath_asset) ){
					$this->fs->mkdir_r($realpath_files.'assets/');
					$this->fs->copy(
						$realpath_asset,
						$realpath_files.'assets/'.$asset_basename
					);

					$ext = preg_replace('/^.*\.([a-zA-Z0-9\-\_]+)$/', '$1', $asset_basename) ?? '';
var_dump($ext);
					switch( strtolower($ext) ){
						case 'mp4':
						case 'mov':
						case 'webm':
							$paragraph->innertext = '<video controls'
								.' src="<'.'?= $px->h($px->path_files('.json_encode('/assets/'.$asset_basename, JSON_UNESCAPED_SLASHES).')) ?'.'>"'
								.'>'
								.'</video>';
							break;

						case 'mp3':
						case 'wav':
							$paragraph->innertext = '<audio controls'
								.' src="<'.'?= $px->h($px->path_files('.json_encode('/assets/'.$asset_basename, JSON_UNESCAPED_SLASHES).')) ?'.'>"'
								.'>'
								.'</audio>';
							break;

						default:
							$paragraph->innertext = '<a class="px2-btn px2-btn--download"'
								.' href="<'.'?= $px->h($px->path_files('.json_encode('/assets/'.$asset_basename, JSON_UNESCAPED_SLASHES).')) ?'.'>"'
								.' download="'.htmlspecialchars($asset_basename).'">'
								.'Download: '.htmlspecialchars($asset_basename)
								.'</a>';
							break;
					}
				}
			}
		}

		$content = $simple_html_dom->outertext;

		return $content;
	}
}
