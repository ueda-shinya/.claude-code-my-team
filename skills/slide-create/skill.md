# /slide-create Skill

A skill that defines the procedure for Asuka to correctly request slide manuscripts from Sora (slide-designer) and output content ready to paste into slide creation tools.

## Trigger Conditions

Execute this skill when any of the following apply:

- The user says "make slides," "create a slide," or similar
- The user inputs `/slide-create`

## Constraints (All Steps)

- **Asuka handles all requests to Sora.** Even if Shinya speaks to Sora directly, Asuka must organize the information before passing it to Sora
- **Always explicitly instruct Sora to "use only the provided content."** Do not allow fabricated features, numbers, or services
- **When using Genspark format, instruct Sora to always include the Genspark constraint notice in the output**

## Execution Steps

Execute the following steps in order.

---

### Step 1: Information Gathering (Asuka confirms with Shinya)

Confirm the following items. Skip any items already known.

| Item | Details |
|---|---|
| Theme / Purpose | What the slides should communicate |
| Target Audience | Beginners / internal team / executives / clients, etc. |
| Tone | Business / casual / educational |
| Tool to Use | Genspark / Gamma / Canva / other |
| Approximate Slide Count | Leave to Sora if not specified |
| Existing Reference Materials | If available, pass the content to Sora |

Ask all items together in one round trip.

---

### Step 2: Request to Sora (Asuka executes)

Asuka requests structure outline creation from Sora (`subagent_type: slide-designer`) with the following:

- All information confirmed in Step 1
- Format specification based on the selected tool
- The following instructions must always be included:
  - "Use only the provided content. Do not independently add non-existent features, numbers, services, or integrations"
  - When using Genspark: "Always include the Genspark constraint notice in the Genspark prompt"
  - "This is a skill-driven request; skip the hearing (Step 1) and proceed directly to creating the structure outline"

---

### Step 2.5: Structure Outline Review (Asuka presents to Shinya)

When Sora returns the structure outline (number of slides and each slide's title), Asuka presents it to Shinya.

- Proceed to instruct Sora to create the detailed manuscript only after Shinya's approval
- If revision requests are given, relay them to Sora to revise the outline

---

### Step 3: Rina Review of Sora's Manuscript (Asuka executes)

Before delivering Sora's manuscript to Shinya, always request a review from Rina (`subagent_type: logic-verifier`).

**What to pass to Rina:**
- (a) The original manuscript or original instructions
- (b) Asuka's instructions to Sora
- (c) Sora's delivered output

**What Rina checks:**
- Whether the output follows instructions (e.g., no-change sections were not changed)
- Logical consistency and coherence across slides
- Consistency of facts and numbers

**Handling review results:**
- No issues → proceed to Step 4
- Issues found → send back to Sora for revision, then have Rina re-check
  - **Maximum 2 revisions.** If unresolved after 2 rounds, escalate to Shinya for a decision
  - **Exception — if Rina flags a "no-change" section:** Sora must not modify it; Asuka reports to Shinya for a decision

**Definition of "important deliverables" (triggers Koto review after Rina's approval):**
The following qualify. If uncertain, Asuka confirms with Shinya (if Sora is uncertain, Sora reports to Asuka; Asuka decides):
- Client-facing deliverables (proposals, LP manuscripts, reports, etc.)
- Content intended for external publication

---

### Step 4: Output Delivery (Asuka delivers to Shinya)

Deliver the Rina-approved manuscript to Shinya.

- **For Genspark:** Guide with "Please paste this text into Genspark's prompt field"
- **For Gamma:** Guide with "Please paste this Markdown into Gamma"
- **For other tools:** Provide paste instructions appropriate to the tool
