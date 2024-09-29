<?php
/**
 * px2-wp-importer
 */
namespace picklesFramework2\px2WpImporter;

/**
 * WpImporter
 */
class WpImporter {

	private $fs;
	private $conditions;

	private $realpath_contents_xml;
	private $realpath_dist;

	/**
	 * constructor
	 */
	public function __construct( $conditions ){
		$this->fs = new \tomk79\filesystem();
		$this->conditions = (object) $conditions;
		$this->realpath_contents_xml = $this->conditions->xml;
		$this->realpath_dist = $this->conditions->dist;
	}

	/**
	 * start
	 */
	public function start(){

		if (!$this->fs->is_dir($this->realpath_dist)) {
			$this->fs->mkdir($this->realpath_dist);
		}
		$this->fs->mkdir($this->realpath_dist.'contents/');
		$this->fs->mkdir($this->realpath_dist.'sitemaps/');
		$this->fs->mkdir($this->realpath_dist.'blogs/');

		// サイトマップを初期化
		$sitemap_definition = $this->get_sitemap_definition();
		$sitemap_header_row = array();
		foreach( $sitemap_definition as $sitemap_definition_key=>$default_value ){
			$sitemap_header_row[$sitemap_definition_key] = '* '.$sitemap_definition_key;
		}
		$this->fs->save_file($this->realpath_dist.'sitemaps/sitemap_imported.csv', $this->fs->mk_csv(array($sitemap_header_row)));

		// ブログマップを初期化
		$blogmap_definition = $this->get_blogmap_definition();
		$blogmap_header_row = array();
		foreach( $blogmap_definition as $blogmap_definition_key=>$default_value ){
			$blogmap_header_row[$blogmap_definition_key] = '* '.$blogmap_definition_key;
		}
		$this->fs->save_file($this->realpath_dist.'blogs/blog_imported.csv', $this->fs->mk_csv(array($blogmap_header_row)));


		// XMLReaderのインスタンス作成
		$reader = new \XMLReader();
		$reader->open($this->realpath_contents_xml);

		// 記事カウント用
		$articleCount = 0;

		while ($reader->read()) {
			// <item> タグ（記事）を見つける
			if ($reader->nodeType == \XMLReader::ELEMENT && $reader->name == 'item') {
				// SimpleXMLで<item>ノード全体を解析
				$itemNode = $reader->readOuterXML();
				$item = simplexml_load_string($itemNode);

				// 記事タイトル
				$title = (string) $item->title;

				// 記事本文 (CDATAセクションとして保存されている可能性があります)
				$content = (string) $item->children('content', true)->encoded;

				// 日付 (オプションで使用できます)
				$pubDate = (string) $item->pubDate;

				// ファイル名を作成
				$fileName = $this->realpath_dist.'contents/article-'.(++$articleCount).'.html';

				// HTMLファイルのコンテンツを構築
				$htmlContent = "
<!DOCTYPE html>
<html>
	<head>
		<title>{$title}</title>
		<meta charset='UTF-8'>
	</head>
	<body>
		<h1>{$title}</h1>
		<p><em>Published on: {$pubDate}</em></p>
		<div>{$content}</div>
	</body>
</html>";

				// HTMLファイルとして保存
				file_put_contents($fileName, $htmlContent);
			}
		}

		// XMLReaderを閉じる
		$reader->close();

	}

	private function get_sitemap_definition(){
		return array(
			'path' => null,
			'content' => null,
			'id' => null,
			'title' => null,
			'title_breadcrumb' => null,
			'title_h1' => null,
			'title_label' => null,
			'title_full' => null,
			'logical_path' => null,
			'list_flg' => null,
			'layout' => null,
			'orderby' => null,
			'keywords' => null,
			'description' => null,
			'category_top_flg' => null,
			'role' => null,
			'proc_type' => null,
		);
	}
	private function get_blogmap_definition(){
		return array(
			'title' => null,
			'path' => null,
			'release_date' => null,
			'update_date' => null,
			'article_summary' => null,
			'article_keywords' => null,
		);
	}

}
