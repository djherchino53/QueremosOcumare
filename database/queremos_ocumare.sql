-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 12-08-2026 a las 22:22:25
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `queremos_ocumare`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `appointments`
--

CREATE TABLE `appointments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `patient_id` bigint(20) UNSIGNED NOT NULL,
  `doctor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('unpaid','paid') NOT NULL DEFAULT 'unpaid',
  `payment_method` varchar(255) DEFAULT NULL,
  `specialty` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `appointments`
--

INSERT INTO `appointments` (`id`, `patient_id`, `doctor_id`, `date`, `time`, `status`, `price`, `payment_status`, `payment_method`, `specialty`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 2, '2026-01-31', '17:33:00', 'completed', 20.00, 'unpaid', NULL, NULL, 'malestar general', '2026-02-01 00:37:38', '2026-02-01 08:25:27'),
(2, 2, 6, '2026-01-31', '17:33:00', 'confirmed', 15.00, 'unpaid', NULL, NULL, 'cita medica por dolor en el hombro', '2026-02-01 01:31:39', '2026-02-01 08:25:12'),
(3, 1, 2, '2026-01-31', '18:27:00', 'cancelled', 20.00, 'unpaid', NULL, NULL, 'recetar nuevo medicamento', '2026-02-01 02:27:04', '2026-02-01 08:25:20'),
(4, 1, 2, '2026-02-01', '00:15:00', 'completed', 15.00, 'unpaid', NULL, NULL, NULL, '2026-02-01 08:14:26', '2026-02-01 08:16:55'),
(5, 1, 2, '2026-02-01', '00:23:00', 'confirmed', 15.00, 'unpaid', NULL, NULL, NULL, '2026-02-01 08:22:17', '2026-02-01 08:22:17'),
(6, 1, 2, '2026-02-01', '00:24:00', 'pending', 15.00, 'unpaid', NULL, NULL, NULL, '2026-02-01 08:23:23', '2026-02-01 08:23:23'),
(7, 1, 2, '2026-02-01', '00:26:00', 'pending', 15.00, 'unpaid', NULL, NULL, 'observacinoes', '2026-02-01 08:26:00', '2026-02-01 08:26:00'),
(8, 2, 2, '2026-02-01', '00:29:00', 'pending', 15.00, 'unpaid', NULL, NULL, NULL, '2026-02-01 08:28:25', '2026-02-01 08:28:25'),
(9, 2, 2, '2026-02-01', '00:31:00', 'pending', 15.00, 'unpaid', NULL, NULL, 'nueva', '2026-02-01 08:29:42', '2026-02-01 08:29:42'),
(10, 3, 2, '2026-02-03', '22:54:00', 'completed', 15.00, 'unpaid', NULL, NULL, NULL, '2026-02-03 06:53:57', '2026-02-03 06:55:33'),
(11, 3, 2, '2026-02-04', '19:45:00', 'confirmed', 15.00, 'unpaid', NULL, NULL, NULL, '2026-02-05 03:44:45', '2026-02-05 03:44:45'),
(12, 3, 2, '2026-02-05', '21:11:00', 'pending', 0.00, 'unpaid', NULL, '', 'malestar, es un caso social', '2026-02-05 05:10:20', '2026-02-05 05:10:20'),
(13, 3, 8, '2026-08-12', '15:23:00', 'attended', 15.00, 'paid', 'Efectivo', 'Consulta', 'dolor abdominal\n', '2026-08-12 23:21:47', '2026-08-13 00:05:38'),
(14, 1, 8, '2026-08-12', '16:08:00', 'pending', 45.00, 'unpaid', NULL, 'Consulta, Infiltracion', NULL, '2026-08-13 00:07:50', '2026-08-13 00:07:50');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cash_movements`
--

CREATE TABLE `cash_movements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `type` enum('in','out') NOT NULL,
  `category` varchar(255) NOT NULL,
  `payment_method` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `appointment_id` bigint(20) UNSIGNED DEFAULT NULL,
  `doctor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cash_movements`
--

INSERT INTO `cash_movements` (`id`, `amount`, `type`, `category`, `payment_method`, `description`, `appointment_id`, `doctor_id`, `user_id`, `date`, `created_at`, `updated_at`) VALUES
(7, 15.00, 'in', 'Cita Médica', NULL, 'Cita Médica (Efectivo): dalton vargas - Dr. Juan Perez', 13, 8, 3, '2026-08-12', '2026-08-12 23:21:47', '2026-08-13 00:05:38');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `doctor_profiles`
--

CREATE TABLE `doctor_profiles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `bio` text DEFAULT NULL,
  `professional_id` varchar(255) DEFAULT NULL,
  `working_days` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`working_days`)),
  `working_hours` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`working_hours`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `doctor_profiles`
--

INSERT INTO `doctor_profiles` (`id`, `user_id`, `bio`, `professional_id`, `working_days`, `working_hours`, `created_at`, `updated_at`) VALUES
(1, 2, NULL, 'mp14', '[\"monday\",\"thursday\",\"friday\"]', '{\"start\":\"08:00\",\"end\":\"16:00\"}', '2026-02-01 07:13:43', '2026-02-01 07:34:25'),
(2, 6, NULL, 'mps985', '[\"saturday\",\"tuesday\",\"wednesday\"]', '{\"start\":\"08:00\",\"end\":\"16:00\"}', '2026-02-01 07:14:17', '2026-02-05 05:14:13'),
(3, 8, NULL, '333', '[\"friday\",\"wednesday\",\"monday\"]', '{\"start\":\"08:00\",\"end\":\"12:00\"}', '2026-02-01 07:58:15', '2026-02-01 07:59:31'),
(4, 3, NULL, NULL, NULL, NULL, '2026-02-20 20:42:58', '2026-02-20 20:42:58'),
(5, 1, NULL, NULL, NULL, NULL, '2026-08-12 23:17:00', '2026-08-12 23:17:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `doctor_specialty`
--

CREATE TABLE `doctor_specialty` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `medical_specialty_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `doctor_specialty`
--

INSERT INTO `doctor_specialty` (`id`, `user_id`, `medical_specialty_id`, `created_at`, `updated_at`) VALUES
(1, 2, 2, NULL, NULL),
(2, 6, 2, NULL, NULL),
(3, 6, 1, NULL, NULL),
(4, 8, 4, NULL, NULL),
(5, 8, 14, NULL, NULL),
(6, 8, 12, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `especialidad_estudios`
--

CREATE TABLE `especialidad_estudios` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `especialidad_id` bigint(20) UNSIGNED NOT NULL,
  `estudio` varchar(255) NOT NULL,
  `costo` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `especialidad_estudios`
--

INSERT INTO `especialidad_estudios` (`id`, `especialidad_id`, `estudio`, `costo`, `created_at`, `updated_at`) VALUES
(1, 1, 'Electrocardiograma', 30.00, '2026-02-05 04:47:02', '2026-02-05 04:47:02'),
(2, 2, 'Elecctrocardiograma', 20.00, '2026-02-05 04:47:51', '2026-02-05 04:47:51'),
(3, 2, 'Evaluación Cardiovascular', 20.00, '2026-02-05 04:48:11', '2026-02-05 04:48:11'),
(4, 4, 'Infiltracion', 30.00, '2026-02-05 04:48:40', '2026-02-05 04:48:40'),
(5, 4, 'yeso', 10.00, '2026-02-05 04:48:51', '2026-02-05 04:48:51'),
(6, 5, 'Ecografia', 20.00, '2026-02-05 04:49:10', '2026-02-05 04:49:10'),
(7, 5, 'Citología', 15.00, '2026-02-05 04:49:22', '2026-02-05 04:49:22');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `medical_histories`
--

CREATE TABLE `medical_histories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `patient_id` bigint(20) UNSIGNED NOT NULL,
  `doctor_id` bigint(20) UNSIGNED NOT NULL,
  `diagnosis` text NOT NULL,
  `treatment` text NOT NULL,
  `prescriptions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`prescriptions`)),
  `attachments_path` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments_path`)),
  `notes` text DEFAULT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `medical_histories`
--

INSERT INTO `medical_histories` (`id`, `patient_id`, `doctor_id`, `diagnosis`, `treatment`, `prescriptions`, `attachments_path`, `notes`, `date`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'hambre vieja', 'comer 3 veces al dia\n2 meriendas (mañana y tarde)\npor 7 dias', NULL, NULL, 'no consumir nada despues de las 10 pm', '2026-01-31', '2026-02-01 00:42:33', '2026-02-01 00:42:33'),
(2, 1, 2, 'malestar general', 'atamel 500mg cada 8 horas por 6 dias', NULL, NULL, 'hacer examenes de hematologia completa', '2026-01-31', '2026-02-01 02:23:45', '2026-02-01 02:23:45'),
(3, 1, 2, 'gripe', 'teragrip 500mg', NULL, NULL, 'si el malestar continua venir a consulta medica', '2026-01-31', '2026-02-01 02:26:26', '2026-02-01 02:26:26'),
(4, 1, 2, 'malestar general', 'acetaminofen 500mg', NULL, NULL, NULL, '2026-02-01', '2026-02-01 08:15:28', '2026-02-01 08:15:28'),
(5, 2, 1, 'hipertensa', 'tomar mucha agua\nmedir la tension 3 veces al dia', NULL, NULL, NULL, '2026-02-01', '2026-02-01 08:42:34', '2026-02-01 08:42:34'),
(6, 3, 2, 'le duele la barriga', 'buscapina 500', NULL, NULL, 'traer examenes de hematologia', '2026-02-03', '2026-02-03 06:55:19', '2026-02-03 06:55:19'),
(7, 3, 8, 'dolor abdominal agudo', 'reposo absoluto por 3 dias', NULL, NULL, 'sin notas adicionales 123456', '2026-08-12', '2026-08-12 23:25:04', '2026-08-12 23:44:51');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `medical_specialties`
--

CREATE TABLE `medical_specialties` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `medical_specialties`
--

INSERT INTO `medical_specialties` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Cardiología', 'médico especialista en el diagnóstico, tratamiento y prevención de las enfermedades del corazón y del aparato circulatorio (vasos sanguíneos). Se encarga de afecciones como hipertensión, arritmias, infartos e insuficiencia cardíaca, utilizando pruebas funcionales, fármacos y procedimientos intervencionistas. ', '2026-02-01 06:45:53', '2026-02-01 06:45:53'),
(2, 'Internista', 'especialista en medicina de adultos enfocado en la atención integral, diagnóstico y tratamiento no quirúrgico de enfermedades complejas, crónicas y sistémicas. Actúa como médico de cabecera, gestionando la salud general del paciente (corazón, pulmones, riñones, etc.) y coordinando cuidados entre otros especialistas. ', '2026-02-01 06:46:38', '2026-02-01 06:46:38'),
(3, 'Fisiatra', 'experto encargado de diagnosticar, tratar y prevenir discapacidades o lesiones funcionales sin cirugía. Su objetivo es restaurar la movilidad, independencia y calidad de vida en pacientes con dolores crónicos, lesiones medulares, secuelas de ACV o problemas musculoesqueléticos. ', '2026-02-01 06:47:14', '2026-02-01 06:47:14'),
(4, 'Traumatología', 'especialista encargado de estudiar, diagnosticar, prevenir y tratar —quirúrgica o conservadoramente— las lesiones y enfermedades del aparato locomotor, que incluyen huesos, articulaciones, músculos, tendones y ligamentos. Abordan fracturas, lesiones deportivas y dolores crónicos, trabajando para recuperar la movilidad y función corporal. ', '2026-02-01 06:48:05', '2026-02-01 06:48:05'),
(5, 'Gineco-ostetricia', 'especialista encargado de la salud integral de la mujer, combinando la atención del sistema reproductivo (ginecología) con el cuidado del embarazo, parto y posparto (obstetricia). Diagnostican y tratan enfermedades ginecológicas, realizan cirugías, gestionan la fertilidad y controlan tanto embarazos de bajo como alto riesgo para garantizar la seguridad de la madre y el feto. ', '2026-02-01 06:49:07', '2026-02-01 06:49:07'),
(6, 'Pedriatría', 'especialista de la salud encargado del cuidado integral de bebés, niños y adolescentes hasta los 18-21 años. Diagnostica, trata y previene enfermedades físicas, mentales y de desarrollo, además de realizar controles de \"niño sano\", administrar vacunas y asesorar en nutrición y crecimiento. ', '2026-02-01 06:49:43', '2026-02-01 06:49:43'),
(7, 'Dermatología', 'especialista capacitado para diagnosticar, tratar y prevenir enfermedades de la piel, cabello, uñas y mucosas, incluyendo condiciones crónicas, infecciones y cáncer. Además de la atención clínica, realizan procedimientos quirúrgicos, estéticos y estudios especializados (como biopsias) para garantizar la salud del órgano más grande del cuerpo. ', '2026-02-01 06:50:17', '2026-02-01 06:50:17'),
(8, 'Odontología', 'es un profesional de la salud capacitado para la prevención, diagnóstico y tratamiento de enfermedades bucodentales, abarcando dientes, encías, lengua y estructuras mandibulares. Realiza limpiezas, obturaciones, extracciones y mejora la estética y función dental para asegurar la salud oral integral de los pacientes. ', '2026-02-01 06:50:57', '2026-02-01 06:50:57'),
(9, 'Psiquiatría', 'es un profesional de la medicina especializado en el diagnóstico, tratamiento y prevención de enfermedades mentales, emocionales y conductuales. A diferencia de otros profesionales de la salud mental, el psiquiatra tiene formación médica, lo que le permite entender la conexión entre los problemas de salud física y mental, recetar fármacos y solicitar exámenes médicos. ', '2026-02-01 06:51:30', '2026-02-01 06:51:30'),
(10, 'Psicologo Clínico', 'es un profesional de la salud mental especializado en evaluar, diagnosticar, tratar y prevenir trastornos mentales, emocionales y de conducta. Utiliza terapias basadas en evidencia, como la cognitivo-conductual, para mejorar el bienestar y la calidad de vida de los pacientes, trabajando en entornos sanitarios o de consulta privada. ', '2026-02-01 06:52:20', '2026-02-01 06:52:33'),
(11, 'Psicología Social', 'es un profesional de la salud mental que investiga e interviene sobre cómo el entorno social, la cultura y las interacciones grupales influyen en la conducta, emociones y pensamientos individuales. Diseña estrategias para mejorar el bienestar comunitario, gestionar conflictos y abordar problemas sociales como la discriminación o la violencia. ', '2026-02-01 06:53:11', '2026-02-01 06:53:11'),
(12, 'Podología - Quiropedia', 'profesional de la salud especializado en el diagnóstico y tratamiento de las enfermedades y alteraciones de los pies. La quiropodia es su tratamiento clínico principal, enfocado en el cuidado, prevención y alivio terapéutico de patologías dérmicas y ungueales, como callosidades, uñas encarnadas o durezas. ', '2026-02-01 06:54:35', '2026-02-01 06:54:35'),
(13, 'Plasma', NULL, '2026-02-01 06:55:17', '2026-02-01 06:55:17'),
(14, 'Pie Diabetico', 'El médico especialista en pie diabético (generalmente endocrinólogo, angiólogo/cirujano vascular o podólogo) evalúa, previene y trata las complicaciones en los pies causadas por la diabetes, como úlceras, infecciones y neuropatía. Su labor incluye la exploración vascular y neurológica, desbridamiento de heridas, control metabólico y educación para prevenir amputaciones. ', '2026-02-01 06:56:02', '2026-02-01 06:56:02'),
(15, 'Electros', 'El médico que realiza e interpreta electros (electrocardiogramas) es generalmente un cardiólogo o un médico capacitado que analiza la actividad eléctrica cardíaca mediante electrodos colocados en pecho y extremidades. Utilizan esta prueba no invasiva para diagnosticar arritmias, infartos, ritmo cardíaco anormal y problemas estructurales. ', '2026-02-01 06:56:47', '2026-02-01 06:56:47'),
(16, 'Laboratorio', 'es el profesional de la salud responsable de analizar muestras biológicas (sangre, tejidos, fluidos) utilizando tecnología sofisticada para diagnosticar, tratar y prevenir enfermedades. Su labor es fundamental, ya que el 70%-80% de las decisiones médicas se basan en sus resultados. ', '2026-02-01 06:57:33', '2026-02-01 06:57:33'),
(17, 'Nefrología', 'médico especialista en medicina interna enfocado en el diagnóstico, tratamiento y manejo integral de las enfermedades renales, incluyendo la insuficiencia renal crónica y aguda. Se encarga del cuidado de los riñones, gestión de diálisis (hemodiálisis/diálisis peritoneal), trasplante renal y control de la hipertensión arterial relacionada. ', '2026-02-01 06:58:05', '2026-02-01 06:58:05'),
(18, 'Psicopedagogía', 'El psicopedagogo es un profesional especializado en la intersección de la psicología y la educación, dedicado a evaluar, diagnosticar y tratar dificultades de aprendizaje en personas de todas las edades. Analiza factores cognitivos, emocionales y sociales para potenciar habilidades, mejorar el rendimiento escolar y fomentar la inclusión. ', '2026-02-01 06:58:54', '2026-02-01 06:58:54'),
(19, 'Ecografía', 'es un profesional de la salud especializado en utilizar ondas sonoras de alta frecuencia (ultrasonido) para generar imágenes en tiempo real de órganos, tejidos y flujo sanguíneo. Estos expertos operan equipos complejos para evaluar estructuras internas, identificar patologías y apoyar diagnósticos médicos en áreas como obstetricia, vascular, cardiología y tejidos blandos.', '2026-02-01 06:59:27', '2026-02-01 06:59:27'),
(20, 'Psicología Escolar', 'es una rama aplicada de la psicología que promueve la salud mental, el aprendizaje y el éxito académico de los estudiantes dentro del entorno educativo. Su objetivo es optimizar la enseñanza y el desarrollo integral, evaluando necesidades, interviniendo en dificultades de aprendizaje y fomentando relaciones saludables. ', '2026-02-01 07:00:03', '2026-02-01 07:00:03');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_01_31_171429_create_patients_table', 1),
(5, '2026_01_31_171430_create_supplies_table', 1),
(6, '2026_01_31_171430_create_supply_movements_table', 1),
(7, '2026_01_31_171431_add_details_to_users_table', 1),
(8, '2026_01_31_171431_create_appointments_table', 1),
(9, '2026_01_31_171435_create_medical_histories_table', 1),
(10, '2026_01_31_211507_add_patient_id_to_supply_movements_table', 2),
(11, '2026_02_01_015629_create_cash_movements_table', 3),
(12, '2026_02_01_015637_add_payment_to_appointments_table', 3),
(13, '2026_02_01_024234_create_medical_specialties_table', 4),
(14, '2026_02_01_030647_create_doctor_specialty_table', 5),
(15, '2026_02_01_030654_create_doctor_profiles_table', 5),
(17, '2026_02_05_004101_create_especialidad_estudios_table', 6),
(18, '2026_02_12_000000_add_payment_method_to_appointments_table', 7),
(19, '2026_02_12_000001_cleanup_unpaid_cash_movements', 8),
(20, '2026_02_12_000002_add_payment_method_to_cash_movements_table', 9);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `patients`
--

CREATE TABLE `patients` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `dni` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `patients`
--

INSERT INTO `patients` (`id`, `name`, `dni`, `phone`, `address`, `email`, `created_at`, `updated_at`) VALUES
(1, 'Hernán Jose Abreü Diaz', '541097', '02392256673', 'Casa 10. Urbanizacion Parque Tuy', 'patricio.charal@gmail.com', '2026-02-01 00:18:18', '2026-02-20 19:50:58'),
(2, 'Ingri Liendo', '6256104', '02392256747', 'casa 37, la rivera, sector la acequia', 'ingriliendo@gmail.com', '2026-02-01 01:27:11', '2026-02-01 01:27:11'),
(3, 'dalton vargas', '234523', '0414132112', 'cruz del calvario', NULL, '2026-02-03 06:53:30', '2026-02-03 06:53:30');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('aTlhAhXC28utCbh9wWg6xr3Q8ykSWBbIixb8QC8C', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiSzRZVUNtNllVNVprVTR0bHM5WWE4VjBmMGtqTDBsWHliSmp3NUZDMiI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjI2OiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvY2FzaCI7czo1OiJyb3V0ZSI7czoxMDoiY2FzaC5pbmRleCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7fQ==', 1786566089),
('csHoL9OLjWwmmJSEFXgIsT13JMSSzNQHQytmpDs5', 8, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiTHJ4S2RzVEU2OThUdVo4UDhpY3NWTHdGdWFkUnZVN2V1M1Qya2tReSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9kYXNoYm9hcmQiO3M6NToicm91dGUiO3M6OToiZGFzaGJvYXJkIjt9czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6ODt9', 1786565933),
('UvhBAi1uxivnCIDqQMQyVa3h6X7SLcIhqWP7PC7g', 3, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiOVRpdU41QVJiejZUNlhmR2NhZnNDOEp0R3hOUHAzSUd2TE94blBGNSI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjM0OiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvYXBwb2ludG1lbnRzIjtzOjU6InJvdXRlIjtzOjE4OiJhcHBvaW50bWVudHMuaW5kZXgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aTozO30=', 1786565932);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `supplies`
--

CREATE TABLE `supplies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `expiration_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `supplies`
--

INSERT INTO `supplies` (`id`, `name`, `description`, `quantity`, `expiration_date`, `created_at`, `updated_at`) VALUES
(1, 'Gasas', 'Gasa Esteril', 18, '2027-12-31', '2026-02-01 00:25:23', '2026-02-05 03:51:26'),
(2, 'Inyectadoras', 'jeringas 10ml', 20, '2027-12-31', '2026-02-01 00:41:27', '2026-02-01 00:41:27'),
(3, 'Alcohol Absoluto', 'alcohol 90%', 4, '2026-12-31', '2026-02-01 00:50:52', '2026-02-01 01:38:54'),
(4, 'atamel', 'acetaminofen 500mg', 9, '2025-12-31', '2026-02-01 01:38:02', '2026-02-01 01:50:43'),
(5, 'Guantes Esteriles', 'una caja de 20 Guantes', 20, '2025-12-31', '2026-02-01 01:52:11', '2026-02-01 01:53:17'),
(6, 'algodones', 'caja', 60, '2028-12-31', '2026-02-03 06:58:09', '2026-02-03 06:58:09'),
(7, 'Sutura', 'sutura 10', 0, '2026-02-19', '2026-02-20 19:14:51', '2026-02-20 19:14:51');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `supply_movements`
--

CREATE TABLE `supply_movements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `supply_id` bigint(20) UNSIGNED NOT NULL,
  `patient_id` bigint(20) UNSIGNED DEFAULT NULL,
  `type` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `supply_movements`
--

INSERT INTO `supply_movements` (`id`, `supply_id`, `patient_id`, `type`, `quantity`, `reason`, `description`, `user_id`, `date`, `created_at`, `updated_at`) VALUES
(1, 4, NULL, 'in', 10, 'Entrada inicial', NULL, 7, '2026-01-31', '2026-02-01 01:38:02', '2026-02-01 01:38:02'),
(2, 3, 2, 'out', 1, 'Entrega a paciente', 'paciente se le entrega un alcohol', 7, '2026-01-31', '2026-02-01 01:38:54', '2026-02-01 01:38:54'),
(3, 4, 1, 'out', 1, 'Entrega a paciente', 'una caja de atamel', 7, '2026-01-31', '2026-02-01 01:50:43', '2026-02-01 01:50:43'),
(4, 5, NULL, 'in', 20, 'Entrada inicial', NULL, 7, '2026-01-31', '2026-02-01 01:52:11', '2026-02-01 01:52:11'),
(5, 1, 2, 'out', 1, 'Entrega a paciente', 'se entrega una gasa al paciente para limpieza de herida expuesta', 1, '2026-02-01', '2026-02-01 07:11:05', '2026-02-01 07:11:05'),
(6, 6, NULL, 'in', 60, 'Entrada inicial', NULL, 1, '2026-02-03', '2026-02-03 06:58:09', '2026-02-03 06:58:09'),
(7, 1, 3, 'out', 1, 'Entrega a paciente', 'hgfhg', 7, '2026-02-04', '2026-02-05 03:51:26', '2026-02-05 03:51:26'),
(8, 7, NULL, 'in', 0, 'Entrada inicial', NULL, 1, '2026-02-20', '2026-02-20 19:14:51', '2026-02-20 19:14:51');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'doctor',
  `phone` varchar(255) DEFAULT NULL,
  `dni` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `role`, `phone`, `dni`) VALUES
(1, 'Super Administrador', 'admin@admin.com', NULL, '$2y$12$Vvted9S73nKfAKWMrlo8qe80mM0ulyqMRgy6fHSfn/l5xkIB/EecO', 'QKrcd5lDUJFjD9RnG7x7OHPb7oJrmIrdT03NZvuSt5pSFeqfn0qYsibkHyXw', '2026-02-01 00:00:51', '2026-02-01 00:16:10', 'super_admin', '04141140982', '16576717'),
(2, 'Edgar Barrios', 'doctor@admin.com', NULL, '$2y$12$y4rhX2R/syqCpt1k7BgV6uQB07pQLwhkJ4jMNmlhoL0i7z15e/wfC', 'Gm2Em51meLgU83ROuaqFJIIWoRLpkXTj9idRlt8JDTpaXpBjHewiQqf7Pr5l', '2026-02-01 00:00:51', '2026-02-01 07:34:25', 'doctor', '0414-9874566', '123456'),
(3, 'Recepción', 'reception@admin.com', NULL, '$2y$12$tVL6zf5V28aNeLv9aHZ/.OigIij8rnnnGareInJE0hYIX4XYfDzCm', 'tOpEq5t58CjKeaYDeVlocoEJc22Y7U4CosGnAwhcmONO5hpWVNvNJksxGi6q', '2026-02-01 00:00:52', '2026-02-01 00:00:52', 'receptionist', NULL, NULL),
(4, 'Hernan Abreu', 'herchino53@gmail.com', NULL, '$2y$12$UjSHax1uE3rXfYWmWJrM.e5WN5s1AL74dDnGwNrwycgJ3.vY2aBG2', NULL, '2026-02-01 00:17:20', '2026-02-03 07:23:27', 'admin', '04141140982', '16576717'),
(6, 'Egdomar Barrios', 'medicogeneral@admin.com', NULL, '$2y$12$jaq6.RBN.mq3PXEATGlHz.g3Cs8Qook44jgaeXG4uJ2sup8skMuN.', NULL, '2026-02-01 01:30:49', '2026-02-01 01:30:49', 'doctor', '04141234565', '12012321'),
(7, 'Lixfe Salazar', 'farmaceutico@admin.com', NULL, '$2y$12$0Km0RvUpXBW73EPlfcu3keZUYDV4wFONB3b9UA4AI4tBVUnJrm0P.', NULL, '2026-02-01 01:37:04', '2026-02-01 01:37:04', 'pharmacist', '04123211122', '25654321'),
(8, 'Juan Perez', 'medicojuan@admin.com', NULL, '$2y$12$4Q474XEy/1D1CebXfBfPmu76KCdUXNOUrbKs.t.lIIcX/pweX3hv.', NULL, '2026-02-01 07:58:07', '2026-02-01 07:58:07', 'doctor', '04149874545', '6541230');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `appointments_patient_id_foreign` (`patient_id`),
  ADD KEY `appointments_doctor_id_foreign` (`doctor_id`);

--
-- Indices de la tabla `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indices de la tabla `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indices de la tabla `cash_movements`
--
ALTER TABLE `cash_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cash_movements_appointment_id_foreign` (`appointment_id`),
  ADD KEY `cash_movements_doctor_id_foreign` (`doctor_id`),
  ADD KEY `cash_movements_user_id_foreign` (`user_id`);

--
-- Indices de la tabla `doctor_profiles`
--
ALTER TABLE `doctor_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `doctor_profiles_user_id_unique` (`user_id`);

--
-- Indices de la tabla `doctor_specialty`
--
ALTER TABLE `doctor_specialty`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_specialty_user_id_foreign` (`user_id`),
  ADD KEY `doctor_specialty_medical_specialty_id_foreign` (`medical_specialty_id`);

--
-- Indices de la tabla `especialidad_estudios`
--
ALTER TABLE `especialidad_estudios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `especialidad_estudios_especialidad_id_foreign` (`especialidad_id`);

--
-- Indices de la tabla `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indices de la tabla `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indices de la tabla `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `medical_histories`
--
ALTER TABLE `medical_histories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medical_histories_patient_id_foreign` (`patient_id`),
  ADD KEY `medical_histories_doctor_id_foreign` (`doctor_id`);

--
-- Indices de la tabla `medical_specialties`
--
ALTER TABLE `medical_specialties`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `medical_specialties_name_unique` (`name`);

--
-- Indices de la tabla `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indices de la tabla `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `patients_dni_unique` (`dni`);

--
-- Indices de la tabla `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indices de la tabla `supplies`
--
ALTER TABLE `supplies`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `supply_movements`
--
ALTER TABLE `supply_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supply_movements_supply_id_foreign` (`supply_id`),
  ADD KEY `supply_movements_user_id_foreign` (`user_id`),
  ADD KEY `supply_movements_patient_id_foreign` (`patient_id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `cash_movements`
--
ALTER TABLE `cash_movements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `doctor_profiles`
--
ALTER TABLE `doctor_profiles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `doctor_specialty`
--
ALTER TABLE `doctor_specialty`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `especialidad_estudios`
--
ALTER TABLE `especialidad_estudios`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `medical_histories`
--
ALTER TABLE `medical_histories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `medical_specialties`
--
ALTER TABLE `medical_specialties`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `patients`
--
ALTER TABLE `patients`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `supplies`
--
ALTER TABLE `supplies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `supply_movements`
--
ALTER TABLE `supply_movements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `appointments_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `cash_movements`
--
ALTER TABLE `cash_movements`
  ADD CONSTRAINT `cash_movements_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cash_movements_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cash_movements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `doctor_profiles`
--
ALTER TABLE `doctor_profiles`
  ADD CONSTRAINT `doctor_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `doctor_specialty`
--
ALTER TABLE `doctor_specialty`
  ADD CONSTRAINT `doctor_specialty_medical_specialty_id_foreign` FOREIGN KEY (`medical_specialty_id`) REFERENCES `medical_specialties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `doctor_specialty_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `especialidad_estudios`
--
ALTER TABLE `especialidad_estudios`
  ADD CONSTRAINT `especialidad_estudios_especialidad_id_foreign` FOREIGN KEY (`especialidad_id`) REFERENCES `medical_specialties` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `medical_histories`
--
ALTER TABLE `medical_histories`
  ADD CONSTRAINT `medical_histories_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `medical_histories_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `supply_movements`
--
ALTER TABLE `supply_movements`
  ADD CONSTRAINT `supply_movements_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `supply_movements_supply_id_foreign` FOREIGN KEY (`supply_id`) REFERENCES `supplies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `supply_movements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
