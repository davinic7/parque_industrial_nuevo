/**
 * Comprueba en un navegador real que el sitio funciona SIN acceso a Internet (sin CDN).
 *
 *   php -S localhost:8080 -t public      (en otra terminal)
 *   node test_sin_cdn.js                 (o: npm run test:sin-cdn)
 *
 * Bloquea toda petición que no sea a localhost y, en páginas clave, verifica que cada librería
 * esté cargada y operativa (Bootstrap, Leaflet, Leaflet.draw, Chart.js, SweetAlert2, SortableJS,
 * Quill), que las tipografías locales se hayan aplicado y que no haya errores 404 locales.
 * Las teselas de los mapas (OpenStreetMap/ArcGIS) quedan bloqueadas: es lo único que requiere Internet.
 *
 * Credenciales (por defecto las de demo): EMPRESA_EMAIL / EMPRESA_PASS / MINISTERIO_EMAIL / MINISTERIO_PASS.
 * Sale con código 1 si algo falla.
 */

const { chromium } = require('playwright');

const BASE = (process.env.BASE_URL || 'http://localhost:8080').replace(/\/$/, '');
const CRED = {
  empresa:    { email: process.env.EMPRESA_EMAIL    || 'empresa@demo.com',               pass: process.env.EMPRESA_PASS    || 'admin123' },
  ministerio: { email: process.env.MINISTERIO_EMAIL || 'ministerio@catamarca.gob.ar',    pass: process.env.MINISTERIO_PASS || 'admin123' },
};

let ok = 0;
const fallos = [];
function registrar(nombre, errores) {
  if (errores.length === 0) { ok++; console.log(`  OK   ${nombre}`); }
  else { fallos.push(nombre); console.log(`  FAIL ${nombre}\n       ${errores.join('\n       ')}`); }
}

async function nuevaSesion(browser, rol) {
  const ctx = await browser.newContext();
  const externos = [];
  const errores404 = [];
  const erroresJs = [];
  // Sin Internet: todo lo que no sea del servidor local se aborta (y se anota).
  await ctx.route('**/*', route => {
    const u = new URL(route.request().url());
    if (u.hostname === 'localhost' || u.hostname === '127.0.0.1') return route.continue();
    externos.push(route.request().url());
    return route.abort();
  });
  const page = await ctx.newPage();
  page.on('response', r => { if (r.status() >= 400 && r.url().startsWith(BASE)) errores404.push(`${r.status()} ${r.url().replace(BASE, '')}`); });
  page.on('pageerror', e => erroresJs.push(e.message));
  if (rol) {
    await page.goto(`${BASE}/login.php`);
    await page.fill('input[name="email"]', CRED[rol].email);
    await page.fill('input[name="password"]', CRED[rol].pass);
    await Promise.all([page.waitForURL(u => !u.pathname.endsWith('/login.php'), { timeout: 15000 }), page.click('button[type="submit"]')]);
  }
  return { ctx, page, externos, errores404, erroresJs };
}

/** Visita una página y devuelve los problemas: 404 locales, errores JS y resultado de las comprobaciones. */
async function revisar(s, ruta, comprobaciones = {}) {
  s.errores404.length = 0; s.erroresJs.length = 0; s.externos.length = 0;
  await s.page.goto(`${BASE}${ruta}`, { waitUntil: 'load', timeout: 30000 });
  await s.page.waitForTimeout(600);
  const problemas = [];
  for (const [nombre, fn] of Object.entries(comprobaciones)) {
    let res;
    try { res = await s.page.evaluate(fn); } catch (e) { res = `error: ${e.message}`; }
    if (res !== true) problemas.push(`${nombre}: ${res === false ? 'no disponible' : res}`);
  }
  problemas.push(...s.errores404.map(e => `404 local: ${e}`));
  problemas.push(...s.erroresJs.filter(e => !/tile|openstreetmap|arcgis/i.test(e)).map(e => `error JS: ${e}`));
  return problemas;
}

const icono = `(() => { const i = document.createElement('i'); i.className = 'bi bi-house'; document.body.appendChild(i);
  const c = getComputedStyle(i, '::before').fontFamily; i.remove(); return /bootstrap-icons/.test(c); })()`;

(async () => {
  const browser = await chromium.launch({ headless: process.env.HEADLESS !== 'false' });
  try {
    console.log('\nPáginas sin acceso a Internet');

    let s = await nuevaSesion(browser, null);
    registrar('login: Bootstrap, iconos y tipografías locales', await revisar(s, '/login.php', {
      'Bootstrap CSS aplicado': () => getComputedStyle(document.documentElement).getPropertyValue('--bs-primary').trim() !== '',
      'Bootstrap Icons': new Function(`return ${icono}`),
      'Inter cargada': async () => { await document.fonts.load('500 16px "Inter"'); return document.fonts.check('500 16px "Inter"'); },
      'Montserrat cargada': async () => { await document.fonts.load('700 16px "Montserrat"'); return document.fonts.check('700 16px "Montserrat"'); },
    }));
    registrar('inicio: Bootstrap JS y Leaflet', await revisar(s, '/', {
      'window.bootstrap': () => typeof window.bootstrap === 'object' && typeof bootstrap.Dropdown === 'function',
      'window.L (Leaflet)': () => typeof window.L === 'object' && typeof L.map === 'function',
    }));
    registrar('mapa público: Leaflet crea el mapa', await revisar(s, '/mapa.php', {
      'mapa Leaflet inicializado': () => document.querySelectorAll('.leaflet-container').length > 0,
      'iconos de Leaflet (marcadores)': () => typeof L.Icon.Default === 'function',
    }));
    await s.ctx.close();

    s = await nuevaSesion(browser, 'ministerio');
    registrar('ministerio/dashboard: Bootstrap, Chart.js y Leaflet', await revisar(s, '/ministerio/dashboard.php', {
      'Bootstrap JS': () => typeof bootstrap === 'object' && typeof bootstrap.Collapse === 'function',
      'Chart.js 4': () => typeof Chart === 'function' && /^4\./.test(Chart.version),
      'Leaflet': () => typeof L === 'object',
      'Font Awesome': () => { const i = document.createElement('i'); i.className = 'fa-solid fa-house'; document.body.appendChild(i);
        const ok = /Font Awesome/.test(getComputedStyle(i, '::before').fontFamily); i.remove(); return ok; },
      'Montserrat cargada': async () => { await document.fonts.load('600 16px "Montserrat"'); return document.fonts.check('600 16px "Montserrat"'); },
    }));
    registrar('ministerio/graficos: Chart.js', await revisar(s, '/ministerio/graficos.php', {
      'Chart.js': () => typeof Chart === 'function',
    }));
    registrar('ministerio/lotes: Leaflet + Leaflet.draw', await revisar(s, '/ministerio/lotes.php', {
      'Leaflet': () => typeof L === 'object',
      'Leaflet.draw': () => typeof L.Control.Draw === 'function' && typeof L.Draw === 'object',
      'estilos de draw': () => [...document.styleSheets].some(x => (x.href || '').includes('leaflet.draw.css')),
    }));
    registrar('ministerio/sitio-publico: editor Quill', await revisar(s, '/ministerio/sitio-publico.php', {
      'Quill': () => typeof Quill === 'function',
    }));
    registrar('ministerio/comunicaciones: SortableJS + iconos', await revisar(s, '/ministerio/comunicaciones.php', {
      'Sortable': () => typeof Sortable === 'function',
      'Bootstrap Icons': new Function(`return ${icono}`),
    }));
    await s.ctx.close();

    s = await nuevaSesion(browser, 'empresa');
    registrar('empresa/dashboard: Bootstrap, Font Awesome y Roboto', await revisar(s, '/empresa/dashboard.php', {
      'Bootstrap JS': () => typeof bootstrap === 'object',
      'Roboto cargada': async () => { await document.fonts.load('500 16px "Roboto"'); return document.fonts.check('500 16px "Roboto"'); },
    }));
    registrar('empresa/mis-datos: Chart.js', await revisar(s, '/empresa/mis-datos.php', {
      'Chart.js': () => typeof Chart === 'function',
    }));
    registrar('empresa/perfil: Leaflet', await revisar(s, '/empresa/perfil.php', {
      'Leaflet': () => typeof L === 'object' && typeof L.map === 'function',
    }));
    registrar('empresa/formularios: SweetAlert2', await revisar(s, '/empresa/formularios.php', {
      'Swal': () => typeof Swal === 'function' && typeof Swal.fire === 'function',
    }));
    registrar('empresa/comunicaciones: SortableJS', await revisar(s, '/empresa/comunicaciones.php', {
      'Sortable': () => typeof Sortable === 'function',
    }));
    await s.ctx.close();
  } finally {
    await browser.close();
  }
  console.log(`\nResultado: ${ok} de ${ok + fallos.length} pruebas OK\n`);
  process.exit(fallos.length ? 1 : 0);
})().catch(e => { console.error(e); process.exit(1); });
