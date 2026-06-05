---
name: kodanIAHUB DARK
colors:
  surface: '#0b1326'
  surface-dim: '#0b1326'
  surface-bright: '#31394d'
  surface-container-lowest: '#060e20'
  surface-container-low: '#131b2e'
  surface-container: '#171f33'
  surface-container-high: '#222a3d'
  surface-container-highest: '#2d3449'
  on-surface: '#dae2fd'
  on-surface-variant: '#bbc9c6'
  inverse-surface: '#dae2fd'
  inverse-on-surface: '#283044'
  outline: '#869490'
  outline-variant: '#3c4947'
  surface-tint: '#59daca'
  primary: '#81ffed'
  on-primary: '#003731'
  primary-container: '#62e2d1'
  on-primary-container: '#006359'
  inverse-primary: '#006a60'
  secondary: '#c1c6da'
  on-secondary: '#2b3040'
  secondary-container: '#414657'
  on-secondary-container: '#b0b4c8'
  tertiary: '#f1e5ff'
  on-tertiary: '#3f008e'
  tertiary-container: '#d9c4ff'
  on-tertiary-container: '#6c22dd'
  error: '#ffb4ab'
  on-error: '#690005'
  error-container: '#93000a'
  on-error-container: '#ffdad6'
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
  background: '#0b1326'
  on-background: '#dae2fd'
  surface-variant: '#2d3449'
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
- **Minimalism:** Massive use of whitespace (optimized for dark mode) and a strict adherence to grid systems to manage information density.
- **Subtle Glassmorphism:** To lean into the "AI" narrative, the system uses translucent surface overlays and background blurs (frosted glass) to represent the fluid nature of data processing.
- **Precision Engineering:** Sharp execution of micro-interactions and high-contrast typography to reinforce a professional, reliable atmosphere.

## Colors
The palette is centered around a vibrant **Aqua Cyan (#62e2d1)**, which acts as the primary "pulse" of the AI.

- **Primary:** Used for active states, primary actions, and brand highlights.
- **Secondary/Surface:** Deep obsidian and slate tones (#1a1f2e) provide a sophisticated backdrop that makes the primary aqua glow.
- **Tertiary:** A deep violet (#7c3aed) is introduced for "Intelligence" features (AI suggestions, automations).
- **Dark Mode (Default):** The system has transitioned to a dark-first approach, providing a high-performance environment. It utilizes deep navy-blacks (#0f172a) to avoid pure black clipping while allowing the primary aqua to pop with high-energy contrast.
- **Light Mode:** A high-contrast light mode remains available, utilizing cool grays and crisp white surfaces to maintain a professional "tech" feel.

## Typography
This design system pairs the bold, geometric authority of **Montserrat** for brand-level communication with the ultra-clean, legible precision of **Hanken Grotesk** for functional UI.

- **Montserrat** is reserved for headlines and large display areas to convey confidence and innovation.
- **Hanken Grotesk** handles the heavy lifting of dashboards and data. Its generous x-height ensures readability in complex AI interfaces against dark backgrounds.
- **Letter Spacing:** Headlines utilize slight negative tracking for a tighter, more "designed" appearance, while labels use positive tracking for immediate scannability.

## Layout & Spacing
The system utilizes a **12-column fluid grid** for desktop and a **4-column grid** for mobile. 

- **Spacing Philosophy:** A strict 4px base unit ensures mathematical consistency.
- **Vertical Rhythm:** Components are stacked using `md` (16px) or `lg` (24px) spacing to maintain airiness.
- **Safe Zones:** Large external margins (`3xl` on desktop) prevent the layout from feeling cramped, reinforcing the "Minimalist" brand pillar.
- **Mobile Reflow:** On mobile devices, side margins compress to 16px, and all multi-column cards collapse into a single vertical stack.

## Elevation & Depth
Depth is created through **Tonal Layering** and **Glassmorphism**, rather than heavy shadows which are less effective on dark backgrounds.

- **Surface Levels:** 
    - `Level 0`: The background (#0f172a).
    - `Level 1`: Content cards and containers (#1a1f2e).
    - `Level 2`: Floating menus, modals, and tooltips.
- **Depth in Dark Mode:** Surfaces use subtle inner borders and tonal variations to define boundaries without clutter.
- **Glass Effect:** Modals and navigation sidebars use a `24px` backdrop-blur with a `10%` navy tint to maintain context of the underlying data.

## Shapes
The shape language is **Rounded**, striking a balance between "Friendly Tech" and "Professional Tool."

- **Standard Radius:** 8px (`0.5rem`) for standard components like buttons and inputs.
- **Large Radius:** 16px (`1rem`) for primary cards and content containers.
- **Extra Large:** 24px (`1.5rem`) for featured promotional sections or large modal overlays.
- **Interactive States:** Buttons do not change radius on hover; instead, they use scale and color shifts to indicate interactivity.

## Components
- **Buttons:** 
    - *Primary:* Solid Aqua background (#62e2d1) with Dark Navy text (#0f172a). No border.
    - *Secondary:* Outlined Aqua border with transparent background and Aqua text.
    - *AI Action:* Solid Violet background (#7c3aed) to signify "Generative" or "Intelligent" features.
- **Inputs:** Darker background with 1px borders. On focus, the border transitions to the Primary Aqua with a subtle outer glow.
- **Chips/Badges:** Small, caps-locked labels using `label-md`. Status chips use a low-opacity background of their respective status color.
- **Cards:** In dark mode, use slightly lighter surface colors (#1a1f2e) and subtle top-edge highlights to define edges.
- **AI Feedback:** A dedicated "Sparkle" icon component used to denote AI-generated content or insights, always colored in the Tertiary Violet.
- **Lists:** Clean rows with 1px dividers. Hover states should use a subtle color shift rather than a shadow.