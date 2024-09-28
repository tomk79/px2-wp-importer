# px2-wp-importer

[Pickles 2](https://pickles2.com/)に、サイト内検索機能を追加します。

## Setup - セットアップ手順

### [Pickles 2 プロジェクト](https://pickles2.com/) をセットアップ

### 1. `composer.json` に、パッケージ情報を追加

```bash
$ composer require tomk79/px2-wp-importer
```

### 2. 管理画面拡張

`config.php` に次のような設定を追加します。

```php
$conf->plugins->px2dt->custom_console_extensions = array(
    'px2-wp-importer' => array(
        'class_name' => 'picklesFramework2\px2WpImporter\cce\main()',
    ),
);
```


## PXコマンド - PX Commands

### PX=wp_importer.create_index

インデックスファイルを生成する。


## 変更履歴 - Change Log

### tomk79/px2-wp-importer v0.1.0 (リリース日未定)

- Initial Release.


## ライセンス - License

MIT License


## 作者 - Author

- (C)Tomoya Koyanagi <tomk79@gmail.com>
- website: <https://www.pxt.jp/>
- Twitter: @tomk79 <https://twitter.com/tomk79/>
