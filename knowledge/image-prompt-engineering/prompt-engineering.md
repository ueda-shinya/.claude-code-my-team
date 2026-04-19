# Prompt Engineering Reference -- Banana Claude

> Load this on-demand when constructing complex prompts or when the user
> asks about prompt techniques. Do NOT load at startup.
>
> Aligned with Google's March 2026 "Ultimate Prompting Guide" for Gemini image generation.

## The 5-Component Prompt Formula

> Based on Google's officially validated prompt structure for Gemini image models.
> Write as natural narrative paragraphs -- NEVER as comma-separated keyword lists.

### Component 1 -- SUBJECT
Who or what is the primary focus. Be specific about physical characteristics,
material, species, age, expression. Never write just "a person" or "a product."

**Good:** "A weathered Japanese ceramicist in his 70s, deep sun-etched
wrinkles mapping decades of kiln work, calloused hands cradling a
freshly thrown tea bowl with an irregular, organic rim"

**Bad:** "old man, ceramic, bowl"

### Component 2 -- ACTION
What the subject is doing, or the primary visual state. Use strong present-
tense verbs. "floats weightlessly," "holds a glowing lantern," "sits perfectly
still." If no action, describe pose or arrangement.

**Good:** "leaning forward with intense concentration, gently smoothing
the rim with a wet thumb, a thin trail of slip running down his wrist"

**Bad:** "making pottery"

### Component 3 -- LOCATION / CONTEXT
Where the scene takes place. Include environmental details, time of day,
atmospheric conditions. "inside the cupola module of the International Space
Station," "on a rain-slicked Tokyo alley at 2am."

**Good:** "inside a traditional wood-fired anagama kiln workshop,
stacked shelves of drying pots visible in the soft background, late
afternoon light filtering through rice paper screens"

**Bad:** "workshop, afternoon"

### Component 4 -- COMPOSITION
Camera perspective, framing, and spatial relationship. "medium shot centered
against the window," "extreme low-angle looking up," "bird's-eye view from
30 meters," "tight close-up on hands."

**Good:** "intimate close-up shot from slightly below eye level,
shallow depth of field isolating the hands and bowl against the
soft bokeh of the workshop behind"

**Bad:** "close up"

### Component 5 -- STYLE (includes lighting)
The visual register, aesthetic, medium, and lighting combined. Reference real
cameras, film stock, photographers, publications, or art movements. Lighting
lives here as a sub-element, not a separate component.

**Good:** "shot on a Fujifilm X-T4 with warm color science and natural
bokeh, warm directional light from a single high window camera-left
creating gentle Rembrandt lighting on the face with deep warm shadows.
Reminiscent of Dorothea Lange's documentary portraiture"

**Bad:** "photorealistic, 8K, masterpiece" (see Banned Keywords below)

## Domain Mode Modifier Libraries

### Cinema Mode
**Camera specs:** RED V-Raptor, ARRI Alexa 65, Sony Venice 2, Blackmagic URSA
**Lenses:** Cooke S7/i, Zeiss Supreme Prime, Atlas Orion anamorphic
**Film stocks:** Kodak Vision3 500T (tungsten), Kodak Vision3 250D (daylight), Fuji Eterna Vivid
**Lighting setups:** three-point, chiaroscuro, Rembrandt, split, butterfly, rim/backlight
**Shot types:** establishing wide, medium close-up, extreme close-up, Dutch angle, overhead crane, Steadicam tracking
**Color grading:** teal and orange, desaturated cold, warm vintage, high-contrast noir

### Product Mode
**Surfaces:** polished marble, brushed concrete, raw linen, acrylic riser, gradient sweep
**Lighting:** softbox diffused, hard key with fill card, rim separation, tent lighting, light painting
**Angles:** 45-degree hero, flat lay, three-quarter, straight-on, worm's-eye
**Style refs:** Apple product photography, Aesop minimal, Bang & Olufsen clean, luxury cosmetics

### Portrait Mode
**Focal lengths:** 85mm (classic), 105mm (compression), 135mm (telephoto), 50mm (environmental)
**Apertures:** f/1.4 (dreamy bokeh), f/2.8 (subject-sharp), f/5.6 (environmental context)
**Pose language:** candid mid-gesture, direct-to-camera confrontational, profile silhouette, over-shoulder glance
**Skin/texture:** freckles visible, pores at macro distance, catch light in eyes, subsurface scattering

### Editorial/Fashion Mode
**Publication refs:** Vogue Italia, Harper's Bazaar, GQ, National Geographic, Kinfolk
**Styling notes:** layered textures, statement accessories, monochromatic palette, contrast patterns
**Locations:** marble staircase, rooftop at golden hour, industrial loft, desert dunes, neon-lit alley
**Poses:** power stance, relaxed editorial lean, movement blur, fabric in wind

### UI/Web Mode
**Styles:** flat vector, isometric 3D, line art, glassmorphism, neumorphism, material design
**Colors:** specify exact hex or descriptive palette (e.g., "cool blues #2563EB to #1E40AF")
**Sizing:** design at 2x for retina, specify exact pixel dimensions needed
**Backgrounds:** transparent (request solid white then post-process), gradient, solid color

### Logo Mode
**Construction:** geometric primitives, golden ratio, grid-based, negative space
**Typography:** bold sans-serif, elegant serif, custom lettermark, monogram
**Colors:** max 2-3 colors, works in monochrome, high contrast
**Output:** request on solid white background, post-process to transparent

### Landscape Mode
**Depth layers:** foreground interest, midground subject, background atmosphere
**Atmospherics:** fog, mist, haze, volumetric light rays, dust particles
**Time of day:** blue hour (pre-dawn), golden hour, magic hour (post-sunset), midnight blue
**Weather:** dramatic storm clouds, clearing after rain, snow-covered, sun-dappled

### Infographic Mode
**Layout:** modular sections, clear visual hierarchy, bento grid, flow top-to-bottom
**Text:** use quotes for exact text, descriptive font style, specify size hierarchy
**Data viz:** bar charts, pie charts, flow diagrams, timelines, comparison tables
**Colors:** high-contrast, accessible palette, consistent brand colors

### Abstract Mode
**Geometry:** fractals, voronoi tessellation, spirals, fibonacci, organic flow, crystalline
**Textures:** marble veining, fluid dynamics, smoke wisps, ink diffusion, watercolor bleed
**Color palettes:** analogous harmony, complementary clash, monochromatic gradient, neon-on-black
**Styles:** generative art, data visualization art, glitch, procedural, macro photography of materials

## Advanced Techniques

### Character Consistency (Multi-turn)
Use `gemini_chat` and maintain descriptive anchors:
- First turn: Generate character with exhaustive physical description
- Following turns: Reference "the same character" + repeat 2-3 key identifiers
- Key identifiers: hair color/style, distinctive clothing, facial feature

**Multi-image reference technique** (3.1 Flash):
- Provide up to 4-5 character reference images in the conversation
- Assign distinct names to each character ("Character A: the red-haired knight")
- Model preserves features across different angles, actions, and environments
- Works best when reference images show the character from multiple angles

### Style Transfer Without Reference Images
Describe the target style exhaustively instead of referencing an image:
```
Render this scene in the style of a 1950s travel poster: flat areas of
color in a limited palette of teal, coral, and cream. Bold geometric
shapes with visible paper texture. Hand-lettered title text with a
mid-century modern typeface feel.
```

### Text Rendering Tips
- Quote exact text: `with the text "OPEN DAILY" in bold condensed sans-serif`
- **25 characters or less** -- this is the practical limit for reliable rendering
- **2-3 distinct phrases max** -- more text fragments degrade quality
- Describe font characteristics, not font names
- Specify placement: "centered at the top third", "along the bottom edge"
- High contrast: light text on dark, or vice versa
- **Text-first hack:** Establish the text concept conversationally first ("I need a sign that says FRESH BREAD"), then generate -- the model anchors on text mentioned early
- Expect creative font interpretations, not exact replication of described styles

### Positive Framing (No Negative Prompts)
Gemini does NOT support negative prompts. Rephrase exclusions:
- Instead of "no blur" → "sharp, in-focus, tack-sharp detail"
- Instead of "no people" → "empty, deserted, uninhabited"
- Instead of "no text" → "clean, uncluttered, text-free"
- Instead of "not dark" → "brightly lit, high-key lighting"

### Search-Grounded Generation
For images based on real-world data (weather, events, statistics),
Gemini can use Google Search grounding to incorporate live information.
Useful for infographics with current data.

**Three-part formula for search-grounded prompts:**
1. `[Source/Search request]` -- What to look up
2. `[Analytical task]` -- What to analyze or extract
3. `[Visual translation]` -- How to render it as an image

**Example:** "Search for the current top 5 programming languages by GitHub usage in 2026, analyze their relative popularity percentages, then generate a clean infographic bar chart with the language logos and percentages in a modern dark theme."

## ❌ BANNED PROMPT KEYWORDS -- NEVER USE THESE

The Nano Banana model's internal system prompt explicitly penalizes these
Stable Diffusion-era terms. Using them degrades output quality.

NEVER include:
- "4k" / "8k" / "ultra HD" / "high resolution" (use the `imageSize` parameter instead)
- "masterpiece"
- "highly detailed" / "ultra detailed"
- "trending on artstation"
- "hyperrealistic" / "ultra realistic"
- "photorealistic" (describe the camera/film instead)
- "best quality"
- "award winning" (use specific publication names instead)

USE THESE INSTEAD (prestigious context anchors that actively improve composition):
- "Pulitzer Prize-winning cover photograph"
- "Vanity Fair editorial portrait"
- "National Geographic cover story"
- "WIRED magazine feature spread"
- "Architectural Digest interior"
- "Magnum Photos documentary"

## ⚠️ NEGATIVE PROMPTS -- No API parameter exists

Nano Banana models have NO dedicated negative prompt parameter. Do not pass
negative instructions as a separate API argument -- it will be ignored.

Correct approach: semantic reframing. Express what you want, not what you
don't want.

❌ WRONG: "no cars, no people, no clutter in the background"
✅ RIGHT: "an empty, deserted street, completely still, no signs of activity"

❌ WRONG: "no watermarks, no text"
✅ RIGHT: (add to prompt) "NEVER include any text, labels, or watermarks"

For critical constraints, ALL CAPS emphasis improves adherence:
- "MUST contain exactly three figures"
- "NEVER include any visible horizon line"
- "ONLY show the product, nothing else in frame"

## Prompt Length Guide

| Use case | Target length | Notes |
|---|---|---|
| Quick draft / concept | 20–60 words (1–2 sentences) | Good for ideation |
| Standard generation | 100–200 words (3–5 sentences) | Production default |
| Complex professional | 200–300 words | Full 5-component treatment |
| Maximum specification | Up to 2,600 tokens | JSON/Markdown structured format supported |

Nano Banana 2 accepts up to 131,072 input tokens. Do not artificially truncate
a prompt to hit a word count target -- quality and specificity matter more.

## Text Rendering in Images

Nano Banana 2 has excellent text rendering. Rules:
1. Enclose desired text in quotation marks in the prompt: "LAUNCH DAY"
2. Specify font characteristics explicitly: "bold white sans-serif," "Century Gothic"
3. Specify placement: "centered at the bottom third," "upper left corner"
4. For complex layouts, describe text placement before requesting the image

Example: Place the text "Happy Birthday, Sarah" in a warm gold serif font
centered in the lower third of the image.

Known limitation: Small text (<16px equivalent) and complex multilingual text
may require iterative refinement.

## Prompt Adaptation Rules

When adapting prompts from the claude-prompts database (Midjourney/DALL-E/etc.)
to Gemini's natural language format:

| Source Syntax | Gemini Equivalent |
|---------------|-------------------|
| `--ar 16:9` | Call `set_aspect_ratio("16:9")` separately |
| `--v 6`, `--style raw` | Remove -- Gemini has no version/style flags |
| `--chaos 50` | Describe variety: "unexpected, surreal composition" |
| `--no trees` | Positive framing: "open clearing with no vegetation" |
| `(word:1.5)` weight | Descriptive emphasis: "prominently featuring [word]" |
| `8K, masterpiece, ultra-detailed` | Remove ALL of these -- they are banned. Use prestigious context anchors instead (see Banned Keywords section) |
| Comma-separated tags | Expand into descriptive narrative paragraphs |
| `shot on Hasselblad` | Keep -- camera specs work well in Gemini |

## Common Prompt Mistakes

1. **Keyword stuffing** -- stacking generic quality terms ("8K, masterpiece, best quality, ultra-realistic") actively degrades output. Use prestigious context anchors instead (see Banned Keywords section)
2. **Tag lists** -- Gemini wants prose, not "red car, sunset, mountain, cinematic"
3. **Missing lighting** -- The single biggest quality differentiator
4. **No composition direction** -- Results in generic centered framing
5. **Vague style** -- "make it look cool" vs specific art direction
6. **Ignoring aspect ratio** -- Always set before generating
7. **Overlong prompts** -- Diminishing returns past ~200 words; be precise, not verbose
8. **Text longer than ~25 characters** -- Rendering degrades rapidly past this limit
9. **Burying key details at the end** -- In long prompts, details placed last may be deprioritized; put critical specifics (exact text, key constraints) in the first third of the prompt
10. **Not iterating with follow-up prompts** -- Use `gemini_chat` for progressive refinement instead of trying to get everything right in one generation

## Proven Prompt Templates

> Extracted from 2,500+ tested prompts. These patterns consistently produce
> high-quality results. Use them as starting points and adapt to the request.

### The Winning Formula (Weight Distribution)

| Component | Weight | What to include |
|-----------|--------|-----------------|
| **Subject** | 30% | Age, skin tone, hair color/style, eye color, body type, expression |
| **Action** | 10% | Movement, pose, gesture, interaction, state of being |
| **Context** | 15% | Location + time of day + weather + context details |
| **Composition** | 10% | Shot type, camera angle, framing, focal length, f-stop |
| **Lighting** | 10% | Quality, direction, color temperature, shadows |
| **Style** | 25% | Art medium, brand names, textures, camera model, color grading |

### Instagram Ad / Social Media

**Pattern:** `[Subject with age/appearance] + [outfit with brand/texture] + [action verb] + [setting] + [camera spec] + [lighting] + [platform aesthetic]`

**Example (Product Placement):**
```
Hyper-realistic gym selfie of athletic 24yo influencer with glowing olive
skin, wearing crinkle-textured athleisure set in mauve. iPhone 16 Pro Max
front-facing portrait mode capturing sweat droplets on collarbones, hazel
eyes enhanced by gym LED lighting. Mirror reflection shows perfect form,
golden morning light through floor-to-ceiling windows. Frayed chestnut
ponytail with baby hairs, visible skin texture with natural erythema from
workout. Vanity Fair wellness editorial aesthetic.
```

**Example (Lifestyle Ad):**
```
A 24-year-old blonde fitness model in a high-energy sports drink
advertisement. Mid-run on a beach, wearing a vibrant orange sports bra
and black shorts, playful smile and sparkling blue eyes exuding vitality.
Bottle of the drink held in hand, waves crashing in background. Shot on
Nikon D850 with 70-200mm f/2.8 lens, natural light, fast shutter speed
capturing motion. Visible skin texture, water droplets, product label
clearly visible. National Geographic fitness feature aesthetic.
```

**Example (Luxury Lifestyle):**
```
Gorgeous Instagram model wearing a designer silk gown, luxury rooftop
restaurant, golden hour lighting, champagne in hand, luxurious aspirational
lifestyle. Captured with Sony A7R IV, 85mm f/1.4 lens, shallow depth of
field, warm color grading.
```

### Product / Commercial Photography

**Pattern:** `[Product with brand/detail] + [dynamic elements] + [surface/setting] + "commercial photography for advertising campaign" + [lighting] + [prestigious publication reference]`

**Example (Beverage):**
```
Gatorade bottle with condensation dripping down the sides, surrounded by
lightning bolts and a burst of vibrant blue and orange light rays. The
Gatorade logo is prominently displayed on the bottle, with splashes of
water frozen in mid-air. Commercial food photography for an advertising
campaign, vibrant complementary colors. Bon Appetit magazine cover aesthetic.
```

**Example (Food):**
```
In and Out burger with layers of fresh lettuce, melted cheese, and pretzel
bun, placed on a white surface with the In and Out logo subtly glowing in
the background. Falling french fries and golden light, warm scene.
Commercial food photography for an advertising campaign, vibrant
complementary colors. Shot in the style of a Bon Appetit feature spread.
```

### Fashion / Editorial

**Pattern:** `[Subject with ethnicity/age/features] + [outfit with texture/brand/cut] + [location] + [pose/action] + [camera + lens] + [lighting quality]`

**Example (Street Style):**
```
A 24-year-old female AI influencer posing confidently in an urban cityscape
during golden hour. Flawless sun-kissed skin, long wavy brown hair, deep
green eyes. Wearing a chic streetwear outfit -- oversized beige blazer,
white top, high-waisted jeans. Captured with Sony A7R IV at 85mm f/1.4,
shallow depth of field with warm golden bokeh.
```

**Example (High Fashion):**
```
Stunning 24-year-old woman, long platinum blonde hair, radiant skin,
piercing blue eyes, dressed in a chic pastel blazer with a modern
minimalist aesthetic, soft sunlight glow, high-end fashion appeal.
Shot on Canon EOS R5, 85mm f/1.2 lens.
```

**Example (Avant-Garde):**
```
A blonde fitness model transformed into a runway-ready fashion icon,
wearing a bold avant-garde outfit: cropped leather jacket with neon pink
accents, paired with high-waisted athletic shorts and knee-high boots.
Captured mid-stride on a minimalist white runway, playful twinkle in her
eye, dramatic studio lighting from above.
```

### SaaS / Tech Marketing

**Pattern:** `[UI mockup or abstract visual] + "on [dark/light] background" + [specific colors with hex] + [typography description] + "clean, premium SaaS aesthetic" + [glassmorphism/gradient/glow effects]`

**Example (Dashboard Hero):**
```
A floating glassmorphism UI card on a deep charcoal background showing a
content analytics dashboard with a rising line graph in teal (#14B8A6),
bar charts in coral (#F97316), and a circular progress indicator at 94%.
Subtle grid lines, frosted glass effect with 20% opacity, teal glow
bleeding from the card edges. Clean premium SaaS aesthetic, no text
smaller than headline size.
```

**Example (Feature Highlight):**
```
An isometric 3D illustration of interconnected data nodes on a dark navy
background. Each node is a glowing teal sphere connected by thin luminous
lines, forming a constellation pattern. One central node pulses brighter
with radiating rings. Modern tech illustration style with subtle depth
of field, volumetric lighting from below.
```

**Example (Comparison/Before-After):**
```
Split-screen image: left side shows a cluttered, dim workspace with
scattered papers, red error indicators, and a frustrated expression
conveyed through a cracked coffee mug and tangled cables. Right side
shows a clean, organized dashboard interface glowing in teal and white
on a dark background, with smooth flowing lines and checkmarks. A sharp
vertical dividing line separates chaos from clarity.
```

### Logo / Branding

**Pattern:** `[Product/bottle/item] + "with [brand element] prominently displayed" + [dynamic visual elements] + "commercial photography" + [lighting style] + [prestigious publication reference]`

**Example:**
```
A sleek matte black bottle with a minimal white logo mark centered on the
label, surrounded by swirling gradient ribbons of teal and coral light.
The bottle sits on a reflective dark surface, sharp studio rim lighting
separating it from the background. Product photography for luxury
branding, dramatic contrast. Wallpaper* magazine design editorial.
```

### Key Tactics That Make Prompts Work

1. **Name real cameras** -- "Sony A7R IV", "Canon EOS R5", "iPhone 16 Pro Max" anchor realism
2. **Specify exact lens** -- "85mm f/1.4" gives the model precise depth-of-field information
3. **Use age + ethnicity + features** -- "24yo with olive skin, hazel eyes" beats "a person"
4. **Name brands for styling** -- "Lululemon mat", "Tom Ford suit" triggers specific visual associations
5. **Include micro-details** -- "sweat droplets on collarbones", "baby hairs stuck to neck"
6. **Add platform context** -- "Instagram aesthetic", "commercial photography for advertising"
7. **Describe textures** -- "crinkle-textured", "metallic silver", "frosted glass"
8. **Use action verbs** -- "mid-run", "posing confidently", "captured mid-stride"
9. **Use prestigious context anchors** -- "Pulitzer Prize-winning photograph," "Vanity Fair editorial," "National Geographic cover" actively improve quality. NEVER use "ultra-realistic," "8K," "masterpiece" -- these are banned (see Banned Keywords)
10. **For products, say "prominently displayed"** -- ensures the product/logo isn't hidden

### Anti-Patterns (What NOT to Do)

- **"A dark-themed Instagram ad showing..."** -- too meta, describes the concept not the image
- **"A sleek SaaS dashboard visualization..."** -- abstract, no visual anchors
- **"Modern, clean, professional..."** -- vague adjectives that mean nothing to the model
- **"A bold call to action with..."** -- describes marketing intent, not visual content
- **Describing what the viewer should feel** -- instead, describe what creates that feeling

## Safety Filter Rephrase Strategies

Gemini's safety filters (Layer 2: server-side output filter) cannot be disabled.
When a prompt is blocked, the only path forward is rephrasing.

### Common Trigger Categories

| Category | Triggers on | Rephrase approach |
|----------|------------|-------------------|
| Violence/weapons | Combat, blood, injuries, firearms | Use metaphor or aftermath: "battle-worn" → "weathered veteran" |
| Medical/gore | Surgery, wounds, anatomical detail | Abstract or clinical: "open wound" → "medical illustration" |
| Real public figures | Named celebrities, politicians | Use archetypes: "Elon Musk" → "a tech entrepreneur in a minimalist office" |
| Children + risk | Minors in any ambiguous context | Add safety context: specify educational, family, or playful framing |
| NSFW/suggestive | Revealing clothing, intimate poses | Use artistic framing: "fashion editorial, fully clothed, editorial pose" |

### Rephrase Patterns

1. **Abstraction** -- Replace specific dangerous elements with abstract concepts
2. **Artistic framing** -- Frame content as art, editorial, or documentary
3. **Metaphor** -- Use symbolic language instead of literal descriptions
4. **Positive emphasis** -- Describe what IS present, not what's dangerous
5. **Context shift** -- Move from threatening to educational/professional context

### Example Rephrases

| Blocked prompt | Successful rephrase |
|----------------|---------------------|
| "a soldier in combat firing a rifle" | "a determined soldier standing guard at dawn, rifle slung over shoulder, morning mist over the outpost" |
| "a scary horror monster" | "a fantastical creature from a dark fairy tale, intricate organic textures, bioluminescent accents, concept art style" |
| "dog in a fight" | "a friendly golden retriever playing energetically in a sunny park, action shot, joyful expression" |
| "medical surgery scene" | "a clean modern operating room viewed from the observation gallery, soft blue surgical lights, professional documentary style" |
| "celebrity portrait of [name]" | "a distinguished middle-aged man in a tailored navy suit, warm studio lighting, editorial portrait style" |

### Key Principle

Layer 2 (output filter) analyzes the generated image, not just the prompt.
Even well-phrased prompts can be blocked if the model's interpretation triggers
the output filter. When this happens, try shifting the visual concept further
from the trigger rather than just changing words.

---

## Nano Banana 2 / Pro 特有の機能と制約（2026-04-18追記）

Google が 2026年2月にリリースした Nano Banana 2（正式名称: Gemini 3.1 Flash Image、ドキュメント上は `gemini-3.1-flash-image-preview`）は、Imagen 4.0 と比較して以下の特徴を持つ。

### 1. ハイブリッド言語戦略（日本語テキスト描画）

Nano Banana 2 は画像内に**正確な日本語文字を描画できる**（Imagen 4.0 は英語のみ）。プロンプト内の言語を役割で使い分けることで、日本語テキスト入りの画像を生成可能。

**使い分けルール：**
- **視覚的描写（Visuals）** → 英語
  - キャラクター外見、背景、画風、構図、照明、スタイル、カメラ指定
- **文字描画（Text Rendering）** → 日本語
  - ダブルクォートで囲む: `Text: "こんにちは"`

**プロンプト例：**
```
A young woman looking out the window at dawn, warm amber light,
Canon EOS R5 35mm f/1.8, shallow depth of field.
Text: "今日は頼んだ。"
```

**Imagen 4.0 との使い分け：**
- 日本語を画像内に描画したい → Nano Banana 2 使用
- 画像にはテキスト不要、Canvaで後載せ → どちらでも可（Nano Banana 2推奨）
- 英語テキストのみ → どちらでも可

### 2. STRICT CONSISTENCY（複数画像の一貫性保持）

スワイプLP・漫画・シリーズ投稿等で**複数画像にわたるキャラクター・画風の一貫性**を保つための手法。

**適用範囲（重要）：**
- **単発画像生成**: 不要。1プロンプト1画像で完結する場合は STRICT CONSISTENCY ブロックは書かない（ノイズになる）
- **レイヤー分割（L1背景/L2メイン/L3エフェクト）**: 原則不要。各レイヤーは異なる役割・構図のため。同一キャラを複数レイヤーで一貫させたい特殊ケースのみ L2（メインビジュアル）内で適用
- **複数セッション・複数プロンプト生成**: 必須。スワイプLP、漫画、SNSシリーズ、商品画像シリーズ、広告カルーセル等

**基本原理：** 画像生成AIはプロンプトを独立生成するため、前の画像のキャラ設定を覚えていない。**定義文を全プロンプトで一言一句変えずに全文繰り返す**ことで一貫性を保つ。

**実装手順：**

1. **キャラクター定義の作成（英語・詳細に）**:
   ```
   # STRICT CHARACTER CONSISTENCY (DO NOT CHANGE)
   A Japanese woman in her late twenties, natural medium-length chestnut hair
   with soft waves, warm almond-shaped dark brown eyes, wearing a cream
   oversized knit sweater. Slim build, height 165cm.
   ```

2. **画風定義の作成（英語・詳細に）**:
   ```
   # STRICT STYLE CONSISTENCY (DO NOT CHANGE)
   Editorial photography, Canon EOS R5 50mm f/2.0, soft natural window light,
   warm amber color palette, shallow depth of field, low-contrast shadows,
   Kinfolk magazine aesthetic.
   ```

3. **全ページ・全プロンプトに全文記述**:
   - 「省略」「As above」「(see page 1)」等の短縮表記は**厳禁**
   - 必ず全プロンプトで定義文全体を繰り返す

**なぜ効くか：** AI は各プロンプトを独立処理するので、省略すると定義を忘れる。毎回全文記述することで、同じ入力を与え続け、出力の分散を最小化する。

**適用シーン：**
- スワイプLP（同一人物が複数スライドに登場）
- 漫画・コミック（同じキャラの複数コマ）
- SNSシリーズ投稿（ブランド・人物の一貫性）
- 商品画像シリーズ（同じ商品の角度違い等）

### 3. Xサムネイル特化プロンプトの3層構造（テキストなし）

2500×1000（--ar 2500:1000）で作るXサムネイル（テキストなし・画像だけで目を止める）の定番構成。

**構造：**
1. **スタイル指定** — どんな画風・雰囲気か
   - 例: "cinematic lighting," "photorealistic," "surreal magical realism," "Ghibli-inspired"
2. **コンテンツ指定** — 何を・どこに・どんな構図で描くか
   - 被写体、配置、スケール感、色彩、ムードを具体的に
3. **品質指定** — 解像度とアスペクト比
   - 末尾に `8k, --ar 2500:1000` を必ず付ける

**実例：**
```
A colossal ancient castle floating on a massive rock high above the clouds,
waterfalls pouring off the edges into the void below, golden sunset light
hitting the castle walls, birds circling the towers, clean atmospheric
perspective, 8k, --ar 2500:1000
```

### 4. Xサムネイル特化プロンプトの5鉄則（テキスト入り）

文字入りサムネを作る際の、文字を目立たせるための5つの指示パターン。

**❶ 文字サイズを「巨大」と明示する**
- `"enormous"`, `"dominant"`, `"filling 60-75% of the frame width"` 等
- 占有率を数字で指示しないと小さく生成される

**❷ フォントスタイルを具体的に指定する**
- `"heavy bold condensed sans-serif"`, `"cinematic title font"`, `"thin serif"` 等
- 「font」だけでは曖昧すぎる

**❸ 文字にエフェクトをかける**
- `"glowing"`, `"metallic"`, `"embossed"`, `"with light rays"` 等
- 文字自体を光らせたり質感を持たせる

**❹ 文字と背景を融合させる**
- `"text integrated into the scene"`, `"letters affected by the environment"` 等
- 文字が背景と一体化するように指示

**❺ テキストは必ずダブルクォートで囲む**
- `"AI時代の生存戦略"` のように書く
- ハイブリッド言語戦略と併用可能（日本語テキストをNano Banana 2で描画する場合）

**プロンプト例（ダークネイビー×白タイトル）：**
```
A deep navy blue gradient background, slightly textured like fine fabric.
Enormous clean text "AI時代の生存戦略" in bold condensed white sans-serif font,
centered, filling 70% of the frame width. The text has a very subtle soft
white glow behind it. Nothing else. Minimalist, powerful, editorial magazine
cover feel, 8k, --ar 2500:1000
```

### 5. 文字入りサムネの背景シンプル化原則

文字入りサムネでは**背景を引き立て役に徹させる**のが鉄則。

- **OK**: 単色、グラデーション、軽いテクスチャ（コンクリート・和紙・木目）、ぼかしたシーン
- **NG**: 複雑な景色、多くの被写体、ビビッドな色使い（文字が埋もれる）
- **一点アクセント**: シンプル背景に1つだけ目を引く要素（赤い線、光る球、幾何形状）を加えるとベター

### Source
- ほしの氏（@Hoshino_AISales）「プロンプト100選」（2026年4月公開）
- かし子氏（@Kashiko_AIart）「NanoBananaPRO 10ページ漫画作成プロンプト Ver.6」
