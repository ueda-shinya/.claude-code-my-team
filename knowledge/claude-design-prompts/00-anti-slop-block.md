# 00. アンチスロップ・システムプロンプト

**必ずプロンプトの冒頭に貼ること。** これが無いと AI 臭い汎用デザイン（Inter / 紫グラデ / 3カラムフィーチャー）に収束する。

## frontend_aesthetics ブロック

```
<frontend_aesthetics>
You tend to converge toward generic, "on distribution" outputs. In frontend
design, this creates what users call the "AI slop" aesthetic. Avoid this:
make creative, distinctive frontends that surprise and delight. Focus on:

Typography: Choose fonts that are beautiful, unique, and interesting. Avoid
generic fonts like Arial and Inter; opt instead for distinctive choices.

Color & Theme: Commit to a cohesive aesthetic. Use CSS variables for
consistency. Dominant colors with sharp accents outperform timid,
evenly-distributed palettes.

Motion: Use animations for effects and micro-interactions. Prioritize
CSS-only solutions. Focus on high-impact moments.

Backgrounds: Create atmosphere and depth rather than defaulting to solid
colors. Layer CSS gradients, geometric patterns, contextual effects.

Avoid generic AI-generated aesthetics:
- Overused font families (Inter, Roboto, Arial, system fonts)
- Clichéd color schemes (particularly purple gradients on white backgrounds)
- Predictable layouts and component patterns
- Cookie-cutter design that lacks context-specific character

Interpret creatively and make unexpected choices. Vary between light/dark
themes, different fonts, different aesthetics. You still tend to converge
on common choices (Space Grotesk, for example). Avoid this!
</frontend_aesthetics>
```

## use_interesting_fonts ブロック（タイポ強化、任意で追加）

```
<use_interesting_fonts>
Never use: Inter, Roboto, Open Sans, Lato, default system fonts

Impact choices:
- Code aesthetic: JetBrains Mono, Fira Code, Space Grotesk
- Editorial: Playfair Display, Crimson Pro, Fraunces
- Startup: Clash Display, Satoshi, Cabinet Grotesk
- Technical: IBM Plex family, Source Sans 3
- Distinctive: Bricolage Grotesque, Obviously, Newsreader

Pairing principle: High contrast = interesting.
Use extremes: 100/200 weight vs 800/900, not 400 vs 600.
Size jumps of 3x+, not 1.5x.

Pick one distinctive font, use it decisively. Load from Google Fonts.
State your choice before coding.
</use_interesting_fonts>
```

## Claude のクセを打ち消す追加指示（必要に応じて）

```
Additional constraints to avoid Claude's default patterns:
- Pick a brand-specific accent color first (avoid default teal #16d5e6)
- Maximum 2 nesting levels for containers
- Specify font stack with weight + tracking, not vibes
- Hero layout: use marquee, alternating-row, or single-column (NOT 3-col feature grid)
- Commit to one icon family, or use type-only
```

出典:
- [Claude Cookbook - Frontend Aesthetics](https://platform.claude.com/cookbook/coding-prompting-for-frontend-aesthetics)
- [rohitg00/awesome-claude-design](https://github.com/rohitg00/awesome-claude-design)
