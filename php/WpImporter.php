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
	private $realpath_assets;
	private $realpath_dist;

	/**
	 * constructor
	 */
	public function __construct( $conditions ){
		$this->fs = new \tomk79\filesystem();
		$this->conditions = (object) $conditions;
		$this->realpath_contents_xml = $this->conditions->xml;
		$this->realpath_assets = $this->conditions->assets;
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
		$this->fs->save_file(
			$this->realpath_dist.'sitemaps/sitemap_imported.csv',
			$this->fs->mk_csv(array($sitemap_header_row))
		);

		// ブログマップを初期化
		$blogmap_definition = $this->get_blogmap_definition();
		$blogmap_header_row = array();
		foreach( $blogmap_definition as $blogmap_definition_key=>$default_value ){
			$blogmap_header_row[$blogmap_definition_key] = '* '.$blogmap_definition_key;
		}
		$this->fs->save_file(
			$this->realpath_dist.'blogs/blog_imported.csv',
			$this->fs->mk_csv(array($blogmap_header_row))
		);


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

				// URL
				$link = (string) $item->link;
				$parsed_url = parse_url(trim($link));
				$path_content = $parsed_url['path'];
				if(preg_match('/\/$/s',$path_content)){
					$path_content .= 'index.html';
				}
				if(!preg_match('/\.html?$/s',$path_content)){
					$path_content .= '.html';
				}

				// description
				$description = (string) $item->description;

				// ファイル名を作成
				$realpath_content = $this->realpath_dist.'contents'.$path_content;

				// 記事の種類
				$postType = $item->children('wp', true)->post_type;

				// 記事の種類
				$author = $item->children('dc', true)->creator;

				// 記事本文 (CDATAセクションとして保存されている可能性があります)
				$content = (string) $item->children('content', true)->encoded;

				// 日付 (オプションで使用できます)
				$pubDate = (string) $item->pubDate;

				if( strlen($content ?? '') && ($postType == 'post' || $postType == 'page') ){
					$contentsProcessor = new ContentsProcessor($this->conditions);
					$content = $contentsProcessor->resolve_images(
						$content,
						array(
							'path' => $path_content,
							'realpath' => $realpath_content,
							'path_files' => '{$dirname}/{$filename}_files/',
						)
					);
				}

				if( $postType == 'post' ){
					// --------------------------------------
					// ブログ記事

					$blogmap_definition = $this->get_blogmap_definition();
					$blogmap_definition['path'] = $path_content;
					$blogmap_definition['title'] = $title;
					$blogmap_definition['release_date'] = $pubDate;
					$blogmap_definition['update_date'] = $pubDate;
					$sitemap_definition['article_summary'] = $description;
					$sitemap_definition['author'] = $author;

					file_put_contents(
						$this->realpath_dist.'blogs/blog_imported.csv',
						$this->fs->mk_csv(array($blogmap_definition)),
						FILE_APPEND
					);

					// HTMLファイルとして保存
					$this->fs->mkdir_r(dirname($realpath_content));
					$this->fs->save_file($realpath_content, $content);

				}elseif( $postType == 'page' ){
					// --------------------------------------
					// 固定ページ

					$sitemap_definition = $this->get_sitemap_definition();
					$sitemap_definition['path'] = $path_content;
					$sitemap_definition['title'] = $title;
					$sitemap_definition['description'] = $description;
					$sitemap_definition['list_flg'] = 1;
					$sitemap_definition['author'] = $author;

					file_put_contents(
						$this->realpath_dist.'sitemaps/sitemap_imported.csv',
						$this->fs->mk_csv(array($sitemap_definition)),
						FILE_APPEND
					);

					// HTMLファイルとして保存
					$this->fs->mkdir_r(dirname($realpath_content));
					$this->fs->save_file($realpath_content, $content);
				}

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

			'author' => null,
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

			'author' => null,
		);
	}

}
