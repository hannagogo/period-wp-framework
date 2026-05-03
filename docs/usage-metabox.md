# MetaBox Usage

## gallery field

新しい `gallery` field type は WordPress 標準の `wp.media` を使用して複数画像を選択できます。

### field config

- `name`: string 必須
- `label`: string
- `type`: `gallery`
- `description`: string
- `button_label`: string, デフォルト `画像を選択`
- `clear_label`: string, デフォルト `クリア`
- `preview`: bool, デフォルト `true`
- `mime`: string|null, デフォルト `image`
- `sortable`: bool, デフォルト `true`

### 保存形式

- 保存値は attachment ID の配列
- hidden input は JSON 文字列で保持
- 空時は `[]` として扱う

### 動作

- `wp_enqueue_media()` が呼ばれる
- preview が有効な場合、保存済み attachment ID のサムネイルを一覧表示
- `SortableJS` が `assets/vendor/sortable/Sortable.min.js` に存在する場合は並び替えを有効化
- SortableJS がない場合は D&D 並び替えなしで動作

### 例

```php
new \Period\WpFramework\Infrastructure\WordPress\MetaBox([
    'id' => 'gallery_box',
    'title' => 'Gallery Box',
    'post_type' => 'post',
    'fields' => [
        [
            'name' => 'gallery_ids',
            'type' => 'gallery',
            'label' => 'Gallery',
            'button_label' => '画像を選択',
            'clear_label' => 'クリア',
            'mime' => 'image',
            'sortable' => true,
        ],
    ],
]);
```

## repeater field

新しい `repeater` field type は複数のフィールドグループを動的に追加・削除・並び替えできます。

### field config

- `name`: string 必須
- `label`: string
- `type`: `repeater`
- `fields`: array 必須（子フィールド定義）
- `description`: string
- `button_label`: string, デフォルト `追加`
- `sortable`: bool, デフォルト `true`
- `min`: int, デフォルト `0`
- `max`: int|null, デフォルト `null`

### 保存形式

- 保存値は JSON 配列
- 각 item はオブジェクトとして保存される
- hidden input は JSON 文字列を保持
- 空時は `[]` として扱う

### 例

```php
new \Period\WpFramework\Infrastructure\WordPress\MetaBox([
    'id' => 'repeater_box',
    'title' => 'Repeater Box',
    'post_type' => 'post',
    'fields' => [
        [
            'name' => 'repeater_entries',
            'type' => 'repeater',
            'label' => 'Entries',
            'fields' => [
                ['name' => 'title', 'type' => 'text', 'label' => 'Title'],
                ['name' => 'image_id', 'type' => 'image', 'label' => 'Image'],
            ],
        ],
    ],
]);
```

## repeater group

`repeater` では `group` 設定を使って各 item を UI 上でまとめられます。

### group config

- `label`: string, グループのタイトル
- `collapsible`: bool, 折りたたみを有効にする
- `default_open`: bool, 初期表示時に開いているか
- `index_label`: bool, ラベルと 1 始まりのインデックスを表示する

### 例

```php
new \Period\WpFramework\Infrastructure\WordPress\MetaBox([
    'id' => 'repeater_box',
    'title' => 'Repeater Box',
    'post_type' => 'post',
    'fields' => [
        [
            'name' => 'repeater_entries',
            'type' => 'repeater',
            'label' => 'Entries',
            'group' => [
                'label' => '項目',
                'collapsible' => true,
                'default_open' => true,
                'index_label' => true,
            ],
            'fields' => [
                ['name' => 'title', 'type' => 'text', 'label' => 'Title'],
                ['name' => 'image_id', 'type' => 'image', 'label' => 'Image'],
            ],
        ],
    ],
]);
```

### 動作

- `group` が設定されている場合、各 item は `data-period-wp-group` ラッパーで囲まれます。
- `collapsible: true` の場合、ヘッダークリックで子フィールドの表示/非表示を切り替えます。
- `index_label: true` の場合、ヘッダーに `label 1`, `label 2` のようにインデックスを表示します。
- 並び替え後もヘッダーのインデックスは更新されます。
