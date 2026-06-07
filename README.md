# CakePHP FatController リファクタリング演習

このリポジトリは、CakePHP 4.5.2 / PHP 8.3.6 で動いている商用システムを想定し、
FatController を段階的にリファクタリングするための具体的な検討材料です。

想定しているのは、画面数や機能数が多く、設定画面のような更新処理が多い一方で、
テストがほとんど存在しない CakePHP アプリケーションです。
このサンプルは実システムそのものを断定するものではありません。
本番コードに適用する前に、仮説としてレビューし、実態に合わせて修正するための土台です。

## 想定画面

サンプル画面では、テナント単位の通知設定を更新します。

- 管理者だけが設定を更新できる
- 入力値を正規化し、バリデーションする
- メイン設定行と通知先行を 1 トランザクションで保存する
- 保存成功後に監査ログを書く
- 設定キャッシュを削除する
- 通知先が変わった場合だけ変更通知を送る

初期実装では、これらをすべて Controller の 1 action に詰め込んでいます。
この種の処理が多数の設定画面に散らばると、変更のたびにテストしづらくなり、
保守コストが上がります。

## ORM の仮説

このサンプルには CakePHP ORM の具体的なクラスを含めています。

- [`src/Model/Entity`](src/Model/Entity)
- [`src/Model/Table`](src/Model/Table)
- [`docs/schema-hypothesis.sql`](docs/schema-hypothesis.sql)

このモデルでは、画面専用のテーブルが存在する前提にしています。
SQL は説明用であり、そのまま本番 migration にするものではありません。
実システムに適用する前に、DB エンジン、命名規則、外部キー、テナント管理テーブルの定義を確認する必要があります。

起動可能なスパゲッティ実装では、以下のテーブルを使います。

- `system_settings`
- `system_setting_recipients`
- `system_feature_flags`
- `audit_logs`

## 読む順番

1. [`examples/step-00-fat-controller/SystemSettingsController.php`](examples/step-00-fat-controller/SystemSettingsController.php)
2. [`src/Model/Table/SystemSettingsTable.php`](src/Model/Table/SystemSettingsTable.php)
3. [`docs/refactoring-plan.md`](docs/refactoring-plan.md)
4. [`src/Application/SystemSettings/UpdateSystemSettings.php`](src/Application/SystemSettings/UpdateSystemSettings.php)
5. [`tests/Application/SystemSettings/UpdateSystemSettingsTest.php`](tests/Application/SystemSettings/UpdateSystemSettingsTest.php)

## ドキュメント

- [`docs/project-status.md`](docs/project-status.md): 現在の到達点、進捗、次にやること
- [`docs/refactoring-plan.md`](docs/refactoring-plan.md): FatController から段階的に切り出す計画
- [`docs/hands-on-guide.md`](docs/hands-on-guide.md): 学習会・社内ハンズオンで使う進行案
- [`AGENTS.md`](AGENTS.md): この教材を壊さず作業するためのルール

## 起動方法

このプロジェクトは Docker で起動します。
構成は Apache HTTP、PHP 8.3、CakePHP 4.5.2、MariaDB 10.11 です。

```sh
docker compose up --build -d
docker compose exec app composer init-db
```

ブラウザで <http://127.0.0.1:8080> を開きます。

起動する画面は、意図的に FatController のままにしている
[`src/Controller/SystemSettingsController.php`](src/Controller/SystemSettingsController.php)
を使います。
この Controller は、リクエスト解析、バリデーション、正規化、認可のような判定、
複数テーブルへの書き込み、監査ログ、キャッシュ削除、GET 表示用データ整形を
1 action に混在させています。

Application 層に切り出した後のテストは、以下で実行できます。

```sh
docker compose exec app composer test
```

CakePHP 公式スタートアップガイドの生成手順を別ディレクトリで再現する場合は、
以下を実行します。

```sh
docker compose run --rm app sh scripts/bootstrap-official.sh /tmp/official-cakephp-app
```

このスクリプトは CakePHP 4 公式の `composer create-project` の流れに従い、
その後 `cakephp/cakephp` を `4.5.2` に固定します。
このリファクタリング演習用コードを上書きしないよう、別ディレクトリに出力します。

## 実システムに適用する前に確認すること

以下の答えによって、このアーキテクチャを維持するか、簡略化するか、拡張するかが変わります。

1. 設定はテナント単位、ユーザー単位、グローバル、または混在のどれか
2. 認可は現在どう実装されているか。Authentication / Authorization plugin、独自 Component、または action 内の role 判定か
3. 設定値は専用テーブル、汎用 key-value テーブル、JSON カラム、または複数パターンのどれで保存されているか
4. 実際に存在する副作用は何か。監査ログ、キャッシュ削除、ファイル書き込み、外部 API、メール送信、または副作用なしのどれか
5. 同時編集は起きるか。ロストアップデートを防ぐ必要があるか
6. 多くの設定画面で共通利用されている BaseController や Component はあるか
7. 利用 DB は何か。テスト DB はどう作成しているか
8. 変更頻度または障害頻度が最も高い設定画面はどれか
