---
name: UI redesign & localization sweep lessons
description: Pitfalls from the 2026-08 design-system remake + full lang sweep (parallel subagent, test assumptions).
---

- **Parallel lang-file writes clobber each other.** A subagent that regenerates lang/*.php from its own source dict will silently drop keys the main agent added by hand in the meantime. **Why:** regeneration is whole-file overwrite, not merge. **How to apply:** when a subagent owns lang-file regeneration, route ALL new keys through its merge recipe (or add yours only after it finishes), then re-verify your keys resolve via tinker.
- **Old tests assert unlocalized artifacts.** Several feature tests passed only because `__('ui.key')` rendered the raw key (missing translation) or because expected text lived inside an inline `<style>` block (assertSeeText doesn't strip style content). Adding real translations / moving CSS out breaks them. Fix by asserting `__('key')` dynamically or visible copy, not raw keys/CSS class names.
- `__('key', [], null, 'fallback')` 4th arg is ignored by Laravel — it's harmless decoration; the real safety net is the key existing in both locales.
- Design language: teal gradient + 3px gold thread accent, wordmark `.brand-mark` (no bitmap logo), `.auth-layout` split logins, bilingual-by-design public verification page. Mobile portal nav = horizontally scrollable pills (full drawer is post-Foundation).
