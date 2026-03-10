# Fix: Error "Unexpected token '<'" al crear/editar cursos

> **Fecha**: 2026-03-10 | **Estado**: ✅ Resuelto

---

## 🔍 Problema

Al crear o editar un curso, aparecía el error:
```
SyntaxError: Unexpected token '<', "<br /> <b>"... is not valid JSON
```

El JavaScript intentaba parsear como JSON una respuesta PHP que contenía HTML de errores mezclado.

---

## 🧩 Causa Raíz

### 1. Closing `?>` tags con whitespace
Archivos PHP como `db_connect.php`, `config.php` y todos los endpoints API tenían `?>` seguido de saltos de línea. Al hacer `require`, ese whitespace se inyectaba **antes** del JSON.

### 2. PHP `display_errors=On` (default XAMPP)
Warnings de funciones como `getimagesize()` generaban HTML (`<br />`, `<b>`) que corrompía el JSON.

### 3. Carpeta `uploads/` no existía
`move_uploaded_file()` fallaba silenciosamente → la imagen quedaba como `photos/LogoOficial.png` por defecto.

---

## 🛠️ Archivos Modificados (13)

| # | Archivo | Cambio |
|---|---------|--------|
| 1 | `config/config.php` | Removido `?>` |
| 2 | `api/db_connect.php` | Removido `?>` |
| 3 | `api/add_course.php` | `ob_start()` + `display_errors=0` + auto-crear `uploads/` + error si falla upload |
| 4 | `api/update_course.php` | `ob_start()` + `display_errors=0` + auto-crear `uploads/` + error si falla upload + removido `?>` |
| 5 | `api/add_course_detail.php` | `ob_start()` + `display_errors=0` + removido `?>` |
| 6 | `api/add_lesson.php` | Reordenado headers + `ob_start()` + removido `?>` |
| 7 | `api/update_detail_course.php` | `ob_start()` + `display_errors=0` + removido `?>` |
| 8 | `api/update_lesson.php` | Reordenado headers + `ob_start()` + removido `?>` |
| 9 | `api/delete_lesson.php` | Reordenado headers + `ob_start()` + removido `?>` |
| 10 | `api/get_full_course.php` | `ob_start()` + `ob_end_clean()` + removido `?>` |
| 11 | `api/check_session.php` | Removido `?>` |
| 12 | `js/creation_Course.js` | Nueva función `safeJsonParse()` para manejo robusto de errores |
| 13 | `uploads/` (nuevo) | Directorio creado para almacenar imágenes de cursos |

---

## 🔧 Patrón aplicado en cada endpoint PHP

```php
<?php
ob_start();                        // 1. Capturar output
ini_set('display_errors', '0');    // 2. Suprimir HTML de errores
error_reporting(E_ALL);            // 3. Seguir logueando errores

require 'db_connect.php';          // 4. Includes (pueden generar whitespace)

ob_end_clean();                    // 5. Limpiar TODO el output capturado
header('Content-Type: application/json'); // 6. Header limpio

// ... código del endpoint ...
// SIN closing ?> al final
```

---

## ✅ Verificación

- ✅ `get_full_course.php?id=11` → JSON limpio sin HTML
- ✅ `update_course.php` → JSON limpio
- ✅ `add_course.php` → JSON limpio
- ✅ Sin `SyntaxError: Unexpected token '<'` en consola
- ✅ Carpeta `uploads/` creada y funcional
