# Librerías de terceros (alojadas localmente)

El sitio no depende de ningún CDN: estas librerías se sirven desde aquí, por lo que funciona sin
acceso a Internet (salvo las teselas de los mapas y reCAPTCHA, que son servicios externos).

Se copiaron sin modificar desde los paquetes de npm indicados. Única excepción:
se quitó el comentario `sourceMappingURL` de los archivos minificados (evita 404 en las
herramientas de desarrollo) y, en Font Awesome, la referencia de respaldo a `.ttf`
(los navegadores actuales usan `.woff2`).

| Carpeta | Paquete npm | Versión | Licencia |
|---|---|---|---|
| `bootstrap/` | bootstrap | 5.3.2 | MIT |
| `bootstrap-icons/` | bootstrap-icons | 1.11.1 | MIT |
| `fontawesome/` | @fortawesome/fontawesome-free | 6.5.1 | CC-BY-4.0 (iconos) / OFL-1.1 (fuentes) / MIT (código) |
| `leaflet/` | leaflet | 1.9.4 | BSD-2-Clause |
| `leaflet-draw/` | leaflet-draw | 1.0.4 | MIT |
| `chartjs/` | chart.js | 4.4.3 | MIT |
| `sweetalert2/` | sweetalert2 | 11.26.25 | MIT |
| `sortablejs/` | sortablejs | 1.15.6 | MIT |
| `quill/` | quill | 1.3.7 | BSD-3-Clause |
| `fonts/` | @fontsource/montserrat, roboto, inter (subconjunto latin) | 5.3.0 | OFL-1.1 |

`fonts/fonts.css` lo escribimos nosotros: declara Montserrat 500/600/700, Roboto 400/500/700 y
Inter 400/500/600. Roboto 600 usa el archivo 700 (no existe un 600 estático).

## Actualizar una librería

```bash
npm install --prefix /tmp/vend bootstrap@<versión>     # o la que corresponda
# copiar los archivos a la carpeta correspondiente y actualizar la tabla de arriba
npm run test:assets && npm run test:sin-cdn            # con el servidor en marcha
```
