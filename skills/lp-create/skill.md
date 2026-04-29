# /lp-create Skill

Manages the full LP (Landing Page) creation workflow with a marketing-first approach.
Locks in messaging strategy and section structure before coding to prevent costly rework.

## Trigger Conditions

Execute this skill when any of the following applies:

- The user says "LP作って", "LPを新規制作したい", "ランディングページを作りたい"
- The user inputs `/lp-create`

## Constraints (Apply to All Steps)

- **Asuka never codes directly.** All coding must be delegated to Shu ("Asuka Never Codes Directly" rule)
- **Approval gates cannot be skipped.** Even if Shinya says "hurry up", always obtain confirmation
- **If a step proceeds without approval, report to Shinya and revert to the previous step**
- **Security review (Sakura) is automatic every time.** Cannot be omitted
- Each step's deliverables carry forward to the next step. If a previous step's output is insufficient, Asuka requests supplementation

## Execution Steps

Execute the following 9 steps in order. Report completion of each step to Shinya before proceeding to the next.

---

### Step 1: Requirements Hearing (Asuka as Facilitator)

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

2. Present all questions for each Phase in one batch (questions are presented once, but the number
   of round trips including deep-dives varies by Phase)

3. After receiving the user's answers, Asuka determines the responsible agent and requests deep-dive
   - Only pass items that need deep-diving to the agent
   - Asuka directly handles factual confirmation items

4. After deep-dive is complete, Asuka summarizes the Phase answers and confirms with Shinya:
   "Here is a summary of Phase X answers. Is this correct?"
   -> Shinya returns "OK" or corrections
   -> Once approved, fill in the corresponding Phase on the hearing sheet and move to the next Phase

5. When all required items in Phase 1-6, Phase 8, and Phase 9 are filled, declare "Hearing complete"
   and Asuka reports completion to Shinya before proceeding to Step 2
   (Phase 7 is not included because it is not required for Kai to start wireframing.
    Phase 7 material information is confirmed when handing off to Kai in Step 4)
```

**Step completion criteria:** All required items in Phase 1-6, Phase 8, and Phase 9 are filled in
(Phase 7 is not included because it mainly consists of optional/recommended items. Its content is confirmed when handing off to Kai)

---

### Step 2: Hearing Content Review (Pre-Finalization Check)

After the hearing is complete, compile all answers into a list and provide Shinya with an opportunity to review and revise.
Do not proceed to Step 3 until Shinya says "confirmed."

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

**Expected output from Ren:**
- Messaging axes (main / sub)
- Section structure proposal (order, purpose of each section, message overview)
- CTA placement strategy

**Ren's responsibility scope:** Determines section structure order, messaging axes, and messages (marketing responsibility)

---

### Step 4: Wireframe Creation (Delegate to Kai)

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

**Copy brushup (Koto step):**
After Kai's wireframe is complete, Asuka reviews Kai's copy skeleton and delegates to Koto (`subagent_type: copywriter`) if any of the following apply:
- The FV headline does not align with the messaging axis
- Mid-page CTA or section heading expressions are weak
- Shinya has declared "leave the copy to the team"

If none of the above apply, the Koto step is skipped.
If Shinya writes the copy himself, declaring so will skip the Koto step.

**Post-decision report (required):** Whether the Koto step is requested or skipped, Asuka must report the decision to Shinya.
- If requested: "Delegating copy brushup to Koto (reason: XX)"
- If skipped: "Kai's copy skeleton is sufficient; skipping the Koto step"

---

### [Approval Gate 1] Structure Approval (Cannot Be Skipped)

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

- **If approved** -> Freeze the section structure. If section additions, removals, or reordering become necessary during coding, pause coding, report the impact scope to Shinya, and obtain re-approval before resuming. CSS and expression adjustments are not subject to the freeze
- **If revision is requested** -> Route the revision to the appropriate agent:
  - Revision involves "section order, messaging axes, or messages" -> **Send back to Ren**
  - Revision involves "design, visuals, or copy expression" -> **Send back to Kai**
  - After revision, run the approval gate again

---

### Step 5: Design Specification (Additional Request to Kai)

Based on the approved wireframe, this step **details and specifies the design direction established in Step 4**. Request design details from Kai.

**Kai's deliverable:** Use `skills/lp-create/design-spec-template.md` to create a design specification document. Fill in all sections of the template and pass the self-check before submitting.

**Information to provide:**
- Kai's output from Step 4 (finalized section structure, copy skeleton, design direction)
- Ren's output from Step 3 (messaging axes and messages) -> pass as the basis for what the visuals need to express
- Target emotional state from Step 1 (Phase 4) -> "What mindset does the user have when they open the page right after clicking the ad?"
- Phase 7 reference LPs and NG designs (design-perspective reference URLs)

**Request (Kai's scope):**

#### 1. Color Scheme & Font

- Specify main / accent / background colors in HEX codes
- Specify heading and body font family, size, weight, and line height
- CTA button color: choose the color with the highest contrast against the background. Do not hardcode a specific color

#### 2. FV (First View) Design Spec

Select the headline, hero visual, and CTA placement pattern from the following and provide the rationale:

| Pattern | Layout | Best suited for |
|---|---|---|
| **A: Left text / Right visual** | Headline, sub-copy, CTA on left; image on right | BtoB, SaaS, tools, information-heavy products |
| **B: Center-aligned** | Full-width background image or video with headline and CTA centered on top | BtoC, emotional appeal, brand appeal, travel, lifestyle |
| **C: Right text / Left visual** | Product/person prominently on left; text on right | Beauty, cosmetics, food, product-focused cases |
| **Mobile (common)** | Single-column vertical stack (headline -> visual -> CTA) | All patterns convert to this structure on mobile |

**FV design prohibitions:**
- Cramming headline, feature descriptions, awards, and CTA all into the FV (causes 3-second bounce)
- Layouts where the visual is too large and the headline becomes unreadable
- Layouts where the CTA does not fit within the FV (especially on mobile)

#### 3. Eye-Flow Pattern

Select a pattern based on the following criteria and determine CTA placement positions:

| Condition | Adopted pattern | CTA position |
|---|---|---|
| Low information density, 2-column, PC-primary, emotional appeal | **Z pattern** | At the Z endpoint (bottom-right, end of diagonal) |
| High information density, 1-column, text-heavy, comparison-minded target | **F pattern** | After each section heading + bottom of page |
| Mobile-only, vertical scroll | **F pattern** (vertical scan) | After benefit section mid-scroll + end of page |

#### 4. Trust Element Placement Design

Place different content at 3 positions. Do not repeat the same element at all 3 locations:

**Near FV (before scroll)** -- First impression of "this page is trustworthy"
- Numbers such as implementation results, registered users ("XX million users")
- Media coverage and award logos
- Client logos (for BtoB)
- Certification and accreditation badges

**Mid-page (after story/comparison)** -- Resolving "does this actually work?" doubt
- Customer testimonials with specific numbers, Before/After
- Case studies (company name, industry, results -- 3-piece set)
- Third-party reviews and rating scores

**Just before CTA (directly above the button)** -- Removing the final hesitation of "is it OK to click?"
- Numbers such as "XX million users" (social proof)
- Money-back guarantee, free trial clearly stated
- Privacy mark, SSL badge
- Microcopy (-> wording is Koto's responsibility)

#### 5. CTA Design

**Number of placements and positions:**
- Standard: 3 points -- within FV / after mid-page trust elements / end of page
- Short LPs (2-3 scrolls): 2 points (FV and end) is acceptable
- The principle is "place CTA before the benefit heat cools down"

**Button design:**
- Minimum size: 44px x 44px (WCAG standard)
- Mobile recommended: 60-72px (assuming thumb tap)
- Color: choose the highest contrast color against the background
- Hover effect: PC only (not needed for mobile)
- Ensure sufficient white space around the button
- Label: state the benefit or action instead of "Submit" (e.g., "Get a free consultation", "Try it now") -> **wording is Koto's responsibility**. Kai defines the label direction and character limit

**Microcopy (Kai's responsibility is placement position and character limit only):**
- Placement: **directly below** the button (placing above may be read before the CTA, so below is safer)
- Character limit: 20-40 characters
- Content direction: words that lower psychological barriers (free, cancel anytime, done in X minutes, etc.) -> wording is Koto's responsibility

**Sticky CTA (for long LPs):**
- Present the option to place a fixed CTA bar at the bottom of the screen
- Must be designed to not encroach on the main content viewing area

#### 6. Layout Details & Bounce Prevention

- Define placement, spacing, and decoration for each section
- Do not install global navigation
- Eliminate external links (do not create exit paths from the LP)
- Minimum spacing between buttons: 8px or more (to prevent accidental taps)

#### 7. Responsive & Mobile Design Approach

- Design mobile-first, treat PC as the extension
- 2-column is PC only. All sections convert to single-column vertical stack on mobile
- Mobile font size: headings 20-28px (too-small headings are a common mistake)
- Set wider margins and line height than PC
- Phone numbers should use `tel:` links
- Ensure sufficient tap area for form input fields

#### 8. Performance Design Policy

**Image format:**

| Format | Use case |
|---|---|
| **WebP** | Default for all photos, illustrations, and LP images |
| **SVG** | Icons, logos, simple shapes |
| **PNG** | Fallback only for cases requiring transparency where WebP is not supported |
| **JPEG** | Not used in principle (replaced by WebP) |

Recommend `<picture>` tag with WebP -> JPEG fallback structure for implementation.

**Lazy loading criteria:**
- FV hero visual: `loading="eager"` (directly impacts LCP; do not lazy-load)
- All images below FV: `loading="lazy"`

**Prohibited effects:**
- Particle animations, parallax scrolling
- CSS animations on properties other than `transform` / `opacity`
- Auto-playing background videos (critical on mobile data connections)

**Performance targets:**
- Page load time: within 3 seconds
- LCP: within 2.5 seconds
- FV image file size: within 300KB

---

**Expected output from Kai:**
- Color scheme (HEX codes) and font specs (size, weight, line height)
- FV design spec (selected pattern + rationale)
- Eye-flow pattern (selected pattern + rationale)
- Trust element placement design (element list for each of the 3 positions)
- CTA design spec (number of placements, positions, button size, label direction, microcopy placement and character limit)
- Layout details and bounce prevention design (no global nav, no external links, etc.)
- Responsive design approach
- Performance design policy (image formats, lazy loading targets, prohibited effects)

**Additional request to Koto (required):** After Kai's design specification is complete, Asuka must delegate the following to Koto (`subagent_type: copywriter`):
- CTA button label wording (based on the direction and character limit defined by Kai)
- Microcopy wording (based on the placement position and character limit defined by Kai)

---

### [Approval Gate 2] Design Approval (Quick Confirmation)

Confirm the design direction with Shinya.
Since the overall structure is already approved via the wireframe, a quick confirmation is sufficient:

```
[Approval Gate 2: Design Direction]
-- Color: Main [#XXXXXX] / Accent [#XXXXXX] / Background [#XXXXXX]
-- Font: Headings [font name, size] / Body [font name, size]
-- FV structure: [Pattern A/B/C + mobile layout] (include selection rationale)
-- Eye-flow: [Z pattern / F pattern] (include selection rationale)
-- CTA placement: [Number of placements and positions (e.g., 3 locations: FV / after mid-page benefit / end)]
-- Trust elements: Near FV [element name] / Mid-page [element name] / Just before CTA [element name]
-- Bounce prevention: No global nav / No external links / [Other notes]
-- Mobile: [Notes (e.g., sticky CTA presence)]
-- Performance: [Image format and prohibited effect notes]
-- Images: New generation [count] / Existing materials [count] (definitions in spec document Section 10; not subject to approval)

Shall we proceed with this direction? (Quick confirmation)
```

---

### Step 5.5: Image Prompt Design (Delegate to Luna)

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
- Asuka saves the output as `image-prompts.md` at the following path (per CLAUDE.md "Client Directory Structure Rules" 4 patterns):
  - Pattern A (1 business, 1 domain): `~/.claude/clients/<client-name>/lp-<date>/image-prompts.md`
  - Pattern B (1 business, multiple domains): `~/.claude/clients/<client-name>/<domain>/lp-<date>/image-prompts.md`
  - Pattern C (multiple businesses): `~/.claude/clients/<client-name>/biz-<business-name>/lp-<date>/image-prompts.md`
  - Pattern D (multiple businesses × multiple domains): `~/.claude/clients/<client-name>/biz-<business-name>/<domain>/lp-<date>/image-prompts.md`

**Step completion criteria:** All images requiring new generation have their prompts saved in `image-prompts.md`

---

### Step 6: Coding (Delegate to Shu)

Delegate to Shu (`subagent_type: backend-engineer`) with the following. In this skill, Shu operates as the **implementation lead (LP PHP/HTML/CSS/JS)**.

**Information to provide:**
- Approved section structure (finalized version from Step 4)
- Design specifications (Kai's output from Step 5)
- Finalized copy (Koto's output from Step 5: CTA button label wording and microcopy wording)
- Hearing sheet Phase 2 (CV definition, post-CV flow)
- Hearing sheet Phase 7 (material list)
- Hearing sheet Phase 9 (CMS, deployment URL, form tool, analytics settings)
- Existing LP codebase (specify reference path)
- Image placeholder specifications (alt text, size)
- PC_PLATFORM (check `~/.claude/.env` for `PC_PLATFORM` and pass it)
- Implement as PHP template within WordPress theme structure

**Notes for Shu:**
- Section structure is frozen. No structural changes allowed
- All images implemented as placeholders (to be replaced later)
- Include JSON-LD (per Web Development Coding Rules)

---

### Step 7: Security & Code Review (Delegate to Sakura -- Automatic)

Delegate to Sakura (`subagent_type: code-reviewer`) with the following:

**Review perspectives:**
- XSS / escaping gaps
- WordPress conventions (`wp_enqueue_style` / `wp_enqueue_script` / `esc_html`, etc.)
- Performance (`loading` attribute usage / image optimization)
- Mobile support (responsive CSS validity)

**Priority and action:**
- **High severity**: Must fix
- **Medium severity**: Must fix
- **Low severity**: Optional (report to Shinya for decision)

---

### Step 8: Fix Issues (Delegate to Shu)

Send Sakura's findings (high and medium severity) to Shu for correction.

- After fixes are complete, request re-review from Sakura
- **Repeat Steps 7-8 until all items pass**
- No hard limit on iterations, but if it exceeds 3 rounds, Asuka reports the situation to Shinya

---

### Step 9: Completion Report

Report to Shinya using the following format:

```
[LP Creation Complete]

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

## Interruption & Resumption

- This skill has a long workflow and sessions may be interrupted
- When interrupting, record the current step and progress in `session-handoff.md`
- If `image-prompts.md` has been created but image generation is not yet complete, always record the path as well
- When resuming, check `session-handoff.md` and resume from the interrupted step
