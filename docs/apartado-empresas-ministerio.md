# Apartado Empresas — Panel Ministerio

## Historia de usuario

**Actor:** Usuario con rol `ministerio` o `admin`

> Como funcionario del Ministerio de Producción, quiero gestionar las empresas
> que forman parte del Parque Industrial: registrarlas manualmente, revisar las
> solicitudes de ingreso que llegan del formulario público, controlar su estado
> y mantener el directorio actualizado.

---

## Flujo completo (de punta a punta)

```
[PÚBLICO] presentar-proyecto.php
        ↓  Empresa interesada llena el formulario con datos + adjuntos
        
[MINISTERIO] solicitudes-proyecto.php  →  Tab "Nuevas" (badge amarillo)
        ↓  Ministerio revisa → abre el detalle → solicitud pasa a "En carpeta"
        
[MINISTERIO] solicitudes-proyecto.php  →  Tab "En carpeta"
        ↓  Ministerio hace clic en "Registrar empresa" → va a nueva-empresa.php
           con los datos pre-cargados (nombre, email, contacto, teléfono)
        
[MINISTERIO] nueva-empresa.php
        ↓  Ministerio elige rubro, modo de acceso y estado inicial → Registrar
        
[SISTEMA] empresas.php
        Empresa creada, aparece en la lista con estado Pendiente
        
[EMPRESA] activa-cuenta.php  o  login.php
        La empresa activa su cuenta y completa su perfil desde su panel
        
[MINISTERIO] empresas.php → dropdown → Activar
        Estado cambia a "Activa" → empresa visible al público
```

---

## Páginas del apartado

| Página | Función |
|--------|---------|
| `ministerio/empresas.php` | Listado de empresas con filtros (buscar, rubro, estado) y acciones |
| `ministerio/nueva-empresa.php` | Alta manual de empresa con datos provisorios |
| `ministerio/solicitudes-proyecto.php` | Embudo de solicitudes del formulario público |
| `public/presentar-proyecto.php` | Formulario público (sin login) para pedir ingreso al parque |

---

## Estados de la empresa

| Estado | Visible al público | Mapa | Inicio | Descripción |
|--------|:-----------------:|:----:|:------:|-------------|
| `pendiente` | ❌ No | ❌ No | ❌ No | Registrada, esperando verificación del Ministerio |
| `activa` | ✅ Sí | ✅ Sí | ✅ Sí | Verificada y operativa |
| `suspendida` | ❌ No | ❌ No | ❌ No | Sanción temporal; la empresa sigue pudiendo iniciar sesión |
| `inactiva` | ❌ No | ❌ No | ❌ No | Desactivada definitivamente |

> **Regla clave:** Solo `activa` aparece en el directorio público, el mapa y la portada.
> Las empresas en cualquier otro estado pueden iniciar sesión y completar su perfil,
> pero no son visibles para el público.

### Banners en el panel de la empresa según estado

- **pendiente** → Alerta amarilla: "Pendiente de verificación — no visible al público"
- **suspendida** → Alerta roja: "Suspendida temporalmente — contactá al Ministerio"
- **inactiva** → Alerta gris: "Inactiva — contactá al Ministerio"
- **activa** → Sin banner

---

## Acciones del Ministerio sobre una empresa

Desde `empresas.php` (dropdown de acciones en cada fila):

| Acción | Resultado |
|--------|-----------|
| Ver perfil (👁) | Abre `empresa-detalle.php` con todos los datos |
| Activar | Estado → `activa` |
| Suspender | Estado → `suspendida` |
| Desactivar | Estado → `inactiva` |
| Enviar reset de contraseña | Genera token de recuperación y envía email al titular |
| Copiar enlace de activación | Disponible si la cuenta aún no fue activada (token vigente) |

---

## Alta manual de empresa (`nueva-empresa.php`)

### Campos requeridos
- **Nombre comercial** — identificador en el sistema
- **Rubro** — selección desde tabla `rubros`
- **Email de acceso** — será el usuario de la empresa en el sistema

### Modo de acceso (radio)
| Opción | Cómo funciona |
|--------|---------------|
| **Activación por email** *(recomendado)* | Se crea el usuario sin contraseña y se genera un token de 48 hs. La empresa activa la cuenta desde el email y elige su contraseña. |
| **Contraseña temporal** | Se genera una contraseña aleatoria (8 caracteres hex) visible en pantalla o enviada por email. La empresa inicia sesión con ella y puede cambiarla. |

### Estado inicial
- **Pendiente de verificación** (default) — no visible al público
- **Activa directamente** — para casos en que ya fue verificada por otra vía

### Opciones
- ✅ Solicitar declaración trimestral — crea notificación interna a la empresa
- ✅ Enviar email al usuario — envía activación o credenciales por email

### Pre-carga desde solicitud
Cuando se llega desde `solicitudes-proyecto.php` via "Registrar empresa",
la URL trae parámetros `?prefill_nombre=...&prefill_email=...` que pre-cargan
el nombre y el email. Un banner azul indica al operador qué datos fueron cargados.

---

## Solicitudes de proyecto (`solicitudes-proyecto.php`)

### Estados de una solicitud

| Estado | Descripción |
|--------|-------------|
| `nueva` | Recién llegada del formulario público. Genera badge en el menú lateral. |
| `en_carpeta` | El ministerio la revisó y está considerando registrar la empresa. Al abrir el detalle de una solicitud "nueva", automáticamente pasa a "en carpeta" (AJAX). |
| `eliminada` | Descartada (soft delete — no se borra de la BD). |

### Acciones en el modal de detalle
- Cambiar estado manualmente
- Agregar observaciones internas (visibles solo para el ministerio)
- **Registrar empresa** → redirige a `nueva-empresa.php` con datos pre-cargados
- **Enviar mensaje** → envía email o mensaje interno si la empresa ya tiene usuario

---

## Adjuntos de solicitudes

Los archivos se guardan en:
```
public/uploads/documento-proyectos/
```
Formato de nombre: `doc1_[uniqid]_[timestamp].[ext]`

Tipos permitidos: PDF, JPG, PNG, DOC, DOCX (máx. 10 MB cada uno)

---

## Tabla de base de datos relevantes

| Tabla | Descripción |
|-------|-------------|
| `empresas` | Datos de la empresa (nombre, rubro, estado, lote_id, etc.) |
| `usuarios` | Credenciales de acceso (email, password, rol, activo) |
| `solicitudes_proyecto` | Solicitudes del formulario público |
| `rubros` | Catálogo de rubros activos |
| `lotes` | Lotes del parque (tabla creada, UI pendiente) |

---

## Navegación del menú lateral (ministerio)

```
EMPRESAS
├── Empresas              → empresas.php  (+ botón "Nueva Empresa" inline)
└── Solicitudes de proyecto → solicitudes-proyecto.php  (badge: nuevas pendientes)
```

> "Nueva empresa" fue removida del menú lateral — es una acción dentro de
> "Empresas", no una sección independiente.
