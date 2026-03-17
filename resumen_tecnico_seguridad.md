# Resumen Técnico: Implementación de Control de Acceso (Bugs 10, 11 y 12)

Este documento detalla las resoluciones de seguridad aplicadas para solventar vulnerabilidades relacionadas con el acceso no autorizado a recursos, reseñas y contenido de cursos pago y gratuitos.

## 🎯 Objetivos y Tareas Atendidas

*   **Tarea 10:** Evitar que estudiantes no inscritos en un curso puedan añadir reseñas y calificaciones, incluso si poseen el enlace directo.
*   **Tarea 11:** Evitar la visualización completa de los módulos y lecciones de cursos de paga si el alumno no está debidamente inscrito.
*   **Tarea 12:** Evitar la visualización de módulos de cursos si el usuario no ha iniciado sesión (protección de cursos en general, incluyendo gratuitos que requieren registro).
*   **Requisito Adicional:** Los recursos de los cursos deben estar protegidos de la misma manera que los módulos. El Video Introductorio debe mantenerse permanentemente público para fomentar la inscripción.

---

## 🛠 Cambios Implementados en el Backend (APIs)

Se estableció un patrón de seguridad con **Verificación Doble (Autenticación + Autorización)** gestionado en el lado del servidor, previniendo que la data sea enviada al frontend si el usuario carece de permisos.

### 1. `api/get_lessons.php` y `api/get_full_course.php` (Tareas 11 y 12)
Se restructuró la lógica de extracción de lecciones para implementar una bandera de acceso (`$hasAccess`):
- Se inicia la sesión (`session_start()`).
- Se verifica el precio del curso desde la tabla `courses`.
- **Reglas de Acceso Implementadas:**
    1.  **Sin Sesión Activa:** Acceso siempre denegado (`$hasAccess = false`), independiente de si el curso es gratis o de pago.
    2.  **Con Sesión + Curso Gratuito:** Acceso concedido (`$hasAccess = true`).
    3.  **Con Sesión + Curso de Pago:** Se verifica la tabla `enrollments`. Acceso concedido solo si existe registro de inscripción activo.
- **Acceso Denegado (Restricción de Data):** En lugar de bloquear toda la respuesta (lo que rompería el UI), se realiza una omisión quirúrgica. Si no hay acceso, se hace `unset()` de los campos `content` y `video_url` para cada lección iterada, devolviendo además un formato especial `{ restricted: true, lessons: [...] }`.

### 2. `api/get_course_detail.php` (Protección de Recursos)
- Se aplicó el mismo patrón de validación de sesión e inscripción.
- Si el usuario no tiene acceso válido, se remueve ( `unset()`) el campo `recursos` de la respuesta JSON, haciéndolo ilegible.
- El campo `intro_video` no se afecta logrando el propósito de mantenerlo visible al público general.

### 3. `api/add_review.php` (Tarea 10)
- Tras la auditoría, se determinó que el backend ya realizaba adecuadamente la consulta para validar inscripciones previo a la inserción de reseñas. Las defensas requeridas estaban únicamente del lado del cliente.

---

## 💻 Cambios Implementados en el Frontend

### 1. `pages/curso2.html` (Interacción y UI)
- **Bloqueo Visual de la Reseña:** Se integró un chequeo asíncrono pegándole al endpoint `/api/get_enrollments.php`. Si el curso iterado no forma parte de las devoluciones o si la API devuelve estado 401 (sin sesión), se aplica `display: none` a todo el contenedor (`.card`) del botón de "Añadir Reseña".
- **Visualización Condicionada de Lecciones:** Se modificó la función `loadCourseLessons()` para leer la propiedad booleana `restricted` proveída por la API modificada. Si el acceso está restringido:
  - Se suprime dinámicamente la renderización de los botones de "Ver más".
  - Se genera un Banner nativo (con la leyenda "🔒 Contenido restringido", invitando a la inscripción) renderizándolo por encima de la lista de módulos.
- **Protección de Enlace de Recursos:** Se modificó la carga para que el botón de `Ver recursos` se inyecte al DOM o se visualice estrictamente si el control de `get_enrollments.php` es exitoso.

---

## ✅ Conclusión de Pruebas y Robustez

Tras la ejecución de pruebas manuales y a través del navegador, simulando estatus de invitado (no logueado), estatus logueado no inscrito, estatus logueado inscrito, cursado pagado y gratuito, se demostró que:

1.  **La implementación es robusta:** Los cursos gratuitos ya no filtran contenido ni URL de recursos si el usuario no está logueado y registrado. Para cursos de pago, es imperiosa la asociación en la base de datos de transacciones/inscripciones.
2.  **Seguridad por Diseño (Defense in Depth):** Al truncar datos confidenciales desde las APIs (`unset(content)`), no hay forma de que usuarios avanzados inspeccionen las cargas JSON en la ficha *Network* de sus navegadores y descifren URLs u otra meta-data que no les corresponde.
3.  **UX Preservada:** El Video Introductorio permanece constante y libre, el UI retiene la lista de módulos (solo sus títulos), proveyendo a prospectos un atractivo esquema de lo que aprenderán sin vulnerar la propiedad del curso.
