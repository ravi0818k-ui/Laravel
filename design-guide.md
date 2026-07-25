# PG A1 – Design Guide

A reference for typography, colors, and styling used across PG A1 projects.

---

## Color Palette

| Token        | Hex       | Usage                                      |
|--------------|-----------|--------------------------------------------|
| `--cream`    | `#faf6f0` | Page background                            |
| `--warm`     | `#f2ebe0` | Section backgrounds, subtle card fills     |
| `--sand`     | `#e8d8c4` | Borders, dividers                          |
| `--amber`    | `#c8813a` | Primary accent – buttons, badges, links    |
| `--amber2`   | `#e09a50` | Hover state for primary accent             |
| `--dark`     | `#1a1410` | Headings, footer background, high contrast |
| `--charcoal` | `#3d3028` | Body text                                  |
| `--mid`      | `#7a6655` | Secondary text, descriptions               |
| `--light`    | `#b8a898` | Muted labels, captions                     |
| `--white`    | `#ffffff` | Card backgrounds, text on dark surfaces    |

### Supplementary Colors

| Color             | Hex       | Usage               |
|-------------------|-----------|---------------------|
| WhatsApp Green    | `#25D366` | WhatsApp CTA button |
| WhatsApp Hover    | `#1ebe5d` | WhatsApp hover      |
| Google Maps Blue  | `#4285F4` | Maps float shadow   |

### CSS Variables (copy-paste ready)

```css
:root {
  --cream: #faf6f0;
  --warm: #f2ebe0;
  --sand: #e8d8c4;
  --amber: #c8813a;
  --amber2: #e09a50;
  --dark: #1a1410;
  --charcoal: #3d3028;
  --mid: #7a6655;
  --light: #b8a898;
  --white: #ffffff;
  --shadow: 0 4px 32px rgba(26, 20, 16, 0.10);
  --shadow2: 0 12px 48px rgba(26, 20, 16, 0.18);
}
```

---

## Typography

### Font Families

| Font              | Weight(s)       | Role                                   |
|-------------------|-----------------|----------------------------------------|
| Playfair Display  | 500, 700, 900   | Headings, prices, display text         |
| DM Sans           | 300, 400, 500   | Body text, descriptions, UI elements   |
| Roboto            | 400, 700, 900   | Logo / branding text                   |

### Google Fonts Import

```html
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700;900&family=DM+Sans:wght@300;400;500&family=Roboto:wght@400;700;900&display=swap" rel="stylesheet" />
```

### Text Styles

| Element              | Font             | Size                          | Weight | Color           | Extra                          |
|----------------------|------------------|-------------------------------|--------|-----------------|--------------------------------|
| Hero Title           | Playfair Display | `clamp(2.4rem, 5.5vw, 4rem)` | 900    | `--white`       | line-height: 1.1               |
| Section Title        | Playfair Display | `clamp(1.8rem, 3.5vw, 2.8rem)` | 700  | `--dark`        | line-height: 1.2               |
| Section Label        | DM Sans          | `0.75rem`                     | 600    | `--amber`       | uppercase, letter-spacing: .18em |
| Body Text            | DM Sans          | `1rem`                        | 300    | `--mid`         | line-height: 1.7               |
| Nav Links            | DM Sans          | `0.88rem`                     | 500    | `--mid`         | uppercase, letter-spacing: .04em |
| Card Title           | Playfair Display | `1.15rem`                     | 700    | `--dark`        | —                              |
| Card Price           | Playfair Display | `1.4rem`                      | 900    | `--dark`        | —                              |
| Card Tags            | DM Sans          | `0.7rem`                      | 500    | `--mid`         | background: `--warm`           |
| Stat Number          | Playfair Display | `1.8rem`                      | 900    | `--amber`       | —                              |
| Stat Label           | DM Sans          | `0.75rem`                     | 400    | white @ 50%     | uppercase, letter-spacing: .1em |
| Button Primary       | DM Sans          | `0.92rem`                     | 600    | `--white`       | background: `--amber`          |
| Button Outline       | DM Sans          | `0.92rem`                     | 500    | `--white`       | border: white @ 60%            |
| Footer Text          | DM Sans          | `0.8rem`                      | 400    | white @ 40%     | —                              |

---

## Shadows

| Token       | Value                                | Usage                 |
|-------------|--------------------------------------|-----------------------|
| `--shadow`  | `0 4px 32px rgba(26, 20, 16, 0.10)` | Cards, subtle depth   |
| `--shadow2` | `0 12px 48px rgba(26, 20, 16, 0.18)`| Hover states, modals  |

---

## Buttons

### Primary Button
```css
.btn-primary {
  background: var(--amber);
  color: var(--white);
  padding: 0.8rem 1.8rem;
  border-radius: 4px;
  font-size: 0.92rem;
  font-weight: 600;
}
.btn-primary:hover {
  background: var(--amber2);
  transform: translateY(-2px);
}
```

### Outline Button
```css
.btn-outline {
  border: 1.5px solid rgba(255, 255, 255, 0.6);
  color: var(--white);
  padding: 0.8rem 1.8rem;
  border-radius: 4px;
  font-size: 0.92rem;
  font-weight: 500;
}
.btn-outline:hover {
  border-color: var(--white);
  background: rgba(255, 255, 255, 0.1);
}
```

---

## Border Radius

| Element       | Radius   |
|---------------|----------|
| Buttons       | `4px`    |
| Cards         | `16px`   |
| Tags / Pills  | `12px` – `24px` |
| Gallery       | `16px`   |
| Small badges  | `2px` – `20px`  |
| Input / CTA   | `6px` – `8px`   |

---

## Spacing & Layout

- Section padding: `5rem 8vw` (desktop), `3.5rem 5vw` (mobile)
- Nav height: `64px`
- Grid gap: `1.5rem`
- Divider: `3rem` wide × `3px` tall, color `--amber`

---

## Animation

| Name     | Usage              | Keyframes                                |
|----------|--------------------|------------------------------------------|
| fadeUp   | Hero content entry | `translateY(22px) → 0`, `opacity 0 → 1` |
| kenBurns | Hero background    | `scale(1.08) → scale(1.0)`              |

---

## Brand Logo

- Font: Roboto, 900 weight
- "PG A" in `--dark` at `1.4rem`
- "1" in `--amber` at `2.5rem`
- Uppercase, letter-spacing: `.04em`

---

## Quick Start (copy into new project)

```css
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --cream: #faf6f0;
  --warm: #f2ebe0;
  --sand: #e8d8c4;
  --amber: #c8813a;
  --amber2: #e09a50;
  --dark: #1a1410;
  --charcoal: #3d3028;
  --mid: #7a6655;
  --light: #b8a898;
  --white: #ffffff;
  --shadow: 0 4px 32px rgba(26, 20, 16, 0.10);
  --shadow2: 0 12px 48px rgba(26, 20, 16, 0.18);
}

html { scroll-behavior: smooth; }

body {
  font-family: 'DM Sans', sans-serif;
  background: var(--cream);
  color: var(--charcoal);
}
```
