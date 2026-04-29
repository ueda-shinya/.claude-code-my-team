---
name: lp-create
description: "LP（ランディングページ）の制作フロー全般を、コピーファースト原則とゲート式承認で管理するオーケストレーター。フル制作だけでなく『LPのストーリー考えて』『LPコピー作って』『LPデザインして』『スワイプLPのストーリーから作り直して』など部分依頼にもRoute判定で対応する。LP / ランディングページ / スワイプLP / 縦長LP / リードLP / 無料配布LP / LP戦略 / LP訴求 / LP構成 / LPストーリー / LPセクション設計 / LPコピー / LPデザイン / LP実装 のいずれかが含まれる依頼に対し、まず本スキルの起動可否を判定する。"
---

# /lp-create Skill

Manages the full LP (Landing Page) creation workflow with a marketing-first approach.
Locks in messaging strategy and section structure before coding to prevent costly rework.

**Important: Copy-first principle.** Industry standard (Unbounce 36,928件分析・CXL/NNG/StoryBrand) では、LP の成果はコピー65〜70% / デザイン30〜35% に依存する。本スキルは戦略 → コピー → デザイン → 実装の順序を**ゲート**で物理的に強制する。

## Trigger Conditions

LP関連キーワード（**LP / ランディングページ / スワイプLP / 縦長LP / リードLP / 無料配布LP / LP戦略 / LP訴求 / LP構成 / LPストーリー / LPセクション設計 / LPコピー / LPデザイン / LP実装**）が含まれる依頼は、フル制作・部分制作を問わず、**まず本スキル `lp-create` の起動可否を最初に判定**する。判定は Step 0（依頼スコープ判定）で行い、Route F〜I のいずれかにルーティングする。明示的に `lp-create` をスキップする場合のみ Route X を許可する。

### 起動トリガー語（例示・網羅ではない）

**フル制作系:**
- 「LP作って」「LPを新規制作したい」「ランディングページを作りたい」「スワイプLP作って」
- 「無料配布LP（を）作って」「リード獲得LP（を）作って」
- `/lp-create`

**部分制作系（必ず本スキル経由で Route 判定）:**
- 「LPのストーリー考えて」「LPの構成考えて」「ストーリーから作り直して」「スワイプLPのストーリーから（作り直して）」
- 「LPコピー作って」「LPの文章書いて」「LPの訴求設計して」「LPのメッセージ考えて」
- 「LPデザインして」「LPのビジュアル設計して」「LPのレイアウト考えて」
- 「LPの実装して」「LPをコーディングして」「LPの HTML/CSS 書いて」
- 「LPの〇〇だけ作って」「LPの△△だけ考えて」（部分指定）
- 「ランディングページの戦略」「LPの訴求設計」

### 既存スキルとの起動順序

`frontend-design`（外部スキル）も LP 関連キーワードで起動候補に挙がる場合がある。**本スキル `lp-create` を必ず先に起動**し、Route 判定で Route D（デザインのみ）かつシンヤさんがモード(2)（frontend-design 一気通貫）を選択した場合に限り `frontend-design` をシュウ経由で発動する。`lp-create` を経由せずに `frontend-design` を直接発動するのはコピーファースト原則に反するため禁止。

## Constraints (Apply to All Steps & Routes)

- **Asuka never codes directly.** All coding must be delegated to Shu ("Asuka Never Codes Directly" rule)
- **Approval gates cannot be skipped.** Even if Shinya says "hurry up", always obtain confirmation
- **If a step proceeds without approval, report to Shinya and revert to the previous step**
- **Security review (Sakura) is automatic every time when code is produced.** Cannot be omitted
- Each step's deliverables carry forward to the next step. If a previous step's output is insufficient, Asuka requests supplementation
- **Copy-first ordering is enforced via Route gates.** デザインはコピー確定後、実装はデザイン確定後にしか実行できない（ゲート違反は技術的に成立しない）
- **並列実行禁止**: Step 3（戦略）/ Step 4（コピー含むワイヤー）/ Step 5（デザイン）/ Step 6（実装）は並列実行禁止。各ステップは前ステップの確定マーカー（`<!-- LP-CREATE-GATE: XXX-CONFIRMED YYYY-MM-DD -->`）が付与された後に開始する
- **複数エージェント並列指示禁止**: 「レンとコトとカイに並列で作業させる」のような並列指示は本スキルの設計に違反する。常に直列実行（前段の出力を後段の入力として渡す）を守る。Route 内で並列依頼の発想が出た場合は、アスカが本制約に基づき却下し、直列フローに戻すこと
- **確定マーカーは英語タグ形式のみで判定する**: ゲート判定は `<!-- LP-CREATE-GATE: STRATEGY-CONFIRMED YYYY-MM-DD -->` / `<!-- LP-CREATE-GATE: COPY-CONFIRMED YYYY-MM-DD -->` / `<!-- LP-CREATE-GATE: DESIGN-CONFIRMED YYYY-MM-DD -->` / `<!-- LP-CREATE-GATE: IMPLEMENTATION-CONFIRMED YYYY-MM-DD -->` の HTML コメント形式で機械的に判定する。日本語の自由文字列（「戦略確定: YYYY-MM-DD」等）は人間向けの併記としては許容するが、ゲート判定には使用しない

---

## Step 0: 依頼スコープ判定（Route 判定 / アスカが必須実行）

依頼内容を分析し、以下の Route F〜X のいずれかに分類する。**この判定を経ずに後続ステップへ進むことを禁止する**。

### Route 一覧

| Route | 内容 | 実行ステップ | 前提条件（ゲート） |
|---|---|---|---|
| **Route F** | フルLP制作（戦略〜実装まで） | Step 1〜9 全実行 | なし |
| **Route S** | ストーリー・戦略のみ | Step 1（簡易） + Step 3 | なし |
| **Route C** | コピーのみ | Step 1（簡易・必要分のみ） + Step 4 のコト部分 | **戦略書（strategy.md）が確定していること**。なければ Route S を先行 |
| **Route D** | デザインのみ | `lp-design-system` スキル委譲（Step 5 相当を独立実行） | **コピー（copy.md）が確定していること**。未確定なら Route C を先行 |
| **Route I** | 実装のみ | Step 6（シュウ） + Step 7（サクラ） + Step 8 | **デザイン仕様書（design-spec.md）が確定していること**。未確定なら Route D を先行 |
| **Route X** | 例外宣言（明示スキップ） | ad-hoc 処理 | **シンヤさんが明示的に「lp-create を使わない」と宣言した場合のみ**。Notion 案件管理に「例外処理」として記録必須 |

### 判定基準（離散）

依頼文を以下の語彙でマッチング：

| 依頼文の特徴 | 判定 Route |
|---|---|
| 「ストーリー」「構成」「戦略」「訴求設計」のみ | **Route S** |
| 「コピー」「文章」「メッセージ」「ライティング」のみ | **Route C** |
| 「デザイン」「ビジュアル」「レイアウト」「見た目」のみ | **Route D** |
| 「実装」「コーディング」「HTML」「CSS」「PHP」のみ | **Route I** |
| 範囲指定なし / 「LP作って」「全部」「一通り」「リニューアル」 | **Route F** |
| シンヤさんが「lp-create 使わない」「ad-hoc で」と明示 | **Route X** |
| 判定が曖昧（複数該当 or どれにも当たらない） | アスカが**1回だけ**シンヤさんに確認: 「フル制作 / 部分制作（どこまで？）どちらで進めますか？」 |

### Route 判定ゲートの強制

- **Route C 実行前**: `strategy.md` の存在と確定マーカー `<!-- LP-CREATE-GATE: STRATEGY-CONFIRMED YYYY-MM-DD -->` を Read+正規表現で確認。マーカーがなければ「先に Route S を実行する必要があります」と報告し、Route S を先行する
- **Route D 実行前**: `copy.md` の存在と確定マーカー `<!-- LP-CREATE-GATE: COPY-CONFIRMED YYYY-MM-DD -->` を Read+正規表現で確認。マーカーがなければ「先に Route C を実行する必要があります」と報告し、Route C を先行する
- **Route I 実行前**: `design-spec.md` の存在と確定マーカー `<!-- LP-CREATE-GATE: DESIGN-CONFIRMED YYYY-MM-DD -->` を Read+正規表現で確認。マーカーがなければ「先に Route D を実行する必要があります」と報告し、Route D を先行する
- **マーカー判定の正規表現**: `<!-- LP-CREATE-GATE: (STRATEGY|COPY|DESIGN|IMPLEMENTATION)-CONFIRMED [0-9]{4}-[0-9]{2}-[0-9]{2} -->` で機械的に一意判定する。日本語の自由文字列マーク（「戦略確定: YYYY-MM-DD」等）は判定対象としない（説明文中の同名語句との誤マッチを避けるため）
- ゲート違反を検知した場合、シンヤさんが「ゲートを飛ばして進める」と明示しても、**コピーファースト原則違反となるためシンヤさんに警告を1回出す**。それでも飛ばす指示が出た場合のみ Route X として扱い、Notion 案件に「ゲート飛ばし」を記録

### Route 別の出力先

各 Route の成果物は次の Route に渡せる形で保存する。保存パスは「Image Save Location Rules」と整合させた以下のパターンを使う：

| 案件種別 | 保存先パターン |
|---|---|
| クライアント案件（単一事業） | `~/.claude/clients/<client-name>/lp-<date>/` |
| クライアント案件（複数事業） | `~/.claude/clients/<client-name>/biz-<business-name>/lp-<date>/` |
| 自社・素振り | `~/.claude/workspaces/<project-name>/` |

各 Route の出力ファイル名:

| Route | 出力ファイル | 必須セクション |
|---|---|---|
| Route S | `strategy.md` | メッセージング軸 / セクション構成案 / CTA配置戦略 / 確定マーカー `<!-- LP-CREATE-GATE: STRATEGY-CONFIRMED YYYY-MM-DD -->` |
| Route C | `copy.md` | 全セクション分のコピー（FVヘッドライン・サブコピー・本文・CTAラベル・マイクロコピー） / 確定マーカー `<!-- LP-CREATE-GATE: COPY-CONFIRMED YYYY-MM-DD -->` |
| Route D | `design-spec.md` | カラー / フォント / FV / 視線誘導 / 信頼要素配置 / CTA / レスポンシブ / パフォーマンス / Image Definition List / 確定マーカー `<!-- LP-CREATE-GATE: DESIGN-CONFIRMED YYYY-MM-DD -->` |
| Route I | 実装ファイル一式（PHP/HTML/CSS/JS） + `image-prompts.md` |
| Route F | 上記すべて |

### Route 判定後の Step 1（簡易ヒアリング）の絞り込み

Route S/C/D/I では Step 1 のヒアリング項目を**Route で必要な範囲のみに絞る**:

| Route | Step 1 で必須のヒアリング Phase |
|---|---|
| Route F | Phase 1〜9（全て） |
| Route S | Phase 1（基本情報）/ Phase 2（CVゴール）/ Phase 3（流入元・戦略）/ Phase 4（ターゲット・ペルソナ）/ Phase 5（商品・サービス情報）/ Phase 8（セクション構成案） |
| Route C | Phase 1（基本情報）/ Phase 4（ターゲット・特に肉声）/ Phase 5（顧客の声）/ Phase 6（コピー・トーン・法的制約） ※ strategy.md を入力として読込 |
| Route D | Phase 1（基本情報）/ Phase 7（デザイン参照・NG）※ copy.md を入力として読込 |
| Route I | Phase 1（基本情報）/ Phase 9（CMS・公開URL・フォーム・解析） ※ design-spec.md と copy.md を入力として読込 |

Route F 以外では、絞り込んだ Phase のみヒアリングを実施し、不要 Phase は飛ばす。

### Route 判定の報告

Route 判定が完了したら、必ずシンヤさんに以下の形式で報告してから次ステップへ進む：

```
[Route 判定結果]
- 判定: Route X
- 根拠: 依頼文の「〇〇」というキーワードから判定
- 実行ステップ: Step 1（簡易・Phase X〜Y）→ Step Z
- 出力先: <パス>
- ゲート確認: <前提ファイル名> = 確定済み / 未確定（Route 〇〇を先行）

このRoute で進めてよろしいですか？
```

シンヤさんの「OK」「進めて」を受領してから後続ステップへ。

---

## Execution Steps (Route F の標準フロー / Route S/C/D/I は該当ステップのみ実行)

以下の Step 1〜9 は Route F の完全フローを定義する。Route S/C/D/I は Step 0 の Route 表に従い、必要なステップのみ実行する。各ステップ完了時はシンヤさんに報告してから次へ進む。

---

### Step 1: Requirements Hearing (Asuka as Facilitator)

**Route 別の Phase 絞り込みは Step 0 の表を参照**。Route F は Phase 1〜9 全実行、Route S/C/D/I は該当 Phase のみ。

#### Role Assignment

| Role | Assignee | Scope |
|---|---|---|
| Facilitator | Asuka | Progress management, phase transitions, summary confirmations, handoff to next step |
| Marketing deep-dive | Ren (`subagent_type: marketing-planner`) | Deep-dive for Phase 2-5 (CV goals, traffic sources, target, product info, numbers, strategy) |
| Copy deep-dive | Koto (`subagent_type: copywriter`) | Deep-dive for Phase 4 & 6 (pain points in user's words, objections, emotions, tone, copy) |

#### Basic Rules

- **Treat the user's initial answer as a "rough draft."** Assume the first answer is incomplete and probe deeper
- **Only one agent probes at a time.** Asuka decides per item whether to hand off to Ren or Koto
- **Deep-dive loop count: minimum 3 rounds, maximum 10 rounds** (see rules below)
  - "Minimum 3 rounds" is the minimum continuation count after a deep-dive has started. If the first answer is substantive, no deep-dive is needed
- **Asuka performs summary confirmation at the end of each phase** (see procedure below)

#### Deep-Dive Rules (Common to Ren & Koto)

```
1. Receive the user's answer
2. Judge whether the answer is "substantive"
   - Substantive: contains specific facts, numbers, real words, or episodes
   - Non-substantive: abstract answers like "make it nice", "somehow", "normally", "nothing special"
   - Non-substantive (additional): answers that merely select an option without explaining the reason or background
3. If non-substantive -> probe using one of these 4 patterns:
   a. Rephrasing: "So you mean XX, is that correct?"
   b. Hypothesis: "Could XX actually be the real issue?"
   c. Contrast: "Is it closer to A or B?"
   d. Draft completion (when stuck after 3+ rounds):
      Ren/Koto presents a draft answer and asks "How about this answer?"
      Shinya modifies or approves to finalize the answer
4. Substantive answer obtained -> deep-dive complete
5. After 10 rounds without a substantive answer -> finalize with answers so far and move on
6. If Shinya explicitly instructs to end the deep-dive (e.g., "that's enough", "move on"),
   terminate even if below the minimum round count.
   However, only if the item is "required" and the answer is still abstract,
   Asuka asks "Shall we proceed as-is?" exactly once for confirmation.
```

#### Handling by Item Rank

| Rank | Policy |
|---|---|
| **Required** | Must obtain some answer. If truly unobtainable, Ren/Koto proposes options for selection. "I don't know" is not accepted as an answer |
| **Recommended** | Probe deeper, but OK if a substantive answer is not obtained. Proceed with a reasonable answer |
| **Optional** | Ren/Koto attempts to elicit an answer by proposing options. If no answer is obtained, leave blank and move on |

#### Phase Assignment

| Phase | Deep-dive lead | Reason |
|---|---|---|
| Phase 1 (Basic info) | Asuka only | Factual confirmation; no deep-dive needed. Just confirm unanswered items |
| Phase 2 (CV goals) | Ren | CV definition, KPIs, and flow require marketing judgment |
| Phase 3 (Traffic sources & marketing strategy) | Ren | Ad alignment and competitive analysis need marketing perspective |
| Phase 4 (Target & persona) | Ren (demographics, consideration stage) + Koto (pain points in words, objections, speech patterns) | Ren handles attributes, Koto handles language quality |
| Phase 5 (Product & service info) | Ren (USP, track record, numbers) + Koto (customer testimonials, episodes) | Ren handles numbers, Koto handles emotions and language |
| Phase 6 (Copy & messaging) | Koto (CTA, tone, legal constraints) | Koto leads copy decisions |
| Phase 7 (Design) | Asuka only | Factual confirmation. No deep-dive needed |
| Phase 8 (Section structure) | Ren (structure validity) | Validate structure from marketing perspective |
| Phase 9 (Technical & operations) | Asuka only | Technical fact confirmation; no deep-dive needed |

#### Procedure

```
1. Asuka references the hearing sheet (~/.claude/skills/lp-create/hearing-sheet.md)
   and asks Shinya questions starting from Phase 1 in order.
   ※ Route S/C/D/I の場合は Step 0 の表に従って必要 Phase のみ。

2. Present all questions for each Phase in one batch (questions are presented once, but the number
   of round trips including deep-dives varies by Phase)

3. After receiving the user's answers, Asuka determines the responsible agent and requests deep-dive
   - Only pass items that need deep-diving to the agent
   - Asuka directly handles factual confirmation items

4. After deep-dive is complete, Asuka summarizes the Phase answers and confirms with Shinya:
   "Here is a summary of Phase X answers. Is this correct?"
   -> Shinya returns "OK" or corrections
   -> Once approved, fill in the corresponding Phase on the hearing sheet and move to the next Phase

5. When all required items in the targeted Phases are filled, declare "Hearing complete"
   and Asuka reports completion to Shinya before proceeding to Step 2
   (Route F: Phase 1-6, 8, 9 が必須。Phase 7 は任意/推奨中心のため Step 4 でカイへ引き継ぎ時に確認)
```

**Step completion criteria:** Route で必要な Phase の Required 項目が全て埋まった

---

### Step 2: Hearing Content Review (Pre-Finalization Check)

After the hearing is complete, compile all answers into a list and provide Shinya with an opportunity to review and revise.
Do not proceed to Step 3 until Shinya says "confirmed."

**Route 適用:** Route F / Route S では実施。Route C/D/I では既存の strategy.md / copy.md / design-spec.md がレビュー済みであるため、本ステップはスキップ可能。スキップした場合は Step 0 のゲート確認をもって代替する。

#### Procedure

**1. Request evaluation from Ren and Koto**

Asuka passes all hearing answers to the following agents and requests evaluation and advice in parallel:
- Ren (`subagent_type: marketing-planner`): Marketing perspective evaluation for Phase 1-6, 8, 9
- Koto (`subagent_type: copywriter`): Copy and language perspective evaluation for Phase 4-6

Request details:
- Rate each item in the assigned Phases on a 1-5 star scale
- Add advice for items rated 3 stars or below
- Items rated 4 stars or above can be marked "-" (no special notes needed)
- **Ren's scope:** Marketing-related items in Phase 1-6, 8, 9 (CV goals, traffic sources, target demographics, USP, track record, numbers, structure, technical)
- **Koto's scope:** Language and emotion-related items in Phase 4-6 (pain points in words, objections, speech patterns, customer testimonials, CTA wording, tone)
- Each item is evaluated by either Ren or Koto exclusively (no duplicate evaluations)

**Star rating criteria:**
```
★★★★★ (5) Very helpful (specific, with numbers, real voices, episodes)
★★★★☆ (4) Helpful (mostly specific)
★★★☆☆ (3) Average (could be more detailed)
★★☆☆☆ (2) Somewhat lacking (needs deeper probing)
★☆☆☆☆ (1) Barely helpful (too abstract)
```

**2. Output the review list**

After receiving evaluations from Ren and Koto, Asuka outputs in the following format:

```
-- Hearing Content Review

| Phase | # | Item | Answer | LP Usefulness | Evaluator | Advice |
|---|---|---|---|---|---|---|
| Phase 1 | 1 | Project name | XX LP | ★★★★★ | Asuka | - |
| Phase 2 | 5 | CV definition | Free consultation form submission | ★★★★★ | Ren | - |
| Phase 4 | 18 | Target pain points | Built a website but no inquiries coming in | ★★★☆☆ | Koto | Adding 1-2 more specific episodes would strengthen the messaging |
...

If you want to revise any items, specify the number.
If no revisions are needed, say "confirmed" to proceed to Step 3.
```

**3. Revision loop**

- Shinya specifies item numbers and provides revised content -> Asuka updates the corresponding items on the hearing sheet
- After updating, Asuka re-evaluates the revised answers and updates the star ratings herself (no re-delegation to Ren or Koto)
- After updating, re-output the list (mark revised items with "(updated)" and reflect the updated star ratings)
- Repeat until Shinya says "confirmed"

**4. Finalization**

When "confirmed" is given, save the hearing sheet as the final version and report to Shinya that the workflow will proceed to Step 3.

**Step completion criteria:** Shinya says "confirmed"

---

### Step 3: Messaging Strategy & Scenario Design (Delegate to Ren)

**Route 適用:** Route F / Route S では実施。Route S はここで完結し、`strategy.md` を出力して終了する。Route C/D/I では既存 `strategy.md` を入力として読込のみ（再生成しない）。

Delegate to Ren (`subagent_type: marketing-planner`) with the following:

**Information to provide:**
- Hearing sheet Phase 1-6 answer content (**the final version confirmed in Step 2**)
- Phase 4 (Target/Persona) real voices and episodes
- Phase 5 (USP/Track record/Customer testimonials)
- GA4 data (if available)
- Problems with existing LP (if renewal)

**Request:**
- Define messaging axes that resonate with the target
- Prioritize messages
- Propose section structure (section order and purpose of each section from a marketing perspective)
- CTA placement strategy

**Expected output from Ren (= `strategy.md` の内容):**
- Messaging axes (main / sub)
- Section structure proposal (order, purpose of each section, message overview)
- CTA placement strategy
- 末尾に確定マーカー（HTMLコメント形式）`<!-- LP-CREATE-GATE: STRATEGY-CONFIRMED YYYY-MM-DD -->` を付与する区画を設ける（付与タイミング: Route S はシンヤさん承認後、Route F は Approval Gate 1 通過後）

**Ren's responsibility scope:** Determines section structure order, messaging axes, and messages (marketing responsibility)

#### Route S 終了処理（Route S のみ）

Route S の場合、本ステップ完了で完結する。以下を実施：

1. `strategy.md` を Step 0 の出力先パスに保存
2. シンヤさんに承認確認: 「戦略確定としてよろしいですか？」
3. 承認受領後、`strategy.md` 末尾に確定マーカー `<!-- LP-CREATE-GATE: STRATEGY-CONFIRMED YYYY-MM-DD -->` を付与（YYYY-MM-DD は当日日付。日本語マーク「戦略確定: YYYY-MM-DD」は人間向けの併記として残してよいが、ゲート判定には英語タグのみ使用）
4. 後続 Route（C/D/I）が必要な場合は Notion 案件管理に「lp-create Route C/D/I 後日実行」を P2/P3 で登録
5. シンヤさんに完了報告

---

### Step 4: Wireframe Creation & Copy (Delegate to Kai + Koto)

**Route 適用:** Route F / Route C で実施。Route C はコピー部分（コト）のみ実行し、ワイヤーフレーム（カイ）はスキップして `copy.md` を出力して終了する。Route D/I では既存 `copy.md` を入力として読込のみ。

#### Route F の場合: カイにワイヤーフレーム委任

Delegate to Kai (`subagent_type: lp-designer`) with the following:

**Information to provide:**
- Ren's messaging strategy & scenario (full output from Step 3) * Kai starts work only after receiving Ren's output
- Hearing sheet Phase 1-8 answer content
- Phase 7 (Design direction) reference URLs, NG designs, and material information
- Phase 8 (Section structure) Shinya's draft proposal (if any)

**Request:**
- Create wireframe based on Ren's section structure proposal
- Copy skeleton for each section (heading and body direction)
- Image list (existing reuse / new generation needed) -- Kai decides -> This list is detailed in Section 10 of the design specification document in Step 5
- Design direction (tone, atmosphere, direction-level only. Detailed specs such as HEX codes and px sizes are determined in Step 5)

**Expected output from Kai:**
- Section structure (finalized)
- Copy skeleton (headings, body overview)
- Image list (existing / new generation)
- Design direction (direction-level)

**Kai's responsibility scope:** Determines visual design, copy skeleton, and image list for each section (design responsibility)

**Section change rule:** If Kai determines that sections need to be added, removed, or reordered, the change must be **confirmed with Ren via Asuka** before proceeding

#### Copy brushup (Koto step)

After Kai's wireframe is complete (Route F) または ヒアリング完了直後 (Route C)、Asuka delegates to Koto (`subagent_type: copywriter`):

**Route F の Koto 起動条件**（以下のいずれかに該当する場合）:
- The FV headline does not align with the messaging axis
- Mid-page CTA or section heading expressions are weak
- Shinya has declared "leave the copy to the team"
- 該当しない場合は Koto ステップをスキップ

**Route C の Koto 起動**: 必ず起動する（コピーが Route C の主目的のため）。strategy.md を入力としてコトに渡し、全セクション分のコピーを作成する。

**Post-decision report (required):** Whether the Koto step is requested or skipped, Asuka must report the decision to Shinya.
- If requested: "Delegating copy brushup to Koto (reason: XX)"
- If skipped: "Kai's copy skeleton is sufficient; skipping the Koto step"

#### Route C 終了処理（Route C のみ）

Route C の場合、本ステップ完了で完結する。以下を実施：

1. コトの出力を `copy.md` として Step 0 の出力先パスに保存
   - 必須セクション: 全セクション分のコピー（FVヘッドライン・サブコピー・本文・CTAラベル・マイクロコピー）
2. シンヤさんに承認確認: 「コピー確定としてよろしいですか？」
3. 承認受領後、`copy.md` 末尾に確定マーカー `<!-- LP-CREATE-GATE: COPY-CONFIRMED YYYY-MM-DD -->` を付与（YYYY-MM-DD は当日日付。日本語マーク併記は任意）
4. 後続 Route（D/I）が必要な場合は Notion 案件管理に「lp-create Route D/I 後日実行」を登録
5. シンヤさんに完了報告

---

### [Approval Gate 1] Structure Approval (Route F のみ・Cannot Be Skipped)

**Route 適用:** Route F のみ。Route S/C/D/I では各 Route 終了処理の承認確認がこれを代替する。

Asuka presents the following to Shinya and obtains approval:

```
[Approval Gate 1: Section Structure]
The LP will be built with the following structure. Please review.

-- Section Structure
1. [Section name] -- [Purpose]
2. [Section name] -- [Purpose]
...

-- Key Copy Skeleton
- [Heading draft]
- [Heading draft]
...

-- Required Image List
- [Image description] (existing / new generation)
...

Shall we proceed with this structure?
```

- **If approved (Route F のみ)** -> 以下を全て実施してから Step 5 へ進む：
  1. セクション構成を凍結する（コーディング中に変更が必要になった場合はコーディングを中断、影響範囲を報告のうえ再承認を得てから再開。CSS や表現の調整は凍結対象外）
  2. **`copy.md` を必ず生成して保存する（Route F での COPY-CONFIRMED ゲート確立）**：
     - Step 4 までに固まったコピー（コトのブラッシュアップ後 or カイのコピースケルトン）を、Route C と同じファイル形式で `copy.md` として Step 0 の出力先パスに保存する
     - 必須セクション: 全セクション分のコピー（FVヘッドライン・サブコピー・本文・CTAラベル・マイクロコピー）
     - ファイル末尾に確定マーカー `<!-- LP-CREATE-GATE: COPY-CONFIRMED YYYY-MM-DD -->` を付与する（YYYY-MM-DD は Approval Gate 1 通過日）
     - **理由**: Route F 完了後に後日 Route D/I を単独実行する際、ゲート判定に必要なため。Approval Gate 1 が「構成承認」と「コピー確定」を兼ねる
  3. シンヤさんに「`copy.md` を保存しました（パス: <path>）」と報告してから Step 5 へ進む
- **If revision is requested** -> Route the revision to the appropriate agent:
  - Revision involves "section order, messaging axes, or messages" -> **Send back to Ren**
  - Revision involves "design, visuals, or copy expression" -> **Send back to Kai**
  - After revision, run the approval gate again（再承認後に上記 `copy.md` 生成を実施する）

---

### Step 5: Design Specification (`lp-design-system` スキル経由でカイへ委任)

**Route 適用:** Route F / Route D で実施。Route D はここで完結し、`design-spec.md` を出力して終了する。Route I では既存 `design-spec.md` を入力として読込のみ。

**本ステップは `lp-design-system` スキルを呼び出して実行する**。本ステップで定義する設計仕様は `lp-design-system` の出力フォーマットに従う。`lp-design-system` の入力（コピー一覧・CVゴール・ターゲット・LP形式・ブランドカラー・ビジュアル制作手段・CTA仕様）は、Step 1〜4 の成果物（および Route D の場合はシンヤさんからの直接入力）から抽出してカイに渡す。

**Information to provide to `lp-design-system`:**
- コピー一覧: Step 4 のコト出力 / Route D の場合は `copy.md` 全文
- CVゴール: Step 1 Phase 2 / Route D の場合はシンヤさんからの直接入力
- ターゲット: Step 1 Phase 4 / Route D の場合はシンヤさんからの直接入力
- LP形式（スクロール型 / スワイプ型）: Step 1 Phase 8 / Route D の場合はシンヤさんからの直接入力
- ブランドカラー: Phase 7 / Route D の場合はシンヤさんからの直接入力（未指定時は `lp-design-system` のデフォルト適用）
- ビジュアル制作手段: Phase 7 / 未指定時は `lp-design-system` のデフォルト（AI画像生成制約）適用
- CTA仕様: Step 3 のレン出力（CTA配置戦略） / Route D の場合はシンヤさんからの直接入力

`lp-design-system` の Step 0〜7 を実行し、統合デザイン仕様書を `design-spec.md` として出力する。

**追加要件（lp-design-system の出力に加えて Kai が補完すべき項目）:**

`lp-design-system` がカバーしない以下の Section 10（Image Definition List）を Kai が補完する:

#### Section 10: Image Definition List

各画像について以下を定義（Step 5.5 で Luna へ渡す）：
- 画像番号
- 配置セクション
- 用途・役割（感情補強 / 具体化 / 雰囲気づくり / 視線誘導のいずれか）
- 既存素材の再利用 or 新規生成
- アスペクト比とサイズ
- 主題・構図の方向性（自然言語で記述。AI画像生成の場合は具体的なシーン描写）
- カラートーン（design-spec のカラーシステムと整合）
- 仮ファイル名

#### Koto への追加委任（コピーの最終調整）

`lp-design-system` の出力に基づき、CTA ボタンラベルとマイクロコピーの最終文言をコトへ委任：
- CTA button label wording (based on the direction and character limit defined by `lp-design-system`)
- Microcopy wording (based on the placement position and character limit defined by `lp-design-system`)

#### Route D 終了処理（Route D のみ）

Route D の場合、本ステップ完了で完結する。以下を実施：

1. `lp-design-system` の出力 + Section 10 + コトの最終文言を統合し、`design-spec.md` として Step 0 の出力先パスに保存
2. シンヤさんに承認確認（Approval Gate 2 を流用）: 「デザイン確定としてよろしいですか？」
3. 承認受領後、`design-spec.md` 末尾に確定マーカー `<!-- LP-CREATE-GATE: DESIGN-CONFIRMED YYYY-MM-DD -->` を付与（YYYY-MM-DD は当日日付。日本語マーク併記は任意）
4. 後続 Route（I）が必要な場合は Notion 案件管理に「lp-create Route I 後日実行」を登録
5. シンヤさんに完了報告

---

### [Approval Gate 2] Design Approval (Quick Confirmation / Route F・D で必須)

Confirm the design direction with Shinya.
Since the overall structure is already approved via the wireframe (Route F) または コピー確定済み（Route D）, a quick confirmation is sufficient.

**Route F の場合、本ゲート通過後に以下を必ず実施してから Step 5.5 へ進む：**

1. **`design-spec.md` を必ず生成して保存する（Route F での DESIGN-CONFIRMED ゲート確立）**：
   - Step 5（lp-design-system 経由）で確定したデザイン仕様を、Route D と同じファイル形式で `design-spec.md` として Step 0 の出力先パスに保存する
   - 必須セクション: カラー / フォント / FV / 視線誘導 / 信頼要素配置 / CTA / レスポンシブ / パフォーマンス / Section 10 画像定義リスト
   - ファイル末尾に確定マーカー `<!-- LP-CREATE-GATE: DESIGN-CONFIRMED YYYY-MM-DD -->` を付与する（YYYY-MM-DD は Approval Gate 2 通過日）
   - **理由**: Route F 完了後に後日 Route I を単独実行する際、ゲート判定に必要なため
2. シンヤさんに「`design-spec.md` を保存しました（パス: <path>）」と報告してから Step 5.5 へ進む

**確認フォーマット:**


```
[Approval Gate 2: Design Direction]
-- Color: Main [#XXXXXX] / Accent [#XXXXXX] / Background [#XXXXXX]
-- Font: Headings [font name, size] / Body [font name, size]
-- FV structure: [Pattern A/B/C + mobile layout] (include selection rationale)
-- Eye-flow: [Z pattern / F pattern] (include selection rationale)
-- CTA placement: [Number of placements and positions]
-- Trust elements: Near FV [element name] / Mid-page [element name] / Just before CTA [element name]
-- Bounce prevention: No global nav / No external links / [Other notes]
-- Mobile: [Notes (e.g., sticky CTA presence)]
-- Performance: [Image format and prohibited effect notes]
-- Images: New generation [count] / Existing materials [count] (definitions in spec document Section 10; not subject to approval)

Shall we proceed with this direction? (Quick confirmation)
```

---

### Step 5.5: Image Prompt Design (Delegate to Luna)

**Route 適用:** Route F / Route D で実施（Route D は任意。シンヤさんが「画像プロンプトも欲しい」と明示した場合のみ）。

Based on Kai's design specification document (Section 10: Image Definition List), delegate image prompt design to Luna (`subagent_type: nano-banana`).

**Purpose of this step:** Image generation is performed in Step 9. Here, only the prompts for "how to generate" are finalized and saved to a file.

**Information to provide:**
- Design specification document Section 10 (full Image Definition List)
- Design specification document Section 1 (color scheme and tone) -> used for color consistency in images
- Hearing sheet Phase 7 (reference LPs and NG designs) -> used as atmosphere reference

**Request to Luna:**
- For each image in Section 10 (new generation only), create a detailed generation prompt
- Skip existing material images (no prompt needed)
- Each prompt must include:
  - Subject and composition details (based on Kai's definitions, made more specific)
  - Style, tone, lighting, and color palette (aligned with the design specification's color scheme)
  - Aspect ratio (carried over from Kai's specification)
  - Negative prompt (if there are elements to avoid)

**Expected output from Luna:**
- Generation prompt for each image (number, proposed filename, prompt body, aspect ratio)
- Asuka saves the output as `image-prompts.md` at the Route 出力先パス（Step 0 参照）

**Step completion criteria:** All images requiring new generation have their prompts saved in `image-prompts.md`

---

### Step 6: Coding (Delegate to Shu)

**Route 適用:** Route F / Route I で実施。Route I は事前に `design-spec.md` と `copy.md` の確定を Step 0 で確認していること。

Delegate to Shu (`subagent_type: backend-engineer`) with the following. In this skill, Shu operates as the **implementation lead (LP PHP/HTML/CSS/JS)**.

**Information to provide:**
- Approved section structure (finalized version from Step 4 / Route I の場合は `design-spec.md` から抽出)
- Design specifications (Kai's output from Step 5 = `design-spec.md`)
- Finalized copy (Koto's output = `copy.md`)
- Hearing sheet Phase 2 (CV definition, post-CV flow) / Route I の場合はシンヤさんからの直接入力
- Hearing sheet Phase 7 (material list)
- Hearing sheet Phase 9 (CMS, deployment URL, form tool, analytics settings) / Route I で必須
- Existing LP codebase (specify reference path)
- Image placeholder specifications (alt text, size)
- PC_PLATFORM (check `~/.claude/.env` for `PC_PLATFORM` and pass it)
- 実装形態の指定（WordPress テーマ内 PHP テンプレート / 静的 HTML / 他フレームワーク等）

**Notes for Shu:**
- Section structure is frozen. No structural changes allowed
- All images implemented as placeholders (to be replaced later)
- Include JSON-LD (per Web Development Coding Rules)
- CSS は FLOCSS 命名規約に従う（CLAUDE.md「CSS Coding Rule」参照）
- Web Project Directory Structure Rule に従い公開/非公開ディレクトリを分離する（CLAUDE.md 該当セクション参照）

---

### Step 7: Security & Code Review (Delegate to Sakura -- Automatic)

**Route 適用:** Route F / Route I で実施（コードが生成される全 Route で必須）。

Delegate to Sakura (`subagent_type: code-reviewer`) with the following:

**Review perspectives:**
- XSS / escaping gaps
- WordPress conventions (`wp_enqueue_style` / `wp_enqueue_script` / `esc_html`, etc.) / 該当する場合
- Performance (`loading` attribute usage / image optimization)
- Mobile support (responsive CSS validity)
- FLOCSS 命名規約遵守
- Web Project Directory Structure 遵守

**Priority and action:**
- **High severity**: Must fix
- **Medium severity**: Must fix
- **Low severity**: Optional (report to Shinya for decision)

---

### Step 8: Fix Issues (Delegate to Shu)

**Route 適用:** Route F / Route I で実施。

Send Sakura's findings (high and medium severity) to Shu for correction.

- After fixes are complete, request re-review from Sakura
- **Repeat Steps 7-8 until all items pass**
- No hard limit on iterations, but if it exceeds 3 rounds, Asuka reports the situation to Shinya

---

### Step 9: Completion Report

**Route 適用:** Route F / Route I で実施（Route S/C/D は各 Route 終了処理が代替）。

Report to Shinya using the following format:

**実装完了マーカーの付与（Route F / Route I 共通）:**

実装が全て完了し、Sakura のセキュリティレビューも全て通過したら、以下を実施：

1. プロジェクトルートに `IMPLEMENTATION.md` を作成し、ファイル末尾に `<!-- LP-CREATE-GATE: IMPLEMENTATION-CONFIRMED YYYY-MM-DD -->` マーカーを付与する
2. `IMPLEMENTATION.md` には実装サマリー（作成ファイル一覧・WordPress 配置先・公開URL予定地）を最低限記載する
3. シンヤさんへの完成報告（下記フォーマット）と同時にマーカー付与を報告する

```
[LP Creation Complete]

-- Route
- 実行 Route: Route F / Route I
- 入力成果物: <strategy.md / copy.md / design-spec.md のうち該当>

-- Created Files
- [File path]: [Description]
- [File path]: [Description]
...

-- Image Placeholder List
The following images need to be replaced later:
- [Placeholder name]: [Expected image description] ([Size])
...

-- Next Steps
1. WordPress deployment procedure: [Procedure overview]
2. Image generation: Generate using `image-prompts.md` saved in Step 5.5 -- choose to generate now or defer
3. Pre-launch check: On-device display verification recommended
```

**Confirm image generation timing with Shinya:**
- "Generate now" -> Load `image-prompts.md` and Asuka executes image generation based on Luna's prompts (per CLAUDE.md Image Generation Flow)
- "Do it later" -> Inform Shinya of the `image-prompts.md` save location, record the following in the "Design & Implementation Decision Log" section of `session-handoff.md`, then complete. Can be resumed later by saying "Generate images for the LP"
  ```
  [YYYY-MM-DD] LP image prompts created, awaiting generation (image-prompts.md: <save path>)
  ```

**Existing material replacement is done by Shinya directly from the WordPress admin panel.**

---

## Interruption & Resumption

- This skill has a long workflow and sessions may be interrupted
- When interrupting, record the current Route, current step, and progress in `session-handoff.md`
- 中断時の必須記録項目:
  - 実行中の Route（F/S/C/D/I）
  - 完了済みステップ番号
  - 出力済みファイル（`strategy.md` / `copy.md` / `design-spec.md` 等）のパスと確定状況
  - 次に実行すべきステップ
- If `image-prompts.md` has been created but image generation is not yet complete, always record the path as well
- When resuming, check `session-handoff.md` and resume from the interrupted step。Route のゲート（戦略確定 / コピー確定 / デザイン確定）が破られていないかを Step 0 で再確認する

---

## Route 別フローのまとめ（早見表）

| Route | 入力 | 実行ステップ | 出力 | 終了マーク |
|---|---|---|---|---|
| F | ヒアリング全 Phase | Step 1〜9 全実行 | strategy.md / copy.md / design-spec.md / 実装一式 / image-prompts.md | 各確定マーカー（`<!-- LP-CREATE-GATE: ... -->`）+ 完了報告 |
| S | ヒアリング Phase 1-5,8 | Step 1（簡易） + Step 3 | strategy.md | `<!-- LP-CREATE-GATE: STRATEGY-CONFIRMED YYYY-MM-DD -->` |
| C | strategy.md + ヒアリング Phase 1,4,5,6 | Step 1（簡易） + Step 4 のコト部分 | copy.md | `<!-- LP-CREATE-GATE: COPY-CONFIRMED YYYY-MM-DD -->` |
| D | copy.md + ヒアリング Phase 1,7 | Step 5（lp-design-system 経由） | design-spec.md | `<!-- LP-CREATE-GATE: DESIGN-CONFIRMED YYYY-MM-DD -->` |
| I | design-spec.md + copy.md + ヒアリング Phase 1,9 | Step 6 + Step 7 + Step 8 + Step 9 | 実装ファイル一式 + image-prompts.md | `<!-- LP-CREATE-GATE: IMPLEMENTATION-CONFIRMED YYYY-MM-DD -->` + 完了報告 |
| X | （シンヤさん明示時のみ） | ad-hoc | （都度判断） | Notion 例外処理記録 |
