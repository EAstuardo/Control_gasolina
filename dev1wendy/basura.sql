-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 29-04-2026 a las 06:12:47
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
-- Base de datos: `basura`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `camiones`
--

CREATE TABLE `camiones` (
  `id_camion` int(11) NOT NULL,
  `numero_placa` varchar(20) NOT NULL,
  `marca` varchar(100) NOT NULL,
  `modelo` varchar(100) NOT NULL,
  `anio` year(4) NOT NULL,
  `capacidad_kg` decimal(10,2) DEFAULT NULL,
  `estado` enum('ACTIVO','INACTIVO','MANTENIMIENTO') DEFAULT 'ACTIVO',
  `id_colonia` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `camiones`
--

INSERT INTO `camiones` (`id_camion`, `numero_placa`, `marca`, `modelo`, `anio`, `capacidad_kg`, `estado`, `id_colonia`, `created_at`) VALUES
(1, 'P-123ABC', 'Ford', 'F-350', '2020', 5000.00, 'ACTIVO', 1, '2026-04-29 01:56:04');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id_cliente` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `id_colonia` int(11) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `colonias`
--

CREATE TABLE `colonias` (
  `id_colonia` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `tarifa_mensual` decimal(10,2) NOT NULL CHECK (`tarifa_mensual` >= 0),
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `colonias`
--

INSERT INTO `colonias` (`id_colonia`, `nombre`, `descripcion`, `tarifa_mensual`, `activo`, `created_at`, `updated_at`) VALUES
(1, 'Colonia Centro', NULL, 150.00, 1, '2026-04-29 01:55:43', '2026-04-29 01:55:43');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `combustible`
--

CREATE TABLE `combustible` (
  `id_combustible` int(11) NOT NULL,
  `id_camion` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `litros` decimal(10,2) NOT NULL CHECK (`litros` > 0),
  `kilometraje` int(11) NOT NULL,
  `precio_litro` decimal(10,2) NOT NULL,
  `costo_total` decimal(10,2) NOT NULL CHECK (`costo_total` > 0),
  `tipo_combustible` enum('DIESEL','GASOLINA') DEFAULT 'DIESEL',
  `id_usuario` int(11) NOT NULL,
  `observaciones` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `combustible`
--

INSERT INTO `combustible` (`id_combustible`, `id_camion`, `fecha`, `litros`, `kilometraje`, `precio_litro`, `costo_total`, `tipo_combustible`, `id_usuario`, `observaciones`, `created_at`) VALUES
(1, 1, '2026-04-26', 50.00, 15000, 22.50, 1125.00, 'DIESEL', 1, NULL, '2026-04-29 03:32:22');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id_pago` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL CHECK (`monto` > 0),
  `fecha_pago` date NOT NULL,
  `anio` smallint(6) NOT NULL,
  `mes` tinyint(4) NOT NULL CHECK (`mes` between 1 and 12),
  `metodo_pago` enum('EFECTIVO','TRANSFERENCIA','TARJETA') DEFAULT 'EFECTIVO',
  `observaciones` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recibos`
--

CREATE TABLE `recibos` (
  `id_recibo` int(11) NOT NULL,
  `id_pago` int(11) NOT NULL,
  `numero_recibo` varchar(50) NOT NULL,
  `fecha_emision` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('ADMIN','COBRADOR','OPERADOR') NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre`, `email`, `password`, `rol`, `activo`, `created_at`, `updated_at`) VALUES
(1, 'Admin Test', 'admin@test.com', '1234', 'ADMIN', 1, '2026-04-29 01:55:56', '2026-04-29 01:55:56');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `camiones`
--
ALTER TABLE `camiones`
  ADD PRIMARY KEY (`id_camion`),
  ADD UNIQUE KEY `numero_placa` (`numero_placa`),
  ADD KEY `idx_camion_colonia` (`id_colonia`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id_cliente`),
  ADD KEY `idx_cliente_colonia` (`id_colonia`);

--
-- Indices de la tabla `colonias`
--
ALTER TABLE `colonias`
  ADD PRIMARY KEY (`id_colonia`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `combustible`
--
ALTER TABLE `combustible`
  ADD PRIMARY KEY (`id_combustible`),
  ADD KEY `idx_combustible_camion` (`id_camion`),
  ADD KEY `idx_combustible_usuario` (`id_usuario`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id_pago`),
  ADD UNIQUE KEY `unique_pago_cliente_mes` (`id_cliente`,`anio`,`mes`),
  ADD KEY `idx_pago_cliente` (`id_cliente`),
  ADD KEY `idx_pago_usuario` (`id_usuario`),
  ADD KEY `idx_pago_periodo` (`anio`,`mes`);

--
-- Indices de la tabla `recibos`
--
ALTER TABLE `recibos`
  ADD PRIMARY KEY (`id_recibo`),
  ADD UNIQUE KEY `id_pago` (`id_pago`),
  ADD UNIQUE KEY `numero_recibo` (`numero_recibo`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `camiones`
--
ALTER TABLE `camiones`
  MODIFY `id_camion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `colonias`
--
ALTER TABLE `colonias`
  MODIFY `id_colonia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `combustible`
--
ALTER TABLE `combustible`
  MODIFY `id_combustible` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id_pago` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `recibos`
--
ALTER TABLE `recibos`
  MODIFY `id_recibo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `camiones`
--
ALTER TABLE `camiones`
  ADD CONSTRAINT `fk_camion_colonia` FOREIGN KEY (`id_colonia`) REFERENCES `colonias` (`id_colonia`);

--
-- Filtros para la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD CONSTRAINT `fk_cliente_colonia` FOREIGN KEY (`id_colonia`) REFERENCES `colonias` (`id_colonia`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `combustible`
--
ALTER TABLE `combustible`
  ADD CONSTRAINT `fk_combustible_camion` FOREIGN KEY (`id_camion`) REFERENCES `camiones` (`id_camion`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_combustible_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `fk_pago_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pago_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `recibos`
--
ALTER TABLE `recibos`
  ADD CONSTRAINT `fk_recibo_pago` FOREIGN KEY (`id_pago`) REFERENCES `pagos` (`id_pago`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
