---
name: Parque Industrial de Catamarca
description: Portal estratégico del parque industrial — industrial moderno, territorial, institucional sin ser genérico.
colors:
  navy: "#1b3a5c"
  navy-deep: "#0f2438"
  navy-mid: "#1e4a73"
  amber: "#c4601a"
  amber-light: "#e8813a"
  warm-stone: "#f7f4f0"
  warm-stone-mid: "#ede9e3"
  warm-stone-deep: "#d9d3ca"
  warm-ink: "#1a1714"
  warm-muted: "#6b6460"
  success: "#1d7a4a"
  success-bg: "#e4f3eb"
  warning: "#b86010"
  warning-bg: "#fdf0e4"
  danger: "#c0392b"
  danger-bg: "#fdecea"
typography:
  display:
    fontFamily: "'Montserrat', system-ui, sans-serif"
    fontSize: "clamp(2rem, 5vw, 3.2rem)"
    fontWeight: 700
    lineHeight: 1.1
    letterSpacing: "-0.02em"
  headline:
    fontFamily: "'Montserrat', system-ui, sans-serif"
    fontSize: "clamp(1.4rem, 3vw, 1.9rem)"
    fontWeight: 700
    lineHeight: 1.2
  title:
    fontFamily: "'Montserrat', system-ui, sans-serif"
    fontSize: "1.1rem"
    fontWeight: 600
    lineHeight: 1.35
  body:
    fontFamily: "'Inter', 'Roboto', system-ui, sans-serif"
    fontSize: "1rem"
    fontWeight: 400
    lineHeight: 1.65
  label:
    fontFamily: "'Inter', 'Roboto', system-ui, sans-serif"
    fontSize: "0.78rem"
    fontWeight: 600
    lineHeight: 1.3
    letterSpacing: "0.06em"
rounded:
  sm: "6px"
  md: "10px"
  lg: "14px"
  pill: "999px"
spacing:
  xs: "8px"
  sm: "16px"
  md: "24px"
  lg: "40px"
  xl: "64px"
components:
  button-primary:
    backgroundColor: "{colors.amber}"
    textColor: "#ffffff"
    rounded: "{rounded.sm}"
    padding: "10px 24px"
  button-primary-hover:
    backgroundColor: "{colors.amber-light}"
    textColor: "#ffffff"
  button-navy:
    backgroundColor: "{colors.navy}"
    textColor: "#ffffff"
    rounded: "{rounded.sm}"
    padding: "10px 24px"
  button-ghost:
    backgroundColor: "transparent"
    textColor: "{colors.navy}"
    rounded: "{rounded.sm}"
    padding: "9px 23px"
  card-public:
    backgroundColor: "#ffffff"
    rounded: "{rounded.lg}"
  card-dashboard:
    backgroundColor: "#ffffff"
    rounded: "16px"
---

# Design System: Parque Industrial de Catamarca

## 1. Overview

**Creative North Star: "La Planta"**

Una planta industrial tiene estructura, propósito y escala. Sus elementos son funcionales antes de ser decorativos. El acero, el hormigón, el herrumbre controlado — tienen peso, textura, carácter. Este sistema lleva ese espíritu a la pantalla: superficies cálidas en lugar de blancas puras, tipografía con masa, color comprometido en lugar de decorativo. No es una web de municipio pintada de azul. Es el portal de un parque que compite por inversión.

Para el sitio público, el diseño es la comunicación: transmite solidez institucional y ambición territorial en los primeros tres segundos. Para los dashboards, el diseño sirve al trabajo: datos claros, estados visibles, sin ruido decorativo.

Lo que este sistema rechaza explícitamente: el genérico institucional argentino (azul eléctrico + blanco puro + tablas sin estilo), las stat tiles de SaaS (número grande + degradado + etiqueta pequeña), el glassmorphism, los gradient text, las side-stripe borders de colores en cards.

**Key Characteristics:**
- Superficies cálidas: off-white piedra (`#f7f4f0`) en lugar de grises puros Bootstrap
- Azul profundo (`#1b3a5c`) como ancla primaria — marino, no eléctrico
- Ámbar terracota (`#c4601a`) como acento comprometido — el color de la tierra de Catamarca
- Montserrat para display/headings con tracking negativo en tamaños grandes
- Sin side-stripe borders, sin gradient text, sin glassmorphism
- Elevación mínima: sombras suaves, no estructurales

## 2. Colors: La Paleta Industrial

La paleta tiene dos fuerzas: el azul profundo del hierro y el ámbar del desierto catamarqueño. Los neutros tienen temperatura cálida (no grises Bootstrap puros).

### Primary
- **Navy Profundo** (`#1b3a5c`): Color base de la interfaz. Navbar, sidebar, headings de sección en sitio público. Ocupa el 40-50% del hero/navegación en el sitio público.
- **Navy Oscuro** (`#0f2438`): Hover states, sidebar gradient, shadows con hue. El fondo real del sidebar.
- **Navy Medio** (`#1e4a73`): Estado hover en navbar y sidebar, fondo de highlight suave.

### Secondary (accent comprometido)
- **Ámbar Terracota** (`#c4601a`): El acento funcional y territorial. CTAs primarios, sección dividers, badges de rubro. No decorativo, siempre con propósito.
- **Ámbar Claro** (`#e8813a`): Hover del ámbar. También como tint de fondo en hover states de cards de acción.

### Neutral
- **Warm Stone** (`#f7f4f0`): Fondo de todo el sitio. Reemplaza completamente `#f8f9fa`. Tiene temperatura, no es blanco frío.
- **Warm Stone Mid** (`#ede9e3`): Bordes suaves, separadores, fondo de inputs.
- **Warm Stone Deep** (`#d9d3ca`): Bordes más definidos, líneas divisorias en tablas.
- **Warm Ink** (`#1a1714`): Texto principal. No es negro puro — tiene temperatura.
- **Warm Muted** (`#6b6460`): Texto secundario, labels, metadatos.

### Status Colors
- **Success** `#1d7a4a` / bg `#e4f3eb`: Estados activos, aprobados, "al día".
- **Warning** `#b86010` / bg `#fdf0e4`: Pendientes, borradores, alertas suaves.
- **Danger** `#c0392b` / bg `#fdecea`: Rechazados, errores críticos.

### Named Rules
**La Regla del Ámbar.** El ámbar es el único acento cálido permitido. Aparece en CTAs, badges de acción y dividers. No se usa para decorar — se usa para llamar la atención. Si algo no merece atención del usuario, no merece ámbar.

**La Regla del Blanco Puro.** `#ffffff` está permitido solo como fondo de cards sobre superficies de color. El fondo de página es siempre `#f7f4f0`. El texto nunca es `#000000`.

## 3. Typography

**Display/Heading Font:** Montserrat (con `system-ui, sans-serif` fallback)
**Body Font:** Inter (con `Roboto, system-ui, sans-serif` fallback)

**Character:** Montserrat tiene la geometría y el peso para representar a una institución industrial — sin ser militar ni frío. Inter es legible a densidades de datos medianas, mejor que Roboto para dashboards.

### Hierarchy
- **Display** (700, `clamp(2rem, 5vw, 3.2rem)`, `lh 1.1`, `ls -0.02em`): Solo para el hero del sitio público. Una sola pieza de texto en toda la página puede ser Display.
- **Headline** (700, `clamp(1.4rem, 3vw, 1.9rem)`, `lh 1.2`): Títulos de sección principales en el sitio público. En dashboards: título de página.
- **Title** (600, `1.1rem`, `lh 1.35`): Nombres de empresa en cards, headers de sección dentro de dashboards, card headers.
- **Body** (400, `1rem`, `lh 1.65`): Texto descriptivo. Límite de longitud: 65–72ch.
- **Label** (600, `0.78rem`, `lh 1.3`, `ls 0.06em`): Metadatos, badges de rubro, section titles en sidebar, table headers.

### Named Rules
**La Regla del Tracking.** Headings Display/Headline usan `letter-spacing: -0.02em`. Labels usan `letter-spacing: 0.06em`. El cuerpo de texto va a `0`. El tracking negativo en headings grandes da densidad industrial sin necesitar serifa.

## 4. Elevation

Sistema plano con sombras ambientales tenues. Las superficies no compiten por altura — están en el mismo plano. La profundidad se comunica por color de fondo (card blanca sobre stone) y sombras difusas, no por sombras estructurales oscuras.

### Shadow Vocabulary
- **Ambient** (`0 2px 8px oklch(25% 0.03 235 / 0.08)`): Cards en reposo. Apenas perceptible.
- **Hover** (`0 6px 24px oklch(25% 0.03 235 / 0.13)`): Cards en hover, elementos elevados.
- **Dashboard Card** (`0 4px 24px rgba(14, 58, 82, 0.08)`): Cards de dashboard — sombra con hue del navy.
- **Topbar** (`0 1px 0 rgba(14, 58, 82, 0.06), 0 4px 20px rgba(14, 58, 82, 0.04)`): Línea sutil + halo. Separa topbar del contenido.

### Named Rules
**La Regla del Plano Único.** Las surfaces son planas en reposo. Las sombras aparecen como respuesta al hover, no como decoración del estado base. Si cada card tiene sombra grande en reposo, ninguna se destaca.

**La Regla de No Estratificar.** Sin cards dentro de cards con sombra. Sin modales sobre drawers. Sin containers nested con su propio elevation.

## 5. Components

### Buttons

**El botón tiene forma clara y personalidad de acento.**

- **Primario (amber):** Fondo `#c4601a`, texto blanco, radius 6px, padding `10px 24px`, font-weight 600. Hover: `#e8813a` + translate(-1px). El CTA del sitio público.
- **Navy:** Fondo `#1b3a5c`, texto blanco, mismo shape. Para acciones de navegación principales en dashboards.
- **Ghost:** Borde `1px solid #1b3a5c`, texto navy, fondo transparente. Hover: fondo `#1b3a5c` + texto blanco. Para acciones secundarias.
- **Sin pill en botones de acción.** El pill (`border-radius: 999px`) queda reservado para chips/badges de rubro. Los botones de acción usan radius 6px.
- Excepción: el botón "Ingresar" del navbar puede ser pill por su posición aislada.

### Chips / Badges de Rubro

- Fondo `#c4601a` (ámbar), texto blanco, radius 999px, padding `3px 12px`, font Label (0.78rem, 600, ls 0.06em).
- Variante secundaria (rubro faltante): fondo `#ede9e3`, texto `#6b6460`, mismo radius.

### Cards — Sitio Público

- Background `#ffffff`, radius 14px, sombra Ambient en reposo, sombra Hover al hover.
- Sin border-left de acento. Sin border de cualquier tipo en la mayoría de los casos.
- Para estado: cambio de background-tint, no stripe lateral.
- Empresa cards: logo en fondo `#f7f4f0` (no gris genérico), body con jerarquía clara nombre → rubro badge → descripción.

### Cards — Dashboard

- Background `#ffffff`, radius 16px, sombra Dashboard Card.
- Card-header: fondo transparente, borde inferior `1px solid rgba(14, 58, 82, 0.06)`, texto navy-dark, font-weight 600.
- `.dashboard-card` status variants: NO usar `border-left` de colores. Usar background-tint en el header o un dot indicator junto al label.

### Stat Tiles (Dashboard empresa)

- Sin gradiente. Sin círculo con ícono.
- El número es el elemento prominente: 1.6rem, font-weight 700, color contextual.
- El label es Label typography debajo del número.
- Sin sombra heroica.

### Inputs / Fields

- Fondo `#fff`, borde `1px solid #d9d3ca`, radius 6px, padding `10px 14px`.
- Focus: borde `#1b3a5c`, box-shadow `0 0 0 3px rgba(27, 58, 92, 0.12)`.
- Error: borde `#c0392b`, box-shadow `0 0 0 3px rgba(192, 57, 43, 0.1)`.

### Navigation — Sitio Público

- Fondo sólido `#1b3a5c` (sin gradiente lineal). 
- Brand: texto blanco, font Montserrat 700.
- Nav links: texto `rgba(255,255,255,0.8)`, hover: blanco, fondo `rgba(255,255,255,0.08)`.
- Active state: fondo `rgba(255,255,255,0.12)`, texto blanco. Sin bottom border de colores.
- "Ingresar": fondo ámbar `#c4601a`, pill shape, texto blanco. Hover: `#e8813a`.

### Navigation — Sidebar Dashboard

- Fondo gradiente `#0f2438` → `#0d2235` (oscuro consistente).
- Nav links: sin `border-left` de acento. Active state: fondo `rgba(255,255,255,0.10)`, texto blanco. Sin stripe lateral.
- Section dividers: label en `rgba(255,255,255,0.38)`, uppercase, Label typography.

### Hero — Sitio Público

- Fondo imagen + overlay navy (sin diagonal gradient, overlay lineal simple `rgba(27,58,92,0.82)`).
- Texto: Display weight para título, body para subtítulo, máximo 2 líneas.
- Subtitle chip: fondo `rgba(255,255,255,0.15)`, backdrop-blur suave, texto blanco.
- Sin texto con text-shadow decorativo.

## 6. Do's and Don'ts

### Do:
- **Do** usar `#f7f4f0` como fondo de página en todo el sitio, nunca `#f8f9fa` ni `#ffffff` directo en body.
- **Do** comunicar estados de cards con background-tint del color de estado — fondo `#e4f3eb` para activo, `#fdf0e4` para pendiente.
- **Do** usar Montserrat con `letter-spacing: -0.02em` en títulos grandes (≥1.4rem).
- **Do** usar ámbar (`#c4601a`) solo para llamadas a la acción y badges de rubro.
- **Do** acompañar el ámbar con texto blanco siempre (contraste suficiente).
- **Do** usar radius 6px en botones de acción y 999px solo en chips/badges de categoría.
- **Do** hacer que el color del nav link active sea fondo tint, no una raya de acento lateral.
- **Do** mantener el sidebar de dashboards con fondo `#0f2438` sólido sin gradient lateral.

### Don't:
- **Don't** usar `border-left` mayor a 1px como acento de color en cards, list items o alerts. Reemplazar siempre con background-tint o sin acento.
- **Don't** usar gradient text (`background-clip: text`). Prohibido absolutamente.
- **Don't** usar iconos en círculos con gradiente en las stat cards del home. Es el patrón SaaS más sobreusado.
- **Don't** usar `linear-gradient(135deg, ...)` en la navbar — fondo sólido navy siempre.
- **Don't** usar `#ffffff` como fondo de página ni `#000000` como texto.
- **Don't** imitar la "web de municipio viejo": sin Arial genérico, sin colores institucionales chillones (azul eléctrico #0066ff ni rojo #ff0000), sin tablas sin estilo, sin banners con degradados horizontales azul-a-verde.
- **Don't** usar glassmorphism como estética por defecto. El blur de backdrop solo en overlays transitorios (modales, dropdowns flotantes).
- **Don't** anidar cards con sombra dentro de cards con sombra.
- **Don't** usar el mismo padding en todos los elementos — la variación de espaciado crea ritmo visual.
- **Don't** centrar todos los headings de sección. El centrado es para elementos únicos (hero title). Las secciones con contenido alineado a la izquierda tienen headings a la izquierda.
