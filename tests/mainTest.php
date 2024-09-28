<?php
/**
 * Test for pickles2/px2-paprika
 */

class mainTest extends PHPUnit\Framework\TestCase {
	private $fs;

	/**
	 * setup
	 */
	public function setUp() : void{
		$this->fs = new \tomk79\filesystem();
	}

	/**
	 * プレビュー表示時のテスト
	 */
	public function testPreview(){

		// トップページの出力コードを検査
		$indexHtml = $this->passthru( [
			'php',
			__DIR__.'/testdata/standard/.px_execute.php' ,
			'-u', 'Mozilla/0.5',
			'/index.html' ,
		] );
		$this->assertTrue( !!preg_match('/\<h1\>HOME\<\/h1\>/si', $indexHtml) );


		// sample.php を実行
		$output = $this->passthru( [
			'php',
			__DIR__.'/testdata/standard/.px_execute.php' ,
			'-u', 'Mozilla/0.5',
			'/basic/php_api-ajax_files/apis/sample.php'
		] );
		$json = json_decode($output);

		$this->assertTrue( is_null($json->paprikaConf->undefined) );
		$this->assertEquals( $json->paprikaConf->sample1, 'config_local.php' );
		$this->assertFalse( property_exists($json->paprikaConf->sample2, 'prop1') );
		$this->assertEquals( $json->paprikaConf->sample2->prop2, 'config_local.php' );
		$this->assertEquals( $json->paprikaConf->sample3, 'config.php' );
		$this->assertEquals( $json->paprikaConf->prepend1, 1 );
		$this->assertEquals( $json->paprikaConf->prepend2, 2 );
		$this->assertEquals( $json->paprikaConf->custom_func_a, 'called' );

		// 後始末
		$output = $this->passthru( [
			'php',
			__DIR__.'/testdata/standard/.px_execute.php' ,
			'/?PX=clearcache' ,
		] );
	}

	/**
	 * カスタムコマンドを実行するテスト
	 */
	public function testCustomCommands(){

		// paprika.test
		$output = $this->passthru( [
			'php',
			__DIR__.'/testdata/standard/.px_execute.php' ,
			'/?PX=paprika.test' ,
		] );

		// paprika.migrate
		$output = $this->passthru( [
			'php',
			__DIR__.'/testdata/standard/.px_execute.php' ,
			'/?PX=paprika.migrate' ,
		] );
		$this->assertTrue( is_file(__DIR__.'/testdata/standard/px-files/paprika/_database.sqlite') );

		// 後始末
		$output = $this->passthru( [
			'php',
			__DIR__.'/testdata/standard/.px_execute.php' ,
			'/?PX=clearcache' ,
		] );
	}


	/**
	 * コマンドを実行し、標準出力値を返す
	 * @param array $ary_command コマンドのパラメータを要素として持つ配列
	 * @return string コマンドの標準出力値
	 */
	private function passthru( $ary_command ){
		$cmd = array();
		foreach( $ary_command as $row ){
			$param = '"'.addslashes($row).'"';
			array_push( $cmd, $param );
		}
		$cmd = implode( ' ', $cmd );
		ob_start();
		passthru( $cmd );
		$bin = ob_get_clean();
		return $bin;
	}

}
