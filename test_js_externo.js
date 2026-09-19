/**
 * Comprueba en un navegador real que el JavaScript movido a public/js/ sigue funcionando
 * (perfil de empresa, lotes, gestión del sitio público y gestión de formularios).
 *
 *   php -S localhost:8080 -t public      (en otra terminal)
 *   node test_js_externo.js              (o: npm run test:js-externo)
 *
 * Solo hace interacciones que NO guardan nada. Usa las cuentas demo (EMPRESA_EMAIL / EMPRESA_PASS /
 * MINISTERIO_EMAIL / MINISTERIO_PASS) y el formulario FORM_ID (por defecto el primero).
 * Sale con código 1 si algo falla.
 */

const { chromium } = require('playwright');

const BASE = (process.env.BASE_URL || 'http://localhost:8080').replace(/\/$/, '');
const CRED = {
  empresa:    { email: process.env.EMPRESA_EMAIL    || 'empresa@demo.com',            pass: process.env.EMPRESA_PASS    || 'admin123' },
  ministerio: { email: process.env.MINISTERIO_EMAIL || 'ministerio@catamarca.gob.ar', pass: process.env.MINISTERIO_PASS || 'admin123' },
};
const FORM_ID = process.env.FORM_ID || '1';

let ok = 0, omitidas = 0;
const fallos = [];
/** Una prueba lanza new Omitir('motivo') cuando la base no tiene los datos necesarios: se informa, no pasa en silencio. */
class Omitir extends Error {}
async function prueba(nombre, fn) {
  try { await fn(); ok++; console.log(`  OK   ${nombre}`); }
  catch (e) {
    if (e instanceof Omitir) { omitidas++; console.log(`  OMITIDA ${nombre}\n       ${e.message}`); return; }
    fallos.push(nombre); console.log(`  FAIL ${nombre}\n       ${String(e.message).split('\n')[0]}`);
  }
}
function igual(esperado, real, msg = '') {
  if (JSON.stringify(esperado) !== JSON.stringify(real)) throw new Error(`${msg}: esperado ${JSON.stringify(esperado)}, obtenido ${JSON.stringify(real)}`);
}
function verdadero(c, msg) { if (!c) throw new Error(msg); }

async function sesion(browser, rol) {
  const ctx = await browser.newContext({ acceptDownloads: true });
  const page = await ctx.newPage();
  const errores = [];
  const locales404 = [];
  page.on('pageerror', e => errores.push(e.message));
  page.on('response', r => { if (r.status() >= 400 && r.url().startsWith(BASE)) locales404.push(`${r.status()} ${r.url().replace(BASE, '')}`); });
  await page.goto(`${BASE}/login.php`);
  await page.fill('input[name="email"]', CRED[rol].email);
  await page.fill('input[name="password"]', CRED[rol].pass);
  await Promise.all([page.waitForURL(u => !u.pathname.endsWith('/login.php'), { timeout: 15000 }), page.click('button[type="submit"]')]);
  return { ctx, page, errores, locales404 };
}
async function ir(s, ruta) {
  s.errores.length = 0; s.locales404.length = 0;
  await s.page.goto(`${BASE}${ruta}`, { waitUntil: 'load' });
  await s.page.waitForTimeout(500);
}
function sinErrores(s) {
  verdadero(s.errores.length === 0, `errores JS: ${s.errores.join(' | ')}`);
  verdadero(s.locales404.length === 0, `404 locales: ${s.locales404.join(' | ')}`);
}

(async () => {
  const browser = await chromium.launch({ headless: process.env.HEADLESS !== 'false' });
  try {
    // ── Empresa: perfil ───────────────────────────────────────────────────────
    let s = await sesion(browser, 'empresa');
    console.log('\nPerfil de empresa (js/empresa-perfil.js)');
    await ir(s, '/empresa/perfil.php');
    await prueba('carga sin errores y el JS externo se ejecutó (__CFG + mapa)', async () => {
      sinErrores(s);
      verdadero(await s.page.evaluate(() => typeof __CFG === 'object' && __CFG.csrfName === 'csrf_token' && !!__CFG.csrfVal), '__CFG no definido');
      verdadero(await s.page.locator('.leaflet-container').count() > 0, 'no se creó el mapa');
      const src = await s.page.locator('script[src*="empresa-perfil.js"]').getAttribute('src');
      verdadero(/empresa-perfil\.js\?v=\d+/.test(src), `sin versión en la URL: ${src}`);
    });
    await prueba('el formato de CUIT se aplica al escribir', async () => {
      await s.page.fill('#inputCuit', '');
      await s.page.locator('#inputCuit').pressSequentially('20304050607');
      igual('20-30405060-7', await s.page.inputValue('#inputCuit'), 'CUIT');
    });
    await prueba('la vista previa del logo responde a un archivo elegido', async () => {
      await s.page.setInputFiles('#logoFileInput', { name: 'logo-prueba.png', mimeType: 'image/png',
        buffer: Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==', 'base64') });
      await s.page.waitForFunction(() => (document.getElementById('logoPreview').src || '').startsWith('data:image/png'), null, { timeout: 5000 });
      igual('logo-prueba.png', (await s.page.textContent('#logoFileName')).trim(), 'nombre mostrado');
    });
    console.log('\nComunicaciones (js/comunicaciones-panel.js, js/comunicaciones-categorias.js, css/comunicaciones-panel.css)');
    await ir(s, '/empresa/comunicaciones.php');
    await prueba('empresa: el panel carga sin errores, con estilos, lista de conversaciones y categorías reordenables', async () => {
      sinErrores(s);
      verdadero(await s.page.evaluate(() => getComputedStyle(document.getElementById('coms-shell')).display === 'grid'), 'no se aplicó el CSS del panel (coms-shell no es grid)');
      await s.page.waitForFunction(() => document.querySelectorAll('#coms-list-items .conv-item').length > 0 || /No hay|vac/i.test(document.getElementById('coms-list-items').textContent), null, { timeout: 8000 });
      verdadero(await s.page.evaluate(() => !!document.getElementById('coms-cat-sortable')._sortable), 'las categorías no son reordenables (Sortable no se inicializó)');
    });
    await prueba('empresa: abrir una conversación muestra su hilo y el editor', async () => {
      const item = s.page.locator('#coms-list-items .conv-item').first();
      if (await item.count() === 0) throw new Omitir('la empresa de la cuenta demo no tiene conversaciones');
      const titulo = (await item.locator('.conv-title').textContent()).trim();
      await item.click();
      await s.page.waitForFunction(() => document.querySelectorAll('#coms-thread-msgs .coms-msg').length > 0, null, { timeout: 8000 });
      verdadero((await s.page.textContent('#coms-thread-title')).includes(titulo.slice(0, 10)), 'el título del hilo no coincide con la conversación elegida');
      verdadero(await s.page.locator('#coms-editor-text').count() === 1, 'no está el editor');
    });

    console.log('\nDeclaraciones de datos (js/empresa-formularios.js, css/empresa-formularios.css)');
    await ir(s, '/empresa/formularios.php');
    await prueba('carga sin errores y los estilos y el JS externos se aplican', async () => {
      sinErrores(s);
      verdadero(await s.page.locator('link[href*="empresa-formularios.css"]').count() === 1, 'falta la hoja de estilos');
      verdadero(await s.page.locator('script[src*="empresa-formularios.js"]').count() === 1, 'falta el script');
      verdadero(await s.page.evaluate(() => typeof Swal === 'function'), 'SweetAlert2 no está cargado');
    });
    await prueba('"Guardar borrador" pide confirmación con SweetAlert2 y "Cancelar" no envía el formulario', async () => {
      const boton = s.page.locator('#djBtnGuardar');
      if (await boton.count() === 0) throw new Omitir('la declaración está en modo consulta (sin botones de edición)');
      const antes = s.page.url();
      await boton.click();
      await s.page.waitForSelector('.swal2-popup', { timeout: 5000 });
      verdadero((await s.page.textContent('.swal2-title')).includes('Guardar borrador'), 'título del aviso inesperado');
      await s.page.click('.swal2-cancel');
      await s.page.waitForSelector('.swal2-popup', { state: 'detached', timeout: 5000 });
      await s.page.waitForTimeout(400);
      igual(antes, s.page.url(), 'la página cambió tras cancelar');
    });
    await s.ctx.close();

    // ── Ministerio ────────────────────────────────────────────────────────────
    s = await sesion(browser, 'ministerio');

    console.log('\nLotes (js/ministerio-lotes.js)');
    await ir(s, '/ministerio/lotes.php');
    await prueba('carga sin errores; LOTES_CFG y la tabla coinciden', async () => {
      sinErrores(s);
      const cfg = await s.page.evaluate(() => ({ api: LOTES_CFG.apiBase, csrf: !!LOTES_CFG.csrf, n: LOTES_CFG.lotes.length }));
      verdadero(/\/api\/lotes\/$/.test(cfg.api) && cfg.csrf, `configuración inválida: ${JSON.stringify(cfg)}`);
      igual(cfg.n, await s.page.locator('#tablaLotes tbody tr[data-id]').count(), 'filas en la tabla vs lotes en la configuración');
      verdadero(await s.page.locator('.leaflet-container').count() > 0, 'no se creó el mapa');
    });
    await prueba('"Nuevo lote" abre el modal y resetea el formulario', async () => {
      await s.page.click('#btnNuevoLote');
      await s.page.waitForSelector('#modalLote.show', { timeout: 5000 });
      igual('', await s.page.inputValue('#loteNumero'), 'número de lote');
      await s.page.click('#modalLote [data-bs-dismiss="modal"]');
      await s.page.waitForSelector('#modalLote', { state: 'hidden', timeout: 5000 });   // esperar a que termine de cerrarse
    });
    await prueba('"Exportar CSV" descarga lotes.csv con BOM y los lotes de la configuración', async () => {
      const [dl] = await Promise.all([s.page.waitForEvent('download', { timeout: 10000 }), s.page.click('#btnExportCsv')]);
      igual('lotes.csv', dl.suggestedFilename(), 'nombre de archivo');
      const fs = require('fs');
      const contenido = fs.readFileSync(await dl.path(), 'utf8');
      verdadero(contenido.charCodeAt(0) === 0xFEFF, 'falta el BOM UTF-8 (Excel mostraría mal los acentos)');
      const primero = await s.page.evaluate(() => LOTES_CFG.lotes[0] && LOTES_CFG.lotes[0].numero_lote);
      if (primero) verdadero(contenido.includes(String(primero)), `el CSV no contiene el lote ${primero}`);
    });

    console.log('\nComunicaciones del Ministerio');
    await ir(s, '/ministerio/comunicaciones.php');
    await prueba('ministerio: el panel carga sin errores, con estilos y categorías reordenables', async () => {
      sinErrores(s);
      verdadero(await s.page.evaluate(() => getComputedStyle(document.getElementById('coms-shell')).display === 'grid'), 'no se aplicó el CSS del panel');
      await s.page.waitForFunction(() => document.querySelectorAll('#coms-list-items .conv-item').length > 0 || /No hay|vac/i.test(document.getElementById('coms-list-items').textContent), null, { timeout: 8000 });
      verdadero(await s.page.evaluate(() => !!document.getElementById('coms-cat-sortable')._sortable), 'Sortable no se inicializó');
    });
    console.log('\nSitio público (js/ministerio-sitio-publico.js)');
    await ir(s, '/ministerio/sitio-publico.php?tab=el_parque');
    let cantidad = 0;
    await prueba('pestaña "El parque": Quill y la lista de servicios se inicializan desde SITIO_CFG', async () => {
      sinErrores(s);
      const cfg = await s.page.evaluate(() => ({ tab: SITIO_CFG.tab, n: SITIO_CFG.servicios.length }));
      igual('el_parque', cfg.tab, 'pestaña');
      verdadero(await s.page.locator('#quill-editor .ql-editor').count() === 1, 'no se creó el editor Quill');
      cantidad = await s.page.locator('#servicios-lista .servicio-item').count();
      igual(cfg.n, cantidad, 'servicios dibujados vs configuración');
    });
    await prueba('agregar / mover / eliminar un servicio actualiza la lista y el campo oculto (sin guardar)', async () => {
      await s.page.fill('#new-svc-titulo', 'zz servicio prueba');
      await s.page.fill('#new-svc-desc', 'descripción de prueba');
      await s.page.click('button[onclick="agregarServicio()"]');
      igual(cantidad + 1, await s.page.locator('#servicios-lista .servicio-item').count(), 'tras agregar');
      let oculto = JSON.parse(await s.page.inputValue('#nosotros_servicios_hidden'));
      igual('zz servicio prueba', oculto[oculto.length - 1].titulo, 'último servicio en el campo oculto');
      await s.page.evaluate(i => moverServicio(i, -1), cantidad);
      oculto = JSON.parse(await s.page.inputValue('#nosotros_servicios_hidden'));
      igual('zz servicio prueba', oculto[cantidad - 1].titulo, 'tras subirlo una posición');
      s.page.once('dialog', d => d.accept());
      await s.page.evaluate(i => eliminarServicio(i), cantidad - 1);
      igual(cantidad, await s.page.locator('#servicios-lista .servicio-item').count(), 'tras eliminar');
    });
    await prueba('al enviar, el HTML del editor Quill pasa al campo oculto', async () => {
      await s.page.click('#quill-editor .ql-editor');
      await s.page.keyboard.type('Texto de prueba');
      await s.page.evaluate(() => document.getElementById('form-el-parque').dispatchEvent(new Event('submit', { cancelable: true })));
      verdadero((await s.page.inputValue('#nosotros_texto_hidden')).includes('Texto de prueba'), 'el campo oculto no recibió el HTML');
    });

    await ir(s, '/ministerio/sitio-publico.php?tab=contacto');
    await prueba('pestaña "Contacto": las URLs sin http se completan con https:// al salir del campo', async () => {
      sinErrores(s);
      igual('contacto', await s.page.evaluate(() => SITIO_CFG.tab), 'pestaña');
      const input = s.page.locator('input[type="url"]').first();
      await input.fill('ejemplo.com/perfil');
      await input.blur();
      igual('https://ejemplo.com/perfil', await input.inputValue(), 'URL normalizada');
    });
    for (const tab of ['inicio', 'legal']) {
      await ir(s, `/ministerio/sitio-publico.php?tab=${tab}`);
      await prueba(`pestaña "${tab}": no inicializa nada ajeno (sin errores, sin Quill)`, async () => {
        sinErrores(s);
        igual(tab, await s.page.evaluate(() => SITIO_CFG.tab), 'pestaña');
        igual(0, await s.page.locator('.ql-editor').count(), 'editores Quill');
      });
    }

    console.log('\nGestión de formularios (js/ministerio-formulario-gestion.js)');
    await ir(s, `/ministerio/formulario-gestion.php?id=${FORM_ID}&tab=enviar`);
    await prueba('pestaña "Enviar": el tipo de filtro muestra solo su bloque', async () => {
      sinErrores(s);
      for (const [valor, caja] of [['rubro', 'boxRubros'], ['ubicacion', 'boxUbic'], ['estado', 'boxEstado'], ['empresas_especificas', 'boxEmp']]) {
        await s.page.selectOption('#tipoFiltro', valor);
        const visibles = await s.page.evaluate(() => ['boxRubros', 'boxUbic', 'boxEstado', 'boxEmp'].filter(id => document.getElementById(id).style.display === 'block'));
        igual([caja], visibles, `con filtro "${valor}"`);
      }
      await s.page.selectOption('#tipoFiltro', 'todos');
      igual([], await s.page.evaluate(() => ['boxRubros', 'boxUbic', 'boxEstado', 'boxEmp'].filter(id => document.getElementById(id).style.display === 'block')), 'con filtro "todos"');
    });
    for (const tab of ['respuestas', 'envios']) {
      await ir(s, `/ministerio/formulario-gestion.php?id=${FORM_ID}&tab=${tab}`);
      await prueba(`pestaña "${tab}" carga sin errores (partial incluido)`, async () => {
        sinErrores(s);
        verdadero(await s.page.locator('.nav-tabs .nav-link.active').count() === 1, 'no hay pestaña activa');
        verdadero((await s.page.locator('.nav-tabs .nav-link.active').getAttribute('href')).includes(`tab=${tab}`), 'pestaña activa equivocada');
      });
    }
    await s.ctx.close();
  } finally {
    await browser.close();
  }
  console.log(`\nResultado: ${ok} de ${ok + fallos.length} pruebas OK` + (omitidas ? ` (${omitidas} omitidas)` : '') + '\n');
  process.exit(fallos.length ? 1 : 0);
})().catch(e => { console.error(e); process.exit(1); });
