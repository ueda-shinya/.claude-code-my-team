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

Execute the following 8 steps in order. Report completion of each step to Shinya before proceeding to the next.

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
   For Asuka's own company LP production, use hearing-sheet-self.md instead.

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
    Phase 7 material information is confirmed when handing off to Kai in Step 3)
```

**Step completion criteria:** All required items in Phase 1-6, Phase 8, and Phase 9 are filled in
(Phase 7 is not included because it mainly consists of optional/recommended items. Its content is confirmed when handing off to Kai)

---

### Step 2: Messaging Strategy & Scenario Design (Delegate to Ren)

Delegate to Ren (`subagent_type: marketing-planner`) with the following:

**Information to provide:**
- Hearing sheet Phase 1-6 answer content (full)
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

### Step 3: Wireframe Creation (Delegate to Kai)

Delegate to Kai (`subagent_type: lp-designer`) with the following:

**Information to provide:**
- Ren's messaging strategy & scenario (full output from Step 2) * Kai starts work only after receiving Ren's output
- Hearing sheet Phase 1-8 answer content
- Phase 7 (Design direction) reference URLs, NG designs, and material information
- Phase 8 (Section structure) Shinya's draft proposal (if any)

**Request:**
- Create wireframe based on Ren's section structure proposal
- Copy skeleton for each section (heading and body direction)
- Image list (existing reuse / new generation needed) — Kai decides
- Design direction (color, font, layout direction)

**Expected output from Kai:**
- Section structure (finalized)
- Copy skeleton (headings, body overview)
- Image list (existing / new generation)
- Design direction

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
1. [Section name] — [Purpose]
2. [Section name] — [Purpose]
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

- **If approved** → Freeze the section structure. If section additions, removals, or reordering become necessary during coding, pause coding, report the impact scope to Shinya, and obtain re-approval before resuming. CSS and expression adjustments are not subject to the freeze
- **If revision is requested** → Route the revision to the appropriate agent:
  - Revision involves "section order, messaging axes, or messages" → **Send back to Ren**
  - Revision involves "design, visuals, or copy expression" → **Send back to Kai**
  - After revision, run the approval gate again

---

### Step 4: Design Specification (Additional Request to Kai)

Based on the approved wireframe, request design details from Kai:

**Request:**
- Color scheme (main / accent / background)
- Font specification (headings / body)
- Layout details (placement, spacing, decoration per section)
- Responsive design approach (mobile display)
- CTA button design

---

### [Approval Gate 2] Design Approval (Quick Confirmation)

Confirm the design direction with Shinya.
Since the overall structure is already approved via the wireframe, a quick confirmation is sufficient:

```
[Approval Gate 2: Design Direction]
-- Color: [Main] / [Accent]
-- Font: [Headings] / [Body]
-- Notes: [Responsive approach, etc.]

Shall we proceed with this direction? (Quick confirmation)
```

---

### Step 5: Coding (Delegate to Shu)

Delegate to Shu (`subagent_type: backend-engineer`) with the following. In this skill, Shu operates as the **implementation lead (LP PHP/HTML/CSS/JS)**.

**Information to provide:**
- Approved section structure (finalized version from Step 3)
- Design specifications (output from Step 4)
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

### Step 6: Security & Code Review (Delegate to Sakura — Automatic)

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

### Step 7: Fix Issues (Delegate to Shu)

Send Sakura's findings (high and medium severity) to Shu for correction.

- After fixes are complete, request re-review from Sakura
- **Repeat Steps 6-7 until all items pass**
- No hard limit on iterations, but if it exceeds 3 rounds, Asuka reports the situation to Shinya

---

### Step 8: Completion Report

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
2. Image generation: To request Luna, say "Generate images for this LP"
3. Pre-launch check: On-device display verification recommended
```

Image generation (Luna) can be deferred. Confirm with Shinya:
- "Proceed with image generation now" → Pass image list to Luna and delegate
- "Do it later" → Save image placeholder list and complete

## Interruption & Resumption

- This skill has a long workflow and sessions may be interrupted
- When interrupting, record the current step and progress in `session-handoff.md`
- When resuming, check `session-handoff.md` and resume from the interrupted step
