# リファクタリング計画

このドキュメントは、CakePHP の FatController を段階的にテストしやすい構造へ移すための計画です。
対象は「設定画面が多く、状態遷移は少ないが、保存処理・副作用・認可・表示整形が Controller に混在している」システムです。

## 基本方針

最初から大きなドメインモデルを作らない。

この種のシステムでまず問題になるのは、複雑な状態遷移ではなく、以下の処理が Controller に混ざっていることです。

- リクエスト値の取得
- 入力値の正規化
- バリデーション
- 認可
- 複数テーブルへの保存
- トランザクション制御
- 監査ログ
- キャッシュ削除
- メールや外部 API などの副作用
- GET 表示用のデータ整形

したがって最初の着地点は、画面単位またはユースケース単位の Application Service です。
Domain Service や Value Object は、実際に複数箇所で同じ業務ルールが確認できてから導入します。

## 現在の教材構成

現在の起動可能な画面は、意図的に汚い FatController のままです。

- 実行される Controller: [`src/Controller/SystemSettingsController.php`](../src/Controller/SystemSettingsController.php)
- 汚い実装の読み物版: [`examples/step-00-fat-controller/SystemSettingsController.php`](../examples/step-00-fat-controller/SystemSettingsController.php)
- ラッパー導入例: [`examples/step-01-wrapper`](../examples/step-01-wrapper)
- 薄い Controller の最終形例: [`examples/step-04-thin-controller/SystemSettingsController.php`](../examples/step-04-thin-controller/SystemSettingsController.php)
- Application Service: [`src/Application/SystemSettings/UpdateSystemSettings.php`](../src/Application/SystemSettings/UpdateSystemSettings.php)
- Application Service のテスト: [`tests/Application/SystemSettings/UpdateSystemSettingsTest.php`](../tests/Application/SystemSettings/UpdateSystemSettingsTest.php)

## Step 00: 現状の挙動を固定する

目的は、既存コードをきれいにする前に、壊してはいけない挙動を明文化することです。

まず [`examples/step-00-fat-controller/SystemSettingsController.php`](../examples/step-00-fat-controller/SystemSettingsController.php) を読みます。
CakePHP の request、ORM、認可、transaction、cache、log、通知が 1 action に混在しており、単体テストが難しい状態です。

この段階で追加するテストは、きれいな設計のテストではなく Characterization Test です。
現在の挙動を固定するために、以下を確認します。

- 権限があるユーザーは正しい入力で保存できる
- 権限がないユーザーは保存できない
- 不正なメールアドレスは保存できない
- 保存失敗時に中途半端な更新が残らない
- 通知先が変わった場合だけ通知が発生する
- 通知先が同じ場合は通知しない

この時点では、既存挙動が業務的に正しいかどうかを判断しません。
まず「今どう動いているか」を固定します。

完了条件:

- 対象 action の主要な分岐がテストで表現されている
- DB 更新と副作用の有無がテストから読める
- 以後の変更で壊れた場合に検知できる

## Step 01: ルールを動かさず保存処理だけラップする

目的は、Controller の中にある保存ブロックへ境界を作ることです。

[`examples/step-01-wrapper/SystemSettingsWriter.php`](../examples/step-01-wrapper/SystemSettingsWriter.php) のように、まずは保存処理をラッパーへ移します。
この段階では、validation や副作用の判断を移しません。

置き換えイメージ:

```php
$settingsWriter->save($tenantId, $enabled, $senderName, $recipients);
```

重要なのは、いきなり正しい設計へ飛ばないことです。
最初のラッパーは、既存の transaction 境界と保存順を維持します。
内部実装が汚くても構いません。

完了条件:

- Controller から直接の複数テーブル保存が減っている
- ラッパー単位で persistence integration test が書ける
- Controller の外へ移したにもかかわらず Step 00 のテストが通る

## Step 02: Command を導入する

目的は、HTTP request とユースケース入力を分離することです。

[`src/Application/SystemSettings/UpdateSystemSettingsCommand.php`](../src/Application/SystemSettings/UpdateSystemSettingsCommand.php) のように、Controller の端で request を型付きの command に変換します。
Application 層には `ServerRequest`、session、CakePHP Entity を渡しません。

これにより、同じユースケースを将来的に CLI、batch、API から呼ぶ場合でも、HTTP に依存しない形を保てます。

完了条件:

- Application 層の public method が CakePHP の request object に依存していない
- 入力値の型と意味が command から読める
- Controller は request から command を作るだけになっている

## Step 03: Application Service に orchestration を移す

目的は、ユースケースの処理順序を Controller から分離することです。

[`src/Application/SystemSettings/UpdateSystemSettings.php`](../src/Application/SystemSettings/UpdateSystemSettings.php) では、以下の順序を Application Service が持ちます。

1. 認可する
2. 正規化する
3. バリデーションする
4. atomic に保存する
5. 監査ログを書く
6. キャッシュを削除する
7. 通知先が変わった場合だけ通知する

外部依存は [`src/Application/SystemSettings/Port`](../src/Application/SystemSettings/Port) の interface にします。
本番では CakePHP ORM、既存 Component、既存 cache、既存 mailer などを adapter として接続します。

完了条件:

- Application Service がユースケースの処理順序を持っている
- テストで認可、保存、監査ログ、cache、通知を fake に差し替えられる
- Controller を通さずに Application Service の分岐をテストできる

## Step 04: Controller を薄くする

目的は、Controller を HTTP の責務だけに戻すことです。

[`examples/step-04-thin-controller/SystemSettingsController.php`](../examples/step-04-thin-controller/SystemSettingsController.php) のように、Controller は以下だけを担当します。

- request から command を作る
- use case を呼ぶ
- flash message を設定する
- redirect 先を決める
- GET 表示用の値を View に渡す

GET 表示用の read model まで無理に update service へ押し込まないでください。
更新処理と表示用 query は別の変更理由を持ちます。

CakePHP 4.5 では DI container から action 引数へ型付き依存を渡せます。
本番実装では `Application::services()` に adapter と Application Service を登録します。

登録イメージ:

```php
public function services(ContainerInterface $container): void
{
    $container->add(Authorizer::class, CakeAuthorizer::class);
    $container->add(SystemSettingsStore::class, CakeSystemSettingsStore::class);
    $container->add(AuditLog::class, CakeAuditLog::class);
    $container->add(SettingsCache::class, CakeSettingsCache::class);
    $container->add(SettingsChangeNotifier::class, CakeSettingsChangeNotifier::class);
    $container->add(UpdateSystemSettings::class)
        ->addArguments([
            Authorizer::class,
            SystemSettingsStore::class,
            AuditLog::class,
            SettingsCache::class,
            SettingsChangeNotifier::class,
        ]);
}
```

完了条件:

- Controller action から業務判断が消えている
- Application Service のテストが主な安全網になっている
- Controller integration test は HTTP の接続確認に絞られている

## 複数画面へ展開する順番

最初から汎用 `SettingsService` を作らないでください。
`save($screenName, array $settings)` のような形にすると、結局 if と配列操作が別の場所へ移動するだけです。

展開手順:

1. 変更頻度または障害頻度が高い設定画面を 1 つ選ぶ
2. Step 00 で既存挙動を固定する
3. Step 01 で保存処理をラップする
4. Step 02-03 で Application Service に移す
5. Step 04 で Controller を薄くする
6. 3 画面終わった時点で共通化できる adapter だけ抽出する

共通化してよい候補:

- transaction adapter
- authorization adapter
- audit log adapter
- cache adapter
- 本当に同じ意味を持つ scalar normalizer

共通化しない候補:

- 画面ごとの保存ルール
- 画面ごとの validation
- 画面ごとの通知条件
- 画面ごとの redirect / flash

## 実システム確認が必要な判断

この教材では、以下をまだ断定しません。

- 認可を transaction の前に置くか、中に含めるか
- 監査ログを設定保存と一緒に rollback する必要があるか
- 通知は同期処理か、queue へ逃がすべきか
- 楽観ロックが必要か
- GET 表示用に query service を作る必要があるか
- 汎用 key-value storage を維持すべきか

これらは一般論では決められません。
本番コードの実装、障害履歴、運用要件、DB 設計を確認してから決めます。
