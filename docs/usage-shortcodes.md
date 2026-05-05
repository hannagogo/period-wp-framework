# ShortcodeRegistrar（WordPress ショートコード）

`ShortcodeRegistrar` は `DocumentRenderer` / `TitleResolver` / `SiteInfo` をショートコードとして利用可能にします。`add_shortcode` が存在しない環境では何も行いません。

### 登録

```php
use Period\WpFramework\Infrastructure\WordPress\ShortcodeRegistrar;

$registrar = new ShortcodeRegistrar();
$registrar->register();
```

---

## [document]

`DocumentRenderer` を使って完全な HTML ドキュメントを生成します。

```
[document]<p>本文</p>[/document]
```

囲んだ内容が `<body>` 内に配置されます。

---

## [title]

`TitleResolver::siteTitle()` を使ってページタイトルを出力します。

```
[title]
```

WordPress がある場合は現在のページタイトルとサイト名を結合した文字列を返します。

---

## [site_name]

`SiteInfo::name()` を使ってサイト名を出力します。

```
[site_name]
```

WordPress がある場合は `get_bloginfo('name')` の結果を返します。
