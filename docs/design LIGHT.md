---
name: kodanIAHUB LIGHT
colors:
  surface: '#faf8ff'
  surface-dim: '#d2d9f4'
  surface-bright: '#faf8ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f2f3ff'
  surface-container: '#eaedff'
  surface-container-high: '#e2e7ff'
  surface-container-highest: '#dae2fd'
  on-surface: '#131b2e'
  on-surface-variant: '#3c4947'
  inverse-surface: '#283044'
  inverse-on-surface: '#eef0ff'
  outline: '#6c7a77'
  outline-variant: '#bbc9c6'
  surface-tint: '#006a60'
  primary: '#006a60'
  on-primary: '#ffffff'
  primary-container: '#62e2d1'
  on-primary-container: '#006359'
  inverse-primary: '#59daca'
  secondary: '#595e6f'
  on-secondary: '#ffffff'
  secondary-container: '#dbdff4'
  on-secondary-container: '#5d6274'
  tertiary: '#732ee4'
  on-tertiary: '#ffffff'
  tertiary-container: '#d9c4ff'
  on-tertiary-container: '#6c22dd'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#79f7e6'
  primary-fixed-dim: '#59daca'
  on-primary-fixed: '#00201c'
  on-primary-fixed-variant: '#005048'
  secondary-fixed: '#dee2f7'
  secondary-fixed-dim: '#c1c6da'
  on-secondary-fixed: '#161b2a'
  on-secondary-fixed-variant: '#414657'
  tertiary-fixed: '#eaddff'
  tertiary-fixed-dim: '#d2bbff'
  on-tertiary-fixed: '#25005a'
  on-tertiary-fixed-variant: '#5a00c6'
  background: '#faf8ff'
  on-background: '#131b2e'
  surface-variant: '#dae2fd'
typography:
  display-lg:
    fontFamily: Montserrat
    fontSize: 48px
    fontWeight: '700'
    lineHeight: 56px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Montserrat
    fontSize: 32px
    fontWeight: '600'
    lineHeight: 40px
    letterSpacing: -0.01em
  headline-lg-mobile:
    fontFamily: Montserrat
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
  headline-md:
    fontFamily: Montserrat
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
  body-lg:
    fontFamily: Hanken Grotesk
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
  body-md:
    fontFamily: Hanken Grotesk
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  label-md:
    fontFamily: Hanken Grotesk
    fontSize: 14px
    fontWeight: '500'
    lineHeight: 20px
    letterSpacing: 0.02em
  code-sm:
    fontFamily: monospace
    fontSize: 13px
    fontWeight: '400'
    lineHeight: 18px
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  base: 4px
  xs: 4px
  sm: 8px
  md: 16px
  lg: 24px
  xl: 32px
  2xl: 48px
  3xl: 64px
  container-max: 1440px
  gutter: 24px
---

## Brand & Style
The design system is engineered for a high-performance AI ecosystem. The brand personality is **visionary, precise, and hyper-efficient**, aiming to evoke a sense of "intelligent clarity." 

The visual style is **Corporate Modern with a Tech-Futurist edge**. It utilizes a hybrid approach:
- **Minimalism:** Strategic use of whitespace and a strict adherence to grid systems to manage information density.
- **Subtle Glassmorphism:** To lean into the "AI" narrative, the system uses translucent surface overlays and background blurs (frosted glass) to represent the fluid nature of data processing.
- **Precision Engineering:** Sharp execution of micro-interactions and high-contrast typography to reinforce a professional, reliable atmosphere.

## Colors
The palette is centered around a vibrant **Aqua Cyan (#62e2d1)**, which acts as the primary "pulse" of the AI.

- **Primary:** Used for active states, primary actions, and brand highlights.
- **Secondary/Surface:** Deep obsidian and slate tones (#1a1f2e) provide a sophisticated contrast against the lighter background layers.
- **Tertiary:** A deep violet (#7c3aed) is introduced for "Intelligence" features (AI suggestions, automations).
- **Light Mode (Default):** The system has transitioned to a light-first approach, utilizing crisp white and cool gray surfaces to maintain a professional "tech" feel.
- **Dark Mode:** A high-contrast dark mode remains available, utilizing deep navy-blacks (#0f172a) to allow the primary aqua and violet accents to pop with high-energy contrast.

## Typography
This design system pairs the bold, geometric authority of **Montserrat** for brand-level communication with the ultra-clean, legible precision of **Hanken Grotesk** for functional UI.

- **Montserrat** is reserved for headlines and large display areas to convey confidence and innovation.
- **Hanken Grotesk** handles the heavy lifting of dashboards and data. Its generous x-height ensures readability in complex AI interfaces.
- **Letter Spacing:** Headlines utilize slight negative tracking for a tighter, more "designed" appearance, while labels use positive tracking for immediate scannability.

## Layout & Spacing
The system utilizes a **12-column fluid grid** for desktop and a **4-column grid** for mobile. 

- **Spacing Philosophy:** A strict 4px base unit ensures mathematical consistency.
- **Vertical Rhythm:** Components are stacked using `md` (16px) or `lg` (24px) spacing to maintain airiness.
- **Safe Zones:** Large external margins (`3xl` on desktop) prevent the layout from feeling cramped, reinforcing the "Minimalist" brand pillar.
- **Mobile Reflow:** On mobile devices, side margins compress to 16px, and all multi-column cards collapse into a single vertical stack.

## Elevation & Depth
Depth is created through **Tonal Layering** and **Glassmorphism**, which provide subtle separation in the light-first interface.

- **Surface Levels:** 
    - `Level 0`: The background (#f8fafc).
    - `Level 1`: Content cards and containers (#ffffff).
    - `Level 2`: Floating menus, modals, and tooltips.
- **Elevation in Light Mode:** Surfaces use soft, ambient shadows with low opacity and a slight neutral tint to define boundaries.
- **Glass Effect:** Modals and navigation sidebars use a `24px` backdrop-blur with a very light white tint to maintain context of the underlying data.

## Shapes
The shape language is **Rounded**, striking a balance between "Friendly Tech" and "Professional Tool."

- **Standard Radius:** 8px (`0.5rem`) for standard components like buttons and inputs.
- **Large Radius:** 16px (`1rem`) for primary cards and content containers.
- **Extra Large:** 24px (`1.5rem`) for featured promotional sections or large modal overlays.
- **Interactive States:** Buttons do not change radius on hover; instead, they use scale and color shifts to indicate interactivity.

## Components
- **Buttons:** 
    - *Primary:* Solid Aqua background (#62e2d1) with Deep Navy text (#1a1f2e). No border.
    - *Secondary:* Outlined Aqua border with transparent background and Aqua text.
    - *AI Action:* Solid Violet background (#7c3aed) to signify "Generative" or "Intelligent" features.
- **Inputs:** Clean white or light gray background with 1px borders. On focus, the border transitions to the Primary Aqua with a subtle outer glow.
- **Chips/Badges:** Small, caps-locked labels using `label-md`. Status chips use a low-opacity background of their respective status color.
- **Cards:** In light mode, use crisp white surfaces and subtle borders/shadows to define edges.
- **AI Feedback:** A dedicated "Sparkle" icon component used to denote AI-generated content or insights, always colored in the Tertiary Violet.
- **Lists:** Clean rows with 1px dividers. Hover states should use a subtle color shift rather than a shadow.