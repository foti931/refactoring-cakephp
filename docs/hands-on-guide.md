# ハンズオン進行案

このドキュメントは、このリポジトリを学習会や社内ハンズオンで使うための進行案です。

## 対象者

- CakePHP の Controller / Table / Template を読める
- FatController の問題を実感している
- テストが少ない既存システムを壊さず改善したい

## ゴール

参加者が、以下を自分の手で確認できる状態を目指します。

- 汚い Controller のどこがテストしづらいか説明できる
- いきなり全面改修しない理由を説明できる
- Characterization Test の価値を説明できる
- wrapper で境界を作る手順を実装できる
- Application Service と port の役割を説明できる
- Controller を薄くすると何がテストしやすくなるか説明できる

## 事前準備

```sh
docker compose up --build -d
docker compose exec app composer test
docker compose exec app composer test:phpunit
```

ブラウザで以下を開き、設定画面が表示されることを確認します。

```text
http://127.0.0.1:8080
```

## Part 1: 汚い Controller を読む

読むファイル:

- [`src/Controller/SystemSettingsController.php`](../src/Controller/SystemSettingsController.php)
- [`templates/SystemSettings/edit.php`](../templates/SystemSettings/edit.php)

確認すること:

- 1 action に何個の責務が混ざっているか
- どの処理が DB に依存しているか
- どの処理が副作用を持つか
- 単体テストしようとした時に何を fake にしたくなるか

演習:

- Controller 内の処理を責務ごとにコメントで分類する
- 「今すぐ移してはいけない処理」と「先に境界だけ作れる処理」を分ける

## Part 2: 現状挙動をテストで固定する

読むファイル:

- [`tests/Application/SystemSettings/UpdateSystemSettingsTest.php`](../tests/Application/SystemSettings/UpdateSystemSettingsTest.php)

演習:

- Bake で Controller test の雛形を生成する
- 既存 Controller の integration test として必要なケースを列挙する
- 保存成功、validation error、権限なし、通知あり/なしの期待値を書く

Bake コマンド:

```sh
docker compose exec app bin/cake bake test controller SystemSettings --no-fixture
```

ポイント:

- この段階のテストは「理想の設計」を検証するものではない
- 既存挙動を固定し、後で安全に動かすためのテストである

## Part 3: 保存処理を wrapper 化する

読むファイル:

- [`examples/step-01-wrapper/SystemSettingsWriter.php`](../examples/step-01-wrapper/SystemSettingsWriter.php)
- [`examples/step-01-wrapper/controller-replacement.php`](../examples/step-01-wrapper/controller-replacement.php)

演習:

- Controller の保存ブロックを探す
- そのまま wrapper に移せる範囲を決める
- validation や通知条件はまだ移さない

ポイント:

- wrapper の中が多少汚くてもよい
- 最初の目的は、責務を完全に正すことではなく、変更可能な境界を作ること

## Part 4: Application Service を読む

読むファイル:

- [`src/Application/SystemSettings/UpdateSystemSettingsCommand.php`](../src/Application/SystemSettings/UpdateSystemSettingsCommand.php)
- [`src/Application/SystemSettings/UpdateSystemSettings.php`](../src/Application/SystemSettings/UpdateSystemSettings.php)
- [`src/Application/SystemSettings/Port`](../src/Application/SystemSettings/Port)

確認すること:

- HTTP request が Application 層に入っていないこと
- CakePHP ORM が Application Service に直接入っていないこと
- port が narrow interface になっていること
- fake を使って分岐テストできること

演習:

- `UpdateSystemSettings` に新しい validation rule を 1 つ追加する
- 対応するテストを追加する
- Controller を触らずにテストできることを確認する

## Part 5: Thin Controller を読む

読むファイル:

- [`examples/step-04-thin-controller/SystemSettingsController.php`](../examples/step-04-thin-controller/SystemSettingsController.php)

確認すること:

- Controller に残っている責務
- Application Service に移った責務
- GET 表示用処理を update use case に混ぜない理由

演習:

- 現在の実行 Controller と thin controller を比較する
- 消えた分岐、残った分岐を一覧化する

## Part 6: 実システムへの適用設計

議論すること:

- 最初に対象にする設定画面はどれか
- 既存の認可処理はどこにあるか
- audit log は rollback 対象か
- cache 削除は同期でよいか
- 通知処理は queue 化すべきか
- テスト DB はどう用意するか

成果物:

- 最初の 1 画面のリファクタリング計画
- 追加する Characterization Test の一覧
- wrapper 化する範囲
- Application Service に移す処理
- 後回しにする処理

## 講師向けメモ

参加者が「最初からきれいな構造へ全部置き換えたい」と考えた場合は、既存挙動を壊すリスクを先に確認してください。
この教材の主題は、理想アーキテクチャの暗記ではなく、商用システムを止めずに改善する順番です。
