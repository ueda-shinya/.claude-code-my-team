# X（Twitter）自動分析・投稿システム構築ウィザード v2

> 誰でもステップバイステップで「X運用の自動化」を導入できるガイド。
> 前提知識ゼロから、最終的にcronで毎日自動レポートが届くところまで。
> 全10ステップ。途中で止めてもそこまでの成果は使える。
>
> **v2更新点**: ブランド設計（Step 0）、ペルソナ×型マトリクス、品質ゲート（5つのNGパターン）、インプレッション爆増戦術（Step 8）、投稿ワークフロー（Step 9）を追加。実運用チームの知見を全面反映。

---

## このウィザードで手に入るもの

| ステップ | できるようになること | 所要時間 | コスト |
|----------|---------------------|----------|--------|
| Step 0 | ブランド設計 — 誰に何を届けるか明確になる | 60分 | 無料 |
| Step 1 | バズる投稿の「型」を理解し、手動で使える | 10分 | 無料 |
| Step 2 | AIに投稿案を作らせる（品質ゲート付き） | 15分 | 無料〜 |
| Step 3 | 競合アカウントを定点観測する | 30分 | 無料 |
| Step 4 | トレンド分析を自動化する（Grok API） | 30分 | 約50円/回 |
| Step 5 | バズポスト発見＋引用RT案を自動生成 | 20分 | 約50円/回 |
| Step 6 | 自分の投稿パフォーマンスを自動分析 | 20分 | 約50円/回 |
| Step 7 | 全部をcronで毎日自動実行＋通知 | 30分 | 約150円/日 |
| Step 8 | インプレッション爆増の6戦術を実装する | 20分 | 無料 |
| Step 9 | 投稿ワークフロー — 品質担保の投稿フロー | 10分 | 無料 |

**原則：Step 0とStep 1だけでも効果がある。無理に全部やらなくていい。**

---

## 前提条件

- Xアカウントを持っている
- パソコン（Mac or Linux推奨。Windowsも可）がある
- コマンドライン（ターミナル）を開ける

Step 4以降は追加で必要：
- Python 3.10以上
- xAI（Grok）APIキー（https://console.x.ai/ で取得）

---

## Step 0: ブランド設計 — 誰に何を届けるか（道具なし・最重要）

**Step 1〜9の全ての土台。ここを飛ばすと、全部が空振りになる。**

バズる型を覚えても、AIに投稿案を作らせても、「誰に」「何を」「どんなトーンで」届けるかが決まっていなければ、投稿は誰にも刺さらない。

### 0-1. アカウントプロフィールを設計する

以下を埋めて、1つのファイルに保存する。

```markdown
## アカウントプロフィール

- **アカウント名**: @xxxxxxx
- **何者か**: （一言で。例：「Claude Codeの社会実装を最前線で苦戦するコンサル」）
- **テーマ・領域**: （例：「Claude Codeの実践活用」）
- **ターゲット層**: （例：「Claude Codeに興味があるがまだ使い方がわからない初心者層」）
- **フォロー動機**: なぜこの人をフォローするのか？（読者視点で3つ）
  1.
  2.
  3.
- **トーン**: （例：「友達に話す感じ」「淡々と事実」「苦戦のリアル」）
- **NGトピック**: （例：「驚き屋的な投稿」「使ったことないツールの推奨」「他者批判」「政治・宗教」）
- **差別化ポイント**: 競合がやっていない、自分だけの勝ち筋は何か？
- **KGI**: （例：「フォロワー10,000人」）
```

### 0-2. ペルソナを3人作る

**「全員に刺さるポスト」を狙うな。全員に向けた文章は誰にも刺さらない。**

以下の10項目でペルソナを3人作る。

| # | 項目 | 書くこと |
|---|------|---------|
| 1 | 名前・年齢・性別 | 架空でいい。具体的に |
| 2 | 職種・役職・業界 | なぜXでAI情報を追っているか想像できるレベルで |
| 3 | 年収レンジ | 有料サービスへの支払い能力の判断に使う |
| 4 | 課題 TOP3 | **最重要。** 具体的な場面・感情付きで。「AIを活用したい」は抽象的すぎる |
| 5 | 情報収集（方法+頻度） | X以外も含む。どういうシチュエーションで投稿を見るか |
| 6 | 意思決定基準 | 何を基準にフォロー/購入を決めるか |
| 7 | よく使うSNS | X以外にどこにいるか |
| 8 | フォロー/購入動機 | なぜあなたのアカウントをフォローするのか（4つ） |
| 9 | 躊躇する理由 | フォロー/購入を迷うブレーキは何か |
| 10 | 普段の言葉・口癖 | Xでの投稿、職場での会話、友人との会話、心の中の独り言 |

**チェックポイント：** 作ったペルソナに「この人は本当にXであなたのアカウントをフォローするか？」と問え。リアリティがなければ作り直せ。

### 0-3. ブランドボイスの具体指針を作る

曖昧な「本質的で簡潔なトーン」ではなく、OKとNGの具体例を書き出す。

```markdown
## こう書く（OK）
- 「○○を3社で試した結果、唯一成果が出たのは...」
- 「地味だけど業務改善のインパクトが一番大きいのはこの機能」

## こう書かない（NG）
- 大げさ: 「〜が全てを変える」
- 命令: 「信じるな」「覚えておけ」
- 煽り: 「知らないとヤバい」「〜時代、終わった」
- 驚き屋: 「！！」「マジで」「ヤバい」
- 説教: 「〜が大事」「〜すべき」
- 評論家: 「これは非常に重要な指摘です」
```

### やってみよう

> アカウントプロフィール、ペルソナ3人、ブランドボイスを1つのファイルにまとめる。
> これが以降の全ステップの羅針盤になる。

---

## Step 1: バズの型を覚える（道具なし・今すぐ使える）

### 1-1. 絶対原則

**バズの3条件：おもしろい・かわいい・役に立つ。**
このどれにも当てはまらない投稿は、何をやっても伸びない。

投稿を書いたら自分に問え：
**「これ、タイムラインでスクロールの手を止めるか？」**
止まらないなら書き直せ。

### 1-2. やってはいけないこと（5つのNGパターン）

| # | NGパターン | 悪い例 | なぜNG | 対処法 |
|---|-----------|--------|-------|--------|
| 1 | テンプレ的まとめ | 「AI活用3選：1.議事録 2.テンプレ 3.SNS」 | Googleで出てくる。「この人ならでは」がない | 「実際に3社で試して、唯一成果が出たのは...」と体験を入れる |
| 2 | 当たり前を偉そうに | 「プロンプトは具体的に書きましょう」 | 「知ってる」でスクロール。感情が動かない | 逆から入る。「プロンプトを具体的に書くな。まず雑に投げろ。理由は...」 |
| 3 | 感情フックのない情報羅列 | 「Claude 3.5がリリース。主な特徴は〜」 | ニュースサイトのコピペ。専門家の視点がない | 冒頭に感情フック。「一つだけ言わせてほしい」「みんなスルーしてるけど」 |
| 4 | 説教調 | 「AI時代には学び続けることが大切です」 | 上から目線。読者は説教されたくてXを開いていない | 「発見の共有」のトーンで。「最近気づいたんだけど」「これ試してみたら」 |
| 5 | ブランドトーン違反 | 「ヤバい！」「革命的すぎる！」「知らないと損！」 | 驚き屋は短期的にインプが出ても長期的に信頼を失う | Step 0で作ったブランドボイスのOK/NG例と照合する |

### 1-3. 8つの型

投稿を作るときに「どの型で書くか」を先に決める。

| # | 型 | 構造 | 狙う感情 |
|---|---|------|----------|
| 1 | 嫁ブロック型 | 日常の出来事→共感 | 「わかる！」 |
| 2 | 失敗談→教訓型 | 自分の失敗→学び | 信頼・共感 |
| 3 | 権威×意外性型 | 「常識は間違い」と逆説提示 | 「え、マジ？」 |
| 4 | 数字インパクト型 | 冒頭に具体的数字 | 信頼・驚き |
| 5 | リスト・まとめ型 | 有益情報をコンパクトに | 「保存しとこ」 |
| 6 | Before/After型 | 過去と現在を対比 | 希望・憧れ |
| 7 | 問いかけ型 | 読者に問いを投げる | 「言いたい！」 |
| 8 | 速報・一次情報型 | 誰よりも早く発信 | 「この人フォローしとこ」 |

### 1-4. ペルソナ×型マトリクス（Step 0のペルソナと組み合わせる）

**ペルソナごとに「刺さる型」は違う。** Step 0で作った3ペルソナに対して、以下のように相性を判定する。

例：

| 型 | ペルソナA（独立志望） | ペルソナB（社内推進） | ペルソナC（キャリアチェンジ） |
|---|----------|----------|----------|
| 型1：嫁ブロック型 | ○ | △ | **◎** |
| 型2：失敗談→教訓型 | **◎** | **◎** | ○ |
| 型3：権威×意外性型 | **◎** | ○ | **◎** |
| 型4：数字インパクト型 | ○ | **◎** | ○ |
| 型5：リスト・まとめ型 | ○ | **◎** | ○ |
| 型6：Before/After型 | **◎** | ○ | **◎** |
| 型7：問いかけ型 | ○ | △ | ○ |
| 型8：速報・一次情報型 | **◎** | ○ | ○ |

凡例: ◎=最も刺さる ○=刺さる △=弱い

**自分のペルソナで同じテーブルを作れ。** ペルソナの課題TOP3のどれに触れる型かで判定する。

### 1-5. 型の組み合わせテクニック

単一の型だけでなく、組み合わせるとさらに強力になる：

- **数字 + 失敗談**: 「売上0円だった3年前。今は月[数字]万。変えたのは[教訓]だけ。」
- **権威 + 問いかけ**: 「[分野]10年やってきて一つだけ確信がある。[逆説]。これ、同業の人どう思う？」
- **リスト + Before/After**: 「半年前は[状態]だった。この3つを始めてから激変した：(1)(2)(3)」
- **速報 + 意外性**: 「【速報】[ニュース]。多くの人は[一般的解釈]と思うだろうけど、実は[逆の見方]。」

### 1-6. 53,568件のデータから判明した法則

- **79%の投稿はRTゼロで消える**（5件に4件は誰にも広まらない）
- **RT6件を超えるとインプが7.8倍に跳ね上がる**（Xのアルゴリズム閾値）
- **「届く投稿」と「反応される投稿」は別物**
  - 届く：文字数・URL・フォーマットで決まる（量的設計）
  - 反応される：衝撃・逆説・共感・実用性で決まる（質的設計）
  - **両方を同時に設計しないとバズらない**

### 1-7. RT6件の壁を超えるための設計要素

RT6件を超えた瞬間にインプが7.8倍になる（アルゴリズム閾値）。最初の6RTを獲得するために、以下の要素を意識的に設計に入れる：

| 設計要素 | 具体的にやること |
|---------|----------------|
| **引用RTしたくなる構造** | 読者が自分の意見を添えたくなる「問い」や「断言」を入れる |
| **自分事化のフック** | ペルソナの日常を具体的に描写し「自分のことだ」と思わせる |
| **ターゲットのアクティブ時間に投稿** | ペルソナごとのX利用時間帯を把握して投稿する |
| **断言する** | 「かもしれない」を「だ」に変える。それだけでRT率が変わる |

### 1-8. 拡散を生む4パターン

1. **個人体験 x 社会観察** — 自分の体験を書いた後に「これって〇〇の問題でもあるよな」と社会に接続する
2. **逆説・反常識** — 「〇〇が大事だと思うでしょ？実は逆で...」と常識の逆から入る
3. **自分事化の具体描写** — ターゲットの日常を描写し「自分のことだ」と感じさせる
4. **断言する** — 「〇〇かもしれない」を「〇〇だ」に変える。それだけでいいね率が変わる

### やってみよう

> 今日1つ投稿を作ってみる。
> Step 0で作ったペルソナの1人を選び、その人に「◎」の型を使う。
> 「狙う感情」を1つ決めてから書き始める。
> 書けたら「スクロール止まるか？」チェック。

---

## Step 2: AIに投稿案を作らせる（品質ゲート付き）

### 2-1. 最もシンプルな方法（Claude / ChatGPTで手動）

以下のプロンプトをそのままコピーして使う。
`{...}` の部分だけ書き換える。

```
あなたはX（Twitter）でバズポストを作る専門家だ。

【アカウント情報】
- 専門分野: {あなたの専門分野}
- トーン: {あなたのトーン}
- NG: テンプレ的まとめ、説教調、感情フックのない情報羅列、驚き屋的表現

【ターゲットペルソナ】
{Step 0で作ったペルソナから1人選んで貼る。名前・課題TOP3・普段の言葉を含める}

【今日のネタ】
{今日のネタ（ニュース、体験、気づき等）}

【バズの型（この中から選べ）】
1. 嫁ブロック型（共感）
2. 失敗談→教訓型（学び）
3. 権威×意外性型（好奇心）
4. 数字インパクト型（信頼）
5. リスト・まとめ型（保存欲求）
6. Before/After型（希望）
7. 問いかけ型（参加欲求）
8. 速報・一次情報型（情報価値）

【指示】
上記の型から3つ異なる型を使い、それぞれ140文字以内の投稿案を作れ。
ペルソナの課題TOP3のいずれかに触れること。

各案に以下を明記：
- 使った型
- 狙う感情（1つ）
- 推奨投稿時間帯
- 「なぜスクロールの手が止まるか」を1文で
- バズの3条件（おもしろい/かわいい/役に立つ）のどれに該当するか
- RT6件を獲得するための仕掛け（引用RTしたくなる構造、自分事化、断言等）

【絶対NG】
- 当たり前のことを偉そうに語るだけの投稿
- 感情が動かない投稿
- 140文字を超える投稿
- 驚き屋トーン（「ヤバい」「革命」「知らないと損」等）
```

### 2-2. 品質ゲート — 生成した案を以下でチェック

AIが出力した各案に対して、以下を順にチェックする。**1つでもNGなら書き直させろ。**

#### チェック1: 「スクロールの手を止めるか？」

読んで感情がピクリとも動かないなら、どんなに内容が正しくてもゴミ。

動かすべき感情（いずれかを明確に狙うこと）：
- 「へぇ！」（驚き）
- 「わかる...」（共感）
- 「これ保存しとこ」（実用）
- 「笑った」（ユーモア）
- 「悔しい、自分もやらなきゃ」（焦り）
- 「え、マジで？」（意外性）

#### チェック2: バズの3条件

各案が以下の3条件のうち **最低1つ** に該当することを確認する。該当しない案は破棄。

| 条件 | 判定基準 |
|------|---------|
| **おもしろい** | 読んだ人が「笑った」「うまい！」と思って誰かに見せたくなるか |
| **かわいい** | 読んだ人が「応援したい」「親しみを感じる」と思うか |
| **役に立つ** | 読んだ人が「保存しよう」「明日から使おう」と思うか |

> 「役に立つ」だけで戦うと差別化できない。**「役に立つ＋おもしろい」「役に立つ＋意外性」の掛け算を常に狙え。**

#### チェック3: 5つのNGパターンに該当しないか

Step 1-2の表と照合する。1つでも該当したら書き直し。

#### チェック4: ブランドトーン照合

Step 0で作ったブランドボイスのNG例と並べて読め。同じ匂いがしたら書き直し。

#### チェック5: ペルソナの課題に触れているか

選んだペルソナの課題TOP3のどれに刺しているか明確にできないなら、的外れ。

### 2-3. Claude in Chrome を使う方法（もう少し便利）

Claude Code + Chrome拡張を使っている場合：
1. Xのタイムラインを開く
2. Claude in Chromeで「今のタイムラインのトレンドを踏まえて投稿案を3つ作って」と指示
3. 上記のプロンプトの要素を口頭で伝える

**メリット：** 今のタイムラインの空気感を直接読み取れる

### やってみよう

> 上のプロンプトをClaudeかChatGPTに貼り付けて、3案作ってもらう。
> 品質ゲート（5チェック）を全て通過した案だけ残す。
> 気に入った案があれば投稿してみる。

---

## Step 3: 競合アカウントを定点観測する

### 3-1. 5カテゴリで観測リストを作る

闇雲にアカウントを集めても分析できない。以下の5カテゴリに分類する。

| カテゴリ | 選ぶ基準 | 目安数 | 学ぶポイント |
|---------|---------|--------|-------------|
| 1. 総合インフルエンサー | 自分と同じ分野で最も影響力がある | 5〜7 | フォロワー規模の作り方・投稿頻度 |
| 2. 実践者・ビルダー | 手を動かして実績を見せている | 5〜7 | 実践発信のリアリティ・差別化手法 |
| 3. 開発者・技術系 | 技術的な信頼性がある | 3〜5 | 技術的信頼性の見せ方 |
| 4. 研究・アカデミック | 業界の権威・学術的裏付け | 3〜5 | 権威性の参考 |
| 5. サービス系 | プロダクト・サービスを展開 | 3〜5 | プロダクト発信の手法 |

合計20〜30アカウント程度でスタート。慣れたら50アカウントまで増やす。

### 3-2. 各アカウントの分析観点

```markdown
## 競合観測メモ（YYYY-MM-DD）

### @xxx（表示名）【カテゴリ: X】
- フォロワー数: xxx
- 直近で最もバズった投稿: （内容）
- いいね/RT: xxx / xxx
- なぜバズったか: （分析）
- 使っている型: （8型のどれか）
- パクれるエッセンス: （構造だけ抜き出す）
- 彼がやっていないこと: （＝あなたの差別化チャンス）
```

### 3-3. 差別化ポイントを見つける

観測リストのアカウント全体を見渡して、以下を考える：

- **彼らが全員やっていること** — これは「必須要件」。あなたもやるべき
- **彼らが誰もやっていないこと** — これが「差別化チャンス」。ここを狙え
- **特定のカテゴリしかやっていないこと** — 別カテゴリの手法を自分の分野に輸入できないか

### 3-4. Playwrightで自動化する（プログラミングできる人向け）

手動がつらくなったら自動化する。以下の手順で環境構築：

```bash
# 1. Pythonパッケージインストール
pip3 install playwright
python3 -m playwright install chromium

# 2. Xにログイン（初回のみ、手動でCookieを保存）
python3 setup_x_login.py

# 3. スクレイプ実行
python3 scrape_benchmarks.py --test  # まず1アカウントでテスト
```

**setup_x_login.py** の中身（コピーして使える）：

```python
"""Xログインセットアップ - Cookieを保存する"""
import json
from playwright.sync_api import sync_playwright

COOKIE_FILE = ".x-cookies.json"

def main():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=False)
        context = browser.new_context()
        page = context.new_page()
        page.goto("https://x.com/login")
        print("ブラウザでXにログインしてください。")
        print("ログイン完了後、ここでEnterを押してください。")
        input()
        page.goto("https://x.com/home")
        page.wait_for_timeout(3000)
        cookies = context.cookies()
        with open(COOKIE_FILE, "w") as f:
            json.dump(cookies, f)
        print(f"Cookie保存完了: {COOKIE_FILE}（{len(cookies)}件）")
        browser.close()

if __name__ == "__main__":
    main()
```

**scrape_benchmarks.py** の最小版（コピーして使える）：

```python
"""競合アカウント巡回スクレイパー（最小版）"""
import json, time, sys, os
from datetime import datetime
from playwright.sync_api import sync_playwright

COOKIE_FILE = ".x-cookies.json"
OUTPUT_DIR = "benchmark"

# --- ここにあなたの監視アカウントを入れる ---
ACCOUNTS = [
    ("username1", "表示名1"),
    ("username2", "表示名2"),
    # 10〜20アカウント追加
]

SCRAPE_JS = """
async () => {
    for (let i = 0; i < 5; i++) {
        window.scrollTo(0, document.body.scrollHeight);
        await new Promise(r => setTimeout(r, 1500));
    }
    const bodyText = document.body.innerText;
    const followersMatch = bodyText.match(/([\d,.]+万?)\\s*フォロワー/);
    const articles = document.querySelectorAll('article');
    const tweets = [];
    const seen = new Set();
    articles.forEach(article => {
        const textEl = article.querySelector('[data-testid="tweetText"]');
        const timeEl = article.querySelector('time');
        const groups = article.querySelectorAll('[role="group"]');
        let metrics = '';
        groups.forEach(g => {
            const a = g.getAttribute('aria-label');
            if (a && a.includes('件の')) metrics = a;
        });
        let statusUrl = '';
        article.querySelectorAll('a[href*="/status/"]').forEach(a => {
            const href = a.getAttribute('href');
            if (href && href.match(/\\/status\\/\\d+$/) && !statusUrl) statusUrl = href;
        });
        if (statusUrl && !seen.has(statusUrl)) {
            seen.add(statusUrl);
            const likes = parseInt((metrics.match(/(\\d+)\\s*件のいいね/) || [0,0])[1]);
            const rts = parseInt((metrics.match(/(\\d+)\\s*件のリポスト/) || [0,0])[1]);
            const imps = parseInt(((metrics.match(/([\\d,]+)\\s*件の表示/) || [0,'0'])[1]).replace(/,/g,''));
            const bms = parseInt((metrics.match(/(\\d+)\\s*件のブックマーク/) || [0,0])[1]);
            if (timeEl && likes > 0) {
                tweets.push({
                    text: textEl ? textEl.innerText.substring(0, 300) : '',
                    time: timeEl.getAttribute('datetime'),
                    url: 'https://x.com' + statusUrl,
                    likes, rts, impressions: imps, bookmarks: bms
                });
            }
        }
    });
    tweets.sort((a, b) => b.likes - a.likes);
    return {
        followers: followersMatch ? followersMatch[1] : '?',
        tweets: tweets.slice(0, 5)
    };
}
"""

def main():
    os.makedirs(OUTPUT_DIR, exist_ok=True)
    date_str = datetime.now().strftime("%Y-%m-%d")

    with open(COOKIE_FILE, "r") as f:
        cookies = json.load(f)

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(
            user_agent="Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) "
                       "AppleWebKit/537.36 Chrome/131.0.0.0 Safari/537.36",
            viewport={"width": 1280, "height": 900}, locale="ja-JP",
        )
        context.add_cookies(cookies)
        page = context.new_page()

        for i, (username, display) in enumerate(ACCOUNTS):
            print(f"[{i+1}/{len(ACCOUNTS)}] @{username}...", end="", flush=True)
            try:
                page.goto(f"https://x.com/{username}", wait_until="domcontentloaded", timeout=30000)
                page.wait_for_timeout(5000)
                data = page.evaluate(SCRAPE_JS)
                if data and data.get("tweets"):
                    top = data["tweets"][0]
                    print(f" {data['followers']}フォロワー / top: {top['likes']}♥")
                    # ファイルに追記
                    path = os.path.join(OUTPUT_DIR, f"{username}.md")
                    with open(path, "a", encoding="utf-8") as f:
                        f.write(f"\n### {date_str}\n")
                        for t in data["tweets"][:3]:
                            f.write(f"- {t['likes']}♥ {t['rts']}RT {t['impressions']}imp | {t['text'][:100]}\n")
                else:
                    print(" 取得失敗")
            except Exception as e:
                print(f" エラー: {e}")
            if i < len(ACCOUNTS) - 1:
                time.sleep(5)

        context.close()
        browser.close()
    print("完了")

if __name__ == "__main__":
    main()
```

### やってみよう

> 5カテゴリに分けて監視アカウントを20個選ぶ。
> 手動でもいいので、今週1回観測してメモを残す。
> 「彼らが誰もやっていないこと」を1つ見つける。

---

## Step 4: トレンド分析を自動化する（Grok API）

### 4-1. なぜGrokか

- Grok（xAI）はXのデータに直接アクセスできる唯一のLLM
- 「今Xで何がバズっているか」をリアルタイムに分析できる
- X API不要（API制限を気にしなくていい）

### 4-2. 環境構築

```bash
# 1. xAI APIキー取得
#    https://console.x.ai/ でアカウント作成 → APIキー発行

# 2. パッケージインストール
pip3 install openai  # GrokはOpenAI互換APIを使う

# 3. 環境変数設定
export XAI_API_KEY="xai-xxxxxxxxxxxx"
# .bashrc や .zshrc に追記しておくと毎回入力不要
```

### 4-3. トレンドリサーチスクリプト

以下をコピーして `trend_research.py` として保存：

```python
"""Xトレンドリサーチ（Grok版）"""
import os, sys
from datetime import datetime
from openai import OpenAI

# === ここをあなたのアカウントに書き換える ===
ACCOUNT_INFO = """
- アカウント: @あなたのアカウント名
- 専門分野: あなたの専門分野
- ターゲット: あなたのターゲット層
- トーン: あなたの投稿トーン
"""
# ==========================================

def main():
    topic = sys.argv[1] if len(sys.argv) > 1 else "あなたの専門分野のキーワード"
    date_str = datetime.now().strftime('%Y-%m-%d')

    client = OpenAI(
        api_key=os.environ["XAI_API_KEY"],
        base_url="https://api.x.ai/v1",
    )

    prompt = f"""あなたはXのデータを熟知したアナリストだ。
あなたのXデータの知識を最大限に活用して、以下をリサーチせよ。

【リサーチ対象】日本のXでの「{topic}」に関するトレンド

{ACCOUNT_INFO}

以下のフォーマットで出力：

## トレンドブリーフ（{date_str}）

### 1. 今X上で熱い話題（5つ）
- 何が起きているか（固有名詞・数字を入れろ）
- なぜ人々が反応しているか（どの感情が動いているか）

### 2. 直近でバズったポスト分析（3つ）
- 元ポストの内容（具体的に）
- いいね・RT・インプの規模感
- なぜスクロールの手が止まったか
- パクれるエッセンス

### 3. 今日の空気感
- X上の温度感
- 今日反応されやすい感情

### 4. 投稿提案（3案）
各案に以下を含めること：
- 狙う感情（1つ）
- 使う型（8型から選択）
- 投稿文（140文字以内）
- 推奨投稿時間帯
- バズ期待度（★1-5）

### 5. 地雷・リスク
- 今日触れない方がいい話題
"""

    print(f"Grok分析中...\n")
    response = client.chat.completions.create(
        model="grok-3", messages=[{"role": "user", "content": prompt}],
        temperature=0.7, max_tokens=4000,
    )
    result = response.choices[0].message.content
    print(result)

    # レポート保存
    os.makedirs("reports", exist_ok=True)
    path = f"reports/trend-{datetime.now().strftime('%Y%m%d-%H%M')}.md"
    with open(path, "w", encoding="utf-8") as f:
        f.write(result)
    print(f"\n保存: {path}")

if __name__ == "__main__":
    main()
```

### 4-4. 使い方

```bash
python3 trend_research.py                    # デフォルト
python3 trend_research.py "Claude Code"      # 特定トピック深掘り
```

### やってみよう

> ACCOUNT_INFO を自分のアカウント情報に書き換えて実行。
> 出てきた投稿案で気に入ったものを投稿してみる。

---

## Step 5: バズポスト発見＋引用RT案を自動生成

### 5-1. なぜ引用RTが有効か

- 伸びている投稿の波に乗れる
- 専門家の一言を添えるだけで権威性が出る
- 自分のフォロワー以外にリーチできる

### 5-2. バズポスト発見スクリプト

以下をコピーして `find_buzz_posts.py` として保存：

```python
"""バズポスト発見＋引用RT案生成（Grok版）"""
import os, sys, json
from datetime import datetime
from openai import OpenAI

# === ここをあなたの情報に書き換える ===
ACCOUNT_INFO = """
- アカウント: @あなたのアカウント名
- 専門分野: あなたの専門分野
- トーン: 本質的、簡潔
"""

# 監視するアカウント（Step 3で作ったリスト）
WATCH_ACCOUNTS = [
    "@account1", "@account2", "@account3",
    # Step 3のリストをここに入れる
]
# =====================================

def main():
    keyword = sys.argv[1] if len(sys.argv) > 1 else "あなたの分野のキーワード"
    accounts_str = ", ".join(WATCH_ACCOUNTS[:25])

    client = OpenAI(
        api_key=os.environ["XAI_API_KEY"],
        base_url="https://api.x.ai/v1",
    )

    prompt = f"""あなたはXのデータを熟知したアナリストだ。

【タスク】
日本のXで「{keyword}」に関連してバズっている（いいね100以上）ポストを見つけ、
引用RTすべきものを選定し、コメント案を生成する。

【注目アカウント】
{accounts_str}

{ACCOUNT_INFO}

以下のフォーマットで出力：

## バズポスト発見レポート

### バズポスト一覧（10件）
各ポスト：
- 投稿者: @ユーザー名
- 内容: ポストの内容
- 数字: いいね / RT / インプ
- バズ要因: なぜ伸びたか

### 引用RT候補（3件）
各候補：
- 元ポスト: 著者・内容・数字
- 引用コメント（140文字以内）: 率直な感想。テンプレ禁止。
- 狙う感情: どの感情を動かすか
- 期待効果: ★1-5
"""

    print("Grokで検索中...\n")
    response = client.chat.completions.create(
        model="grok-3", messages=[{"role": "user", "content": prompt}],
        temperature=0.7, max_tokens=4000,
    )
    result = response.choices[0].message.content
    print(result)

    os.makedirs("reports", exist_ok=True)
    path = f"reports/buzz-{datetime.now().strftime('%Y%m%d-%H%M')}.md"
    with open(path, "w", encoding="utf-8") as f:
        f.write(result)
    print(f"\n保存: {path}")

if __name__ == "__main__":
    main()
```

### やってみよう

> WATCH_ACCOUNTS に Step 3 で選んだアカウントを入れて実行。
> 出てきた引用RT候補で良いものがあれば引用RTしてみる。

---

## Step 6: 自分の投稿パフォーマンスを自動分析

### 6-1. 何を分析するか

- エンゲージメント率（いいね / インプレッション）
- **RT6件の壁を超えたか**（超えるとインプが7.8倍）
- どの型・感情フックが効いたか
- フォロワー推移

### 6-2. 自己分析スクリプト

以下をコピーして `analyze_own.py` として保存：

```python
"""自アカウント投稿分析（Playwright + Grok）"""
import os, sys, json
from datetime import datetime
from playwright.sync_api import sync_playwright
from openai import OpenAI

COOKIE_FILE = ".x-cookies.json"
OWN_ACCOUNT = "あなたのアカウント名"  # @なし

SCRAPE_JS = """
async () => {
    for (let i = 0; i < 8; i++) {
        window.scrollTo(0, document.body.scrollHeight);
        await new Promise(r => setTimeout(r, 1500));
    }
    const bodyText = document.body.innerText;
    const followersMatch = bodyText.match(/([\d,.]+万?)\\s*フォロワー/);
    const articles = document.querySelectorAll('article');
    const tweets = [];
    const seen = new Set();
    articles.forEach(article => {
        const textEl = article.querySelector('[data-testid="tweetText"]');
        const timeEl = article.querySelector('time');
        const groups = article.querySelectorAll('[role="group"]');
        let metrics = '';
        groups.forEach(g => {
            const a = g.getAttribute('aria-label');
            if (a && a.includes('件の')) metrics = a;
        });
        let statusUrl = '';
        article.querySelectorAll('a[href*="/status/"]').forEach(a => {
            const href = a.getAttribute('href');
            if (href && href.match(/\\/status\\/\\d+$/) && !statusUrl) statusUrl = href;
        });
        if (statusUrl && !seen.has(statusUrl)) {
            seen.add(statusUrl);
            const likes = parseInt((metrics.match(/(\\d+)\\s*件のいいね/) || [0,0])[1]);
            const rts = parseInt((metrics.match(/(\\d+)\\s*件のリポスト/) || [0,0])[1]);
            const imps = parseInt(((metrics.match(/([\\d,]+)\\s*件の表示/) || [0,'0'])[1]).replace(/,/g,''));
            const bms = parseInt((metrics.match(/(\\d+)\\s*件のブックマーク/) || [0,0])[1]);
            if (timeEl) {
                tweets.push({
                    text: textEl ? textEl.innerText.substring(0, 500) : '',
                    time: timeEl.getAttribute('datetime'),
                    likes, rts, impressions: imps, bookmarks: bms
                });
            }
        }
    });
    tweets.sort((a, b) => new Date(b.time) - new Date(a.time));
    return { followers: followersMatch ? followersMatch[1] : '?', tweets: tweets.slice(0, 15) };
}
"""

def main():
    # スクレイプ
    with open(COOKIE_FILE, "r") as f:
        cookies = json.load(f)
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        ctx = browser.new_context(
            user_agent="Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) "
                       "AppleWebKit/537.36 Chrome/131.0.0.0 Safari/537.36",
            viewport={"width": 1280, "height": 900}, locale="ja-JP",
        )
        ctx.add_cookies(cookies)
        page = ctx.new_page()
        page.goto(f"https://x.com/{OWN_ACCOUNT}", wait_until="domcontentloaded", timeout=30000)
        page.wait_for_timeout(6000)
        data = page.evaluate(SCRAPE_JS)
        ctx.close(); browser.close()

    tweets = data.get("tweets", [])
    followers = data.get("followers", "?")
    rt6 = sum(1 for t in tweets if t["rts"] >= 6)
    print(f"フォロワー: {followers} / 取得: {len(tweets)}件 / RT6超: {rt6}件\n")

    # Grok分析
    tweets_text = ""
    for i, t in enumerate(tweets, 1):
        eng = f"{t['likes']/t['impressions']*100:.2f}%" if t['impressions'] > 0 else "N/A"
        tweets_text += f"投稿{i}: {t['text'][:300]}\n  ♥{t['likes']} RT{t['rts']} imp{t['impressions']} BM{t['bookmarks']} Eng:{eng}\n\n"

    client = OpenAI(api_key=os.environ["XAI_API_KEY"], base_url="https://api.x.ai/v1")
    prompt = f"""以下は@{OWN_ACCOUNT}（フォロワー{followers}）の直近投稿データだ。分析せよ。

{tweets_text}

【分析基準】
- 79%の投稿はRTゼロで消える
- RT6件超でインプ7.8倍（アルゴリズム閾値）
- バズの4パターン: 個人体験×社会観察、逆説、自分事化、断言

2000文字以内、結論ファースト、箇条書き中心で：

■ パフォーマンス概要（平均エンゲージメント率、RT6超の数、最も伸びた投稿）
■ 効いた型・感情フック
■ 改善すべき点
■ 次のアクション提案（3つ、具体的な投稿案を含む）
"""
    response = client.chat.completions.create(
        model="grok-3", messages=[{"role": "user", "content": prompt}],
        temperature=0.6, max_tokens=2000,
    )
    print(response.choices[0].message.content)

if __name__ == "__main__":
    main()
```

### やってみよう

> OWN_ACCOUNT を自分のアカウント名に変えて実行。
> 「改善すべき点」と「次のアクション提案」を次の投稿に活かす。

---

## Step 7: 定期実行を自動化する

### 7-1. 現実的な選択肢を選ぶ

**重要：cronは「常時起動しているマシン」が前提。**
普通のノートPC（スリープする・持ち歩く）ではcronは安定しない。

自分の環境に合った方法を選ぶ：

| 環境 | おすすめ方法 | 難易度 |
|------|------------|--------|
| **普通のPC（ノート等）** | A. 朝のルーティンで手動実行 | 簡単 |
| **普通のPC + 少し自動化** | B. ログイン時に自動実行 | 普通 |
| **常時起動サーバーあり** | C. cronで完全自動化 | 上級 |
| **サーバーなし＋完全自動化したい** | D. クラウド（GitHub Actions等） | 上級 |

### 7-2. パイプライン統合スクリプト（全方法共通）

個別スクリプトをバラバラに実行するのは面倒。1コマンドで全部動かすスクリプトを作る。

以下をコピーして `daily_pipeline.py` として保存：

```python
"""日次自動パイプライン — 全スクリプトを一括実行"""
import os, sys, subprocess
from datetime import datetime

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
REPORT_DIR = os.path.join(SCRIPT_DIR, "reports")

def run_script(name: str, args: list = None) -> tuple:
    """サブスクリプトを実行して結果を返す"""
    cmd = [sys.executable, os.path.join(SCRIPT_DIR, name)]
    if args:
        cmd.extend(args)
    print(f"\n{'='*60}")
    print(f"実行中: {name} {' '.join(args or [])}")
    print(f"{'='*60}\n")
    result = subprocess.run(cmd, capture_output=True, text=True, timeout=180)
    if result.returncode == 0:
        print(result.stdout)
        return True, result.stdout
    else:
        print(f"エラー: {result.stderr}")
        return False, result.stderr

def main():
    mode = "all"
    if len(sys.argv) > 1:
        if sys.argv[1] == "--trend-only":
            mode = "trend"
        elif sys.argv[1] == "--buzz-only":
            mode = "buzz"
        elif sys.argv[1] == "--benchmark-only":
            mode = "benchmark"

    date_str = datetime.now().strftime('%Y-%m-%d %H:%M')
    print(f"╔══════════════════════════════════════════╗")
    print(f"║  日次パイプライン                         ║")
    print(f"║  {date_str}                        ║")
    print(f"╚══════════════════════════════════════════╝")

    os.makedirs(REPORT_DIR, exist_ok=True)
    results = {}

    # Step 1: ベンチマーク監視（競合の伸びポスト分析）
    if mode in ("all", "benchmark"):
        ok, out = run_script("scrape_benchmarks.py")
        results["benchmark"] = {"ok": ok, "output": out}

    # Step 2: トレンドリサーチ
    if mode in ("all", "trend"):
        ok, out = run_script("trend_research.py", ["--broad"])
        results["trend"] = {"ok": ok, "output": out}

    # Step 3: バズポスト検索＋引用RTコメント
    if mode in ("all", "buzz"):
        ok, out = run_script("find_buzz_posts.py")
        results["buzz"] = {"ok": ok, "output": out}

    # サマリー
    print(f"\n{'='*60}")
    print("日次パイプライン完了")
    print(f"{'='*60}")
    for key, val in results.items():
        status = "OK" if val["ok"] else "FAIL"
        print(f"  {key}: {status}")
    print(f"\nレポートは {REPORT_DIR}/ に保存されています。")

if __name__ == "__main__":
    main()
```

使い方：
```bash
python3 daily_pipeline.py                  # 全部実行
python3 daily_pipeline.py --trend-only     # トレンドリサーチのみ
python3 daily_pipeline.py --buzz-only      # バズポスト検索のみ
python3 daily_pipeline.py --benchmark-only # ベンチマーク監視のみ
```

### 7-3. 方法A：朝のルーティンで手動実行（最も現実的）

朝コーヒーを入れながら1コマンド叩くだけ。これで十分。

```bash
python3 daily_pipeline.py
```

**これが最も続く方法。** 完全自動化にこだわって結局動かないより、手動1コマンドを毎日叩く方がいい。

### 7-4. 方法B：ログイン時に自動実行（Mac）

Macならログイン時に自動実行できる。PCを開くたびにレポートが生成される。

```bash
# LaunchAgent を作成（Macのみ）
mkdir -p ~/Library/LaunchAgents
cat > ~/Library/LaunchAgents/com.x-analysis.morning.plist << EOF
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    <key>Label</key>
    <string>com.x-analysis.morning</string>
    <key>ProgramArguments</key>
    <array>
        <string>/usr/bin/python3</string>
        <string>/path/to/your/project/daily_pipeline.py</string>
    </array>
    <key>WorkingDirectory</key>
    <string>/path/to/your/project</string>
    <key>RunAtLoad</key>
    <true/>
    <key>StandardOutPath</key>
    <string>/path/to/your/project/reports/cron.log</string>
    <key>StandardErrorPath</key>
    <string>/path/to/your/project/reports/cron.log</string>
</dict>
</plist>
EOF

# 有効化
launchctl load ~/Library/LaunchAgents/com.x-analysis.morning.plist
```

### 7-5. 方法C：cronで完全自動化（常時起動サーバー向け）

Mac mini、自宅サーバー、VPSなど **24時間動いているマシン** がある場合のみ。

```bash
crontab -e
```

```cron
# === X運用自動パイプライン ===
PYTHON=/usr/bin/python3
WORKDIR=/path/to/your/project

# 朝の偵察（パイプライン一括実行）
30 6 * * * cd $WORKDIR && $PYTHON daily_pipeline.py >> reports/cron.log 2>&1

# 自アカウント分析（週1回・日曜）
0 18 * * 0 cd $WORKDIR && $PYTHON analyze_own.py >> reports/cron.log 2>&1
```

### 7-6. 方法D：GitHub Actionsで完全自動化（サーバーなし）

自分のマシンを使わずクラウドで実行。ただしPlaywright（スクレイプ）は使えないため、Grok分析のみ。

```yaml
# .github/workflows/x-analysis.yml
name: X Daily Analysis
on:
  schedule:
    - cron: '30 21 * * *'  # UTC 21:30 = JST 6:30
  workflow_dispatch:  # 手動実行も可能

jobs:
  analyze:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-python@v5
        with:
          python-version: '3.12'
      - run: pip install openai
      - run: python daily_pipeline.py --trend-only
        env:
          XAI_API_KEY: ${{ secrets.XAI_API_KEY }}
      - uses: actions/upload-artifact@v4
        with:
          name: reports-${{ github.run_number }}
          path: reports/
```

GitHub Secretsに `XAI_API_KEY` を設定すること。
無料枠（月2,000分）で十分足りる。

### 7-7. LINE通知を追加する（オプション）

各スクリプトの末尾に以下を追加すると、結果がLINEに届く：

```python
import urllib.request

def send_line(message, token, user_id):
    body = json.dumps({
        "to": user_id,
        "messages": [{"type": "text", "text": message[:4500]}]
    }).encode("utf-8")
    req = urllib.request.Request(
        "https://api.line.me/v2/bot/message/push",
        data=body,
        headers={
            "Content-Type": "application/json",
            "Authorization": f"Bearer {token}",
        },
        method="POST",
    )
    urllib.request.urlopen(req)
```

必要な環境変数：
- `LINE_CHANNEL_ACCESS_TOKEN` — LINE Messaging API トークン
- `CEO_LINE_USER_ID` — あなたのLINEユーザーID

### 7-8. スケジュール設計（どの方法でも共通）

| タイミング | 処理 | コスト |
|-----------|------|--------|
| 毎朝 | パイプライン一括（トレンド + バズ + ベンチマーク） | ~150円 |
| 週1回 | 自アカウント分析 | ~50円 |

**日次コスト：約150円（月4,500円）+ 週50円**

### やってみよう

> まずはcronなしで `python3 daily_pipeline.py` を手動実行して動作確認。
> 問題なければ自分の環境に合った方法（A〜D）を選んで設定。

---

## Step 8: インプレッション爆増の6戦術

### 8-0. アルゴリズムの大原則

**Xのアルゴリズムは「このアカウントは何の専門家か」を判定している。**

- 関係ない話題にリプすると → その話題に興味があるアカウントとXに認識される
- そこでスコアが悪い（タップされない）と → アカウント自体の評価が下がる
- つまり：**発信軸をぶらさないことが最重要。関係ない話題への無理なリプは自殺行為。**

### 8-1. 戦術1：トレンドワード×自分の専門性（効果：大）

**やり方：** トレンドワードを拾い、自分の専門分野と掛け合わせたポストを作る。

**ポイント：**
- トレンドに「乗る」のではなく、トレンドを「自分のフィールドに引き込む」
- 無関係なトレンドには乗らない（アルゴリズム的に逆効果）
- 自分の得意分野と自然に繋がるトレンドだけを狙い撃ちする

**自動化：** Step 4のGrokでトレンドを取得 → 自分の専門性との接点があるものだけフィルタ → 投稿案生成

### 8-2. 戦術2：バズっている投稿への引用RT＋率直な感想（効果：大）

**やり方：** 今バズっているポストを見つけ、引用RTで自分の率直な感想・見解を添える。

**ポイント：**
- 何気ない感想でもOK（「率直さ」がポイント）
- 伸びてる投稿の波に乗れる
- 専門家としての見解を一言添えるだけで権威性が出る
- 元ポストの話題が自分の領域と合っていることが必須

**自動化：** Step 5のスクリプトで検索 → 引用RT用のコメント案を生成

### 8-3. 戦術3：ニュース速報への専門家リプ（効果：中〜大）

**やり方：** ニュース系アカウントの関連ニュースに、専門家視点でリプライする。

**ポイント：**
- **自分の専門分野に関連するニュースだけに反応する**
- リプの内容は「専門家のワンポイント解説」
- 「へぇ」「なるほど」と思わせる一言を添える
- 一般ニュース（政治・芸能等）にはリプしない（アルゴリズム的に逆効果）

### 8-4. 戦術4：過去にバズったポストの再活用（効果：中）

**やり方：** 過去に伸びた自分の投稿を、ストーリーの一部として再紹介する。

**ポイント：**
- 単なる再投稿ではなく「ストーリーとして紹介」する
- 「このポストがきっかけで〇〇が始まった」等の文脈を添える
- やりすぎ注意（月1〜2回程度が限界）

**自動化：** Step 6の自己分析から過去のエンゲージメント上位を抽出 → 再利用候補をリストアップ

### 8-5. 戦術5：体験談ベースの長文記事→Xシェア（効果：大）

**やり方：** 自分の体験を記事（note等）にまとめ、Xでシェアする。

**ポイント：**
- URL共有はX内リポストよりアルゴリズム的に強い（外部から人を引っ張ってくるため）
- 体験談＝唯一無二のコンテンツ。誰にもパクれない
- 「〇〇してみた」「〇〇の全記録」系のタイトルが強い

### 8-6. 戦術6：「弱さを見せる」ポスト（効果：特大だが使いどころ限定）

**やり方：** 正直な弱さ・困難を見せるポスト。

**ポイント：**
- 「完璧な専門家」より「頑張っている人間」に人は共感する
- 嘘はダメ。本当に困っている時、本当に感じていることだけ
- 使いすぎると「かまってちゃん」になる。ここぞという時だけ

**注意：** これは自動化しない。あなたの本音でしか書けない。

### やらないことリスト

- 関係ないトレンドへの無理乗り
- 政治・芸能ニュースへのリプ
- 毎日同じパターンの投稿
- フォロワー数稼ぎの相互フォロー
- 「いいね返し」のための義理エンゲージメント

### やってみよう

> 今日、戦術1〜3のどれか1つをやってみる。
> 明日、それがどのくらい反応があったか確認する。

---

## Step 9: 投稿ワークフロー — 品質担保の投稿フロー

**投稿は「書いたら出す」ではない。品質チェック→投稿→フォローアクションの3フェーズで運用する。**

### 9-1. 投稿前チェックリスト（全項目クリアするまで投稿するな）

#### 内容チェック
- [ ] **誤字脱字がないか**: 誤字は信頼を一瞬で壊す
- [ ] **文字数が140文字以内か**: 再カウント（AIの出力は超えていることがある）
- [ ] **意図しない意味に読めないか**: 皮肉・批判と誤解される表現に注意
- [ ] **リンクがある場合、リンク切れしていないか**

#### ブランドチェック
- [ ] **5つのNGパターンに該当しないか**（Step 1-2参照）
- [ ] **ブランドボイスのNG例と同じ匂いがしないか**（Step 0参照）
- [ ] **アカウントの専門領域から外れていないか**

#### タイミングチェック
- [ ] **投稿時間はターゲットペルソナのアクティブ時間か**
- [ ] **深夜帯（0:00-6:00）ではないか**
- [ ] **同日にすでに投稿済みの場合、間隔は十分か**（最低2時間空ける）

#### トレンド投稿の場合の追加チェック
- [ ] **便乗感が出ていないか**: トレンドと自分の専門性の接点が自然か
- [ ] **タイミングが遅すぎないか**: ピークを過ぎていたら投稿しない
- [ ] **そのトレンドに関する地雷がないか**: 少しでもリスクを感じたら投稿しない
- [ ] **同じトレンドに乗った他の投稿と差別化できているか**

### 9-2. 投稿実行

チェックリストを全てクリアしたら投稿する。

手動の場合：Xアプリで投稿。
自動の場合（X APIを使う場合）：
```bash
python3 post_tweet.py "{投稿内容}"
```

### 9-3. 投稿後のフォローアクション

投稿して終わりではない。

#### 初速チェック（投稿後30分〜1時間）
- いいね・RT・リプライの数を確認
- **RT6件の壁を超えたか確認**
- 初速が弱い場合、時間帯が合っていなかった可能性を記録

#### リプライ監視
- **ポジティブなリプライ**: 感謝の返信を検討（親近感向上）
- **質問リプライ**: 丁寧に回答（信頼構築の最大チャンス）
- **ネガティブなリプライ**: 冷静に対応方針を判断。感情的に反応しない

#### 振り返りメモ（投稿後24時間以内）

```markdown
## 投稿振り返り（YYYY-MM-DD HH:MM）
- 投稿URL: [URL]
- 使用した型: [型名]
- ターゲットペルソナ: [A/B/C]
- 24時間後の数値: いいね[X] / RT[X] / インプ[X] / ブックマーク[X]
- RT6件の壁: [超えた / 超えなかった]
- 特筆すべきリプライ・反応: [あれば記載]
- 次回への改善点: [あれば記載]
```

### やってみよう

> 次の投稿からこのチェックリストを使う。
> 投稿後24時間以内に振り返りメモを書く。
> 1週間続けたら、振り返りメモを見返してパターンを見つける。

---

## 日次運用フロー（全ステップ統合版）

Step 0〜9をマスターしたら、以下の日次フローで回す。

### Phase 0: 自動パイプライン（cron / 手動1コマンド）
- トレンドリサーチ → バズポスト検索 → 競合監視
- `python3 daily_pipeline.py`

### Phase 1: 偵察
- パイプライン結果を確認
- 今日のトレンド・バズ・競合の動向を把握

### Phase 2: 作戦会議
- 今日投稿すべきか判断（無理に投稿しない）
- 投稿する場合：テーマ・型・ターゲットペルソナ・タイミングを決定

### Phase 3: 制作
- 型を使って3案以上生成（Step 2のプロンプト）
- 品質ゲート5チェックを通過した案だけ残す

### Phase 4: 投稿
- Step 9のチェックリストを通す
- 投稿実行

### Phase 5: 振り返り
- 初速チェック → リプライ監視 → 振り返りメモ
- 週1回、自アカウント分析（Step 6）を実施

**投稿しない判断も立派な判断。** トレンドに乗れるネタがない日は沈黙する。ただし3日以上の空白は避ける。

---

## トラブルシューティング

### Q: スクレイプでツイートが取得できない
- Cookieが期限切れ → `setup_x_login.py` を再実行
- Xの仕様変更 → SCRAPE_JS のセレクタを確認

### Q: Grok APIでエラーが出る
- `XAI_API_KEY` が正しいか確認
- APIクレジットの残高を https://console.x.ai/ で確認

### Q: cronが動いていない
- `crontab -l` で設定を確認
- `reports/cron.log` でエラーを確認
- パスが絶対パスになっているか確認

### Q: Macのスリープ中にcronが動かない
- `caffeinate` やスリープ無効化設定を検討
- または朝起きた時に手動で `python3 daily_pipeline.py` を実行

---

## コスト管理

| 利用レベル | 日次コスト | 月次コスト |
|-----------|-----------|-----------|
| Step 0-2のみ（手動） | 0円 | 0円 |
| Step 4-5を毎日（トレンド+バズ） | ~100円 | ~3,000円 |
| 全自動（Step 7 パイプライン） | ~150円 | ~4,500円 |

Grok APIの料金：入力$3/M tokens、出力$15/M tokens（2025年時点）

---

## まとめ：段階的に導入せよ

```
Step 0（ブランド設計）          ← 最初にやる。全ての土台
  ↓
Step 1（型を覚える）            ← 今日からできる
  ↓
Step 2（AIに投稿案を作らせる）  ← 明日からできる
  ↓
Step 3（競合を観測する）        ← 今週からできる
  ↓
Step 4-5（Grokで自動分析）     ← APIキー取得後すぐ
  ↓
Step 6（自己分析）             ← 投稿が増えてから
  ↓
Step 7（全自動化）             ← 運用が安定してから
  ↓
Step 8（インプ爆増戦術）       ← 基盤ができてから
  ↓
Step 9（投稿ワークフロー）     ← Step 2から始めてもいい
```

**全部を一度にやろうとしない。Step 0でブランドを設計し、Step 1で型を覚えるだけで投稿の質は劇的に変わる。**
