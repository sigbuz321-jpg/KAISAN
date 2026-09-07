# KAISAN Applied Interface Kit

This is the reuse guide for the KAISAN applied interface kit, part of the KAISAN design system package. It documents the applied kit structure, component files, usage workflow, design notes, and source basis so future agents can reuse it like a Claude Design package.

## Applied Kit Structure

The applied kit structure organizes sixteen component spec pages, shared styles, and a launcher:

```
ui_kits/app/
├── README.md              ← this reuse guide
├── index.html             ← launcher (loads ../colors_and_type.css + styles.css)
├── styles.css             ← shared k-* component classes (~19 KB)
├── components/            ← modular CSS partials
│   └── button.css         ← button CSS partial
├── button.html            ← 01. Button spec
├── badge.html             ← 02. Badge spec
├── badge-level.html       ← 03. Badge-level spec
├── progress.html          ← 04. Progress spec
├── banner.html            ← 05. Banner spec
├── field.html             ← 06. Field spec
├── answer-option.html     ← 07. Answer-option spec
├── question-card.html     ← 08. Question-card spec
├── question-map.html      ← 09. Question-map spec
├── timer.html             ← 10. Timer spec
├── tab-pill.html          ← 11. Tab-pill spec
├── list-card.html         ← 12. List-card spec
├── save-indicator.html    ← 13. Save-indicator spec
├── leaderboard-row.html   ← 14. Leaderboard-row spec
├── app-header.html        ← 15. App-header spec
└── tab-bar.html           ← 16. Tab-bar spec
```

Token layer: `styles.css` begins with `@import '../colors_and_type.css'` so token changes cascade automatically. No raw hex or hardcoded spacing anywhere in the kit.

Shared stylesheet: `styles.css` defines all component classes with `k-*` prefix — stable Vue/Blade binding targets.

Launcher: `index.html` renders a composed mini-interface and links to every spec page.

## Component Files

Sixteen component files, each extracted from `kaisan-tahap-2-komponen.html`:

| Component | File | CSS class | Variants × States |
|---|---|---|---|
| Button | `button.html` | `k-btn` | primary / secondary / ghost / danger × default / hover / focus / disabled / processing |
| Badge | `badge.html` | `k-badge` | accent / neutral / success / warning / info / danger |
| Badge-level | `badge-level.html` | `k-badge-level` | Mahir / Berkembang / Mulai |
| Progress | `progress.html` | `k-progress` | accent / complete |
| Banner | `banner.html` | `k-banner` | info / success / warning / danger |
| Field | `field.html` | `k-field` | default / focused / error / disabled / prefix |
| Answer-option | `answer-option.html` | `k-answer` | default / selected / correct / wrong / locked |
| Question-card | `question-card.html` | `k-qcard` | header + prompt + options |
| Question-map | `question-map.html` | `k-qmap` | 5-col grid, 4 cell states |
| Timer | `timer.html` | `k-timer` | default / danger (blink) / success |
| Tab-pill | `tab-pill.html` | `k-tpill` | segmented pills, active underline |
| List-card | `list-card.html` | `k-lcard` | icon + title + meta + trail |
| Save-indicator | `save-indicator.html` | `k-save` | saving / saved / failed |
| Leaderboard-row | `leaderboard-row.html` | `k-lbrow` | rank 1–3 + meta + delta |
| App-header | `app-header.html` | `k-appheader` | sticky 56 px top bar |
| Tab-bar | `tab-bar.html` | `k-tabbar` | 4-tab bottom nav |

## Usage Workflow

**Review:** Open `index.html` → composed mini-interface → click component links → see all variants/states with copyable code.

**Build a screen:**

1. Pick components from spec pages.
2. Copy markup into a new `.html` file.
3. Link `../colors_and_type.css` and `styles.css`.
4. Follow `../README.md`: phone = 390 × 844 single column; desktop = 240 px sidebar + 1200 px content.
5. Indonesian copy, 24-hour time (`13:42`), OKLch tokens.

**Wire into Vue:**
```vue
<button class="k-btn k-btn--primary" :disabled="loading" @click="submit">
  Mulai Latihan
</button>
```

**Wire into Blade:**
```blade
<button class="k-btn k-btn--primary" {{ $attributes }}>Mulai Latihan</button>
```

**Add a component:** create `<name>.html`, add CSS to `styles.css` with `k-` prefix, document in `../README.md`, add to `index.html` nav, run audit.

## Design Notes

- Touch targets ≥ 44 px on phone. Button md = 40 px, sm = 32 px.
- Semantic pairing: `--*-soft` always with `--*-text`, never `--muted`.
- One `k-btn--primary` per visual region — extras to ghost/secondary.
- Processing state: spinner replaces label, `pointer-events: none`.
- Focus ring: `outline: 2px solid var(--accent); outline-offset: 2px` on `:focus-visible`.
- Reduced motion: 0 ms transitions under `prefers-reduced-motion: reduce`.
- Indonesian copy: 24 h time, borrowed English lowercase, acronyms untranslated.
- No raw hex, no raw shadows, no neon success/danger.

## Source Basis

Primary: `kaisan-tahap-2-komponen.html` (~121 KB) from **Lengkapi brief KAISAN** (`be121444-a3e3-4114-b507-ea32f44b815c`). Each spec page reproduces the variant × state matrix, HTML/Blade snippets, and Indonesian copy.

Secondary: `kaisan-tahap-3-layar.html` (77 KB) + `kaisan-tahap-4-panel-guru.html` (66 KB) confirm component composition.

Foundation: all tokens from `kaisan-tahap-1-fondasi.html` (37 KB), codified in `../colors_and_type.css`.

No components invented beyond what the source defines.

## Editing Rules

1. Never redefine tokens in `styles.css` — add to `../colors_and_type.css`.
2. Use `k-*` prefix. Extend, never rename.
3. One component per file; stack states on same page.
4. Keep `../README.md` synchronized.
5. Preserve source artifacts.

## Verification

```powershell
& "$env:OD_NODE_BIN" "$env:OD_BIN" tools connectors design-system-package-audit --path . --fail-on-warnings
```
