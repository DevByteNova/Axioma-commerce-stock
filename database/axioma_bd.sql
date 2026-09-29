-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 24-09-2026 a las 23:10:19
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
-- Base de datos: `axioma_bd`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `ID_CLIENTE` int(11) NOT NULL,
  `NOMBRE` varchar(100) NOT NULL,
  `APELLIDOS` varchar(100) NOT NULL,
  `DIRECCION` varchar(255) DEFAULT NULL,
  `TELEFONO` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`ID_CLIENTE`, `NOMBRE`, `APELLIDOS`, `DIRECCION`, `TELEFONO`, `email`, `password`) VALUES
(1, 'Carlos', 'Mendoza', 'Calle 1 # 2-3', '3001112233', 'carlos@email.com', 'eb52fc9a4b3a81a2000a9e774d5aa515'),
(2, 'Ana', 'Gómez', 'Calle 2 # 3-4', '3002223344', 'ana@email.com', 'eb52fc9a4b3a81a2000a9e774d5aa515'),
(3, 'Luis', 'Torres', 'Calle 3 # 4-5', '3003334455', 'luis@email.com', 'eb52fc9a4b3a81a2000a9e774d5aa515'),
(4, 'María', 'Rodríguez', 'Calle 4 # 5-6', '3004445566', 'maria@email.com', 'eb52fc9a4b3a81a2000a9e774d5aa515'),
(5, 'Jorge', 'Pérez', 'Calle 5 # 6-7', '3005556677', 'jorge@email.com', 'eb52fc9a4b3a81a2000a9e774d5aa515'),
(6, 'Lucía', 'Fernández', 'Calle 6 # 7-8', '3006667788', 'lucia@email.com', 'eb52fc9a4b3a81a2000a9e774d5aa515'),
(7, 'Pedro', 'Ramírez', 'Calle 7 # 8-9', '3007778899', 'pedro@email.com', 'eb52fc9a4b3a81a2000a9e774d5aa515'),
(8, 'Sofía', 'Castro', 'Calle 8 # 9-10', '3008889900', 'sofia@email.com', 'eb52fc9a4b3a81a2000a9e774d5aa515'),
(9, 'David', 'Morales', 'Calle 9 # 10-11', '3009990011', 'david@email.com', 'eb52fc9a4b3a81a2000a9e774d5aa515'),
(10, 'Valentina', 'Ruiz', 'Calle 10 # 11-12', '3011112233', 'valentina@email.com', 'eb52fc9a4b3a81a2000a9e774d5aa515'),
(11, 'Mateo', 'Silva', 'Calle 11 # 12-13', '3012223344', 'mateo@email.com', 'eb52fc9a4b3a81a2000a9e774d5aa515'),
(12, 'Camila', 'Vargas', 'Calle 12 # 13-14', '3013334455', 'camila@email.com', 'eb52fc9a4b3a81a2000a9e774d5aa515'),
(13, 'Andrés', 'Castro', 'Calle 13 # 14-15', '3014445566', 'andres@email.com', 'eb52fc9a4b3a81a2000a9e774d5aa515'),
(14, 'Valeria', 'Ortiz', 'Calle 14 # 15-16', '3015556677', 'valeria@email.com', 'eb52fc9a4b3a81a2000a9e774d5aa515'),
(15, 'Gabriel', 'Molina', 'Calle 15 # 16-17', '3016667788', 'gabriel@email.com', 'eb52fc9a4b3a81a2000a9e774d5aa515'),
(16, 'Daniela', 'Herrera', 'Calle 16 # 17-18', '3017778899', 'daniela@email.com', 'eb52fc9a4b3a81a2000a9e774d5aa515'),
(17, 'Esteban', 'Ríos', 'Calle 17 # 18-19', '3018889900', 'esteban@email.com', 'eb52fc9a4b3a81a2000a9e774d5aa515'),
(18, 'Mariana', 'Paredes', 'Calle 18 # 19-20', '3019990011', 'mariana@email.com', 'eb52fc9a4b3a81a2000a9e774d5aa515'),
(19, 'Felipe', 'Navas', 'Calle 19 # 20-21', '3021112233', 'felipe@email.com', 'eb52fc9a4b3a81a2000a9e774d5aa515'),
(20, 'Juliana', 'Blanco', 'Calle 20 # 21-22', '3022233344', 'juliana@email.com', 'eb52fc9a4b3a81a2000a9e774d5aa515'),
(21, 'Cliente', 'Prueba', 'Calle Falsa 789', '3009876543', 'cliente.test@email.com', 'cliente123');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `facturas`
--

CREATE TABLE `facturas` (
  `ID_FACTURA` int(11) NOT NULL,
  `FECHA` date NOT NULL,
  `ID_CLIENTE` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `facturas`
--

INSERT INTO `facturas` (`ID_FACTURA`, `FECHA`, `ID_CLIENTE`) VALUES
(1, '2026-08-07', 1),
(2, '2026-08-08', 2),
(3, '2026-08-09', 3),
(4, '2026-08-10', 4),
(5, '2026-08-11', 5),
(6, '2026-08-12', 6),
(7, '2026-08-13', 7),
(8, '2026-08-14', 8),
(9, '2026-08-15', 9),
(10, '2026-08-16', 10),
(11, '2026-08-17', 11),
(12, '2026-08-18', 12),
(13, '2026-08-19', 13),
(14, '2026-08-20', 14),
(15, '2026-08-21', 15),
(16, '2026-08-22', 16),
(17, '2026-08-23', 17),
(18, '2026-08-24', 18),
(19, '2026-08-25', 19),
(20, '2026-08-27', 20),
(21, '2026-08-27', 21),
(22, '2026-09-24', 21),
(23, '2026-09-24', 21),
(24, '2026-09-24', 21);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metodos_pago`
--

CREATE TABLE `metodos_pago` (
  `ID_PAGO` int(11) NOT NULL,
  `nombre_metodo` varchar(50) NOT NULL,
  `estado` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `metodos_pago`
--

INSERT INTO `metodos_pago` (`ID_PAGO`, `nombre_metodo`, `estado`) VALUES
(1, 'Tarjeta de Crédito / Débito', 1),
(2, 'PSE (Pago Seguro en Línea)', 1),
(3, 'Efectivo', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `ID_PRODUCTO` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `DESCRIPCION` varchar(250) NOT NULL,
  `PRECIO` decimal(10,2) NOT NULL,
  `STOCK` int(11) DEFAULT 0,
  `talla` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`ID_PRODUCTO`, `nombre`, `DESCRIPCION`, `PRECIO`, `STOCK`, `talla`) VALUES
(1, 'Pijama Franela Polar Osito', 'Pijama abrigada de invierno con diseño de osito', 45000.00, 15, 'M'),
(2, 'Pijama Corta Verano Stripes', 'Pijama fresca de dos piezas para verano', 32000.00, 20, 'S'),
(3, 'Pijama Elegante Satín Noir', 'Pijama sofisticada de satín negro', 65000.00, 10, 'L'),
(4, 'Pijama Térmica Antifrío', 'Pijama de alta retención térmica', 55000.00, 12, 'XL'),
(5, 'Pijama Clásica Algodón Blue', 'Pijama tradicional en suave algodón azul', 38000.00, 25, 'M'),
(6, 'Pijama Satín Rosa Pastel', 'Conjunto de satín delicado color rosa', 60000.00, 8, 'S'),
(7, 'Pijama Franela Cuadros Leñador', 'Estilo clásico a cuadros de franela', 48000.00, 14, 'L'),
(8, 'Pijama Short y Camisola Floral', 'Estampado floral con short cómodo', 35000.00, 18, 'M'),
(9, 'Pijama Manga Larga Lunar', 'Pijama de manga larga con puntos blancos', 42000.00, 22, 'S'),
(10, 'Pijama Conjunto 2 Piezas Gray', 'Conjunto cómodo color gris', 40000.00, 30, 'L'),
(11, 'Pijama Enterizo Unicornio', 'Pijama enteriza con capucha temática', 75000.00, 5, 'M'),
(12, 'Pijama Maternal Confort', 'Especial para comodidad en etapa maternal', 50000.00, 10, 'L'),
(13, 'Pijama Infantil Dinosaurios', 'Diseño divertido para niños', 30000.00, 18, 'S'),
(14, 'Pijama Dúo Pareja King & Queen', 'Pijama combinada para parejas', 95000.00, 7, 'XL'),
(15, 'Pijama Navideña Reno', 'Motivo especial de temporada navideña', 52000.00, 15, 'M'),
(16, 'Pijama Estampada Gatos', 'Diseño tierno con estampado de gatos', 36000.00, 19, 'S'),
(17, 'Pijama Lisa Minimalista', 'Diseño liso de corte sencillo', 34000.00, 25, 'M'),
(18, 'Pijama Oversize Comfort', 'Corte amplio y holgado para máxima comodidad', 46000.00, 11, 'L'),
(19, 'Pijama Deluxe Encaje', 'Acabados finos con detalles de encaje', 70000.00, 5, 'S'),
(20, 'Pijama Básica Algodón Grey', 'Básica de uso diario en algodón', 28000.00, 39, 'M');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `ID_CLIENTE` int(11) DEFAULT NULL,
  `ID_VENDEDOR` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `rol` varchar(20) DEFAULT 'Vendedor'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `usuario`, `email`, `password_hash`, `ID_CLIENTE`, `ID_VENDEDOR`, `is_active`, `created_at`, `rol`) VALUES
(1, 'carlos.m', 'carlos@email.com', 'eb52fc9a4b3a81a2000a9e774d5aa515', 1, NULL, 1, '2026-08-07 20:43:06', 'Cliente'),
(2, 'ana.g', 'ana@email.com', 'eb52fc9a4b3a81a2000a9e774d5aa515', 2, NULL, 1, '2026-08-08 20:43:06', 'Cliente'),
(3, 'luis.t', 'luis@email.com', 'eb52fc9a4b3a81a2000a9e774d5aa515', 3, NULL, 1, '2026-08-09 20:43:06', 'Cliente'),
(4, 'maria.r', 'maria@email.com', 'eb52fc9a4b3a81a2000a9e774d5aa515', 4, NULL, 1, '2026-08-10 20:43:06', 'Cliente'),
(5, 'jorge.p', 'jorge@email.com', 'eb52fc9a4b3a81a2000a9e774d5aa515', 5, NULL, 1, '2026-08-11 20:43:06', 'Cliente'),
(6, 'lucia.f', 'lucia@email.com', 'eb52fc9a4b3a81a2000a9e774d5aa515', 6, NULL, 1, '2026-08-12 20:43:06', 'Cliente'),
(7, 'pedro.r', 'pedro@email.com', 'eb52fc9a4b3a81a2000a9e774d5aa515', 7, NULL, 1, '2026-08-13 20:43:06', 'Cliente'),
(8, 'sofia.c', 'sofia@email.com', 'eb52fc9a4b3a81a2000a9e774d5aa515', 8, NULL, 1, '2026-08-14 20:43:06', 'Cliente'),
(9, 'david.m', 'david@email.com', 'eb52fc9a4b3a81a2000a9e774d5aa515', 9, NULL, 1, '2026-08-15 20:43:06', 'Cliente'),
(10, 'valentina.r', 'valentina@email.com', 'eb52fc9a4b3a81a2000a9e774d5aa515', 10, NULL, 1, '2026-08-16 20:43:06', 'Cliente'),
(11, 'mateo.s', 'mateo@email.com', 'eb52fc9a4b3a81a2000a9e774d5aa515', 11, NULL, 1, '2026-08-17 20:43:06', 'Cliente'),
(12, 'camila.v', 'camila@email.com', 'eb52fc9a4b3a81a2000a9e774d5aa515', 12, NULL, 1, '2026-08-18 20:43:06', 'Cliente'),
(13, 'andres.c', 'andres@email.com', 'eb52fc9a4b3a81a2000a9e774d5aa515', 13, NULL, 1, '2026-08-19 20:43:06', 'Cliente'),
(14, 'valeria.o', 'valeria@email.com', 'eb52fc9a4b3a81a2000a9e774d5aa515', 14, NULL, 1, '2026-08-20 20:43:06', 'Cliente'),
(15, 'gabriel.m', 'gabriel@email.com', 'eb52fc9a4b3a81a2000a9e774d5aa515', 15, NULL, 1, '2026-08-21 20:43:06', 'Cliente'),
(16, 'daniela.h', 'daniela@email.com', 'eb52fc9a4b3a81a2000a9e774d5aa515', 16, NULL, 1, '2026-08-22 20:43:06', 'Cliente'),
(17, 'esteban.r', 'esteban@email.com', 'eb52fc9a4b3a81a2000a9e774d5aa515', 17, NULL, 1, '2026-08-23 20:43:06', 'Cliente'),
(18, 'mariana.p', 'mariana@email.com', 'eb52fc9a4b3a81a2000a9e774d5aa515', 18, NULL, 1, '2026-08-24 20:43:06', 'Cliente'),
(19, 'felipe.n', 'felipe@email.com', 'eb52fc9a4b3a81a2000a9e774d5aa515', 19, NULL, 1, '2026-08-25 20:43:06', 'Cliente'),
(20, 'juliana.b', 'juliana@email.com', 'eb52fc9a4b3a81a2000a9e774d5aa515', 20, NULL, 1, '2026-08-27 20:43:06', 'Cliente'),
(21, 'admin', 'admin@axioma.com', 'eb52fc9a4b3a81a2000a9e774d5aa515', NULL, NULL, 1, '2026-08-27 20:48:30', 'Administrador'),
(22, 'carlos.v', 'carlos.v@axioma.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 1, 1, '2026-08-27 20:49:30', 'Vendedor'),
(23, 'ana.v', 'ana.v@axioma.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 2, 1, '2026-08-27 20:49:30', 'Vendedor'),
(24, 'luis.v', 'luis.v@axioma.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 3, 1, '2026-08-27 20:49:30', 'Vendedor'),
(25, 'maria.v', 'maria.v@axioma.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 4, 1, '2026-08-27 20:49:30', 'Vendedor'),
(26, 'jorge.v', 'jorge.v@axioma.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 5, 1, '2026-08-27 20:49:30', 'Vendedor'),
(27, 'test_vendedor', 'prueba.v@axioma.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 21, 1, '2026-08-27 20:55:03', 'Vendedor'),
(28, 'superadmin', 'super@axioma.com', 'admin123', NULL, NULL, 1, '2026-08-27 20:56:48', 'Administrador'),
(29, 'vendedor_test', 'vendedor.test@axioma.com', 'vendedor123', NULL, 22, 1, '2026-08-27 20:57:29', 'Vendedor'),
(30, 'cliente_test', 'cliente.test@email.com', 'cliente123', 21, NULL, 1, '2026-08-27 20:59:26', 'Cliente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vendedor`
--

CREATE TABLE `vendedor` (
  `ID_VENDEDOR` int(11) NOT NULL,
  `NOMBRE` varchar(100) NOT NULL,
  `DIRECCION` varchar(255) DEFAULT NULL,
  `CORREO` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `vendedor`
--

INSERT INTO `vendedor` (`ID_VENDEDOR`, `NOMBRE`, `DIRECCION`, `CORREO`) VALUES
(1, 'Carlos Vendedor', 'Calle 10 # 20-30', 'carlos.v@axioma.com'),
(2, 'Ana Vendedora', 'Carrera 15 # 45-12', 'ana.v@axioma.com'),
(3, 'Luis Vendedor', 'Avenida 68 # 12-40', 'luis.v@axioma.com'),
(4, 'María Vendedora', 'Diagonal 45 # 30-10', 'maria.v@axioma.com'),
(5, 'Jorge Vendedor', 'Transversal 23 # 50-60', 'jorge.v@axioma.com'),
(6, 'Lucía Vendedora', 'Calle 100 # 15-20', 'lucia.v@axioma.com'),
(7, 'Pedro Vendedor', 'Carrera 7 # 72-10', 'pedro.v@axioma.com'),
(8, 'Sofía Vendedora', 'Calle 53 # 25-30', 'sofia.v@axioma.com'),
(9, 'David Vendedor', 'Avenida Boyacá # 49-20', 'david.v@axioma.com'),
(10, 'Valentina Vendedora', 'Calle 80 # 11-90', 'valentina.v@axioma.com'),
(11, 'Mateo Vendedor', 'Carrera 30 # 8-45', 'mateo.v@axioma.com'),
(12, 'Camila Vendedora', 'Calle 140 # 7-50', 'camila.v@axioma.com'),
(13, 'Andrés Vendedor', 'Carrera 50 # 26-85', 'andres.v@axioma.com'),
(14, 'Valeria Vendedora', 'Calle 26 # 68C-61', 'valeria.v@axioma.com'),
(15, 'Gabriel Vendedor', 'Avenida 1 Mayo # 10-20', 'gabriel.v@axioma.com'),
(16, 'Daniela Vendedora', 'Calle 72 # 10-34', 'daniela.v@axioma.com'),
(17, 'Esteban Vendedor', 'Carrera 9 # 116-40', 'esteban.v@axioma.com'),
(18, 'Mariana Vendedora', 'Calle 116 # 15-25', 'mariana.v@axioma.com'),
(19, 'Felipe Vendedor', 'Carrera 13 # 35-12', 'felipe.v@axioma.com'),
(20, 'Juliana Vendedora', 'Calle 63 # 24-18', 'juliana.v@axioma.com'),
(21, 'Prueba Vendedor', 'Calle Falsa 123', 'prueba.v@axioma.com'),
(22, 'Vendedor Test', 'Avenida Siempre Viva 456', 'vendedor.test@axioma.com');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

CREATE TABLE `ventas` (
  `ID_VENTAS` int(11) NOT NULL,
  `ID_PRODUCTO` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `FECHA_VENTA` timestamp NOT NULL DEFAULT current_timestamp(),
  `ID_VENDEDOR` int(11) NOT NULL,
  `ID_CLIENTE` int(11) DEFAULT 1,
  `ID_PAGO` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ventas`
--

INSERT INTO `ventas` (`ID_VENTAS`, `ID_PRODUCTO`, `cantidad`, `total`, `FECHA_VENTA`, `ID_VENDEDOR`, `ID_CLIENTE`, `ID_PAGO`) VALUES
(1, 1, 2, 90000.00, '2026-08-07 20:42:53', 1, 1, 1),
(2, 3, 1, 65000.00, '2026-08-08 20:42:53', 2, 2, 2),
(3, 5, 3, 114000.00, '2026-08-09 20:42:53', 3, 3, 3),
(4, 2, 1, 32000.00, '2026-08-10 20:42:53', 4, 4, 4),
(5, 8, 2, 70000.00, '2026-08-11 20:42:53', 5, 5, 5),
(6, 11, 1, 75000.00, '2026-08-12 20:42:53', 6, 6, 6),
(7, 14, 1, 95000.00, '2026-08-13 20:42:53', 7, 7, 7),
(8, 10, 2, 80000.00, '2026-08-14 20:42:53', 8, 8, 8),
(9, 12, 1, 50000.00, '2026-08-15 20:42:53', 9, 9, 9),
(10, 15, 2, 104000.00, '2026-08-16 20:42:53', 10, 10, 10),
(11, 4, 1, 55000.00, '2026-08-17 20:42:53', 11, 11, 1),
(12, 6, 1, 60000.00, '2026-08-18 20:42:53', 12, 12, 2),
(13, 7, 2, 96000.00, '2026-08-19 20:42:53', 13, 13, 3),
(14, 9, 3, 126000.00, '2026-08-20 20:42:53', 14, 14, 4),
(15, 13, 2, 60000.00, '2026-08-21 20:42:53', 15, 15, 5),
(16, 16, 1, 36000.00, '2026-08-22 20:42:53', 16, 16, 6),
(17, 17, 2, 68000.00, '2026-08-23 20:42:53', 17, 17, 7),
(18, 18, 1, 46000.00, '2026-08-24 20:42:53', 18, 18, 8),
(19, 19, 1, 70000.00, '2026-08-25 20:42:53', 19, 19, 9),
(20, 20, 4, 112000.00, '2026-08-27 20:42:53', 20, 20, 10),
(21, 1, 2, 75000.00, '2026-08-27 21:02:43', 1, 21, 1),
(22, 19, 1, 70000.00, '2026-09-25 01:41:23', 1, 21, 3),
(23, 16, 1, 36000.00, '2026-09-25 02:08:02', 1, 21, 1),
(24, 18, 1, 46000.00, '2026-09-25 02:08:02', 1, 21, 1),
(25, 20, 1, 28000.00, '2026-09-25 03:46:47', 1, 21, 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`ID_CLIENTE`);

--
-- Indices de la tabla `facturas`
--
ALTER TABLE `facturas`
  ADD PRIMARY KEY (`ID_FACTURA`),
  ADD KEY `fk_factura_cliente` (`ID_CLIENTE`);

--
-- Indices de la tabla `metodos_pago`
--
ALTER TABLE `metodos_pago`
  ADD PRIMARY KEY (`ID_PAGO`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`ID_PRODUCTO`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_usuario_cliente` (`ID_CLIENTE`),
  ADD KEY `fk_usuario_vendedor` (`ID_VENDEDOR`);

--
-- Indices de la tabla `vendedor`
--
ALTER TABLE `vendedor`
  ADD PRIMARY KEY (`ID_VENDEDOR`);

--
-- Indices de la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`ID_VENTAS`),
  ADD KEY `fk_ventas_producto` (`ID_PRODUCTO`),
  ADD KEY `fk_ventas_vendedor` (`ID_VENDEDOR`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `ID_CLIENTE` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `facturas`
--
ALTER TABLE `facturas`
  MODIFY `ID_FACTURA` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de la tabla `metodos_pago`
--
ALTER TABLE `metodos_pago`
  MODIFY `ID_PAGO` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `ID_PRODUCTO` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de la tabla `vendedor`
--
ALTER TABLE `vendedor`
  MODIFY `ID_VENDEDOR` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `ID_VENTAS` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `facturas`
--
ALTER TABLE `facturas`
  ADD CONSTRAINT `fk_factura_cliente` FOREIGN KEY (`ID_CLIENTE`) REFERENCES `clientes` (`ID_CLIENTE`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuario_cliente` FOREIGN KEY (`ID_CLIENTE`) REFERENCES `clientes` (`ID_CLIENTE`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_usuario_vendedor` FOREIGN KEY (`ID_VENDEDOR`) REFERENCES `vendedor` (`ID_VENDEDOR`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD CONSTRAINT `fk_ventas_producto` FOREIGN KEY (`ID_PRODUCTO`) REFERENCES `productos` (`ID_PRODUCTO`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ventas_vendedor` FOREIGN KEY (`ID_VENDEDOR`) REFERENCES `vendedor` (`ID_VENDEDOR`) ON UPDATE CASCADE;

-- Hashes bcrypt de las cuentas de prueba.
UPDATE `usuarios` SET `password_hash` = '$2y$10$kqxjv4SX2QMg9y5suD8DMeeHtbuI4VQWpyGdOn/qdtt0xn3k6hk66' WHERE `usuario` = 'admin';
UPDATE `usuarios` SET `password_hash` = '$2y$10$TstFYJ.X.im8RHTJZjJS2eANYNUlGy8RHaP9l8iIGW12P/xIWMKrS' WHERE `usuario` = 'vendedor_test';
UPDATE `usuarios` SET `password_hash` = '$2y$10$y3a7FhygEnm.bi/jo/ri5u.wTEwmg0eT6i.ahu3xSOJpUtCHi6Ytm' WHERE `usuario` = 'cliente_test';

-- --------------------------------------------------------
-- Catálogo por categorías e imágenes (ver migracion_catalogo.sql)
-- --------------------------------------------------------

-- 1. Tabla de categorías
CREATE TABLE IF NOT EXISTS `categorias` (
  `ID_CATEGORIA` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(60) NOT NULL,
  `slug` varchar(60) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ID_CATEGORIA`),
  UNIQUE KEY `uq_categoria_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `categorias` (`ID_CATEGORIA`, `nombre`, `slug`, `descripcion`) VALUES
(1, 'Mujer', 'mujer', 'Pijamas y batas para dama: satín, algodón, franela y encaje.'),
(2, 'Hombre', 'hombre', 'Pijamas para caballero: conjuntos, franela y térmicas.'),
(3, 'Infantil', 'infantil', 'Diseños divertidos y cómodos para niñas y niños.'),
(4, 'Temáticas y ocasiones', 'tematicas', 'Pijamas de temporada, para parejas y fechas especiales.');

-- 2. Nuevas columnas en productos
ALTER TABLE `productos`
  ADD COLUMN IF NOT EXISTS `ID_CATEGORIA` int(11) DEFAULT NULL AFTER `talla`,
  ADD COLUMN IF NOT EXISTS `imagen` varchar(255) DEFAULT NULL AFTER `ID_CATEGORIA`;

ALTER TABLE `productos`
  ADD KEY IF NOT EXISTS `fk_producto_categoria` (`ID_CATEGORIA`);

ALTER TABLE `productos`
  ADD CONSTRAINT `fk_producto_categoria` FOREIGN KEY IF NOT EXISTS (`ID_CATEGORIA`)
  REFERENCES `categorias` (`ID_CATEGORIA`) ON DELETE SET NULL ON UPDATE CASCADE;

-- 3. Clasificar los productos existentes
UPDATE `productos` SET `ID_CATEGORIA` = 1 WHERE `ID_PRODUCTO` IN (1, 2, 3, 6, 8, 9, 12, 19) AND `ID_CATEGORIA` IS NULL;
UPDATE `productos` SET `ID_CATEGORIA` = 2 WHERE `ID_PRODUCTO` IN (4, 5, 7, 10, 17, 18, 20) AND `ID_CATEGORIA` IS NULL;
UPDATE `productos` SET `ID_CATEGORIA` = 3 WHERE `ID_PRODUCTO` IN (13, 16) AND `ID_CATEGORIA` IS NULL;
UPDATE `productos` SET `ID_CATEGORIA` = 4 WHERE `ID_PRODUCTO` IN (11, 14, 15) AND `ID_CATEGORIA` IS NULL;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
