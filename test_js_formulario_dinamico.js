/**
 * Navegador: JavaScript del formulario dinámico de la empresa (public/js/empresa-formulario-dinamico.js).
 * No se ejecuta solo: lo lanza tests/js_formulario_dinamico.php (npm run test:js-formulario), que prepara
 * la empresa y el formulario temporales y pasa FD_EMAIL, FD_PASS, FD_FORM_ID y FD_PIDS por el entorno.
 */

const { chromium } = require('playwright');

const BASE = (process.env.BASE_URL || 'http://localhost:8080').replace(/\/$/, '');
const { FD_EMAIL, FD_PASS, FD_FORM_ID } = process.env;
const P = JSON.parse(process.env.FD_PIDS || '{}');
if (!FD_EMAIL || !FD_FORM_ID || !P.texto) {
  console.error('Faltan datos: ejecutar con php tests/js_formulario_dinamico.php');
  process.exit(2);
}

let ok = 0;
const fallos = [];
async function prueba(nombre, fn) {
  try { await fn(); ok++; console.log(`  OK   ${nombre}`); }
  catch (e) { fallos.push(nombre); console.log(`  FAIL ${nombre}\n       ${String(e.message).split('\n')[0]}`); }
}
function igual(esperado, real, msg = '') {
  if (JSON.stringify(esperado) !== JSON.stringify(real)) throw new Error(`${msg}: esperado ${JSON.stringify(esperado)}, obtenido ${JSON.stringify(real)}`);
}
function verdadero(c, msg) { if (!c) throw new Error(msg); }
const COORD = /^-?\d+\.\d{8},-?\d+\.\d{8}$/;

(async () => {
  const browser = await chromium.launch({ headless: process.env.HEADLESS !== 'false' });
  const ctx = await browser.newContext();
  const page = await ctx.newPage();
  const errores = [], locales404 = [];
  page.on('pageerror', e => errores.push(e.message));
  page.on('response', r => { if (r.status() >= 400 && r.url().startsWith(BASE)) locales404.push(`${r.status()} ${r.url().replace(BASE, '')}`); });
  // Sin Internet: las teselas de los mapas no interesan
  await ctx.route('**/*', route => new URL(route.request().url()).hostname === 'localhost' ? route.continue() : route.abort());
  try {
    await page.goto(`${BASE}/login.php`);
    await page.fill('input[name="email"]', FD_EMAIL);
    await page.fill('input[name="password"]', FD_PASS);
    await Promise.all([page.waitForURL(u => !u.pathname.endsWith('/login.php'), { timeout: 15000 }), page.click('button[type="submit"]')]);
    await page.goto(`${BASE}/empresa/formulario_dinamico.php?id=${FD_FORM_ID}`, { waitUntil: 'load' });
    await page.waitForTimeout(700);

    console.log('\nFormulario dinámico (js/empresa-formulario-dinamico.js)');
    await prueba('carga sin errores y FD_CFG lista solo las preguntas con mapa, en orden', async () => {
      verdadero(errores.length === 0, `errores JS: ${errores.join(' | ')}`);
      verdadero(locales404.length === 0, `404 locales: ${locales404.join(' | ')}`);
      const cfg = await page.evaluate(() => FD_CFG);
      igual([{ pid: P.direccion, tipo: 'direccion' }, { pid: P.ubicacion, tipo: 'ubicacion' }], cfg.mapas, 'mapas');
      verdadero(typeof cfg.defLat === 'number' && typeof cfg.defLng === 'number', 'coordenadas por defecto no numéricas');
      const src = await page.locator('script[src*="empresa-formulario-dinamico.js"]').getAttribute('src');
      verdadero(/\.js\?v=\d+/.test(src), `sin versión: ${src}`);
    });

    await prueba('contador de caracteres: cuenta hacia atrás y avisa cerca del límite', async () => {
      const campo = page.locator(`input[name="campo_${P.texto}"]`);
      const restantes = campo.locator('xpath=following-sibling::div[contains(@class,"char-counter-wrap")]');
      igual('50', (await restantes.locator('.chars-remaining').textContent()).trim(), 'al inicio');
      await campo.pressSequentially('hola');
      igual('46', (await restantes.locator('.chars-remaining').textContent()).trim(), 'tras escribir 4 caracteres');
      await campo.fill('x'.repeat(40));
      verdadero((await restantes.getAttribute('class')).includes('text-warning'), 'con 10 restantes debe estar en amarillo');
      await campo.fill('x'.repeat(46));
      verdadero((await restantes.getAttribute('class')).includes('text-danger'), 'con 4 restantes debe estar en rojo');
    });

    await prueba('campo numérico: descarta letras y símbolos, conserva dígitos , . -', async () => {
      const campo = page.locator(`input[name="campo_${P.numero}"]`);
      await campo.fill('');
      await campo.pressSequentially('a1b2,3-x.4 $');
      igual('12,3-.4', await campo.inputValue(), 'valor filtrado');
      const restantes = campo.locator('xpath=following-sibling::div[contains(@class,"char-counter-wrap")]//span[contains(@class,"chars-remaining")]');
      igual(String(25 - 7), (await restantes.textContent()).trim(), 'contador');
    });

    await prueba('pregunta "dirección": mapa Leaflet; un clic fija latitud, longitud y el campo oculto', async () => {
      const mapa = page.locator(`#mapDir${P.direccion}`);
      verdadero(await mapa.locator('.leaflet-container').count() + (await mapa.evaluate(el => el.classList.contains('leaflet-container') ? 1 : 0)) > 0, 'no se creó el mapa');
      await mapa.scrollIntoViewIfNeeded();
      const caja = await mapa.boundingBox();
      await page.mouse.click(caja.x + caja.width * 0.4, caja.y + caja.height * 0.6);
      const lat = await page.inputValue(`#dir_lat_${P.direccion}`), lng = await page.inputValue(`#dir_lng_${P.direccion}`);
      verdadero(/^-?\d+\.\d{6}$/.test(lat) && /^-?\d+\.\d{6}$/.test(lng), `lat/lng con 6 decimales: ${lat} / ${lng}`);
      const oculto = await page.evaluate(id => document.getElementById('campo_' + id).value, P.direccion);
      verdadero(COORD.test(oculto), `campo oculto con 8 decimales: ${oculto}`);
      igual(`${Number(lat).toFixed(2)}`, Number(oculto.split(',')[0]).toFixed(2), 'la latitud del campo oculto coincide');
      igual(1, await mapa.locator('.leaflet-marker-icon').count(), 'marcadores');
    });

    await prueba('pregunta "dirección": "actualizar mapa" (dirActualizarMapa) mueve el marcador y el campo oculto', async () => {
      await page.fill(`#dir_lat_${P.direccion}`, '-28.470000');
      await page.fill(`#dir_lng_${P.direccion}`, '-65.780000');
      await page.click(`button[onclick="dirActualizarMapa(${P.direccion})"]`);
      igual('-28.47000000,-65.78000000', await page.evaluate(id => document.getElementById('campo_' + id).value, P.direccion), 'campo oculto');
      igual(1, await page.locator(`#mapDir${P.direccion} .leaflet-marker-icon`).count(), 'sigue habiendo un solo marcador');
    });

    await prueba('pregunta con "ubicación" en la etiqueta: un clic en el mapa escribe "lat,lng" en el campo', async () => {
      const mapa = page.locator(`#mapUbicacion${P.ubicacion}`);
      await mapa.scrollIntoViewIfNeeded();
      const caja = await mapa.boundingBox();
      await page.mouse.click(caja.x + caja.width * 0.5, caja.y + caja.height * 0.5);
      const valor = await page.inputValue(`input[name="campo_${P.ubicacion}"]`);
      verdadero(COORD.test(valor), `valor con 8 decimales: ${valor}`);
      igual(1, await mapa.locator('.leaflet-marker-icon').count(), 'marcadores');
      await page.mouse.click(caja.x + caja.width * 0.3, caja.y + caja.height * 0.3);
      igual(1, await mapa.locator('.leaflet-marker-icon').count(), 'un segundo clic reemplaza el marcador (no suma otro)');
    });

    await prueba('los valores de los mapas viajan al servidor: guardar y recargar los conserva', async () => {
      const dirAntes = await page.evaluate(id => document.getElementById('campo_' + id).value, P.direccion);
      const ubiAntes = await page.inputValue(`input[name="campo_${P.ubicacion}"]`);
      await Promise.all([page.waitForLoadState('load'), page.locator('button[name="accion"][value="guardar"], button[value="guardar"]').first().click()]);
      await page.waitForTimeout(700);
      igual(dirAntes, await page.evaluate(id => document.getElementById('campo_' + id).value, P.direccion), 'dirección tras recargar');
      igual(ubiAntes, await page.inputValue(`input[name="campo_${P.ubicacion}"]`), 'ubicación tras recargar');
      igual(dirAntes.split(',').map(Number).map(n => n.toFixed(6)), [await page.inputValue(`#dir_lat_${P.direccion}`), await page.inputValue(`#dir_lng_${P.direccion}`)], 'lat/lng reconstruidos desde el valor guardado');
      igual(1, await page.locator(`#mapDir${P.direccion} .leaflet-marker-icon`).count(), 'el marcador se dibuja con el valor guardado');
      verdadero(errores.length === 0, `errores JS tras recargar: ${errores.join(' | ')}`);
    });
  } finally {
    await browser.close();
  }
  console.log(`\nResultado: ${ok} de ${ok + fallos.length} pruebas OK\n`);
  process.exit(fallos.length ? 1 : 0);
})().catch(e => { console.error(e); process.exit(1); });
