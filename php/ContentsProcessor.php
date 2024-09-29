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

	/**
	 * constructor
	 */
	public function __construct( $conditions = null ){
		$this->fs = new \tomk79\filesystem();
		$this->conditions = (object) $conditions;
	}

	/**
	 * 記事中で参照されている画像のリンクを解決する
	 * 
	 * @param String $content 加工前のコンテンツHTML
	 */
	public function resolve_images( $content ){

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

		$imgs = $simple_html_dom->find('img');
		foreach($imgs as $img){

			// var_dump($img->src); // TODO: サーバーから画像ファイルをダウンロードして、`_files/` に格納する

		}

		return $content;
	}
}
