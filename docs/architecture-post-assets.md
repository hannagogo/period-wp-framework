# PostAssets 設計仕様
## Overview
PostAssets は、投稿単位で CSS / JavaScript を管理する機能である。
対象 meta:
```text
csscode
cssfile
jscode
jsfile

CSS Compile Pipeline

CSS 入力は保存時に必ず compile pipeline を通す。

使用 meta:

csscode
csscode_compiled
csscode_minified
csscode_last_compile_error
csscode_last_compiled_at

Compile Flow

csscode
↓
compile pipeline
↓
csscode_compiled
↓
optional minify
↓
csscode_minified

Rules

* 純 CSS の場合はそのまま csscode_compiled に保存する
* Sass / SCSS の場合は CSS に compile して csscode_compiled に保存する
* compile 成功時は csscode_last_compile_error をクリアする
* compile 成功時は csscode_last_compiled_at を更新する
* compile 失敗時も投稿保存は止めない
* compile 失敗時は csscode_last_compile_error に保存する
* compile 失敗時も csscode_compiled / csscode_minified は維持する
* csscode_minified は保存先のみ先に用意し、minify 処理は後続で実装する
* front 出力は csscode_minified → csscode_compiled の順で利用する

Output Rules

cssfile / jsfile
→ register / enqueue
csscode_compiled / csscode_minified
→ <style>...</style>
jscode
→ <script>...</script>

Editor UI

PostAssets の csscode / jscode 編集UIは CodeMirror 化する。

* textarea と同期する
* 保存形式には依存させない
* CSS / SCSS / JavaScript の syntax highlight、indent、validation を検討する

