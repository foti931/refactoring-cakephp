# AGENTS.md

このリポジトリは、CakePHP 4.5.2 / PHP 8.3 系の FatController を題材にしたリファクタリング教材です。
AI agent または開発者が作業する場合は、このファイルの方針に従ってください。

## 目的

このプロジェクトの目的は、きれいな完成形だけを見せることではありません。
汚い既存コードを再現し、そこから安全に段階的リファクタリングする手順を学べる教材にすることです。

そのため、以下を同時に維持します。

- 意図的に汚い FatController の実行版
- 段階的な改善例
- テストしやすい Application Service の例
- ハンズオンで使える説明ドキュメント

## 作業ルール

- `src/Controller/SystemSettingsController.php` は、教材上の汚い現状として扱う
- 汚い Controller を勝手に薄くしない
- 改善後の形は `examples/` または `src/Application/` に追加する
- 挙動を変える場合は、先にテストまたはドキュメントで意図を明確にする
- `vendor/`, `tmp/`, `logs/` は commit しない
- Docker / CakePHP / MariaDB の起動手順を壊さない
- 実システムに未確認のことを、確定事項として書かない

## よく使うコマンド

起動:

```sh
docker compose up --build -d
docker compose exec app composer init-db
```

テスト:

```sh
docker compose exec app composer test
```

Bake / PHPUnit:

```sh
docker compose exec app bin/cake bake test controller SystemSettings --no-fixture
docker compose exec app composer test:phpunit
```

画面:

```text
http://127.0.0.1:8080
```

## 重要なファイル

- [`README.md`](README.md): プロジェクト概要と起動方法
- [`docs/project-status.md`](docs/project-status.md): 現状ステータスと進捗管理
- [`docs/refactoring-plan.md`](docs/refactoring-plan.md): 段階的リファクタリング計画
- [`docs/hands-on-guide.md`](docs/hands-on-guide.md): ハンズオン進行案
- [`src/Controller/SystemSettingsController.php`](src/Controller/SystemSettingsController.php): 意図的に汚い実行版 Controller
- [`examples/step-00-fat-controller`](examples/step-00-fat-controller): 汚い Controller の読み物版
- [`examples/step-01-wrapper`](examples/step-01-wrapper): 保存処理 wrapper 化の例
- [`examples/step-04-thin-controller`](examples/step-04-thin-controller): 薄い Controller の例
- [`src/Application/SystemSettings`](src/Application/SystemSettings): Application Service の例
- [`tests/Application/SystemSettings`](tests/Application/SystemSettings): Application Service のテスト
- [`tests/TestCase/Controller`](tests/TestCase/Controller): Bake 生成の CakePHP 標準テスト雛形

## 教材として守るべき構造

このリポジトリでは、以下の対比が重要です。

| 役割 | 場所 |
| --- | --- |
| 汚い現状 | `src/Controller/SystemSettingsController.php` |
| 汚い現状の読み物 | `examples/step-00-fat-controller` |
| 小さな境界作り | `examples/step-01-wrapper` |
| Application Service | `src/Application/SystemSettings` |
| 薄い Controller | `examples/step-04-thin-controller` |
| 進捗管理 | `docs/project-status.md` |

この対比が崩れる変更は、教材の価値を下げます。
実行版をきれいにする場合は、別 branch または別 step として扱い、現状再現版を残してください。

## 設計判断の基準

この教材では、設定画面中心の商用システムを想定しています。
状態遷移が少ないため、最初から重いドメインモデルを作るより、ユースケース単位の Application Service を優先します。

ただし、以下が実コードで確認できた場合は Value Object や Domain Service の導入を検討します。

- 複数画面で同じ業務ルールが重複している
- DB 保存前に守るべき不変条件がある
- 単なる scalar では意味を取り違える値がある
- validation ではなく業務判断として表現すべきルールがある

## 変更時の確認

作業後は最低限以下を確認してください。

```sh
docker compose exec app composer test
```

Docker が使えない環境では、変更理由と未検証であることを PR または作業メモに残してください。

## 禁止事項

- `vendor/` を commit する
- `tmp/` や `logs/` を commit する
- 実システム未確認の仕様を断定する
- 汚い現状再現を消して、完成形だけにする
- すべての設定画面を 1 つの巨大な `SettingsService` にまとめる
