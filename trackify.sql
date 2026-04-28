-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 28-04-2026 a las 22:23:29
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
-- Base de datos: `usuarios`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id_categoria` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `tipo` enum('ingreso','gasto') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id_categoria`, `nombre`, `tipo`) VALUES
(1, 'Otros', 'ingreso'),
(2, 'Servicios', 'gasto');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `consejos_ia`
--

CREATE TABLE `consejos_ia` (
  `id_consejos` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `mensaje` text NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metas_ahorro`
--

CREATE TABLE `metas_ahorro` (
  `id_meta` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `nombre_meta` varchar(100) NOT NULL,
  `monto_objetivo` decimal(10,0) NOT NULL,
  `monto_actual` decimal(10,0) NOT NULL,
  `fecha_limite` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientos`
--

CREATE TABLE `movimientos` (
  `id_movimiento` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `tipo` enum('ingreso','gasto') NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `fecha` date NOT NULL,
  `es_hormiga` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `movimientos`
--

INSERT INTO `movimientos` (`id_movimiento`, `id_usuario`, `id_categoria`, `monto`, `tipo`, `descripcion`, `fecha`, `es_hormiga`) VALUES
(1, 2, 1, 5000.00, 'ingreso', 'Transferencia', '2026-04-28', 0),
(2, 2, 2, 7000.00, 'gasto', 'Gas', '2026-04-28', 0),
(3, 1, 1, 40000.00, 'ingreso', 'Transferencia', '2026-04-28', 0),
(4, 1, 2, 30000.00, 'gasto', 'Luz', '2026-04-28', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `clave` varchar(255) NOT NULL,
  `fecha_registro` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre`, `email`, `clave`, `fecha_registro`) VALUES
(1, 'Agus', 'agustinacecch@sagradocorazon.edu.ar', '$2y$10$qVjzvUsVks3tHAYnpcaDDOzZo.6eTjaPRmbzgYa7Npv2MoA0oKbge', '2026-04-28 17:14:39'),
(2, 'María', 'mariadevirgiglio@sagradocorazon.edu.ar', '$2y$10$FJiAgwd9.dHL0uuJcaBXtOeHvBmiWaetZ3Sjrw5NQycjC4HME.8Pu', '2026-04-28 17:20:49');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id_categoria`);

--
-- Indices de la tabla `consejos_ia`
--
ALTER TABLE `consejos_ia`
  ADD PRIMARY KEY (`id_consejos`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `metas_ahorro`
--
ALTER TABLE `metas_ahorro`
  ADD PRIMARY KEY (`id_meta`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `movimientos`
--
ALTER TABLE `movimientos`
  ADD PRIMARY KEY (`id_movimiento`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_categoria` (`id_categoria`);

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
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `metas_ahorro`
--
ALTER TABLE `metas_ahorro`
  MODIFY `id_meta` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `movimientos`
--
ALTER TABLE `movimientos`
  MODIFY `id_movimiento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `consejos_ia`
--
ALTER TABLE `consejos_ia`
  ADD CONSTRAINT `consejos_ia_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `metas_ahorro`
--
ALTER TABLE `metas_ahorro`
  ADD CONSTRAINT `metas_ahorro_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `movimientos`
--
ALTER TABLE `movimientos`
  ADD CONSTRAINT `movimientos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `movimientos_ibfk_2` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
