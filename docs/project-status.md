# プロジェクトステータス

このドキュメントは、教材としての現状と、リファクタリング作業の進捗を管理するための台帳です。

## 現在の到達点

| 項目 | 状態 | メモ |
| --- | --- | --- |
| Docker 起動 | 完了 | Apache HTTP + PHP 8.3 + MariaDB 10.11 |
| CakePHP version | 完了 | `cakephp/cakephp: 4.5.2` |
| 汚い FatController の再現 | 完了 | [`src/Controller/SystemSettingsController.php`](../src/Controller/SystemSettingsController.php) |
| 複数テーブル保存 | 完了 | settings / recipients / feature flags / audit logs |
| 初期 DB 作成 script | 完了 | [`scripts/init-db.php`](../scripts/init-db.php) |
| Application Service 例 | 完了 | [`src/Application/SystemSettings`](../src/Application/SystemSettings) |
| Application Service test | 完了 | 5 ケース |
| Thin Controller 例 | 完了 | [`examples/step-04-thin-controller`](../examples/step-04-thin-controller) |
| CakePHP 公式生成 script | 完了 | [`scripts/bootstrap-official.sh`](../scripts/bootstrap-official.sh) |
| 本番 adapter 実装 | 未着手 | 実システムの Table / Component / side effect 確認後 |
| Controller integration test | 未着手 | ハンズオン課題に残す |
| ハンズオン教材化 | 進行中 | 本ドキュメント群を追加 |

## 現在の起動手順

```sh
docker compose up --build -d
docker compose exec app composer init-db
```

ブラウザで以下を開きます。

```text
http://127.0.0.1:8080
```

## 現在の検証手順

```sh
docker compose exec app composer test
```

期待値:

```text
PASS testUpdatesNormalizedSettingsAndEffects
PASS testDoesNotNotifyWhenNormalizedRecipientsDidNotChange
PASS testRejectsInvalidInputBeforeSaving
PASS testDoesNotReadOrSaveWhenAuthorizationFails
PASS testDoesNotRunEffectsWhenAtomicSaveFails
```

## 現在あえて汚くしている点

[`src/Controller/SystemSettingsController.php`](../src/Controller/SystemSettingsController.php) は、教材として意図的に以下を混在させています。

- request method 判定
- request data の直接参照
- checkbox 値の変換
- 改行区切りメールアドレスの分解
- validation
- 認可のような role 判定
- DB transaction
- 複数 Table の読み書き
- delete / insert による関連行更新
- feature flag 更新
- audit log 書き込み
- cache 削除
- 通知の条件判定
- GET 表示用のデータ整形

この汚さを消す前に、テストで現状挙動を固定することが重要です。

## 進捗管理

| Phase | 目的 | 状態 | 完了条件 |
| --- | --- | --- | --- |
| 0 | 汚い現状を再現する | 完了 | Docker で動く FatController がある |
| 1 | 現状挙動を固定する | 未着手 | Controller integration test が主要分岐を覆う |
| 2 | 保存処理を wrapper 化する | 例のみ完了 | 本体 Controller の保存処理が wrapper 経由になる |
| 3 | Command を導入する | 例のみ完了 | HTTP request を Application 層へ渡さない |
| 4 | Application Service へ移す | 例のみ完了 | use case 単体で分岐をテストできる |
| 5 | CakePHP adapter を実装する | 未着手 | ORM / cache / audit / notifier が port に接続される |
| 6 | Controller を薄くする | 例のみ完了 | Controller が HTTP 変換と redirect に集中する |
| 7 | 2 画面目へ展開する | 未着手 | 共通化すべきものと個別に残すものを比較できる |

## 次にやること

1. `SystemSettingsController` の Controller integration test を追加する
2. 現在の保存処理を `SystemSettingsWriter` 相当へ移す
3. 移動後も Controller integration test が通ることを確認する
4. Application Service に接続する CakePHP adapter を実装する
5. 実行 Controller を thin controller へ差し替える
6. 2 つ目の設定画面サンプルを追加し、共通化の判断材料を作る

## 判断待ち

実システムへ適用するには、以下の情報が必要です。

- 認可の実装方式
- 設定値の保存方式
- 監査ログの要件
- cache の種類と削除粒度
- 通知処理の同期/非同期
- 同時編集の有無
- 既存の BaseController / Component の責務
- テスト DB の作り方
