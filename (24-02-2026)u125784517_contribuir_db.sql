-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 24-02-2026 a las 20:38:30
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `u125784517_contribuir_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `instructor` varchar(100) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `price` int(11) DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT NULL,
  `students` int(11) DEFAULT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `courses`
--

INSERT INTO `courses` (`id`, `title`, `instructor`, `avatar`, `category`, `price`, `rating`, `students`, `duration`, `image`, `description`) VALUES
(1, 'Formación de Artistas Educadores', 'Con Tribu Ir', 'https://i.ibb.co/84Kf9Rd0/552183644-17852106906549939-7631989156897828754-n.webp', 'Administración Educativa', 20000, 4.9, 204, '28 horas', 'https://images.unsplash.com/photo-1552664730-d307ca884978?w=600&h=400&fit=crop', 'Somos una iniciativa pionera en Chile impulsada por CREO de Creer y Crear, Con Tribu Ir y la Red de Artistas Educadores de Chile (RAECH). Su propósito es profesionalizar y fortalecer el rol del \"Artista Educador\", integrando el arte no solo como una técnica, sino como una herramienta pedagógica para la educación emocional y la transformación social. El programa combina modelos teóricos, herramientas de gestión práctica (como formalización y planificación) y enfoques inclusivos (DUA y neurociencias) para preparar a los artistas en el desarrollo de experiencias educativas significativas en contextos escolares, culturales y psicosociales.'),
(2, 'Trabajo Colaborativo en I+D Aumentado con IA', 'Alpha Docere', 'photos/LogoAlphadocere.png', 'Innovación y Tecnología', 0, 4.8, 121, '4 semanas', 'photos/LogoAlphadocere.png', 'Curso online de 4 semanas enfocado en aprender a trabajar colaborativamente en entornos de investigación y desarrollo (I+D) utilizando inteligencia artificial como apoyo. Los participantes aprenderán a transformar ideas en problemas estructurados, formular hipótesis, documentar de manera clara, iterar con feedback y definir un camino personal dentro del ecosistema de innovación. No se requiere experiencia técnica previa.'),
(3, 'Fotografía Profesional con Cámara y Móvil', 'Carlos Rivas', 'https://i.ibb.co/84Kf9Rd0/552183644-17852106906549939-7631989156897828754-n.webp', 'Fotografía', 52000, 4.7, 200, '12 horas', 'photos/LogoOficial.png', 'Aprende composición, iluminación y edición para obtener fotos de calidad profesional con cualquier dispositivo.'),
(5, 'Yoga y Bienestar Integral', 'Fernanda López', 'https://i.ibb.co/84Kf9Rd0/552183644-17852106906549939-7631989156897828754-n.webp', 'Salud', 30000, 4.9, 152, '8 horas', 'photos/LogoOficial.png', 'Rutinas de yoga, respiración y relajación para mejorar tu salud física y mental.'),
(6, 'Inglés Conversacional Intensivo', 'John Peterson', 'https://i.ibb.co/84Kf9Rd0/552183644-17852106906549939-7631989156897828754-n.webp', 'Idiomas', 70000, 4.4, 98, '20 horas', 'photos/LogoOficial.png', 'Desarrolla fluidez en inglés con ejercicios prácticos de conversación en situaciones reales.'),
(7, 'Excel Avanzado para el Trabajo', 'Ricardo Muñoz', 'https://i.ibb.co/84Kf9Rd0/552183644-17852106906549939-7631989156897828754-n.webp', 'Ofimática', 40000, 4.6, 261, '14 horas', 'photos/LogoOficial.png', 'Funciones avanzadas, tablas dinámicas y automatización para mejorar tu productividad laboral.');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detail_courses`
--

CREATE TABLE `detail_courses` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `learning_objectives` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`learning_objectives`)),
  `requirements` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`requirements`)),
  `intro_video` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detail_courses`
--

INSERT INTO `detail_courses` (`id`, `course_id`, `learning_objectives`, `requirements`, `intro_video`) VALUES
(1, 1, '[\"Integrar el arte y la emoción como vehículos de aprendizaje\",\"Desarrollar habilidades socioemocionales y comunicativas\",\"Profesionalizar la gestión del artista educador\",\"Aplicar estrategias de inclusión y neuroeducación\"]', '[\"Experiencia docente (deseable)\",\"Interés en desarrollo personal\",\"Compromiso de 5-7 hrs\\/semana\"]', 'https://youtu.be/u_Ij1Kkd9Q0'),
(2, 2, '[\"obj1\",\"obj2\"]', '[\"req1\",\"req2\"]', 'https://www.youtube.com/watch?v=BBXoKa8RCZw');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `progress` int(11) DEFAULT 0,
  `hours_completed` int(11) DEFAULT 0,
  `enrolled_date` timestamp NULL DEFAULT current_timestamp(),
  `completed_date` timestamp NULL DEFAULT NULL,
  `is_completed` tinyint(1) DEFAULT 0,
  `certificate_issued` tinyint(1) DEFAULT 0,
  `certificate_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `enrollments`
--

INSERT INTO `enrollments` (`id`, `user_id`, `course_id`, `progress`, `hours_completed`, `enrolled_date`, `completed_date`, `is_completed`, `certificate_issued`, `certificate_url`) VALUES
(8, 5, 1, 15, 3, '2026-02-10 17:20:00', NULL, 0, 0, NULL),
(13, 5, 2, 0, 0, '2026-02-16 22:14:24', NULL, 0, 0, NULL),
(20, 5, 6, 0, 0, '2026-02-20 15:45:17', NULL, 0, 0, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lessons`
--

CREATE TABLE `lessons` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `order_number` int(11) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `is_free` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `lessons`
--

INSERT INTO `lessons` (`id`, `course_id`, `title`, `description`, `order_number`, `content`, `video_url`, `duration`, `is_free`, `created_at`) VALUES
(1, 2, 'Introducción: ¿Qué es I+D?', 'Comprende qué significa realmente Investigación y Desarrollo en términos prácticos y cómo funciona en equipos tecnológicos.', 1, 'En esta lección aprenderás:\n- Qué es I+D en la práctica (no solo teoría)\n- Cómo trabajan los equipos de desarrollo\n- La cultura colaborativa en contextos técnicos\n- Por qué el orden es fundamental antes de ejecutar', NULL, 25, 1, '2026-02-16 20:48:44'),
(2, 2, 'El Rol del Perfil No Técnico', 'Descubre cómo puedes aportar valor en equipos de desarrollo sin necesariamente saber programar.', 2, 'Contenido:\n- Diferencia entre ejecutor técnico y gestor de I+D\n- Habilidades clave del perfil no programador\n- Cómo comunicarte efectivamente con desarrolladores\n- La importancia de la documentación clara', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 20, 1, '2026-02-16 20:48:44'),
(3, 2, 'Cultura Colaborativa y Emocional', 'Aprende sobre la dimensión humana del trabajo en equipos tecnológicos y cómo gestionar la colaboración.', 3, 'Temas:\n- Trabajo colaborativo con enfoque emocional\n- Respeto por los tiempos y procesos de otros\n- Manejo de la frustración en entornos técnicos\n- Construcción de confianza en equipos distribuidos', 'https://docs.google.com/document/d/1JlWkNvLZkqKo2E8VCmrQGz_lX76QgsGx8ZWGM3LBTpE/edit?tab=t.0', 30, 0, '2026-02-16 20:48:44'),
(4, 2, 'De Idea Vaga a Idea Estructurada', 'Ejercicio práctico: transforma tu idea inicial en un documento ordenado y comprensible.', 4, 'Actividad práctica:\n- Plantilla de estructuración\n- Identificar: Problema, Contexto, Intención\n- Uso de IA como asistente para clarificar\n- Reformulación y síntesis\n\nEntregable: Documento estructurado versión 1', 'https://www.youtube.com/watch?v=2ne4Tqdf9QE', 45, 0, '2026-02-16 20:48:44'),
(5, 2, 'De Idea a Problema Definido', 'Aprende a transformar intuiciones en problemas claramente definidos y accionables.', 5, 'Contenido:\n- Diferencia entre idea y problema\n- Técnicas para identificar el problema real\n- Formular problemas de manera específica\n- Validar que el problema vale la pena resolver', NULL, 30, 0, '2026-02-16 20:48:44'),
(6, 2, 'Construcción de Hipótesis', 'Descubre cómo formular hipótesis testables que guíen tu proceso de desarrollo.', 6, 'Aprenderás:\n- Qué es una hipótesis en I+D\n- Diferencia entre opinión, suposición e hipótesis\n- Cómo construir hipótesis validables\n- Criterios de éxito medibles', NULL, 35, 0, '2026-02-16 20:48:44'),
(7, 2, 'Descomposición en Tareas', 'Convierte tu proyecto en tareas concretas y organizadas que un equipo pueda ejecutar.', 7, 'Proceso:\n- De visión general a tareas específicas\n- Priorización de tareas\n- Estimación básica de complejidad\n- Organización secuencial vs paralela', NULL, 40, 0, '2026-02-16 20:48:44'),
(8, 2, 'Documentación para Desarrolladores', 'Aprende a crear documentación técnica clara que facilite el trabajo del equipo de desarrollo.', 8, 'Técnicas:\n- Estructura de un brief técnico\n- Información esencial vs accesoria\n- Uso de ejemplos y casos de uso\n- Formato de tickets y tareas\n- IA como herramienta para estructurar', NULL, 35, 0, '2026-02-16 20:48:44'),
(9, 2, 'Proyecto Semana 2: Mini-Brief Técnico', 'Crea tu primer mini-brief técnico con problema, hipótesis y tareas definidas.', 9, 'Entregable:\n- Problema definido\n- Hipótesis clara y testable\n- Objetivo concreto medible\n- Lista de tareas priorizadas\n\nEvaluación: Índice de Orden (IO) + Inicio de Índice de Iteración (II)', NULL, 60, 0, '2026-02-16 20:48:44'),
(10, 2, '¿Qué Significa Iterar?', 'Comprende el concepto de iteración y por qué es fundamental en I+D.', 10, 'Conceptos:\n- Iteración vs revisión simple\n- El valor del prototipado rápido\n- Aprender del error sin bloquearse\n- Versionamiento como práctica', NULL, 25, 0, '2026-02-16 20:48:44'),
(11, 2, 'Recibir Feedback Técnico', 'Desarrolla habilidades para recibir, procesar y aplicar retroalimentación de equipos técnicos.', 11, 'Habilidades:\n- Escucha activa del feedback\n- Separar crítica constructiva de crítica personal\n- Hacer preguntas clarificadoras\n- Documentar los aprendizajes', NULL, 30, 0, '2026-02-16 20:48:44'),
(12, 2, 'Reformulación de Hipótesis', 'Aprende a ajustar y mejorar tus hipótesis basándote en nueva información y feedback.', 12, 'Proceso:\n- Evaluar hipótesis con datos reales\n- Identificar supuestos incorrectos\n- Reformular sin perder el foco\n- Mantener trazabilidad de cambios', NULL, 35, 0, '2026-02-16 20:48:44'),
(13, 2, 'IA como Simulador de Revisión', 'Utiliza la Inteligencia Artificial para simular revisiones críticas de tu trabajo.', 13, 'Técnicas:\n- Prompts para solicitar crítica constructiva\n- Simulación de preguntas de desarrollador\n- Identificación de ambigüedades\n- Mejora iterativa con IA', NULL, 40, 0, '2026-02-16 20:48:44'),
(14, 2, 'Proyecto Semana 3: Versión Mejorada', 'Entrega una versión 2.0 de tu proyecto incorporando feedback y mejoras estructurales.', 14, 'Entregable:\n- Versión 2 mejorada del proyecto\n- Documento de cambios realizados\n- Justificación de cada cambio\n- Mayor claridad estructural\n\nEvaluación: Índice de Iteración (II) + Refuerzo de Índice de Colaboración (IC)', NULL, 60, 0, '2026-02-16 20:48:44'),
(15, 2, 'Opciones en el Ecosistema', 'Conoce las diferentes opciones de participación en el ecosistema Alpha Docere.', 15, 'Caminos disponibles:\n- Cliente / Founder: Desarrolla tu proyecto con apoyo\n- Colaborador I+D: Intégrate a equipos de trabajo\n- Comunidad Abierta: Formación continua y networking\n- Alianzas Estratégicas: Proyectos colaborativos', NULL, 30, 0, '2026-02-16 20:48:44'),
(16, 2, 'Responsabilidad y Compromiso', 'Reflexiona sobre el compromiso necesario para avanzar en cada camino del ecosistema.', 16, 'Temas:\n- Qué implica ser cliente vs colaborador\n- Responsabilidades en cada rol\n- Gestión de expectativas realistas\n- Modelo de sostenibilidad (pago voluntario)', NULL, 25, 0, '2026-02-16 20:48:44'),
(17, 2, 'Autoevaluación y Reflexión', 'Evalúa tu proceso de aprendizaje y los cambios logrados durante el curso.', 17, 'Ejercicio:\n- Formulario de cierre\n- Comparación: autopercepción inicial vs desempeño final\n- Cálculo del Delta IPC (transformación lograda)\n- Identificación de fortalezas y áreas de mejora', NULL, 35, 0, '2026-02-16 20:48:44'),
(18, 2, 'Planificación', 'Crea un plan concreto de acción para los próximos días.', 18, 'Componentes del plan:\n- Objetivos específicos a corto plazo\n- Recursos necesarios\n- Hitos medibles\n- Sistema de seguimiento\n- Siguiente paso inmediato', NULL, 40, 0, '2026-02-16 20:48:44'),
(19, 2, 'Proyecto Final: Plan Personal y Decisión', 'Presenta tu plan de acción y elige tu camino dentro del ecosistema.', 19, 'Entregable final:\n- Reflexión de aprendizaje completo\n- Autoevaluación del IPC\n- Plan de acción 90 días\n- Elección de camino (Cliente/Colaborador/Comunidad)\n- Compromiso personal\n\nEvaluación: Índice de Proyección (IP) + IPC Final consolidado', NULL, 75, 0, '2026-02-16 20:48:44'),
(20, 2, 'Ceremonia de Cierre y Próximos Pasos', 'Sesión final de cierre, feedback grupal y activación de siguiente fase.', 20, 'Contenido:\n- Presentación de proyectos destacados\n- Feedback de pares\n- Entrega simbólica de IPC\n- Activación de acceso a comunidad/proyectos\n- Celebración de logros', NULL, 90, 0, '2026-02-16 20:48:44'),
(21, 1, 'Módulo 1: El Rol del Artista Educador y Habilidades Socioemocionales', 'Este módulo sienta las bases filosóficas y prácticas del rol.', 1, 'Este módulo sienta las bases filosóficas y prácticas del rol. Se explora la importancia del arte en la educación desde una mirada antroposófica y emocional, destacando cómo la belleza y la emoción son puertas al conocimiento. Se enfoca intensamente en el \"ser\" del educador, entregando herramientas para el autocuidado, la proactividad y habilidades blandas esenciales como la comunicación asertiva, la escucha activa y la resolución colaborativa de conflictos dentro del aula.', 'https://www.youtube.com/embed/u_Ij1Kkd9Q0', 6, 0, '2026-02-20 14:33:30'),
(22, 1, 'Módulo 2: Gestión, Identidad y Formalización', 'Este módulo aborda la dimensión profesional y administrativa.', 2, 'Este módulo aborda la dimensión profesional y administrativa. Enseña a gestionar el recurso más valioso, el tiempo, mediante técnicas como la Matriz de Eisenhower y el método Pomodoro. Además, profundiza en la construcción de la identidad profesional y guía paso a paso en la formalización del emprendimiento: diferencias entre persona natural y jurídica, tipos de sociedades (EIRL, SpA), inicio de actividades en el SII y acceso a fondos concursables, permitiendo al artista operar legalmente y con estabilidad.', NULL, 7, 0, '2026-02-20 14:33:30'),
(23, 1, 'Módulo 3: Diseño de Talleres, Metodologías y Evaluación', 'Este módulo enseña la didáctica y estructuración de talleres.', 3, 'Aquí se entra en la didáctica pura. Se enseña a estructurar talleres efectivos definiendo propósitos claros, ejes temáticos y lenguajes artísticos. Se promueve una estructura flexible que equilibre la técnica con la experiencia emocional. También se aborda la evaluación desde una perspectiva formativa y creativa, alejándose de lo tradicional para valorar el proceso y la evolución del participante mediante la retroalimentación constructiva y la metacognición.', NULL, 8, 0, '2026-02-20 14:33:30'),
(24, 1, 'Módulo 4: Inclusión, Neurociencias y Proyección Profesional', 'El módulo final conecta la práctica artística con la ciencia y la ética social.', 4, 'El módulo final conecta la práctica artística con la ciencia y la ética social. Analiza el tránsito desde la exclusión hacia la inclusión, presentando el Diseño Universal para el Aprendizaje (DUA) como marco para atender la diversidad. Se respalda la efectividad del arte con evidencia neurocientífica (activación de redes cerebrales, dopamina y neuronas espejo). Finalmente, cierra con la proyección profesional: cómo construir una propuesta de valor, \"vender\" proyectos a instituciones educativas y adaptar el lenguaje para lograr impacto y financiamiento.', NULL, 7, 0, '2026-02-20 14:33:30');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lesson_progress`
--

CREATE TABLE `lesson_progress` (
  `id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `is_completed` tinyint(1) DEFAULT 0,
  `completed_date` timestamp NULL DEFAULT NULL,
  `time_watched` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `course_id`, `rating`, `comment`, `created_at`) VALUES
(1, 3, 2, 5, 'Excelente curso. Me ayudó a estructurar mi proyecto de manera profesional y entender cómo trabajar con equipos técnicos. La metodología IPC es muy clara y el uso de IA como herramienta fue revelador. Totalmente recomendado para founders no técnicos.', '2026-01-22 13:30:00'),
(2, 1, 2, 4, 'Voy en la semana 2 y ya veo resultados. El enfoque en ordenar el pensamiento antes de ejecutar es valioso. Las plantillas y ejercicios prácticos hacen que todo sea aplicable de inmediato. Espero terminar pronto para implementar todo lo aprendido.', '2026-01-24 17:15:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `currency` varchar(3) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_id` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `invoice_number` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `created_at`) VALUES
(1, 'Tomas', 'tomas@tomas.cl', '$2y$10$9kerykRLBUak8yNInuoon.eYwxr0WaVyIdBf71ACEUu255u5ylaqW', '2025-10-11 21:20:58'),
(2, 'test', 'test@test.cl', '$2y$10$RgP6CfKgG9AyWcRwZwNh8.wF4z4eA.wNSPCQTr5fBTqXPlmiyynT6', '2025-10-11 23:15:21'),
(3, 'AlonsoDeus', 'Alonso@Diaz.cl', '$2y$10$e8jkjjR.9pFbMsz9MRdi4eqRTxMPLV9Z2iue2a4oVXbrvVZnkAH3K', '2025-10-14 13:07:41'),
(4, 'Joaco', 'joaco@joaco.cl', '$2y$10$rnNNjAXEMIX58Zur5lh5re/ggei9UZXEOvA2FOKJI/orBZ7R9YY56', '2025-10-14 21:30:05'),
(5, 'Arturo Quiroga Tello', 'arturo@arturo.cl', '$2y$10$pNs4w/ZlsaLDbmNXpZpll.0vCSQNkWlmciQKJFYihxVRagL2uTr8y', '2026-02-13 13:46:29');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `detail_courses`
--
ALTER TABLE `detail_courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indices de la tabla `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_enrollment` (`user_id`,`course_id`),
  ADD KEY `idx_enrollments_user` (`user_id`),
  ADD KEY `idx_enrollments_course` (`course_id`);

--
-- Indices de la tabla `lessons`
--
ALTER TABLE `lessons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lessons_course` (`course_id`);

--
-- Indices de la tabla `lesson_progress`
--
ALTER TABLE `lesson_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_lesson_progress` (`enrollment_id`,`lesson_id`),
  ADD KEY `lesson_id` (`lesson_id`),
  ADD KEY `idx_lesson_progress_enrollment` (`enrollment_id`);

--
-- Indices de la tabla `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_review` (`user_id`,`course_id`),
  ADD KEY `idx_reviews_course` (`course_id`);

--
-- Indices de la tabla `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `idx_transactions_user` (`user_id`),
  ADD KEY `idx_transactions_status` (`status`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `detail_courses`
--
ALTER TABLE `detail_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `lessons`
--
ALTER TABLE `lessons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT de la tabla `lesson_progress`
--
ALTER TABLE `lesson_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `detail_courses`
--
ALTER TABLE `detail_courses`
  ADD CONSTRAINT `detail_courses_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `lessons`
--
ALTER TABLE `lessons`
  ADD CONSTRAINT `lessons_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `lesson_progress`
--
ALTER TABLE `lesson_progress`
  ADD CONSTRAINT `lesson_progress_ibfk_1` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lesson_progress_ibfk_2` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
