# Merge de Base de Datos - Script Final


## Resumen de cambios fusionados

### Tablas nuevas (4)
| Tabla | Descripción |
|---|---|
| `roles` | 3 roles: admin, instructor, student |
| `permissions` | 7 permisos granulares (course.create, course.edit_own, etc.) |
| `role_permissions` | Relación N:M entre roles y permisos |
| `login_attempts` | Registro de intentos de login por IP/email |

### Tabla `users` - Campos agregados
| Campo | Tipo | Propósito |
|---|---|---|
| `role_id` | INT NOT NULL DEFAULT 3 | FK → `roles(id)`, default = student |
| `image` | VARCHAR(255) NULL | Foto de perfil |
| `description` | TEXT NULL | Bio/descripción |
| `reset_code` | VARCHAR(10) NULL | Código para recuperar contraseña |
| `reset_code_expires_at` | TIMESTAMP NULL | Expiración del código |

### Tabla `courses` - Cambios
| Cambio | Detalle |
|---|---|
| ❌ Eliminado | Campo `instructor` VARCHAR(100) |
| ✅ Mantenido | `instructor_id` INT **NOT NULL** (FK → `users(id)`) |
| ➕ Agregado | `total_hours` INT NULL |
| ➕ Agregado | `level` VARCHAR(50) NULL |

### Datos incluidos
- **6 usuarios** (5 originales + joaquin admin)
- **7 cursos** (2 originales + 5 nuevos)
- **3 enrollments** (incluye enrollment curso 6)



## Diagrama de relaciones (11 tablas)

![Diagrama ERD de la base de datos fusionada](Diagrama%20bd_merge_galindez(08-03-2026).png)

