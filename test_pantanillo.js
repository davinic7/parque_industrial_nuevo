/**
 * Test completo del Parque Industrial de Catamarca
 * Cubre: sitio público, APIs, panel empresa, panel ministerio
 *
 * Uso:
 *   node test_pantanillo.js              ← servidor local (default)
 *   BASE_URL=https://... node test_pantanillo.js  ← contra producción
 *   HEADLESS=false node test_pantanillo.js        ← con ventana visible
 *
 * Credenciales por .env.test o variables de entorno:
 *   EMPRESA_EMAIL / EMPRESA_PASS
 *   MINISTERIO_EMAIL / MINISTERIO_PASS
 */

const { chromium } = require('playwright');
const path = require('path');
const fs   = require('fs');

// ── DOTENV ────────────────────────────────────────────────────────────────────
const ENV_FILE = path.join(__dirname, '.env.test');
if (fs.existsSync(ENV_FILE)) {
  fs.readFileSync(ENV_FILE, 'utf8').split('\n').forEach(line => {
    const [k, ...rest] = line.split('=');
    if (k && rest.length) process.env[k.trim()] = rest.join('=').trim();
  });
}

// ── CONFIG ────────────────────────────────────────────────────────────────────
const BASE_URL = (process.env.BASE_URL || 'http://localhost:8080').replace(/\/$/, '');

const CREDS = {
  empresa: {
    email:    process.env.EMPRESA_EMAIL    || 'empresa@demo.com',
    password: process.env.EMPRESA_PASS     || 'admin123',
  },
  ministerio: {
    email:    process.env.MINISTERIO_EMAIL || 'admin@parqueindustrial.gob.ar',
    password: process.env.MINISTERIO_PASS  || 'admin123',
  },
};

const SCREENSHOT_DIR = path.join(__dirname, 'test_screenshots');
const TIMEOUT        = 12000;  // ms por selector
const NAV_TIMEOUT    = 20000;  // ms para page.goto

// ── ESTADO GLOBAL ─────────────────────────────────────────────────────────────
let passCount = 0, failCount = 0;
const results = [];
let _page = null;

// ── HELPERS ───────────────────────────────────────────────────────────────────
function ok(label) {
  passCount++;
  results.push({ status: '✅', label });
  console.log(`  ✅ ${label}`);
}

function fail(label, err) {
  failCount++;
  const msg = err?.message?.split('\n')[0] ?? String(err);
  results.push({ status: '❌', label, error: msg });
  console.error(`  ❌ ${label}\n     ${msg}`);
}

async function shot(page, name) {
  const file = path.join(SCREENSHOT_DIR, `${name}.png`);
  await page.screenshot({ path: file, fullPage: false }).catch(() => {});
  return file;
}

async function go(page, url) {
  await page.goto(`${BASE_URL}${url}`, { timeout: NAV_TIMEOUT });
  await page.waitForLoadState('domcontentloaded');
}

async function sel(page, selector, timeout = TIMEOUT) {
  await page.waitForSelector(selector, { timeout });
}

async function check(label, fn, { retries = 0 } = {}) {
  let lastErr;
  for (let i = 0; i <= retries; i++) {
    try { await fn(); ok(label); return; }
    catch (err) {
      lastErr = err;
      if (i === retries && _page) {
        const safe = label.replace(/[^a-z0-9]/gi, '_').slice(0, 50);
        await shot(_page, `FAIL_${safe}`);
      }
      if (i < retries) await new Promise(r => setTimeout(r, 1200));
    }
  }
  fail(label, lastErr);
}

async function loginAs(page, role) {
  await go(page, '/login.php');
  await page.fill('input[name="email"]',    CREDS[role].email);
  await page.fill('input[name="password"]', CREDS[role].password);
  await page.click('button[type="submit"]');
  await page.waitForLoadState('domcontentloaded');
}

async function logout(page) {
  await go(page, '/logout.php');
}

// Verifica que una página cargue sin HTTP 500 y tenga al menos un <h1>/<h2>
async function pageOk(page, url, screenshotName) {
  await go(page, url);
  const status = await page.evaluate(() => {
    const el = document.querySelector('[data-http-status]');
    return el ? parseInt(el.dataset.httpStatus) : 200;
  });
  if (status === 500) throw new Error(`HTTP 500 en ${url}`);
  // Verifica que no haya PHP fatal visible
  const body = await page.textContent('body').catch(() => '');
  if (body.includes('Fatal error') || body.includes('Parse error')) {
    throw new Error('PHP Fatal/Parse error visible en la página');
  }
  await sel(page, 'h1, h2, .card, main, .container', 6000);
  if (screenshotName) await shot(page, screenshotName);
}

// ─────────────────────────────────────────────────────────────────────────────
// SUITE 1 — RENDIMIENTO
// ─────────────────────────────────────────────────────────────────────────────
async function suiteRendimiento(page) {
  console.log('\n⚡ RENDIMIENTO (páginas públicas)');
  const paginas = [
    { url: '/',                nombre: 'Inicio'        },
    { url: '/empresas.php',    nombre: 'Directorio'    },
    { url: '/estadisticas.php',nombre: 'Estadísticas'  },
    { url: '/mapa.php',        nombre: 'Mapa'          },
  ];
  for (const { url, nombre } of paginas) {
    await check(`${nombre} carga en < 10 s`, async () => {
      const t0 = Date.now();
      await go(page, url);
      const ms = Date.now() - t0;
      console.log(`     ⏱ ${ms} ms`);
      if (ms > 10000) throw new Error(`Tardó ${ms} ms (> 10 s)`);
    });
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// SUITE 2 — SITIO PÚBLICO
// ─────────────────────────────────────────────────────────────────────────────
async function suitePublico(page) {
  console.log('\n🌐 SITIO PÚBLICO');

  // ── Inicio ──
  await check('Inicio: carga stat-cards y navbar', async () => {
    await go(page, '/');
    await sel(page, '.stat-cards, .stat-card');
    await shot(page, '01_inicio');
  });

  await check('Inicio: navbar tiene Empresas, Mapa, Estadísticas, Noticias', async () => {
    await go(page, '/');
    const links = await page.$$eval('nav a', els => els.map(e => e.textContent.trim()));
    for (const item of ['Empresas', 'Mapa', 'Estadísticas', 'Noticias']) {
      if (!links.some(l => l.includes(item)))
        throw new Error(`Falta enlace en navbar: ${item}`);
    }
  });

  // ── Directorio de empresas ──
  await check('Empresas: directorio carga', async () => {
    await go(page, '/empresas.php');
    await sel(page, '.card, table, .empresa-card');
    await shot(page, '02_empresas');
  });

  await check('Empresas: búsqueda por texto responde sin 500', async () => {
    await go(page, '/empresas.php?q=a');
    await page.waitForLoadState('domcontentloaded');
    if (!page.url().includes('empresas')) throw new Error('Redirigió fuera de empresas');
  });

  await check('Empresas: filtro por rubro responde sin error', async () => {
    await go(page, '/empresas.php?rubro=Textil');
    await page.waitForLoadState('domcontentloaded');
    const body = await page.textContent('body').catch(() => '');
    if (body.includes('Fatal error')) throw new Error('Fatal PHP error');
  });

  // ── Ficha empresa ──
  await check('Empresa (ficha pública): primera empresa cargable', async () => {
    await go(page, '/empresas.php');
    const href = await page.$eval(
      'a[href*="empresa.php?id="], .card a[href*="id="]',
      el => el.getAttribute('href')
    ).catch(() => null);
    if (!href) { console.log('     ⚠️  Sin empresas activas — test omitido'); return; }
    await go(page, href.startsWith('http') ? new URL(href).pathname + new URL(href).search : href);
    await sel(page, 'h1, h2, .empresa-nombre');
    await shot(page, '03_empresa_ficha');
  });

  // ── Mapa ──
  await check('Mapa: contenedor Leaflet presente', async () => {
    await go(page, '/mapa.php');
    await sel(page, '#map, .leaflet-container', 15000);
    await shot(page, '04_mapa');
  }, { retries: 2 });

  // ── Páginas informativas ──
  await check('El Parque: carga contenido institucional', async () => {
    await pageOk(page, '/el-parque.php', '05_el_parque');
  });

  await check('Parque (alternativa): carga sin error', async () => {
    await pageOk(page, '/parque.php', null);
  });

  await check('Nosotros: carga sin error', async () => {
    await pageOk(page, '/nosotros.php', null);
  });

  await check('Sitemap: devuelve XML válido con <urlset>', async () => {
    const res  = await page.request.get(`${BASE_URL}/sitemap.php`);
    if (res.status() !== 200) throw new Error(`HTTP ${res.status()}`);
    const text = await res.text();
    if (!text.includes('<urlset') && !text.includes('<?xml'))
      throw new Error('La respuesta no contiene XML de sitemap');
  });

  // ── Estadísticas ──
  await check('Estadísticas: gráficos canvas presentes', async () => {
    await go(page, '/estadisticas.php');
    await sel(page, 'canvas, .chart, [id*="chart"], [id*="grafico"]');
    await page.waitForTimeout(1200);
    await shot(page, '06_estadisticas');
  });

  // ── Noticias ──
  await check('Noticias: página carga con título y buscador', async () => {
    await go(page, '/noticias.php');
    // h1 siempre presente; cuando no hay publicaciones muestra h2 "Aún no hay noticias"
    await sel(page, 'h1, h2, section');
    await sel(page, 'form input[name="buscar"]'); // buscador siempre presente
    await shot(page, '07_noticias');
  });

  await check('Noticias: filtro por tipo responde sin error', async () => {
    await go(page, '/noticias.php?tipo=empleados');
    const body = await page.textContent('body').catch(() => '');
    if (body.includes('Fatal error')) throw new Error('Fatal PHP con filtro tipo');
  });

  await check('Publicación (detalle): primera publicación cargable', async () => {
    await go(page, '/noticias.php');
    const href = await page.$eval(
      'a[href*="publicacion.php?id="], .card a[href*="publicacion"]',
      el => el.getAttribute('href')
    ).catch(() => null);
    if (!href) { console.log('     ⚠️  Sin publicaciones aprobadas — test omitido'); return; }
    await go(page, href.startsWith('http') ? new URL(href).pathname + new URL(href).search : href);
    await sel(page, 'h1, h2, article, .publicacion-titulo');
    await shot(page, '08_publicacion_detalle');
  });

  // ── Formulario público ──
  await check('Presentar proyecto: formulario visible con campos requeridos', async () => {
    await go(page, '/presentar-proyecto.php');
    await sel(page, 'form');
    await sel(page, 'input[name="nombre_empresa"], input[name="contacto"]');
    const reqs = await page.$$eval('input[required], textarea[required]', els => els.length);
    if (reqs === 0) throw new Error('Ningún campo tiene atributo required');
    await shot(page, '09_presentar_proyecto');
  });

  await check('Presentar proyecto: POST vacío no devuelve 500', async () => {
    const res = await page.request.post(`${BASE_URL}/presentar-proyecto.php`, { form: {} });
    if (res.status() === 500) throw new Error('HTTP 500 con POST vacío');
  });

  // ── Recuperar contraseña ──
  await check('Recuperar contraseña: formulario de email visible', async () => {
    await go(page, '/recuperar.php');
    await sel(page, 'input[name="email"], input[type="email"]');
  });

  // ── Login (credenciales incorrectas) ──
  await check('Login: rechaza credenciales incorrectas', async () => {
    await go(page, '/login.php');
    await page.fill('input[name="email"]',    'noexiste@test.com');
    await page.fill('input[name="password"]', 'wrongpassword123');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('domcontentloaded');
    const url      = page.url();
    const hasError = await page.$('.alert-danger, .alert-error, [class*="error"]');
    if (!url.includes('login') && !hasError)
      throw new Error('No mostró error con credenciales incorrectas');
    await shot(page, '10_login_error');
  });

  // ── API pública: lotes ──
  await check('API pública /api/lotes/listar_publico.php devuelve JSON válido', async () => {
    const res  = await page.request.get(`${BASE_URL}/api/lotes/listar_publico.php`);
    const text = await res.text();
    JSON.parse(text); // lanza si no es JSON
    if (res.status() === 500) throw new Error('HTTP 500');
  });
}

// ─────────────────────────────────────────────────────────────────────────────
// SUITE 3 — PROTECCIÓN DE RUTAS
// ─────────────────────────────────────────────────────────────────────────────
async function suiteProteccion(page, context) {
  console.log('\n🔒 PROTECCIÓN DE RUTAS (sin sesión)');
  await context.clearCookies();

  const rutas = [
    '/empresa/dashboard.php',
    '/empresa/perfil.php',
    '/empresa/formularios.php',
    '/empresa/publicaciones.php',
    '/empresa/comunicaciones.php',
    '/empresa/notificaciones.php',
    '/empresa/cambiar-contrasena.php',
    '/empresa/mis-datos.php',
    '/ministerio/dashboard.php',
    '/ministerio/empresas.php',
    '/ministerio/nueva-empresa.php',
    '/ministerio/formularios.php',
    '/ministerio/formularios-dinamicos.php',
    '/ministerio/publicaciones.php',
    '/ministerio/banners.php',
    '/ministerio/lotes.php',
    '/ministerio/solicitudes-proyecto.php',
  ];

  for (const ruta of rutas) {
    await check(`Sin sesión redirige: ${ruta}`, async () => {
      await page.goto(`${BASE_URL}${ruta}`, { timeout: NAV_TIMEOUT });
      await page.waitForLoadState('domcontentloaded');
      const finalPath = new URL(page.url()).pathname;
      if (finalPath === ruta)
        throw new Error(`Acceso permitido sin autenticación`);
    });
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// SUITE 4 — PANEL EMPRESA
// ─────────────────────────────────────────────────────────────────────────────
async function suiteEmpresa(page) {
  console.log('\n🏭 PANEL EMPRESA');

  await check('Login empresa: redirige al dashboard', async () => {
    await loginAs(page, 'empresa');
    const url = page.url();
    if (!url.includes('empresa')) throw new Error(`Redirigió a: ${url}`);
    await shot(page, '20_empresa_login');
  });

  // Dashboard
  await check('Empresa / dashboard: carga con stat-cards', async () => {
    await go(page, '/empresa/dashboard.php');
    await sel(page, '.card, .stat-card, .stat-cards');
    await shot(page, '21_empresa_dashboard');
  });

  await check('Empresa / dashboard: muestra % completitud de perfil', async () => {
    await go(page, '/empresa/dashboard.php');
    await sel(page, '[class*="progress"], [class*="completit"], [class*="percent"], .progress-bar');
  });

  // Perfil
  await check('Empresa / perfil: carga con campos editables', async () => {
    await go(page, '/empresa/perfil.php');
    await sel(page, 'input[name="nombre"], input[name="cuit"], select[name="rubro"]');
    await shot(page, '22_empresa_perfil');
  });

  await check('Empresa / perfil: edición de teléfono + guardado muestra éxito', async () => {
    await go(page, '/empresa/perfil.php');
    const tel = page.locator('input[name="telefono"]');
    await tel.clear();
    await tel.fill('3834000001');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('domcontentloaded');
    const flash = await page.$('.alert-success, .alert-info, [class*="success"]');
    if (!flash) throw new Error('No apareció confirmación de guardado');
    await shot(page, '23_empresa_perfil_guardado');
  });

  // Mis datos
  await check('Empresa / mis-datos: carga sin error', async () => {
    await pageOk(page, '/empresa/mis-datos.php', '24_empresa_mis_datos');
  });

  // Cambiar contraseña
  await check('Empresa / cambiar-contrasena: formulario presente', async () => {
    await go(page, '/empresa/cambiar-contrasena.php');
    await sel(page, 'input[type="password"]');
    await shot(page, '25_empresa_cambiar_pass');
  });

  // Formularios
  await check('Empresa / formularios: carga sin error', async () => {
    await go(page, '/empresa/formularios.php');
    await sel(page, '.card, table, .alert, h1, h2');
    await shot(page, '26_empresa_formularios');
  });

  await check('Empresa / formularios: botón nuevo formulario presente o estado visible', async () => {
    await go(page, '/empresa/formularios.php');
    const body = await page.textContent('body').catch(() => '');
    if (body.includes('Fatal error')) throw new Error('Fatal PHP error');
    // Acepta tanto "Nuevo formulario" como el listado con estado
    const hasBtn  = await page.$('a[href*="formulario"], button[class*="btn"]');
    const hasInfo = await page.$('.badge, .alert, table, .estado');
    if (!hasBtn && !hasInfo) throw new Error('Ni botón ni estado de formulario visibles');
  });

  // Formulario dinámico
  await check('Empresa / formulario_dinamico: carga sin error', async () => {
    await go(page, '/empresa/formulario_dinamico.php');
    const body = await page.textContent('body').catch(() => '');
    if (body.includes('Fatal error')) throw new Error('Fatal PHP error');
    await sel(page, 'h1, h2, form, .card, .alert');
    await shot(page, '27_empresa_formulario_dinamico');
  });

  // Formulario presentación
  await check('Empresa / formulario_presentacion: carga sin error', async () => {
    await go(page, '/empresa/formulario_presentacion.php');
    const body = await page.textContent('body').catch(() => '');
    if (body.includes('Fatal error')) throw new Error('Fatal PHP error');
    await sel(page, 'h1, h2, form, .card, .alert');
  });

  // Publicaciones
  await check('Empresa / publicaciones: listado carga', async () => {
    await go(page, '/empresa/publicaciones.php');
    await sel(page, 'h1, h2, .card, table, .alert');
    await shot(page, '28_empresa_publicaciones');
  });

  await check('Empresa / publicaciones: botón "Nueva publicación" visible', async () => {
    await go(page, '/empresa/publicaciones.php');
    const btn = await page.$('a[href*="publicacion"][class*="btn"], button:has-text("Nueva"), a:has-text("Nueva")');
    if (!btn) throw new Error('Botón Nueva publicación no encontrado');
  });

  // Galería
  await check('Empresa / galeria_api: endpoint responde sin 500', async () => {
    const res = await page.request.get(`${BASE_URL}/empresa/galeria_api.php`);
    if (res.status() === 500) throw new Error('HTTP 500');
  });

  // Notificaciones
  await check('Empresa / notificaciones: carga sin error', async () => {
    await go(page, '/empresa/notificaciones.php');
    await sel(page, 'h1, h2, .card, .list-group, .alert');
    await shot(page, '29_empresa_notificaciones');
  });

  // Comunicaciones
  await check('Empresa / comunicaciones: carga sin error', async () => {
    await go(page, '/empresa/comunicaciones.php');
    await sel(page, 'h1, h2, .card, .inbox, .alert, main');
    await shot(page, '30_empresa_comunicaciones');
  });

  // API comunicaciones badge (empresa)
  await check('API /api/comunicaciones/badge.php: JSON válido como empresa', async () => {
    const res  = await page.request.get(`${BASE_URL}/api/comunicaciones/badge.php`);
    if (res.status() === 500) throw new Error('HTTP 500');
    const text = await res.text();
    JSON.parse(text);
  });

  // Logout
  await check('Empresa / logout: sesión cerrada correctamente', async () => {
    await logout(page);
    if (page.url().includes('empresa/dashboard'))
      throw new Error('Sigue en dashboard tras logout');
    await shot(page, '31_empresa_logout');
  });
}

// ─────────────────────────────────────────────────────────────────────────────
// SUITE 5 — PANEL MINISTERIO
// ─────────────────────────────────────────────────────────────────────────────
async function suiteMinisterio(page) {
  console.log('\n🏛️  PANEL MINISTERIO');

  await check('Login ministerio: redirige al dashboard', async () => {
    await loginAs(page, 'ministerio');
    if (!page.url().includes('ministerio')) throw new Error(`Redirigió a: ${page.url()}`);
    await shot(page, '40_ministerio_login');
  });

  // Dashboard
  await check('Ministerio / dashboard: KPIs visibles (≥ 3 cards)', async () => {
    await go(page, '/ministerio/dashboard.php');
    await sel(page, '.card');
    const n = await page.$$eval('.card', els => els.length);
    if (n < 3) throw new Error(`Solo ${n} card(s)`);
    await shot(page, '41_ministerio_dashboard');
  });

  await check('Ministerio / dashboard: sección actividad reciente visible', async () => {
    await go(page, '/ministerio/dashboard.php');
    // Siempre hay un card-header "Actividad Reciente"; si no hay datos muestra <p class="text-muted">
    await sel(page, '.card-header, .empresa-timeline, p.text-muted');
  });

  // Empresas
  await check('Ministerio / empresas: lista carga', async () => {
    await go(page, '/ministerio/empresas.php');
    await sel(page, 'table, .card, .empresa-row, .alert');
    await shot(page, '42_ministerio_empresas');
  });

  await check('Ministerio / empresas: filtros de búsqueda presentes', async () => {
    await go(page, '/ministerio/empresas.php');
    await sel(page, 'input[name="q"], select[name="rubro"], select[name="estado"]');
  });

  await check('Ministerio / empresa-detalle: primera empresa cargable', async () => {
    await go(page, '/ministerio/empresas.php');
    const href = await page.$eval(
      'a[href*="empresa-detalle"]',
      el => el.getAttribute('href')
    ).catch(() => null);
    if (!href) { console.log('     ⚠️  Sin empresas — test omitido'); return; }
    await go(page, href.startsWith('/') ? href : '/ministerio/' + href);
    await sel(page, 'h1, h2, .card');
    await shot(page, '43_ministerio_empresa_detalle');
  });

  await check('Ministerio / empresa-editar: formulario editable carga', async () => {
    await go(page, '/ministerio/empresas.php');
    const href = await page.$eval(
      'a[href*="empresa-editar"]',
      el => el.getAttribute('href')
    ).catch(() => null);
    if (!href) { console.log('     ⚠️  Sin empresas — test omitido'); return; }
    await go(page, href.startsWith('/') ? href : '/ministerio/' + href);
    await sel(page, 'form, input[name="nombre"]');
    await shot(page, '44_ministerio_empresa_editar');
  });

  await check('Ministerio / empresa-metricas: carga sin error', async () => {
    await go(page, '/ministerio/empresas.php');
    const href = await page.$eval(
      'a[href*="empresa-metricas"]',
      el => el.getAttribute('href')
    ).catch(() => null);
    if (!href) { console.log('     ⚠️  Sin empresas con métricas — test omitido'); return; }
    await go(page, href.startsWith('/') ? href : '/ministerio/' + href);
    const body = await page.textContent('body').catch(() => '');
    if (body.includes('Fatal error')) throw new Error('Fatal PHP error');
    await sel(page, 'h1, h2, .card, .alert');
  });

  await check('Ministerio / nueva-empresa: formulario carga', async () => {
    await go(page, '/ministerio/nueva-empresa.php');
    await sel(page, 'form, input[name="nombre"], input[name="email"]');
    await shot(page, '45_ministerio_nueva_empresa');
  });

  // Formularios DJ
  await check('Ministerio / formularios (DJ): lista carga', async () => {
    await go(page, '/ministerio/formularios.php');
    await sel(page, 'table, .card, .alert, h1');
    await shot(page, '46_ministerio_formularios');
  });

  // Formularios dinámicos
  await check('Ministerio / formularios-dinamicos: lista carga', async () => {
    await go(page, '/ministerio/formularios-dinamicos.php');
    await sel(page, 'table, .card, .alert, h1, h2');
    await shot(page, '47_ministerio_form_dinamicos');
  });

  await check('Ministerio / formulario-nuevo: formulario de creación visible', async () => {
    await go(page, '/ministerio/formulario-nuevo.php');
    await sel(page, 'form, input[name="titulo"], textarea[name="descripcion"]');
    await shot(page, '48_ministerio_form_nuevo');
  });

  await check('Ministerio / formulario-editar: carga con id de formulario existente o sin parámetro', async () => {
    await go(page, '/ministerio/formularios-dinamicos.php');
    const href = await page.$eval(
      'a[href*="formulario-editar"]',
      el => el.getAttribute('href')
    ).catch(() => null);
    if (!href) { console.log('     ⚠️  Sin formularios dinámicos — test omitido'); return; }
    await go(page, href.startsWith('/') ? href : '/ministerio/' + href);
    const body = await page.textContent('body').catch(() => '');
    if (body.includes('Fatal error')) throw new Error('Fatal PHP error');
    await sel(page, 'h1, h2, form, .card');
    await shot(page, '49_ministerio_form_editar');
  });

  await check('Ministerio / formulario-gestion: carga sin error', async () => {
    await go(page, '/ministerio/formularios-dinamicos.php');
    const href = await page.$eval(
      'a[href*="formulario-gestion"]',
      el => el.getAttribute('href')
    ).catch(() => null);
    if (!href) { console.log('     ⚠️  Sin formularios — test omitido'); return; }
    await go(page, href.startsWith('/') ? href : '/ministerio/' + href);
    const body = await page.textContent('body').catch(() => '');
    if (body.includes('Fatal error')) throw new Error('Fatal PHP error');
    await sel(page, 'h1, h2, .card, table, .alert');
  });

  // Publicaciones
  await check('Ministerio / publicaciones: carga con tabs o lista', async () => {
    await go(page, '/ministerio/publicaciones.php');
    await sel(page, '.nav-tabs, .nav-pills, [role="tablist"], table, .card, .alert');
    await shot(page, '50_ministerio_publicaciones');
  });

  // Lotes
  await check('Ministerio / lotes: mapa y tabla cargan', async () => {
    await go(page, '/ministerio/lotes.php');
    await sel(page, '#map, .leaflet-container, table, .card', 15000);
    await shot(page, '51_ministerio_lotes');
  });

  // Comunicaciones
  await check('Ministerio / comunicaciones: carga sin error', async () => {
    await go(page, '/ministerio/comunicaciones.php');
    const body = await page.textContent('body').catch(() => '');
    if (body.includes('Fatal error')) throw new Error('Fatal PHP error');
    await sel(page, 'h1, h2, .card, .inbox, .alert, main');
    await shot(page, '52_ministerio_comunicaciones');
  });

  await check('Ministerio / mensajes-entrada: carga sin error', async () => {
    await go(page, '/ministerio/mensajes-entrada.php');
    const body = await page.textContent('body').catch(() => '');
    if (body.includes('Fatal error')) throw new Error('Fatal PHP error');
    await sel(page, 'h1, h2, .card, .alert, main');
  });

  await check('Ministerio / notificaciones: carga sin error', async () => {
    await go(page, '/ministerio/notificaciones.php');
    await sel(page, 'h1, h2, .card, form, .alert');
    await shot(page, '53_ministerio_notificaciones');
  });

  await check('Ministerio / plantillas: carga sin error', async () => {
    await pageOk(page, '/ministerio/plantillas.php', '54_ministerio_plantillas');
  });

  // Configuración del sitio
  await check('Ministerio / sitio-publico: formulario de configuración visible', async () => {
    await go(page, '/ministerio/sitio-publico.php');
    await sel(page, 'form, input, textarea');
    await shot(page, '55_ministerio_sitio_publico');
  });

  await check('Ministerio / nosotros-editar: editor visible', async () => {
    await go(page, '/ministerio/nosotros-editar.php');
    await sel(page, 'form, textarea, input');
    await shot(page, '56_ministerio_nosotros_editar');
  });

  await check('Ministerio / banners: lista o formulario visible', async () => {
    await go(page, '/ministerio/banners.php');
    await sel(page, 'table, .card, form, .alert');
    await shot(page, '57_ministerio_banners');
  });

  // Estadísticas y reportes
  await check('Ministerio / estadisticas-config: formulario visible', async () => {
    await go(page, '/ministerio/estadisticas-config.php');
    await sel(page, 'form, input, textarea, .card');
    await shot(page, '58_ministerio_estadisticas_config');
  });

  await check('Ministerio / graficos: gráficos o cards visibles', async () => {
    await go(page, '/ministerio/graficos.php');
    await sel(page, 'canvas, .chart, .card, h1');
    await page.waitForTimeout(1000);
    await shot(page, '59_ministerio_graficos');
  });

  await check('Ministerio / reporte: formulario de exportación visible', async () => {
    await go(page, '/ministerio/reporte.php');
    await sel(page, 'form, .card, h1, select, input');
    await shot(page, '60_ministerio_reporte');
  });

  await check('Ministerio / exportar: cards de descarga visibles', async () => {
    await go(page, '/ministerio/exportar.php');
    // La página usa h2 (no h1) + .card + button[name="formato"]
    await sel(page, '.card, .row.g-4, button[name="formato"], h2');
    await shot(page, '61_ministerio_exportar');
  });

  // Solicitudes
  await check('Ministerio / solicitudes-proyecto: lista carga', async () => {
    await go(page, '/ministerio/solicitudes-proyecto.php');
    await sel(page, 'table, .card, .alert, h1');
    await shot(page, '62_ministerio_solicitudes');
  });

  // API lotes (ministerio)
  await check('API /api/lotes/listar.php: JSON válido como ministerio', async () => {
    const res  = await page.request.get(`${BASE_URL}/api/lotes/listar.php`);
    if (res.status() === 500) throw new Error('HTTP 500');
    const text = await res.text();
    JSON.parse(text);
  });

  // API comunicaciones badge (ministerio)
  await check('API /api/comunicaciones/badge.php: JSON válido como ministerio', async () => {
    const res  = await page.request.get(`${BASE_URL}/api/comunicaciones/badge.php`);
    if (res.status() === 500) throw new Error('HTTP 500');
    const text = await res.text();
    JSON.parse(text);
  });

  // Logout
  await check('Ministerio / logout: sesión cerrada', async () => {
    await logout(page);
    if (page.url().includes('ministerio/dashboard'))
      throw new Error('Sigue en dashboard tras logout');
    await shot(page, '63_ministerio_logout');
  });
}

// ─────────────────────────────────────────────────────────────────────────────
// SUITE 6 — EXPORTACIÓN CSV (solo si hay datos)
// ─────────────────────────────────────────────────────────────────────────────
async function suiteExportacion(page) {
  console.log('\n📦 EXPORTACIÓN CSV');
  await loginAs(page, 'ministerio');

  await check('Exportar CSV empresas: descarga en < 15 s con contenido', async () => {
    await go(page, '/ministerio/exportar.php');
    const btnEmpresas = page.locator('form').filter({
      has: page.locator('input[value="empresas"]'),
    }).locator('button[type="submit"], button:has-text("Descargar")').first();
    if (!(await btnEmpresas.count())) {
      console.log('     ⚠️  Botón exportar empresas no encontrado — omitido');
      return;
    }
    const [download] = await Promise.all([
      page.waitForEvent('download', { timeout: 15000 }),
      btnEmpresas.click(),
    ]);
    const fname = download.suggestedFilename();
    if (!fname.endsWith('.csv')) throw new Error(`Nombre inesperado: ${fname}`);
    const stream = await download.createReadStream();
    const first  = await new Promise((res, rej) => {
      stream.once('data', d => res(d.toString()));
      stream.once('error', rej);
      setTimeout(() => res(''), 3000);
    });
    if (!first || first.trim().length < 5)
      throw new Error('CSV vacío');
    console.log(`     📄 ${fname} — ${first.slice(0, 60).replace(/\n/g, '↵')}`);
  });

  await check('Exportar CSV formularios: descarga en < 15 s', async () => {
    await go(page, '/ministerio/exportar.php');
    const btnForms = page.locator('form').filter({
      has: page.locator('input[value="formularios"]'),
    }).locator('button[type="submit"], button:has-text("Descargar")').first();
    if (!(await btnForms.count())) {
      console.log('     ⚠️  Botón exportar formularios no encontrado — omitido');
      return;
    }
    const [download] = await Promise.all([
      page.waitForEvent('download', { timeout: 15000 }),
      btnForms.click(),
    ]);
    const fname = download.suggestedFilename();
    if (!fname.endsWith('.csv')) throw new Error(`Nombre inesperado: ${fname}`);
    console.log(`     📄 ${fname}`);
  });
}

// ─────────────────────────────────────────────────────────────────────────────
// MAIN
// ─────────────────────────────────────────────────────────────────────────────
(async () => {
  if (!fs.existsSync(SCREENSHOT_DIR)) fs.mkdirSync(SCREENSHOT_DIR);

  console.log('══════════════════════════════════════════════════════════');
  console.log('  TEST SUITE — Parque Industrial de Catamarca');
  console.log(`  URL: ${BASE_URL}`);
  console.log(`  Empresa:    ${CREDS.empresa.email}`);
  console.log(`  Ministerio: ${CREDS.ministerio.email}`);
  console.log('══════════════════════════════════════════════════════════');

  const headless = process.env.HEADLESS !== 'false';
  const browser  = await chromium.launch({ headless, slowMo: headless ? 0 : 300 });
  const context  = await browser.newContext({
    viewport: { width: 1280, height: 800 },
    locale:   'es-AR',
  });
  const page = await context.newPage();
  _page = page;

  // Captura errores JS del sitio
  const jsErrors = [];
  page.on('console',  msg => {
    if (msg.type() === 'error') {
      const entry = `[${page.url().replace(/.*localhost:\d+/, '')}] ${msg.text()}`;
      jsErrors.push(entry);
    }
  });
  page.on('pageerror', err => {
    const entry = `JS: [${page.url().replace(/.*localhost:\d+/, '')}] ${err.message}`;
    jsErrors.push(entry);
  });
  // Captura 404s de red con URL
  const net404s = [];
  page.on('response', r => { if (r.status() === 404) net404s.push(r.url()); });

  try {
    await suiteRendimiento(page);
    await suitePublico(page);
    await suiteProteccion(page, context);
    await suiteEmpresa(page);
    await suiteMinisterio(page);
    await suiteExportacion(page);
  } catch (fatalErr) {
    console.error('\n💥 Error fatal:', fatalErr.message);
  } finally {
    await browser.close();
  }

  // ── RESUMEN ─────────────────────────────────────────────────────────────────
  const total = passCount + failCount;
  console.log('\n══════════════════════════════════════════════════════════');
  console.log('  RESUMEN FINAL');
  console.log('══════════════════════════════════════════════════════════');
  console.log(`  Total:    ${total}`);
  console.log(`  ✅ Pasan: ${passCount}`);
  console.log(`  ❌ Fallan: ${failCount}`);
  console.log(`  📸 Screenshots: ${SCREENSHOT_DIR}`);

  if (net404s.length) {
    const uniq = [...new Set(net404s)];
    console.log(`\n  ⚠️  Recursos 404 (${uniq.length} únicos):`);
    uniq.forEach(u => console.log(`     • ${u}`));
  }

  if (jsErrors.length) {
    const n = Math.min(jsErrors.length, 8);
    console.log(`\n  ⚠️  Errores JS en navegador (${jsErrors.length} total):`);
    jsErrors.slice(0, n).forEach(e => console.log(`     • ${e.slice(0, 120)}`));
  }

  if (failCount > 0) {
    console.log('\n  Tests fallidos:');
    results.filter(r => r.status === '❌').forEach(r => {
      console.log(`     ❌ ${r.label}`);
      if (r.error) console.log(`        ${r.error}`);
    });
  }

  console.log('══════════════════════════════════════════════════════════\n');
  process.exit(failCount > 0 ? 1 : 0);
})();
