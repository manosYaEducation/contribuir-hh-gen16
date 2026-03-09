# Librerías externas

## PHPMailer v6.x

- **Fuente:** [github.com/PHPMailer/PHPMailer](https://github.com/PHPMailer/PHPMailer)
- **Licencia:** LGPL-2.1
- **Uso en el proyecto:** Envío de correos SMTP para el sistema de recuperación de contraseña
- **Archivos incluidos:**
  - `PHPMailer.php` — Clase principal para crear y enviar correos
  - `SMTP.php` — Manejo de la conexión SMTP
  - `Exception.php` — Excepciones personalizadas de PHPMailer

### ⚠️ Notas importantes

- **No modificar estos archivos.** Son de la librería externa.
- Para actualizar, descargar la última versión desde el repositorio oficial.
- Se cargaron manualmente (sin Composer) para mantener la arquitectura simple.
- Configuración SMTP se define en `.env` y se lee desde `config/config.php`.
