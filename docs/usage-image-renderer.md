# Image Renderer

`ImageRenderer` は WordPress の attachment ID から画像 HTML を生成するレンダラーです。

### 使用例

```php
use Period\WpFramework\Infrastructure\WordPress\ImageRenderer;

$renderer = new ImageRenderer();
echo $renderer->render(123, [
    'size' => 'full',
    'class' => 'custom-image',
    'wrapper' => true,
    'wrapper_class' => 'image',
    'lazy' => true,
    'alt' => '代替テキスト',
]);
```

### 引数

- `size`: string, デフォルト `full`
- `class`: string, 追加の wrapper class
- `wrapper`: bool, デフォルト `true`
- `wrapper_class`: string, デフォルト `image`
- `lazy`: bool, デフォルト `true`
- `alt`: string|null, 明示的な alt テキスト

### 仕様

- `wp_get_attachment_image_src` が存在しない場合は空文字を返す
- 取得できない添付ファイルの場合は空文字を返す
- `ImageUtil::orientation` による orientation class を追加
- 比率制御は CSS (`object-fit`) を使用してください
- `esc_url` / `esc_attr` があればそれを利用し、安全に出力する

