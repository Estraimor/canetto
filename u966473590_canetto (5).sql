-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 04-07-2026 a las 13:09:03
-- Versión del servidor: 8.4.7
-- Versión de PHP: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `u966473590_canetto`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditoria`
--

DROP TABLE IF EXISTS `auditoria`;
CREATE TABLE IF NOT EXISTS `auditoria` (
  `idauditoria` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int DEFAULT NULL,
  `usuario_nombre` varchar(100) DEFAULT NULL,
  `accion` varchar(100) NOT NULL,
  `modulo` varchar(50) DEFAULT NULL,
  `descripcion` text,
  `ip` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `sucursal_nombre` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`idauditoria`)
) ENGINE=InnoDB AUTO_INCREMENT=198 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `auditoria`
--

INSERT INTO `auditoria` (`idauditoria`, `usuario_id`, `usuario_nombre`, `accion`, `modulo`, `descripcion`, `ip`, `created_at`, `sucursal_nombre`) VALUES
(1, 1, 'Luciano', 'editar', 'roles', 'Editó rol: Cliente', '::1', '2026-04-03 14:47:25', NULL),
(2, 1, 'Luciano', 'editar', 'roles', 'Editó rol: Cliente', '::1', '2026-04-03 18:11:26', NULL),
(3, 1, 'Luciano', 'editar', 'roles', 'Editó rol: Cliente', '::1', '2026-04-03 18:12:17', NULL),
(4, 1, 'Luciano', 'editar', 'roles', 'Actualizó roles de usuario: POedro (2 rol/es)', '::1', '2026-04-03 18:12:27', NULL),
(5, 1, 'Luciano', 'editar', 'roles', 'Actualizó roles de usuario: POedro (1 rol/es)', '::1', '2026-04-03 18:12:41', NULL),
(6, 1, 'Luciano', 'crear', 'metodos_pago', 'Creó método de pago: prueba', '::1', '2026-04-03 18:13:06', NULL),
(7, 1, 'Luciano', 'eliminar', 'metodos_pago', 'Eliminó método de pago: prueba', '::1', '2026-04-03 18:13:14', NULL),
(8, 1, 'Luciano', 'crear', 'sucursales', 'Creó sucursal: Casa Central Juany', '::1', '2026-04-03 18:13:58', NULL),
(9, 1, 'Luciano', 'editar', 'sucursales', 'Editó sucursal: Casa Central Juany', '::1', '2026-04-03 18:14:21', NULL),
(10, 1, 'Luciano', 'eliminar', 'sucursales', 'Eliminó sucursal: Casa Central Juany', '::1', '2026-04-03 18:14:28', NULL),
(11, 1, 'Luciano', 'producir', 'produccion', 'Producción masa congelada: juan x 100 u. | Stock congelado: 5 → 105 | Ingredientes consumidos: Huevo: -5 U', '::1', '2026-04-04 03:13:50', NULL),
(12, 6, 'Pinocho Barros', 'crear', 'sucursales', 'Creó sucursal: Casa central', '::1', '2026-04-04 22:47:27', NULL),
(13, 6, 'Pinocho Barros', 'crear', 'sucursales', 'Creó sucursal: Prueba nueva', '::1', '2026-04-04 22:56:12', NULL),
(14, 6, 'Pinocho Barros', 'eliminar', 'sucursales', 'Eliminó sucursal: Casa central', '::1', '2026-04-04 22:57:14', NULL),
(15, 6, 'Pinocho Barros', 'crear', 'ofertas', 'Creó oferta: Oferta del prueba', '::1', '2026-04-04 23:46:33', NULL),
(16, 6, 'Pinocho Barros', 'editar', 'ofertas', 'Editó oferta: Oferta del prueba', '::1', '2026-04-04 23:47:40', NULL),
(17, 6, 'Pinocho Barros', 'editar', 'ofertas', 'Editó oferta: Oferta del prueba', '::1', '2026-04-05 19:43:47', NULL),
(18, 6, 'Pinocho Barros', 'editar', 'roles', 'Actualizó roles de usuario: Luciano (1 rol/es)', '::1', '2026-04-05 19:44:41', NULL),
(19, 6, 'Pinocho Barros', 'editar', 'roles', 'Actualizó roles de usuario: Luciano (1 rol/es)', '::1', '2026-04-05 19:45:06', NULL),
(20, 6, 'Pinocho Barros', 'crear', 'ofertas', 'Creó oferta: Estammos de super descuento', '::1', '2026-04-05 19:49:54', NULL),
(21, 6, 'Pinocho Barros', 'editar', 'productos', 'Editó producto: box degustacion (ID: 2) | Tipo: box | Precio: $15,000.00', '::1', '2026-04-05 19:50:37', NULL),
(22, 6, 'Pinocho Barros', 'editar', 'productos', 'Desactivó producto: Luciano juan (ID: 1)', '::1', '2026-04-05 19:54:45', NULL),
(23, 6, 'Pinocho Barros', 'editar', 'productos', 'Activó producto: Luciano juan (ID: 1)', '::1', '2026-04-05 19:54:56', NULL),
(24, 6, 'Pinocho Barros', 'editar', 'ofertas', 'Editó oferta: Oferta del prueba', '::1', '2026-04-05 20:25:13', NULL),
(25, 6, 'Pinocho Barros', 'editar', 'productos', 'Editó producto: Box Degustacion (ID: 2) | Tipo: box | Precio: $15,000.00', '::1', '2026-04-05 21:18:12', NULL),
(26, 1, 'Luciano', 'crear', 'materias_primas', 'Creó materia prima: \'Manteca\' (ID: 3) | Stock inicial: 150 G (mín: 50) | Nota: prueba', '::1', '2026-04-06 19:54:30', NULL),
(27, 1, 'Luciano', 'editar', 'materias_primas', 'Editó materia prima: \'Manteca\' (ID: 3) | Stock: 150 G (mín: 50) | Nota: prueba | Estado: inactivo', '::1', '2026-04-06 19:54:51', NULL),
(28, 1, 'Luciano', 'eliminar', 'materias_primas', 'Desactivó materia prima: \'chocolate\' (ID: 2)', '::1', '2026-04-06 19:55:51', NULL),
(29, 1, 'Luciano', 'editar', 'materias_primas', 'Editó materia prima: \'chocolate\' (ID: 2) | Stock: 68000 ml (mín: 5000) | Nota: paco | Estado: activo', '::1', '2026-04-06 19:58:04', NULL),
(30, 1, 'Luciano', 'editar', 'materias_primas', 'Editó materia prima: \'Manteca\' (ID: 3) | Stock: 150 G (mín: 50) | Nota: prueba | Estado: activo', '::1', '2026-04-06 19:58:09', NULL),
(31, 1, 'Luciano', 'crear', 'recetas', 'Creó receta: \'Mica juany\' (ID: 10) | Ingredientes: 2 | Cantidad base: 45 u. | Masa total: 5000 | Obs: Prueba de nueva receta', '::1', '2026-04-06 20:01:04', NULL),
(32, 1, 'Luciano', 'crear', 'productos', 'Creó producto: Receta mica | Tipo: producto | Precio: $1,500.00 | Stock mín. congelado: 10 | Stock mín. hecho: 10', '::1', '2026-04-06 20:05:52', NULL),
(33, 1, 'Luciano', 'editar', 'productos', 'Desactivó producto: Receta mica (ID: 4)', '::1', '2026-04-06 20:06:01', NULL),
(34, 1, 'Luciano', 'editar', 'productos', 'Activó producto: Receta mica (ID: 4)', '::1', '2026-04-06 20:06:17', NULL),
(35, 1, 'Luciano', 'producir', 'produccion', 'Producción masa congelada: Mica juany x 45 u. | Stock congelado: 0 → 45 | Ingredientes consumidos: chocolate: -85 ml | Huevo: -5 U', '::1', '2026-04-06 20:09:30', NULL),
(36, 1, 'Luciano', 'crear', 'ventas', 'Nueva venta #3 | Cliente: polaco Diesel (nuevo) | Productos: Prod #3 x6 | Total: $9,000.00 | Método: Efectivo', '::1', '2026-04-06 20:24:37', NULL),
(37, 1, 'Luciano', 'editar', 'ventas', 'Actualizó estado de venta #3: \'En Preparacion\' → \'Pendiente\'', '::1', '2026-04-06 20:25:44', NULL),
(38, 1, 'Luciano', 'editar', 'ventas', 'Actualizó estado de venta #3: \'Pendiente\' → \'En Preparacion\'', '::1', '2026-04-06 20:25:51', NULL),
(39, 1, 'Luciano', 'editar', 'ventas', 'Actualizó estado de venta #3: \'En Preparacion\' → \'En manos del Repartidor\'', '::1', '2026-04-06 20:26:06', NULL),
(40, 1, 'Luciano', 'crear', 'ventas', 'Nueva venta #4 | Cliente:  | Productos: Prod #3 x1 | Total: $1,500.00 | Método: Efectivo', '::1', '2026-04-06 20:32:45', NULL),
(41, 1, 'Luciano', 'registrar', 'compras', 'Compra: chocolate × 4 L = 4000 ml | Proveedor: mime | Costo: $15,000.00/L | Stock 67915.00 → 71915', '::1', '2026-04-06 20:43:15', NULL),
(42, 1, 'Luciano', 'cancelar', 'compras', 'Canceló compra #5: chocolate x 4000.00 u. | Sin motivo especificado | Stock descontado: -4000.00', '::1', '2026-04-06 20:46:16', NULL),
(43, 1, 'Luciano', 'reactivar', 'compras', 'Reactivó compra #5: chocolate x 4000.00 u. | Stock restaurado: +4000.00 → Nuevo stock: 71915.00', '::1', '2026-04-06 20:46:22', NULL),
(44, 1, 'Luciano', 'editar', 'ventas', 'Actualizó estado de venta #4: \'En Preparacion\' → \'Pendiente\'', '::1', '2026-04-06 20:50:02', NULL),
(45, 1, 'Luciano', 'crear', 'sucursales', 'Creó sucursal: SEDE posadas', '::1', '2026-04-06 20:55:04', NULL),
(46, 1, 'Luciano', 'crear', 'ofertas', 'Creó oferta: Receta mica', '::1', '2026-04-06 20:57:55', NULL),
(47, 1, 'Luciano', 'editar', 'ofertas', 'Editó oferta: Receta mica', '::1', '2026-04-06 20:58:07', NULL),
(48, 1, 'Luciano', 'editar', 'ofertas', 'Editó oferta: Receta mica', '::1', '2026-04-06 20:58:14', NULL),
(49, 1, 'Luciano', 'crear', 'ventas', 'Pedido online #5 | Cliente: Luciano | pruebastock ×1 | Total: $1,500.00 | Retiro: SEDE posadas', '10.157.72.120', '2026-04-06 21:14:17', NULL),
(50, 1, 'Luciano', 'editar', 'ventas', 'Actualizó estado de venta #5: \'Pendiente\' → \'En Preparacion\'', '::1', '2026-04-06 21:14:46', NULL),
(51, 1, 'Luciano', 'editar', 'ventas', 'Actualizó estado de venta #5: \'En Preparacion\' → \'Entregado\'', '::1', '2026-04-06 21:15:18', NULL),
(52, 1, 'Luciano', 'editar', 'roles', 'Actualizó roles de usuario: Paco (1 rol/es)', '::1', '2026-04-09 23:42:57', NULL),
(53, 1, 'Luciano', 'editar', 'roles', 'Actualizó roles de usuario: POedro (1 rol/es)', '::1', '2026-04-09 23:43:07', NULL),
(54, 4, 'Paco Paco', 'crear', 'ventas', 'Pedido online #9 | Cliente: Paco Paco | pruebastock ×1 | Total: $1,500.00', '192.168.232.20', '2026-04-09 23:55:33', NULL),
(55, 4, 'Paco Paco', 'crear', 'ventas', 'Pedido online #10 | Cliente: Paco Paco | pruebastock ×1 | Total: $1,500.00', '192.168.232.20', '2026-04-09 23:57:07', NULL),
(56, 1, 'Luciano', 'crear', 'roles', 'Creó rol: Repartidor', '::1', '2026-04-11 22:53:57', NULL),
(57, 1, 'Luciano', 'editar', 'roles', 'Actualizó roles de usuario: POedro (1 rol/es)', '::1', '2026-04-11 22:54:08', NULL),
(58, 1, 'Luciano', 'editar', 'roles', 'Actualizó roles de usuario: Juany (1 rol/es)', '::1', '2026-04-11 22:57:27', NULL),
(59, 1, 'Luciano', 'editar', 'roles', 'Actualizó roles de usuario: POedro (0 rol/es)', '::1', '2026-04-11 22:57:44', NULL),
(60, 1, 'Luciano', 'editar', 'roles', 'Actualizó roles de usuario: Luciano (1 rol/es)', '::1', '2026-04-11 22:59:35', NULL),
(61, 1, 'Luciano', 'editar', 'roles', 'Actualizó roles de usuario: Paco (1 rol/es)', '::1', '2026-04-11 22:59:42', NULL),
(62, 1, 'Luciano', 'editar', 'roles', 'Actualizó roles de usuario: Juany (1 rol/es)', '::1', '2026-04-11 23:00:14', NULL),
(63, 8, 'Juany garcia', 'crear', 'ventas', 'Pedido online #11 | Cliente: Juany garcia | pruebastock ×1 | Total: $1,500.00', '::1', '2026-04-12 02:46:17', NULL),
(64, 1, 'Luciano', 'editar', 'ventas', 'Actualizó estado de venta #11: \'Pendiente\' → \'En Preparacion\'', '::1', '2026-04-12 02:47:05', NULL),
(65, 1, 'Luciano', 'editar', 'ventas', 'Actualizó estado de venta #11: \'En Preparacion\' → \'En manos del Repartidor\'', '::1', '2026-04-12 02:47:38', NULL),
(66, 8, 'Juany garcia', 'crear', 'ventas', 'Pedido online #12 | Cliente: Juany garcia | pruebastock ×1 | Total: $1,500.00 | Retiro: SEDE posadas', '::1', '2026-04-12 03:33:01', NULL),
(67, 1, 'Luciano', 'editar', 'pedidos', 'Estado pedido #12: \'Pendiente\' → \'En Preparacion\'', '::1', '2026-04-12 03:33:42', NULL),
(68, 1, 'Luciano', 'editar', 'pedidos', 'Estado pedido #12: \'En Preparacion\' → \'En Preparacion\'', '::1', '2026-04-12 03:34:17', NULL),
(69, 1, 'Luciano', 'editar', 'pedidos', 'Estado pedido #12: \'En Preparacion\' → \'En manos del Repartidor\'', '::1', '2026-04-12 03:34:39', NULL),
(70, 1, 'Luciano', 'editar', 'roles', 'Actualizó roles de usuario: Juany (2 rol/es)', '::1', '2026-04-12 16:20:34', NULL),
(71, 1, 'Luciano', 'crear', 'productos', 'Creó producto: Prueba cooki | Tipo: producto | Precio: $100.00 | Stock mín. congelado: 3 | Stock mín. hecho: ', '201.213.90.163', '2026-04-14 04:53:25', 'Casa Central'),
(72, 1, 'Luciano', 'editar', 'productos', 'Desactivó producto: Prueba cooki (ID: 5)', '201.213.90.163', '2026-04-14 04:53:39', 'Casa Central'),
(73, 1, 'Luciano', 'editar', 'productos', 'Activó producto: Prueba cooki (ID: 5)', '201.213.90.163', '2026-04-14 04:53:43', 'Casa Central'),
(74, 8, 'Juany garcia', 'crear', 'ventas', 'Pedido online #13 | Cliente: Juany garcia | pruebastock ×1 | Total: $1,500.00 | Retiro: Prueba nueva', '201.213.90.163', '2026-04-14 04:56:10', 'Casa Central'),
(75, 8, 'Juany garcia', 'crear', 'ventas', 'Pedido online #14 | Cliente: Juany garcia | pruebastock ×1 | Total: $1,500.00 | Retiro: Prueba nueva', '201.213.90.163', '2026-04-14 05:04:15', 'Casa Central'),
(76, 8, 'Juany garcia', 'crear', 'ventas', 'Pedido online #15 | Cliente: Juany garcia | pruebastock ×1 | Total: $1,500.00 | Retiro: Prueba nueva', '201.213.90.163', '2026-04-14 05:27:43', 'Casa Central'),
(77, 8, 'Juany garcia', 'crear', 'ventas', 'Pedido online #16 | Cliente: Juany garcia | pruebastock ×1 | Total: $1,500.00', '201.213.90.163', '2026-04-14 05:29:43', 'Casa Central'),
(78, 1, 'Luciano', 'editar', 'pedidos', 'Estado pedido #16: \'Pendiente\' → \'En Preparacion\'', '201.213.90.163', '2026-04-14 05:30:19', 'Casa Central'),
(79, 1, 'Luciano', 'editar', 'pedidos', 'Estado pedido #16: \'En Preparacion\' → \'En manos del Repartidor\'', '201.213.90.163', '2026-04-14 05:40:14', 'Casa Central'),
(80, 1, 'Luciano', 'editar', 'pedidos', 'Estado pedido #16: \'En manos del Repartidor\' → \'En Preparacion\'', '201.213.90.163', '2026-04-14 05:42:27', 'Casa Central'),
(81, 1, 'Luciano', 'editar', 'pedidos', 'Estado pedido #16: \'En Preparacion\' → \'Pendiente\'', '201.213.90.163', '2026-04-14 05:52:43', 'Casa Central'),
(82, 1, 'Luciano', 'editar', 'pedidos', 'Estado pedido #16: \'Pendiente\' → \'Cancelado\'', '201.213.90.163', '2026-04-14 05:52:49', 'Casa Central'),
(83, 1, 'Luciano', 'editar', 'ofertas', 'Editó oferta: Estammos de super descuento', '201.213.90.163', '2026-04-14 05:53:29', 'Casa Central'),
(84, 1, 'Luciano', 'editar', 'ofertas', 'Editó oferta: Oferta del prueba', '201.213.90.163', '2026-04-14 05:53:35', 'Casa Central'),
(85, 1, 'Luciano', 'editar', 'ofertas', 'Editó oferta: Receta mica', '201.213.90.163', '2026-04-14 05:53:41', 'Casa Central'),
(86, 8, 'Juany garcia', 'crear', 'ventas', 'Pedido online #17 | Cliente: Juany garcia | pruebastock ×1 | Total: $1,500.00', '181.86.140.67', '2026-04-17 18:05:09', 'Casa Central'),
(87, 8, 'Juany garcia', 'editar', 'roles', 'Actualizó roles de usuario: Paco (0 rol/es)', '181.97.24.59', '2026-04-17 19:23:04', NULL),
(88, 8, 'Juany garcia', 'crear', 'packaging', 'Creó packaging: \'caja chica\' (ID: 1) | Stock: 10 (mín: 5)', '181.97.24.59', '2026-04-17 19:37:46', 'Casa Central'),
(89, 8, 'Juany garcia', 'editar', 'ofertas', 'Editó oferta: Oferta del prueba', '181.97.24.59', '2026-04-17 19:51:37', 'Casa Central'),
(90, 8, 'Juany garcia', 'editar', 'ofertas', 'Editó oferta: Oferta del prueba', '181.97.24.59', '2026-04-17 19:51:48', 'Casa Central'),
(91, 8, 'Juany garcia', 'eliminar', 'ofertas', 'Eliminó oferta: Oferta del prueba', '181.97.24.59', '2026-04-17 19:52:03', 'Casa Central'),
(92, 8, 'Juany garcia', 'crear', 'ofertas', 'Creó oferta: Oferton loco', '181.97.24.59', '2026-04-17 19:54:03', 'Casa Central'),
(93, 8, 'Juany garcia', 'editar', 'ofertas', 'Editó oferta: Oferton loco', '181.97.24.59', '2026-04-17 19:59:42', 'Casa Central'),
(94, 8, 'Juany garcia', 'editar', 'ofertas', 'Editó oferta: Oferton loco', '181.97.24.59', '2026-04-17 20:00:10', 'Casa Central'),
(95, 8, 'Juany garcia', 'editar', 'ofertas', 'Editó oferta: Oferton loco', '181.97.24.59', '2026-04-17 20:00:37', 'Casa Central'),
(96, 8, 'Juany garcia', 'editar', 'pedidos', 'Estado pedido #17: \'Pendiente\' → \'En manos del Repartidor\'', '181.97.24.59', '2026-04-17 20:09:27', 'Casa Central'),
(97, 8, 'Juany garcia', 'crear', 'ventas', 'Pedido online #18 | Cliente: Juany garcia | pruebastock (15% OFF) ×1 | Total: $1,275.00', '181.97.24.59', '2026-04-17 20:17:02', 'Casa Central'),
(98, 8, 'Juany garcia', 'editar', 'sucursales', 'Editó sucursal: Prueba nueva', '181.97.24.59', '2026-04-17 20:49:43', NULL),
(99, 8, 'Juany garcia', 'editar', 'roles', 'Actualizó roles de usuario: Juany Hilbert (2 rol/es)', '181.97.24.59', '2026-04-17 20:54:55', NULL),
(100, 8, 'Juany garcia', 'editar', 'roles', 'Actualizó roles de usuario: Juany Hilbert (3 rol/es)', '181.97.24.59', '2026-04-17 20:56:53', NULL),
(101, 8, 'Juany garcia', 'editar', 'ofertas', 'Editó oferta: Oferton loco', '181.97.24.59', '2026-04-17 20:57:25', 'Casa Central'),
(102, 1, 'Luciano', 'crear', 'ofertas', 'Creó panel: Estammos de super descuento', '::1', '2026-04-20 00:06:11', 'Casa Central'),
(103, 1, 'Luciano', 'editar', 'sucursales', 'Editó sucursal: Prueba nueva', '::1', '2026-04-20 01:43:59', NULL),
(104, 1, 'Luciano', 'editar', 'roles', 'Actualizó roles de usuario: Paco (1 rol/es)', '::1', '2026-04-20 01:51:26', NULL),
(105, 4, 'Paco Paco', 'crear', 'ventas', 'Pedido online #19 | Cliente: Paco Paco | pruebastock ×1 | Total: $6,000.00', '::1', '2026-04-26 02:25:34', 'Casa Central'),
(106, 4, 'Juancarlo Chupapija', 'crear', 'ventas', 'Pedido online #32 | Cliente: Juancarlo Chupapija | Cookie Classic Choco Chip ×4 | Total: $9,800.00 | Retiro: SEDE posadas', '::1', '2026-05-03 18:22:31', 'Casa Central'),
(107, 4, 'Juancarlo Chupapija', 'crear', 'ventas', 'Pedido online #33 | Cliente: Juancarlo Chupapija | Cookie Classic Choco Chip ×4 | Total: $9,800.00 | Retiro: Prueba nueva', '::1', '2026-05-03 18:38:11', 'Casa Central'),
(108, 4, 'Juancarlo Chupapija', 'crear', 'ventas', 'Pedido online #34 | Cliente: Juancarlo Chupapija | Cookie Doble Chocolate ×4 | Total: $9,200.00 | Retiro: Sucursal Centro', '::1', '2026-05-03 18:41:27', 'Casa Central'),
(109, 4, 'Juancarlo Chupapija', 'crear', 'ventas', 'Pedido online #35 | Cliente: Juancarlo Chupapija | Cookie Doble Chocolate ×4 | Total: $9,200.00 | Retiro: Sucursal Centro', '::1', '2026-05-03 18:43:58', 'Casa Central'),
(110, 1, 'Luciano', 'editar', 'pedidos', 'Estado pedido #35: \'Pendiente\' → \'Entregado\'', '::1', '2026-05-04 03:01:39', 'Casa Central'),
(111, 1, 'Luciano', 'hornear', 'produccion', 'Horneado: Cookie Classic Choco Chip x 10 u. | Congelado consumido: -10 (resta: 38) | Hecho producido: +10', '::1', '2026-05-05 05:21:16', 'Casa Central'),
(112, 1, 'Luciano', 'editar', 'roles', 'Actualizó roles de usuario: Luciano (2 rol/es)', '::1', '2026-05-05 05:54:19', NULL),
(113, 1, 'Luciano', 'editar', 'roles', 'Actualizó roles de usuario: Luciano (3 rol/es)', '::1', '2026-05-05 05:55:24', NULL),
(114, 1, 'Luciano', 'crear', 'ventas', 'Pedido online #36 | Cliente: Luciano | Cookie Classic Choco Chip ×4 | Total: $8,500.00 | Retiro: Sucursal Centro', '::1', '2026-05-05 05:56:10', 'Casa Central'),
(115, 1, 'Luciano', 'editar', 'pedidos', 'Estado pedido #36: \'Pendiente\' → \'En manos del Repartidor\'', '::1', '2026-05-05 05:57:15', 'Casa Central'),
(116, 1, 'Luciano', 'editar', 'pedidos', 'Estado pedido #36: \'En manos del Repartidor\' → \'Listo para retiro\'', '::1', '2026-05-05 05:57:20', 'Casa Central'),
(117, 1, 'Luciano', 'editar', 'pedidos', 'Estado pedido #36: \'Listo para retiro\' → \'En manos del Repartidor\'', '::1', '2026-05-05 05:57:26', 'Casa Central'),
(118, 1, 'Luciano', 'crear', 'ventas', 'Pedido online #37 | Cliente: Luciano | Cookie Classic Choco Chip ×4 | Total: $18,200.00', '::1', '2026-05-05 06:28:05', 'Casa Central'),
(119, 1, 'Luciano', 'editar', 'pedidos', 'Estado pedido #37: \'Pendiente\' → \'En manos del Repartidor\'', '::1', '2026-05-05 06:30:25', 'Casa Central'),
(120, 1, 'Luciano', 'crear', 'ventas', 'Pedido online #109 | Cliente: Luciano | Cookie Doble Chocolate ×4 | Total: $19,800.00', '::1', '2026-05-06 01:20:52', 'Casa Central'),
(121, 1, 'Luciano', 'crear', 'ventas', 'Pedido online #110 | Cliente: Luciano | Cookie Doble Chocolate ×4 | Total: $19,800.00', '::1', '2026-05-06 01:24:47', 'Casa Central'),
(122, 1, 'Luciano', 'crear', 'ventas', 'Pedido online #111 | Cliente: Luciano | Cookie Doble Chocolate ×4 | Total: $13,800.00', '::1', '2026-05-06 01:32:06', 'Casa Central'),
(123, 1, 'Luciano', 'editar', 'pedidos', 'Estado pedido #111: \'Pendiente\' → \'En manos del Repartidor\'', '::1', '2026-05-06 01:32:44', 'Casa Central'),
(124, 1, 'Luciano', 'crear', 'ventas', 'Pedido online #112 | Cliente: Luciano | Cookie Classic Choco Chip ×4 | Total: $11,500.00', '::1', '2026-05-08 00:50:32', 'Casa Central'),
(125, 1, 'Luciano', 'crear', 'ventas', 'Pedido online #113 | Cliente: Luciano | Cookie Classic Choco Chip ×4 | Total: $9,700.00', '::1', '2026-05-08 01:27:19', 'Casa Central'),
(126, 1, 'Luciano', 'crear', 'ventas', 'Pedido online #114 | Cliente: Luciano | Cookie Classic Choco Chip ×4 | Total: $9,700.00', '::1', '2026-05-08 01:31:39', 'Casa Central'),
(127, 1, 'Luciano', 'crear', 'ventas', 'Pedido online #115 | Cliente: Luciano | Cookie Cacao Intenso ×4 | Total: $8,500.00 | Retiro: Sucursal Centro', '::1', '2026-05-08 01:32:53', 'Casa Central'),
(128, 1, 'Luciano', 'crear', 'ventas', 'Pedido online #116 | Cliente: Luciano | Cookie Classic Choco Chip ×11 | Total: $22,300.00', '::1', '2026-05-08 01:41:28', 'Casa Central'),
(129, 4, 'Juancarlo Chupapija', 'crear', 'ventas', 'Pedido online #117 | Cliente: Juancarlo Chupapija | Cookie Dulce de Leche ×4 | Total: $9,600.00', '::1', '2026-05-09 00:09:06', 'Casa Central'),
(130, 4, 'Juancarlo Chupapija', 'crear', 'ventas', 'Pedido online #118 | Cliente: Juancarlo Chupapija | Cookie Classic Choco Chip ×4 | Total: $7,700.00 | Retiro: Sucursal Centro', '::1', '2026-05-09 00:12:19', 'Casa Central'),
(131, 1, 'Luciano', 'asignar', 'compras', 'Asignó materia prima \'Azúcar común\' al proveedor \'Distribuidora El Sabor\'', '::1', '2026-05-11 00:21:00', 'Casa Central'),
(132, 1, 'Luciano', 'registrar', 'compras', 'Compra: Azúcar común × 1000 G = 1 Kg | Proveedor: Distribuidora El Sabor | Costo: $5.00/G | Stock 15.00 → 16', '::1', '2026-05-11 00:34:47', 'Casa Central'),
(133, 1, 'Luciano', 'editar', 'pedidos', 'Estado pedido #118: \'Pendiente\' → \'Listo para retiro\'', '::1', '2026-05-15 23:37:39', 'Casa Central'),
(134, 1, 'Luciano', 'editar', 'pedidos', 'Estado pedido #118: \'Listo para retiro\' → \'En manos del Repartidor\'', '::1', '2026-05-15 23:37:44', 'Casa Central'),
(135, 1, 'Luciano', 'editar', 'pedidos', 'Estado pedido #118: \'En manos del Repartidor\' → \'Pendiente\'', '::1', '2026-05-15 23:38:11', 'Casa Central'),
(136, 1, 'Luciano', 'editar', 'pedidos', 'Estado pedido #118: \'Pendiente\' → \'En manos del Repartidor\'', '::1', '2026-05-17 01:37:05', 'Casa Central'),
(137, 1, 'Luciano', 'editar', 'packaging', 'Editó packaging: \'Caja individual premium\' (ID: 2) | Stock: 50 (mín: 10) | Precio: $0', '::1', '2026-05-17 22:58:17', 'Casa Central'),
(138, 1, 'Luciano', 'crear', 'cupones', 'Creó cupón: SQA74VG5 | porcentaje 10', '::1', '2026-05-19 00:04:08', 'Casa Central'),
(139, 1, 'Luciano', 'editar', 'sucursales', 'Editó sucursal: Prueba nueva', '::1', '2026-05-19 00:14:13', NULL),
(140, 1, 'Luciano', 'editar', 'sucursales', 'Editó sucursal: Sucursal Centro', '::1', '2026-05-19 00:14:20', NULL),
(141, 1, 'Luciano', 'editar', 'sucursales', 'Editó sucursal: Sucursal Norte', '::1', '2026-05-19 00:14:24', NULL),
(142, 1, 'Luciano', 'eliminar', 'cupones', 'Desactivó cupón: SQA74VG5 (ID:1)', '::1', '2026-05-19 00:39:20', 'Casa Central'),
(143, 1, 'Luciano', 'crear', 'cupones', 'Creó cupón: ZUM77UT2 | porcentaje 15', '::1', '2026-05-19 00:39:50', 'Casa Central'),
(144, 1, 'Luciano', 'editar', 'cupones', 'Editó cupón: SQA74VG5 (ID:1)', '::1', '2026-05-24 12:37:04', 'Casa Central'),
(145, 1, 'Luciano', 'editar', 'configuracion_tienda', 'Actualizó configuración: horario_activado, horario_apertura, horario_cierre', '::1', '2026-05-27 00:45:51', 'Casa Central'),
(146, 1, 'Luciano', 'editar', 'configuracion_tienda', 'Actualizó configuración: tienda_modo, horario_activado, horario_apertura, horario_cierre', '::1', '2026-05-27 01:20:03', 'Casa Central'),
(147, 1, 'Luciano', 'editar', 'configuracion_tienda', 'Actualizó configuración: tienda_modo, horario_activado, horario_apertura, horario_cierre', '::1', '2026-05-27 01:20:47', 'Casa Central'),
(148, 1, 'Luciano', 'editar', 'configuracion_tienda', 'Actualizó configuración: tienda_modo, horario_activado, horario_apertura, horario_cierre', '::1', '2026-05-27 23:19:52', 'Casa Central'),
(196, 1, 'Luciano', 'editar', 'configuracion_tienda', 'Actualizó configuración: limite_toppings', '::1', '2026-06-18 19:19:26', 'Casa Central'),
(197, 1, 'Luciano', 'editar', 'configuracion_tienda', 'Actualizó configuración: tienda_modo, horario_activado, horario_apertura, horario_cierre', '::1', '2026-06-18 19:19:29', 'Casa Central');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `box_productos`
--

DROP TABLE IF EXISTS `box_productos`;
CREATE TABLE IF NOT EXISTS `box_productos` (
  `idbox_productos` int NOT NULL AUTO_INCREMENT,
  `producto_box` int DEFAULT NULL,
  `producto_item` int DEFAULT NULL,
  `cantidad` int DEFAULT NULL,
  PRIMARY KEY (`idbox_productos`),
  UNIQUE KEY `producto_box` (`producto_box`,`producto_item`),
  KEY `producto_item` (`producto_item`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `box_productos`
--

INSERT INTO `box_productos` (`idbox_productos`, `producto_box`, `producto_item`, `cantidad`) VALUES
(4, 2, 3, 7),
(5, 16, 6, 1),
(6, 16, 7, 1),
(7, 16, 8, 1),
(8, 16, 9, 1),
(9, 16, 10, 1),
(10, 16, 11, 1),
(11, 17, 6, 1),
(12, 17, 7, 1),
(13, 17, 8, 1),
(14, 17, 9, 1),
(15, 17, 10, 1),
(16, 17, 11, 1),
(17, 17, 12, 1),
(18, 17, 13, 1),
(19, 17, 14, 1),
(20, 17, 15, 1),
(21, 18, 15, 1),
(22, 18, 9, 1),
(23, 18, 7, 1),
(24, 18, 11, 1),
(25, 19, 6, 1),
(26, 19, 7, 1),
(27, 19, 8, 1),
(28, 19, 9, 1),
(29, 19, 10, 1),
(30, 19, 11, 1),
(31, 19, 12, 1),
(32, 19, 13, 1),
(33, 20, 6, 1),
(34, 20, 7, 1),
(35, 20, 8, 1),
(36, 20, 9, 1),
(37, 20, 10, 1),
(38, 20, 11, 1),
(39, 20, 12, 1),
(40, 20, 13, 1),
(41, 20, 14, 1),
(42, 20, 15, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `compra_materia_prima`
--

DROP TABLE IF EXISTS `compra_materia_prima`;
CREATE TABLE IF NOT EXISTS `compra_materia_prima` (
  `id` int NOT NULL AUTO_INCREMENT,
  `proveedor_idproveedor` int NOT NULL,
  `materia_prima_idmateria_prima` int NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `stock_anterior` decimal(10,2) NOT NULL DEFAULT '0.00',
  `costo` decimal(10,2) DEFAULT NULL,
  `unidad_compra` varchar(10) DEFAULT NULL,
  `cantidad_original` decimal(10,3) DEFAULT NULL,
  `stock_nuevo` decimal(10,2) DEFAULT NULL,
  `observaciones` text,
  `estado` enum('activa','cancelada') NOT NULL DEFAULT 'activa',
  `cancelado_at` datetime DEFAULT NULL,
  `cancelado_motivo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `usuario_id` int DEFAULT NULL,
  `cancelado_por` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_compra_proveedor` (`proveedor_idproveedor`),
  KEY `fk_compra_materia` (`materia_prima_idmateria_prima`),
  KEY `fk_compra_usuario` (`usuario_id`),
  KEY `fk_compra_cancelado_por` (`cancelado_por`)
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `compra_materia_prima`
--

INSERT INTO `compra_materia_prima` (`id`, `proveedor_idproveedor`, `materia_prima_idmateria_prima`, `cantidad`, `stock_anterior`, `costo`, `unidad_compra`, `cantidad_original`, `stock_nuevo`, `observaciones`, `estado`, `cancelado_at`, `cancelado_motivo`, `created_at`, `usuario_id`, `cancelado_por`) VALUES
(16, 4, 4, 25.00, 5.00, 85.00, 'kg', 25.000, 30.00, NULL, 'activa', NULL, NULL, '2026-01-05 12:00:00', 1, NULL),
(17, 5, 3, 10.00, 2.00, 420.00, 'kg', 10.000, 12.00, NULL, 'activa', NULL, NULL, '2026-01-05 12:10:00', 1, NULL),
(18, 1, 1, 90.00, 12.00, 4.50, 'docena', 6.000, 18.00, NULL, 'activa', NULL, NULL, '2026-01-05 12:20:00', 1, NULL),
(19, 6, 9, 5.00, 1.00, 680.00, 'kg', 5.000, 6.00, NULL, 'activa', NULL, NULL, '2026-01-05 12:30:00', 1, NULL),
(20, 7, 7, 20.00, 3.00, 120.00, 'kg', 20.000, 23.00, NULL, 'activa', NULL, NULL, '2026-01-05 12:40:00', 1, NULL),
(21, 4, 4, 20.00, 8.00, 85.00, 'kg', 20.000, 28.00, NULL, 'activa', NULL, NULL, '2026-01-19 12:00:00', 1, NULL),
(22, 5, 3, 8.00, 4.00, 420.00, 'kg', 8.000, 12.00, NULL, 'activa', NULL, NULL, '2026-01-19 12:10:00', 1, NULL),
(23, 6, 9, 4.00, 2.00, 680.00, 'kg', 4.000, 6.00, NULL, 'activa', NULL, NULL, '2026-01-19 12:20:00', 1, NULL),
(24, 1, 1, 72.00, 6.00, 4.50, 'docena', 6.000, 12.00, NULL, 'activa', NULL, NULL, '2026-01-19 12:30:00', 1, NULL),
(25, 4, 4, 30.00, 6.00, 88.00, 'kg', 30.000, 36.00, NULL, 'activa', NULL, NULL, '2026-02-04 12:00:00', 1, NULL),
(26, 5, 3, 12.00, 3.00, 430.00, 'kg', 12.000, 15.00, NULL, 'activa', NULL, NULL, '2026-02-04 12:10:00', 1, NULL),
(27, 1, 1, 108.00, 8.00, 4.80, 'docena', 9.000, 17.00, NULL, 'activa', NULL, NULL, '2026-02-04 12:20:00', 1, NULL),
(28, 6, 9, 6.00, 1.50, 700.00, 'kg', 6.000, 7.50, NULL, 'activa', NULL, NULL, '2026-02-04 12:30:00', 1, NULL),
(29, 7, 7, 22.00, 4.00, 125.00, 'kg', 22.000, 26.00, NULL, 'activa', NULL, NULL, '2026-02-04 12:40:00', 1, NULL),
(30, 7, 10, 3.00, 0.50, 580.00, 'kg', 3.000, 3.50, NULL, 'activa', NULL, NULL, '2026-02-04 12:50:00', 1, NULL),
(31, 4, 4, 25.00, 10.00, 88.00, 'kg', 25.000, 35.00, NULL, 'activa', NULL, NULL, '2026-02-20 12:00:00', 1, NULL),
(32, 5, 3, 10.00, 5.00, 430.00, 'kg', 10.000, 15.00, NULL, 'activa', NULL, NULL, '2026-02-20 12:10:00', 1, NULL),
(33, 6, 9, 5.00, 2.00, 700.00, 'kg', 5.000, 7.00, NULL, 'activa', NULL, NULL, '2026-02-20 12:20:00', 1, NULL),
(34, 4, 4, 35.00, 7.00, 90.00, 'kg', 35.000, 42.00, NULL, 'activa', NULL, NULL, '2026-03-04 12:00:00', 1, NULL),
(35, 5, 3, 15.00, 2.50, 440.00, 'kg', 15.000, 17.50, NULL, 'activa', NULL, NULL, '2026-03-04 12:10:00', 1, NULL),
(36, 1, 1, 120.00, 10.00, 5.00, 'docena', 10.000, 20.00, NULL, 'activa', NULL, NULL, '2026-03-04 12:20:00', 1, NULL),
(37, 6, 9, 7.00, 1.00, 720.00, 'kg', 7.000, 8.00, NULL, 'activa', NULL, NULL, '2026-03-04 12:30:00', 1, NULL),
(38, 7, 7, 25.00, 3.00, 130.00, 'kg', 25.000, 28.00, NULL, 'activa', NULL, NULL, '2026-03-04 12:40:00', 1, NULL),
(39, 7, 10, 4.00, 0.80, 590.00, 'kg', 4.000, 4.80, NULL, 'activa', NULL, NULL, '2026-03-04 12:50:00', 1, NULL),
(40, 4, 5, 8.00, 2.00, 320.00, 'kg', 8.000, 10.00, NULL, 'activa', NULL, NULL, '2026-03-04 13:00:00', 1, NULL),
(41, 4, 4, 30.00, 12.00, 90.00, 'kg', 30.000, 42.00, NULL, 'activa', NULL, NULL, '2026-03-20 12:00:00', 1, NULL),
(42, 5, 3, 12.00, 5.00, 440.00, 'kg', 12.000, 17.00, NULL, 'activa', NULL, NULL, '2026-03-20 12:10:00', 1, NULL),
(43, 6, 9, 6.00, 2.00, 720.00, 'kg', 6.000, 8.00, NULL, 'activa', NULL, NULL, '2026-03-20 12:20:00', 1, NULL),
(44, 1, 1, 96.00, 8.00, 5.00, 'docena', 8.000, 16.00, NULL, 'activa', NULL, NULL, '2026-03-20 12:30:00', 1, NULL),
(45, 4, 4, 40.00, 8.00, 92.00, 'kg', 40.000, 48.00, NULL, 'activa', NULL, NULL, '2026-04-03 12:00:00', 1, NULL),
(46, 5, 3, 16.00, 3.00, 450.00, 'kg', 16.000, 19.00, NULL, 'activa', NULL, NULL, '2026-04-03 12:10:00', 1, NULL),
(47, 1, 1, 132.00, 11.00, 5.20, 'docena', 11.000, 22.00, NULL, 'activa', NULL, NULL, '2026-04-03 12:20:00', 1, NULL),
(48, 6, 9, 8.00, 1.50, 740.00, 'kg', 8.000, 9.50, NULL, 'activa', NULL, NULL, '2026-04-03 12:30:00', 1, NULL),
(49, 7, 7, 28.00, 4.00, 135.00, 'kg', 28.000, 32.00, NULL, 'activa', NULL, NULL, '2026-04-03 12:40:00', 1, NULL),
(50, 7, 10, 5.00, 1.00, 600.00, 'kg', 5.000, 6.00, NULL, 'activa', NULL, NULL, '2026-04-03 12:50:00', 1, NULL),
(51, 4, 5, 10.00, 2.50, 330.00, 'kg', 10.000, 12.50, NULL, 'activa', NULL, NULL, '2026-04-03 13:00:00', 1, NULL),
(52, 4, 4, 35.00, 10.00, 92.00, 'kg', 35.000, 45.00, NULL, 'activa', NULL, NULL, '2026-04-18 12:00:00', 1, NULL),
(53, 5, 3, 14.00, 5.00, 450.00, 'kg', 14.000, 19.00, NULL, 'activa', NULL, NULL, '2026-04-18 12:10:00', 1, NULL),
(54, 6, 9, 7.00, 2.50, 740.00, 'kg', 7.000, 9.50, NULL, 'activa', NULL, NULL, '2026-04-18 12:20:00', 1, NULL),
(55, 1, 1, 108.00, 9.00, 5.20, 'docena', 9.000, 18.00, NULL, 'activa', NULL, NULL, '2026-04-18 12:30:00', 1, NULL),
(56, 4, 4, 20.00, 5.00, 95.00, 'kg', 20.000, 25.00, NULL, 'activa', NULL, NULL, '2026-05-02 12:00:00', 1, NULL),
(57, 5, 3, 8.00, 2.00, 460.00, 'kg', 8.000, 10.00, NULL, 'activa', NULL, NULL, '2026-05-02 12:10:00', 1, NULL),
(58, 1, 1, 72.00, 6.00, 5.40, 'docena', 6.000, 12.00, NULL, 'activa', NULL, NULL, '2026-05-02 12:20:00', 1, NULL),
(59, 6, 9, 4.00, 1.00, 760.00, 'kg', 4.000, 5.00, NULL, 'activa', NULL, NULL, '2026-05-02 12:30:00', 1, NULL),
(60, 7, 7, 15.00, 3.00, 140.00, 'kg', 15.000, 18.00, NULL, 'activa', NULL, NULL, '2026-05-02 12:40:00', 1, NULL),
(61, 7, 7, 1.00, 15.00, 5.00, 'G', 1000.000, 16.00, NULL, 'activa', NULL, NULL, '2026-05-11 00:34:47', 1, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion_tienda`
--

DROP TABLE IF EXISTS `configuracion_tienda`;
CREATE TABLE IF NOT EXISTS `configuracion_tienda` (
  `clave` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`clave`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `configuracion_tienda`
--

INSERT INTO `configuracion_tienda` (`clave`, `valor`, `updated_at`) VALUES
('tienda_abierta', '1', '2026-05-14 11:05:55'),
('tienda_mensaje_cierre', 'Cerramos la tienda en este horario nos vemos a las 18:00 HS hasta las 21 :00 Hs', '2026-05-11 22:15:53'),
('horario_activado', '1', '2026-06-18 16:19:29'),
('horario_apertura', '08:00', '2026-06-18 16:19:29'),
('horario_cierre', '23:00', '2026-06-18 16:19:29'),
('horario_forzado_cerrado', '0', '2026-05-26 21:34:11'),
('min_cookies_pedido', '4', '2026-05-26 21:34:53'),
('max_cookies_pedido', '100', '2026-05-26 21:34:53'),
('mensaje_min_pedido', 'El pedido mínimo es de {min} cookies.', '2026-05-26 21:34:53'),
('tienda_modo', 'abierta', '2026-06-18 16:19:29'),
('limite_toppings', '3', '2026-06-18 16:19:26');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cupones`
--

DROP TABLE IF EXISTS `cupones`;
CREATE TABLE IF NOT EXISTS `cupones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo` enum('porcentaje','fijo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'porcentaje',
  `valor` decimal(10,2) NOT NULL DEFAULT '0.00',
  `min_pedido` decimal(10,2) NOT NULL DEFAULT '0.00',
  `max_usos` int DEFAULT NULL,
  `usos_actuales` int NOT NULL DEFAULT '0',
  `un_uso_por_usuario` tinyint(1) NOT NULL DEFAULT '1',
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cupones`
--

INSERT INTO `cupones` (`id`, `codigo`, `descripcion`, `tipo`, `valor`, `min_pedido`, `max_usos`, `usos_actuales`, `un_uso_por_usuario`, `fecha_inicio`, `fecha_fin`, `activo`, `created_at`, `updated_at`) VALUES
(1, 'SQA74VG5', 'Descuento por buen usuario', 'porcentaje', 10.00, 0.00, 1, 0, 1, '2026-05-18', '2026-05-18', 1, '2026-05-18 21:04:08', '2026-05-24 09:37:04'),
(2, 'ZUM77UT2', 'Buena persona', 'porcentaje', 15.00, 0.00, 1, 0, 1, '2026-05-18', '2026-05-18', 1, '2026-05-18 21:39:50', '2026-05-18 21:39:50');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cupones_usos`
--

DROP TABLE IF EXISTS `cupones_usos`;
CREATE TABLE IF NOT EXISTS `cupones_usos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cupon_id` int NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `venta_id` int DEFAULT NULL,
  `usado_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `datos_bancarios`
--

DROP TABLE IF EXISTS `datos_bancarios`;
CREATE TABLE IF NOT EXISTS `datos_bancarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titular` varchar(200) NOT NULL DEFAULT '',
  `banco` varchar(100) NOT NULL DEFAULT '',
  `cbu` varchar(22) NOT NULL DEFAULT '',
  `alias` varchar(50) NOT NULL DEFAULT '',
  `instrucciones` text,
  `pin_hash` varchar(255) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_ventas`
--

DROP TABLE IF EXISTS `detalle_ventas`;
CREATE TABLE IF NOT EXISTS `detalle_ventas` (
  `iddetalle_ventas` int NOT NULL AUTO_INCREMENT,
  `ventas_idventas` int NOT NULL,
  `productos_idproductos` int NOT NULL,
  `cantidad` int DEFAULT NULL,
  `precio_unitario` decimal(10,2) DEFAULT NULL,
  `precio_original` decimal(10,2) DEFAULT NULL,
  `descuento_pct` decimal(5,2) DEFAULT NULL,
  PRIMARY KEY (`iddetalle_ventas`),
  KEY `fk_detalle_ventas_ventas1_idx` (`ventas_idventas`),
  KEY `fk_detalle_ventas_productos1_idx` (`productos_idproductos`)
) ENGINE=InnoDB AUTO_INCREMENT=206 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `detalle_ventas`
--

INSERT INTO `detalle_ventas` (`iddetalle_ventas`, `ventas_idventas`, `productos_idproductos`, `cantidad`, `precio_unitario`, `precio_original`, `descuento_pct`) VALUES
(49, 38, 8, 4, 1200.00, 1200.00, NULL),
(50, 39, 9, 5, 640.00, 640.00, NULL),
(51, 40, 10, 2, 2800.00, 2800.00, NULL),
(52, 41, 11, 3, 800.00, 800.00, NULL),
(53, 42, 6, 4, 1700.00, 1700.00, NULL),
(54, 43, 7, 5, 720.00, 720.00, NULL),
(55, 44, 8, 2, 2100.00, 2100.00, NULL),
(56, 45, 9, 3, 1700.00, 1700.00, NULL),
(57, 46, 10, 4, 1800.00, 1800.00, NULL),
(58, 47, 11, 5, 560.00, 560.00, NULL),
(59, 48, 6, 2, 2200.00, 2200.00, NULL),
(60, 49, 7, 3, 1966.67, 1966.67, NULL),
(61, 50, 8, 4, 825.00, 825.00, NULL),
(62, 51, 9, 5, 1220.00, 1220.00, NULL),
(63, 52, 10, 2, 2350.00, 2350.00, NULL),
(64, 53, 11, 3, 1833.33, 1833.33, NULL),
(65, 54, 6, 4, 2050.00, 2050.00, NULL),
(66, 55, 7, 5, 760.00, 760.00, NULL),
(67, 56, 8, 2, 2600.00, 2600.00, NULL),
(68, 57, 9, 3, 1366.67, 1366.67, NULL),
(69, 58, 10, 4, 1575.00, 1575.00, NULL),
(70, 59, 11, 5, 580.00, 580.00, NULL),
(71, 60, 6, 2, 3700.00, 3700.00, NULL),
(72, 61, 7, 3, 1533.33, 1533.33, NULL),
(73, 62, 8, 4, 1450.00, 1450.00, NULL),
(74, 63, 9, 5, 1820.00, 1820.00, NULL),
(75, 64, 10, 2, 1750.00, 1750.00, NULL),
(76, 65, 11, 3, 1666.67, 1666.67, NULL),
(77, 66, 6, 4, 1075.00, 1075.00, NULL),
(78, 67, 7, 5, 1340.00, 1340.00, NULL),
(79, 68, 8, 2, 1550.00, 1550.00, NULL),
(80, 69, 9, 3, 1800.00, 1800.00, NULL),
(81, 70, 10, 4, 1225.00, 1225.00, NULL),
(82, 71, 11, 5, 740.00, 740.00, NULL),
(83, 72, 6, 2, 2650.00, 2650.00, NULL),
(84, 73, 7, 3, 2600.00, 2600.00, NULL),
(85, 74, 8, 4, 1000.00, 1000.00, NULL),
(86, 75, 9, 5, 1300.00, 1300.00, NULL),
(87, 76, 10, 2, 1600.00, 1600.00, NULL),
(88, 77, 11, 3, 1900.00, 1900.00, NULL),
(89, 78, 6, 4, 1200.00, 1200.00, NULL),
(90, 79, 7, 5, 1700.00, 1700.00, NULL),
(91, 80, 8, 2, 1950.00, 1950.00, NULL),
(92, 81, 9, 3, 2066.67, 2066.67, NULL),
(93, 82, 10, 4, 1125.00, 1125.00, NULL),
(94, 83, 11, 5, 1420.00, 1420.00, NULL),
(95, 84, 6, 2, 2800.00, 2800.00, NULL),
(96, 85, 7, 3, 3100.00, 3100.00, NULL),
(97, 86, 8, 4, 1050.00, 1050.00, NULL),
(98, 87, 9, 5, 1360.00, 1360.00, NULL),
(99, 88, 10, 2, 1800.00, 1800.00, NULL),
(100, 89, 11, 3, 2500.00, 2500.00, NULL),
(101, 90, 6, 4, 1275.00, 1275.00, NULL),
(102, 91, 7, 5, 1780.00, 1780.00, NULL),
(103, 92, 8, 2, 2350.00, 2350.00, NULL),
(104, 93, 9, 3, 2100.00, 2100.00, NULL),
(105, 94, 10, 4, 850.00, 850.00, NULL),
(106, 95, 11, 5, 1160.00, 1160.00, NULL),
(107, 96, 6, 2, 3600.00, 3600.00, NULL),
(108, 97, 7, 3, 1466.67, 1466.67, NULL),
(109, 98, 8, 4, 1650.00, 1650.00, NULL),
(110, 99, 9, 5, 1000.00, 1000.00, NULL),
(111, 100, 10, 2, 4050.00, 4050.00, NULL),
(112, 101, 11, 3, 1366.67, 1366.67, NULL),
(113, 102, 6, 4, 1925.00, 1925.00, NULL),
(114, 103, 7, 5, 1060.00, 1060.00, NULL),
(115, 104, 8, 2, 4750.00, 4750.00, NULL),
(116, 105, 9, 3, 1800.00, 1800.00, NULL),
(117, 106, 10, 4, 1825.00, 1825.00, NULL),
(118, 107, 11, 5, 920.00, 920.00, NULL),
(119, 108, 6, 2, 3050.00, 3050.00, NULL),
(178, 111, 7, 4, 2000.00, NULL, NULL),
(179, 112, 6, 4, 1800.00, NULL, NULL),
(180, 113, 6, 4, 1800.00, NULL, NULL),
(181, 114, 6, 4, 1800.00, NULL, NULL),
(182, 115, 14, 4, 2000.00, NULL, NULL),
(183, 116, 6, 11, 1800.00, NULL, NULL),
(184, 117, 9, 4, 1900.00, NULL, NULL),
(185, 118, 6, 4, 1800.00, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `direccion`
--

DROP TABLE IF EXISTS `direccion`;
CREATE TABLE IF NOT EXISTS `direccion` (
  `iddireccion` int NOT NULL AUTO_INCREMENT,
  `usuario_idusuario` int NOT NULL,
  `calle` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ciudad` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provincia` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `codigo_postal` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion_formateada` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitud` decimal(10,8) DEFAULT NULL,
  `longitud` decimal(11,8) DEFAULT NULL,
  `activo` tinyint DEFAULT NULL,
  `alias` varchar(70) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `principal` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`iddireccion`),
  KEY `fk_direccion_usuario1_idx` (`usuario_idusuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `direcciones_guardadas`
--

DROP TABLE IF EXISTS `direcciones_guardadas`;
CREATE TABLE IF NOT EXISTS `direcciones_guardadas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_idusuario` int NOT NULL,
  `apodo` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `direccion` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `lat` decimal(10,8) DEFAULT NULL,
  `lng` decimal(11,8) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_idusuario`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `direcciones_guardadas`
--

INSERT INTO `direcciones_guardadas` (`id`, `usuario_idusuario`, `apodo`, `direccion`, `lat`, `lng`, `created_at`) VALUES
(2, 8, 'Mi casa', 'Av. Mitre 1250, Posadas, Misiones', -27.36708400, -55.89609600, '2026-05-01 23:33:00'),
(3, 8, 'Trabajo', 'San Lorenzo 1580, Posadas, Misiones', -27.37200000, -55.90100000, '2026-05-01 23:33:00'),
(4, 9, 'Casa de mamá', 'Junín 890, Posadas, Misiones', -27.37500000, -55.88800000, '2026-05-01 23:33:00'),
(5, 9, 'Departamento', 'Av. López Torres 2100, Posadas, Misiones', -27.35900000, -55.91200000, '2026-05-01 23:33:00'),
(6, 1, 'Canetto HQ', 'San Martín 456, Posadas, Misiones', -27.37100000, -55.89800000, '2026-05-01 23:33:00'),
(11, 1, 'Casa', 'Padre Serrano 2745, Municipio de Posadas, Provincia de Misiones', -27.37378188, -55.91553912, '2026-05-07 22:27:05'),
(10, 1, 'Trabajo', 'Los Cardenales 6941, Municipio de Posadas, Provincia de Misiones', -27.41326102, -55.98421991, '2026-05-07 21:50:27');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estado_produccion`
--

DROP TABLE IF EXISTS `estado_produccion`;
CREATE TABLE IF NOT EXISTS `estado_produccion` (
  `idestado_produccion` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`idestado_produccion`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `estado_produccion`
--

INSERT INTO `estado_produccion` (`idestado_produccion`, `nombre`) VALUES
(1, 'Congelado'),
(2, 'Listo para usar');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estado_venta`
--

DROP TABLE IF EXISTS `estado_venta`;
CREATE TABLE IF NOT EXISTS `estado_venta` (
  `idestado_venta` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`idestado_venta`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `estado_venta`
--

INSERT INTO `estado_venta` (`idestado_venta`, `nombre`) VALUES
(1, 'Pendiente'),
(2, 'En Preparacion'),
(3, 'En manos del Repartidor'),
(4, 'Entregado'),
(5, 'Pendiente de Pago'),
(6, 'Cancelado'),
(7, 'Listo para retiro');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materia_prima`
--

DROP TABLE IF EXISTS `materia_prima`;
CREATE TABLE IF NOT EXISTS `materia_prima` (
  `idmateria_prima` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unidad_medida_idunidad_medida` int NOT NULL,
  `stock_actual` decimal(10,2) DEFAULT NULL,
  `stock_minimo` decimal(10,2) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `nota` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `peso_unitario_g` decimal(8,3) DEFAULT NULL COMMENT 'Peso en gramos por unidad. Completar si la unidad es contable (huevos, piezas, etc.)',
  PRIMARY KEY (`idmateria_prima`),
  KEY `fk_materia_prima_unidad_medida1_idx` (`unidad_medida_idunidad_medida`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `materia_prima`
--

INSERT INTO `materia_prima` (`idmateria_prima`, `nombre`, `unidad_medida_idunidad_medida`, `stock_actual`, `stock_minimo`, `activo`, `nota`, `created_at`, `updated_at`, `peso_unitario_g`) VALUES
(1, 'Huevo', 3, 35.00, 8.00, 1, 'mal producto', '2026-02-27 21:48:06', '2026-04-03 09:09:37', NULL),
(2, 'chocolate', 2, 71915.00, 5000.00, 1, 'paco', '2026-02-27 21:57:52', '2026-04-06 17:46:22', NULL),
(3, 'Manteca', 4, 150.00, 50.00, 1, 'prueba', '2026-04-06 16:54:30', '2026-04-06 16:58:09', NULL),
(4, 'Harina 0000', 5, 25.00, 5.00, 1, 'Harina especial galletería', '2026-05-01 23:33:00', NULL, 1000.000),
(5, 'Azúcar impalpable', 5, 10.00, 2.00, 1, 'Para masas y decoración', '2026-05-01 23:33:00', NULL, 1000.000),
(6, 'Azúcar rubio', 5, 12.00, 3.00, 1, 'Aporta sabor caramelizado', '2026-05-01 23:33:00', NULL, 1000.000),
(7, 'Azúcar común', 5, 16.00, 3.00, 1, NULL, '2026-05-01 23:33:00', '2026-05-10 21:34:47', 1000.000),
(8, 'Esencia de vainilla', 2, 800.00, 100.00, 1, 'Pura de bourbon', '2026-05-01 23:33:00', NULL, NULL),
(9, 'Chips de chocolate semiamargo', 4, 4500.00, 800.00, 1, 'Calidad premium', '2026-05-01 23:33:00', NULL, NULL),
(10, 'Cacao amargo en polvo', 4, 3000.00, 500.00, 1, 'Sin azúcar añadida', '2026-05-01 23:33:00', NULL, NULL),
(11, 'Dulce de leche repostero', 5, 8.00, 2.00, 1, 'Para rellenos y coberturas', '2026-05-01 23:33:00', NULL, 1000.000),
(12, 'Crema de leche', 2, 3000.00, 500.00, 1, '35% materia grasa', '2026-05-01 23:33:00', NULL, NULL),
(13, 'Sal fina', 4, 2000.00, 200.00, 1, NULL, '2026-05-01 23:33:00', NULL, NULL),
(14, 'Polvo para hornear', 4, 600.00, 100.00, 1, NULL, '2026-05-01 23:33:00', NULL, NULL),
(15, 'Bicarbonato de sodio', 4, 400.00, 50.00, 1, NULL, '2026-05-01 23:33:00', NULL, NULL),
(16, 'Maní pelado tostado', 4, 3000.00, 500.00, 1, NULL, '2026-05-01 23:33:00', NULL, NULL),
(17, 'Extracto de limón', 2, 500.00, 100.00, 1, NULL, '2026-05-01 23:33:00', NULL, NULL),
(18, 'Cobertura de chocolate blanco', 4, 2500.00, 400.00, 1, NULL, '2026-05-01 23:33:00', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materia_prima_has_proveedor`
--

DROP TABLE IF EXISTS `materia_prima_has_proveedor`;
CREATE TABLE IF NOT EXISTS `materia_prima_has_proveedor` (
  `materia_prima_idmateria_prima` int NOT NULL,
  `proveedor_idproveedor` int NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `costo` decimal(12,5) DEFAULT NULL,
  PRIMARY KEY (`materia_prima_idmateria_prima`,`proveedor_idproveedor`),
  KEY `fk_materia_prima_has_proveedor_proveedor1_idx` (`proveedor_idproveedor`),
  KEY `fk_materia_prima_has_proveedor_materia_prima1_idx` (`materia_prima_idmateria_prima`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `materia_prima_has_proveedor`
--

INSERT INTO `materia_prima_has_proveedor` (`materia_prima_idmateria_prima`, `proveedor_idproveedor`, `created_at`, `updated_at`, `costo`) VALUES
(7, 7, '2026-05-10 21:21:00', '2026-05-10 21:34:47', 5.00000);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mermas`
--

DROP TABLE IF EXISTS `mermas`;
CREATE TABLE IF NOT EXISTS `mermas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tipo` enum('producto','materia_prima','topping') NOT NULL,
  `referencia_id` int NOT NULL,
  `cantidad` decimal(10,3) NOT NULL,
  `unidad` varchar(20) DEFAULT NULL,
  `motivo` enum('vencimiento','produccion','accidente','control_calidad','otro') NOT NULL DEFAULT 'otro',
  `descripcion` text,
  `costo_estimado` decimal(10,2) DEFAULT '0.00',
  `usuario_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_fecha` (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metodo_pago`
--

DROP TABLE IF EXISTS `metodo_pago`;
CREATE TABLE IF NOT EXISTS `metodo_pago` (
  `idmetodo_pago` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`idmetodo_pago`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `metodo_pago`
--

INSERT INTO `metodo_pago` (`idmetodo_pago`, `nombre`) VALUES
(1, 'Efectivo'),
(2, 'Mercado Pago'),
(3, 'Transferencia Bancaria');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones_admin`
--

DROP TABLE IF EXISTS `notificaciones_admin`;
CREATE TABLE IF NOT EXISTS `notificaciones_admin` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipo` varchar(40) NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `descripcion` text,
  `link` varchar(300) DEFAULT NULL,
  `leida` tinyint(1) DEFAULT '0',
  `referencia_id` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `datos_json` text,
  PRIMARY KEY (`id`),
  KEY `idx_leida` (`leida`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `notificaciones_admin`
--

INSERT INTO `notificaciones_admin` (`id`, `tipo`, `titulo`, `descripcion`, `link`, `leida`, `referencia_id`, `created_at`, `datos_json`) VALUES
(1, 'stock_bajo', 'Stock bajo: Receta mica', 'Stock actual: 0.00 / Mínimo: 10.00', '/canetto/administracion/stock/index.php', 1, NULL, '2026-04-12 00:22:30', NULL),
(2, 'pedido_nuevo', 'Nuevo pedido #12', '📱 App · Juany garcia · $1,500.00', '/canetto/administracion/Ventas/Pedidos/index.php', 1, 12, '2026-04-12 00:33:08', NULL),
(3, 'stock_bajo', 'Stock bajo: Receta mica', 'Stock actual: 0.00 / Mínimo: 10.00', '/administracion/stock/index.php', 1, NULL, '2026-04-13 20:03:16', NULL),
(4, 'stock_bajo', 'Stock bajo: Receta mica', 'Stock actual: 0.00 / Mínimo: 10.00', '/administracion/stock/index.php', 1, NULL, '2026-04-14 02:34:54', NULL),
(5, 'stock_bajo', 'Stock bajo: Prueba cooki', 'Stock actual: 0.00 / Mínimo: 0.00', '/administracion/stock/index.php', 1, NULL, '2026-04-14 04:53:27', NULL),
(6, 'pedido_nuevo', 'Nuevo pedido #13', '📱 App · Juany garcia · $1,500.00', '/administracion/Ventas/Pedidos/index.php', 1, 13, '2026-04-14 04:56:15', NULL),
(7, 'pedido_nuevo', 'Nuevo pedido #14', '📱 App · Juany garcia · $1,500.00', '/administracion/Ventas/Pedidos/index.php', 1, 14, '2026-04-14 05:05:13', NULL),
(8, 'pedido_nuevo', 'Nuevo pedido #15', '📱 App · Juany garcia · $1,500.00', '/administracion/Ventas/Pedidos/index.php', 1, 15, '2026-04-14 05:28:13', NULL),
(9, 'pedido_nuevo', 'Nuevo pedido #16', '📱 App · Juany garcia · $1,500.00', '/administracion/Ventas/Pedidos/index.php', 1, 16, '2026-04-14 05:30:00', NULL),
(10, 'stock_bajo', 'Stock bajo: Receta mica', 'Stock actual: 0.00 / Mínimo: 10.00', 'https://administracion.canettocookies.com/stock/index.php', 1, NULL, '2026-04-17 19:18:16', NULL),
(11, 'stock_bajo', 'Stock bajo: Prueba cooki', 'Stock actual: 0.00 / Mínimo: 0.00', 'https://administracion.canettocookies.com/stock/index.php', 1, NULL, '2026-04-17 19:18:16', NULL),
(12, 'pedido_nuevo', 'Nuevo pedido #18', '📱 App · Juany garcia · $1,275.00', 'https://administracion.canettocookies.com/Ventas/Pedidos/index.php', 1, 18, '2026-04-17 20:17:12', NULL),
(13, 'stock_bajo', 'Stock bajo: Receta mica', 'Stock actual: 0.00 / Mínimo: 10.00', 'https://administracion.canettocookies.com/stock/index.php', 1, NULL, '2026-04-18 01:16:12', NULL),
(14, 'stock_bajo', 'Stock bajo: Prueba cooki', 'Stock actual: 0.00 / Mínimo: 0.00', 'https://administracion.canettocookies.com/stock/index.php', 1, NULL, '2026-04-18 01:16:12', NULL),
(15, 'pedido_nuevo', 'Nuevo pedido #19', '📱 App · Paco Paco · $6,000.00', 'http://localhost/canetto/administracion/Ventas/Pedidos/index.php', 1, 19, '2026-04-25 23:25:39', NULL),
(16, 'stock_bajo', 'Stock bajo: Cookie Doble Chocolate', 'El stock cayó por debajo del mínimo (12u).', '/administracion/stock.php', 1, 7, '2026-05-01 23:33:00', NULL),
(17, 'stock_bajo', 'Stock bajo: Harina 0000', 'El stock de Harina 0000 está en el mínimo (5kg).', '/administracion/materiaprima.php', 1, 4, '2026-05-01 23:33:00', NULL),
(18, 'pedido_nuevo', 'Nuevo pedido pendiente #1', 'Pedido de Juany Garcia por $5700 - Retiro en sucursal.', '/administracion/ventas.php', 1, NULL, '2026-05-01 23:33:00', NULL),
(19, 'pedido_nuevo', 'Pedido pendiente de pago', 'Pedido de $20.000 con pago pendiente de confirmación.', '/administracion/ventas.php', 1, NULL, '2026-05-01 23:33:00', NULL),
(20, 'stock_bajo', 'Stock crítico: Cookie Red Velvet', 'Solo quedan 6 unidades disponibles de Red Velvet.', '/administracion/stock.php', 1, 15, '2026-05-01 23:33:00', NULL),
(21, 'produccion', 'Producción completada: Vainilla', '48 cookies Vainilla pasaron a estado Listo para usar.', '/administracion/produccion.php', 1, NULL, '2026-05-01 23:33:00', NULL),
(22, 'produccion', 'Producción completada: Mantequilla', '42 cookies Mantequilla pasaron a estado Listo para usar.', '/administracion/produccion.php', 1, NULL, '2026-05-01 23:33:00', NULL),
(23, 'pedido_nuevo', 'Pedido urgente: Box Premium x10', 'Verificar stock para entrega hoy.', '/administracion/ventas.php', 1, NULL, '2026-05-01 23:33:00', NULL),
(24, 'stock_bajo', 'Stock crítico: Dulce de Leche Repostero', 'Solo 8kg disponibles. Recomendamos comprar urgente.', '/administracion/materiaprima.php', 1, 11, '2026-05-01 23:33:00', NULL),
(25, 'nuevo_usuario', 'Nuevo cliente registrado', 'Juany Hilbert se registró como nuevo cliente.', '/administracion/clientes.php', 1, 9, '2026-05-01 23:33:00', NULL),
(26, 'oferta_vence', 'Oferta vence en 7 días: 2x1 Vainilla', 'La oferta 2x1 Vainilla vence el próximo lunes.', '/administracion/ofertas.php', 1, NULL, '2026-05-01 23:33:00', NULL),
(27, 'compra_materia', 'Compra de materia prima registrada', 'Compra de 2500g Cobertura Chocolate Blanco a Dulciara SA.', '/administracion/compras.php', 1, NULL, '2026-05-01 23:33:00', NULL),
(28, 'pedido_nuevo', 'Nuevo pedido #32', '📱 App · Juancarlo Chupapija · $9,800.00', 'http://localhost/canetto/administracion/Ventas/Pedidos/index.php', 1, 32, '2026-05-03 15:22:50', NULL),
(29, 'pedido_nuevo', 'Nuevo pedido #33', '📱 App · Juancarlo Chupapija · $9800.00', 'http://localhost/canetto/administracion/Ventas/Pedidos/index.php', 1, 33, '2026-05-03 15:38:16', '{\"cliente\":\"Juancarlo Chupapija\",\"origen\":\"📱 App\",\"entrega\":\"🏪 Retiro\",\"metodo\":\"Efectivo\",\"total\":\"9800.00\",\"productos\":[{\"nombre\":\"Cookie Classic Choco Chip\",\"cantidad\":4,\"precio_unitario\":\"1800.00\",\"tipo\":\"producto\",\"contenido_box\":null}],\"toppings\":[]}'),
(30, 'pedido_nuevo', 'Nuevo pedido #34', '📱 App · Juancarlo Chupapija · $9200.00', 'http://localhost/canetto/administracion/Ventas/Pedidos/index.php', 1, 34, '2026-05-03 15:41:39', '{\"cliente\":\"Juancarlo Chupapija\",\"origen\":\"📱 App\",\"entrega\":\"🏪 Retiro\",\"metodo\":\"Efectivo\",\"total\":\"9200.00\",\"productos\":[{\"nombre\":\"Cookie Doble Chocolate\",\"cantidad\":4,\"precio_unitario\":\"2000.00\",\"tipo\":\"producto\",\"contenido_box\":null}],\"toppings\":[]}'),
(31, 'pedido_nuevo', 'Nuevo pedido #35', '📱 App · Juancarlo Chupapija · $9200.00', 'http://localhost/canetto/administracion/Ventas/Pedidos/index.php', 1, 35, '2026-05-03 15:44:02', '{\"cliente\":\"Juancarlo Chupapija\",\"origen\":\"📱 App\",\"entrega\":\"🏪 Retiro\",\"metodo\":\"Efectivo\",\"total\":\"9200.00\",\"productos\":[{\"nombre\":\"Cookie Doble Chocolate\",\"cantidad\":4,\"precio_unitario\":\"2000.00\",\"tipo\":\"producto\",\"contenido_box\":null}],\"toppings\":[]}'),
(32, 'pedido_nuevo', 'Nuevo pedido #36', '📱 App · Luciano · $8500.00', 'http://localhost/canetto/administracion/Ventas/Pedidos/index.php', 1, 36, '2026-05-05 02:56:14', '{\"cliente\":\"Luciano\",\"origen\":\"📱 App\",\"entrega\":\"🏪 Retiro\",\"metodo\":\"Efectivo\",\"total\":\"8500.00\",\"productos\":[{\"nombre\":\"Cookie Classic Choco Chip\",\"cantidad\":4,\"precio_unitario\":\"1800.00\",\"tipo\":\"producto\",\"contenido_box\":null}],\"toppings\":[]}'),
(33, 'pedido_nuevo', 'Nuevo pedido #37', '📱 App · Luciano · $18200.00', 'http://localhost/canetto/administracion/Ventas/Pedidos/index.php', 1, 37, '2026-05-05 03:28:13', '{\"cliente\":\"Luciano\",\"origen\":\"📱 App\",\"entrega\":\"🛵 Envío\",\"metodo\":\"Efectivo\",\"total\":\"18200.00\",\"productos\":[{\"nombre\":\"Cookie Classic Choco Chip\",\"cantidad\":4,\"precio_unitario\":\"1800.00\",\"tipo\":\"producto\",\"contenido_box\":null}],\"toppings\":[]}'),
(34, 'pedido_nuevo', 'Nuevo pedido #109', '📱 App · Luciano · $19800.00', 'http://localhost/canetto/administracion/Ventas/Pedidos/index.php', 1, 109, '2026-05-05 22:21:08', '{\"cliente\":\"Luciano\",\"origen\":\"📱 App\",\"entrega\":\"🛵 Envío\",\"metodo\":\"Efectivo\",\"total\":\"19800.00\",\"productos\":[{\"nombre\":\"Cookie Doble Chocolate\",\"cantidad\":4,\"precio_unitario\":\"2000.00\",\"tipo\":\"producto\",\"contenido_box\":null}],\"toppings\":[]}'),
(35, 'pedido_nuevo', 'Nuevo pedido #110', '📱 App · Luciano · $19800.00', 'http://localhost/canetto/administracion/Ventas/Pedidos/index.php', 1, 110, '2026-05-05 22:25:10', '{\"cliente\":\"Luciano\",\"origen\":\"📱 App\",\"entrega\":\"🛵 Envío\",\"metodo\":\"Efectivo\",\"total\":\"19800.00\",\"productos\":[{\"nombre\":\"Cookie Doble Chocolate\",\"cantidad\":4,\"precio_unitario\":\"2000.00\",\"tipo\":\"producto\",\"contenido_box\":null}],\"toppings\":[]}'),
(36, 'pedido_nuevo', 'Nuevo pedido #111', '📱 App · Luciano · $13800.00', 'http://localhost/canetto/administracion/Ventas/Pedidos/index.php', 1, 111, '2026-05-05 22:32:10', '{\"cliente\":\"Luciano\",\"origen\":\"📱 App\",\"entrega\":\"🛵 Envío\",\"metodo\":\"Efectivo\",\"total\":\"13800.00\",\"productos\":[{\"nombre\":\"Cookie Doble Chocolate\",\"cantidad\":4,\"precio_unitario\":\"2000.00\",\"tipo\":\"producto\",\"contenido_box\":null}],\"toppings\":[\"Almendras laminadas\"]}'),
(37, 'pedido_nuevo', 'Nuevo pedido #112', '📱 App · Luciano · $11500.00', 'http://localhost/canetto/administracion/Ventas/Pedidos/index.php', 1, 112, '2026-05-07 21:50:47', '{\"cliente\":\"Luciano\",\"origen\":\"📱 App\",\"entrega\":\"🛵 Envío\",\"metodo\":\"Efectivo\",\"total\":\"11500.00\",\"productos\":[{\"nombre\":\"Cookie Classic Choco Chip\",\"cantidad\":4,\"precio_unitario\":\"1800.00\",\"tipo\":\"producto\",\"contenido_box\":null}],\"toppings\":[\"Almendras laminadas\"]}'),
(38, 'pedido_nuevo', 'Nuevo pedido #113', '📱 App · Luciano · $9700.00', 'http://localhost/canetto/administracion/Ventas/Pedidos/index.php', 1, 113, '2026-05-07 22:27:24', '{\"cliente\":\"Luciano\",\"origen\":\"📱 App\",\"entrega\":\"🛵 Envío\",\"metodo\":\"Efectivo\",\"total\":\"9700.00\",\"productos\":[{\"nombre\":\"Cookie Classic Choco Chip\",\"cantidad\":4,\"precio_unitario\":\"1800.00\",\"tipo\":\"producto\",\"contenido_box\":null}],\"toppings\":[\"Azúcar glass\"]}'),
(39, 'pedido_nuevo', 'Nuevo pedido #114', '📱 App · Luciano · $9700.00', 'http://localhost/canetto/administracion/Ventas/Pedidos/index.php', 1, 114, '2026-05-07 22:31:43', '{\"cliente\":\"Luciano\",\"origen\":\"📱 App\",\"entrega\":\"🛵 Envío\",\"metodo\":\"Efectivo\",\"total\":\"9700.00\",\"productos\":[{\"nombre\":\"Cookie Classic Choco Chip\",\"cantidad\":4,\"precio_unitario\":\"1800.00\",\"tipo\":\"producto\",\"contenido_box\":null}],\"toppings\":[\"Azúcar glass\"]}'),
(40, 'pedido_nuevo', 'Nuevo pedido #115', '📱 App · Luciano · $8500.00', 'http://localhost/canetto/administracion/Ventas/Pedidos/index.php', 1, 115, '2026-05-07 22:32:59', '{\"cliente\":\"Luciano\",\"origen\":\"📱 App\",\"entrega\":\"🏪 Retiro\",\"metodo\":\"Efectivo\",\"total\":\"8500.00\",\"productos\":[{\"nombre\":\"Cookie Cacao Intenso\",\"cantidad\":4,\"precio_unitario\":\"2000.00\",\"tipo\":\"producto\",\"contenido_box\":null}],\"toppings\":[\"Azúcar glass\"]}'),
(41, 'pedido_nuevo', 'Nuevo pedido #116', '📱 App · Luciano · $22300.00', 'http://localhost/canetto/administracion/Ventas/Pedidos/index.php', 1, 116, '2026-05-07 22:41:31', '{\"cliente\":\"Luciano\",\"origen\":\"📱 App\",\"entrega\":\"🛵 Envío\",\"metodo\":\"Efectivo\",\"total\":\"22300.00\",\"productos\":[{\"nombre\":\"Cookie Classic Choco Chip\",\"cantidad\":11,\"precio_unitario\":\"1800.00\",\"tipo\":\"producto\",\"contenido_box\":null}],\"toppings\":[\"Azúcar glass\"]}'),
(42, 'pedido_nuevo', 'Nuevo pedido #117', '📱 App · Juancarlo Chupapija · $9600.00', 'http://localhost/canetto/administracion/Ventas/Pedidos/index.php', 1, 117, '2026-05-08 21:09:09', '{\"cliente\":\"Juancarlo Chupapija\",\"origen\":\"📱 App\",\"entrega\":\"🛵 Envío\",\"metodo\":\"Efectivo\",\"total\":\"9600.00\",\"productos\":[{\"nombre\":\"Cookie Dulce de Leche\",\"cantidad\":4,\"precio_unitario\":\"1900.00\",\"tipo\":\"producto\",\"contenido_box\":null}],\"toppings\":[]}'),
(43, 'pedido_nuevo', 'Nuevo pedido #118', '📱 App · Juancarlo Chupapija · $7700.00', 'http://localhost/canetto/administracion/Ventas/Pedidos/index.php', 1, 118, '2026-05-08 21:12:29', '{\"cliente\":\"Juancarlo Chupapija\",\"origen\":\"📱 App\",\"entrega\":\"🏪 Retiro\",\"metodo\":\"Efectivo\",\"total\":\"7700.00\",\"productos\":[{\"nombre\":\"Cookie Classic Choco Chip\",\"cantidad\":4,\"precio_unitario\":\"1800.00\",\"tipo\":\"producto\",\"contenido_box\":null}],\"toppings\":[\"Azúcar glass\"]}');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notif_repartidores`
--

DROP TABLE IF EXISTS `notif_repartidores`;
CREATE TABLE IF NOT EXISTS `notif_repartidores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `cuerpo` text NOT NULL,
  `leida` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_uid_leida` (`usuario_id`,`leida`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `notif_repartidores`
--

INSERT INTO `notif_repartidores` (`id`, `usuario_id`, `titulo`, `cuerpo`, `leida`, `created_at`) VALUES
(1, 13, '🛵 Tenés un pedido nuevo', 'El pedido #123 fue asignado a vos. ¡Abrí la app!', 0, '2026-06-07 18:15:09'),
(2, 15, '🛵 Tenés un pedido nuevo', 'El pedido #129 fue asignado a vos. ¡Abrí la app!', 0, '2026-06-07 18:21:00'),
(3, 18, '🛵 Tenés un pedido nuevo', 'El pedido #136 fue asignado a vos. ¡Abrí la app!', 0, '2026-06-07 18:22:35');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `oferta`
--

DROP TABLE IF EXISTS `oferta`;
CREATE TABLE IF NOT EXISTS `oferta` (
  `idoferta` int NOT NULL AUTO_INCREMENT,
  `titulo` varchar(200) NOT NULL,
  `descripcion` text,
  `emoji` varchar(10) DEFAULT '?',
  `tipo` varchar(20) DEFAULT 'promo',
  `tipo_panel` varchar(30) DEFAULT 'promo',
  `valor` decimal(10,2) DEFAULT NULL,
  `activo` tinyint DEFAULT '1',
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `imagen` varchar(255) DEFAULT NULL,
  `productos_idproductos` int DEFAULT NULL,
  `link` varchar(500) DEFAULT NULL,
  `btn_txt` varchar(80) DEFAULT NULL,
  PRIMARY KEY (`idoferta`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `oferta`
--

INSERT INTO `oferta` (`idoferta`, `titulo`, `descripcion`, `emoji`, `tipo`, `tipo_panel`, `valor`, `activo`, `fecha_inicio`, `fecha_fin`, `created_at`, `imagen`, `productos_idproductos`, `link`, `btn_txt`) VALUES
(2, 'Estammos de super descuento', 'Veni y PRoba el descuento de esto', '🎉', 'descuento', 'promo', 15.00, 0, '2026-04-05', '2026-04-10', '2026-04-05 19:49:54', NULL, 2, NULL, NULL),
(3, 'Receta mica', 'UNA Nueva Galleta que te puede hacer patinar el coco LOQUITA', '🎉', 'promo', 'promo', 1200.00, 0, '2026-04-06', '2026-04-15', '2026-04-06 20:57:55', 'oferta_69d41e53b88c5.png', 4, NULL, NULL),
(4, 'Oferton loco', 'Oferta  unica', '🎉', 'descuento', 'promo', 15.00, 1, '2026-04-17', '2026-04-23', '2026-04-17 19:54:03', 'oferta_69e28fdb6d324.jpg', 3, NULL, NULL),
(5, 'Estammos de super descuento', 'Veni y PRoba el descuento de esto', '🎉', 'descuento', 'promo', 15.00, 0, '2026-04-05', '2026-04-10', '2026-04-20 00:06:11', NULL, 2, NULL, NULL),
(6, '¡Envío gratis hoy!', 'Envío sin costo en todos los pedidos del día.', '🚚', 'banner', 'promo', 0.00, 1, '2026-05-02', '2026-05-02', '2026-05-02 02:33:00', NULL, NULL, NULL, 'Pedí ahora'),
(7, '2x1 en Vainilla Clásica', 'Llevá 2 cookies vainilla al precio de 1. Solo esta semana.', '🍪', 'descuento', 'promo', 50.00, 1, '2026-05-02', '2026-05-09', '2026-05-02 02:33:00', NULL, 13, NULL, 'Aprovechar'),
(8, 'Box Navidad -20%', 'El Box Navidad x8 con 20% de descuento por tiempo limitado.', '🎄', 'descuento', 'promo', 20.00, 1, '2026-05-02', '2026-06-01', '2026-05-02 02:33:00', NULL, 19, NULL, 'Ver oferta'),
(9, '¡Nueva! Cookie Red Velvet', 'Probá nuestra cookie más instagrameable de temporada.', '❤️', 'nuevo', 'nuevo', 0.00, 1, '2026-05-02', '2026-06-01', '2026-05-02 02:33:00', NULL, 15, NULL, 'Ver cookie'),
(10, 'Combo 3 cookies -15%', 'Llevá cualquier combinación de 3 cookies con 15% off.', '☕', 'descuento', 'promo', 15.00, 1, '2026-05-02', '2026-06-01', '2026-05-02 02:33:00', NULL, NULL, NULL, 'Ver combo'),
(11, 'Box San Valentín disponible', 'Sorprendé a esa persona especial con nuestro box especial.', '💝', 'banner', 'especial', 0.00, 1, '2026-05-02', '2026-06-01', '2026-05-02 02:33:00', NULL, 18, NULL, 'Regalar'),
(12, 'Maní y Chocolate -10%', 'La cookie más pedida con descuento especial de la semana.', '🥜', 'descuento', 'promo', 10.00, 1, '2026-05-02', '2026-05-09', '2026-05-02 02:33:00', NULL, 10, NULL, 'Aprovechar'),
(13, 'Retiro gratis en sucursal', 'Retirá tus cookies en cualquiera de nuestras sucursales sin costo.', '📍', 'banner', 'info', 0.00, 1, '2026-05-02', '2026-06-01', '2026-05-02 02:33:00', NULL, NULL, NULL, 'Ver sucursales'),
(14, 'Box Especial x12 ¡La mejor relación precio!', 'El box más completo: 10 sabores al mejor precio por unidad.', '💰', 'promo', 'promo', 0.00, 1, '2026-05-02', '2026-06-01', '2026-05-02 02:33:00', NULL, 17, NULL, 'Ver pack'),
(15, 'Oreo Crumble ¡Volvió!', 'Uno de los sabores más pedidos regresa por tiempo limitado.', '🍫', 'nuevo', 'nuevo', 0.00, 1, '2026-05-02', '2026-06-01', '2026-05-02 02:33:00', NULL, 11, NULL, 'Ver cookie'),
(16, 'Cookie Limón Glaseado -15%', 'Fresca, cítrica y ahora con descuento. ¡No te la pierdas!', '🍋', 'descuento', 'promo', 15.00, 1, '2026-05-02', '2026-05-09', '2026-05-02 02:33:00', NULL, 12, NULL, 'Aprovechar'),
(17, 'Personalizá tu box', 'Armá tu propio box con los sabores que más te gustan.', '🎁', 'banner', 'info', 0.00, 1, '2026-05-02', '2026-06-01', '2026-05-02 02:33:00', NULL, NULL, NULL, 'Armar box');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `packaging`
--

DROP TABLE IF EXISTS `packaging`;
CREATE TABLE IF NOT EXISTS `packaging` (
  `idpackaging` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stock_actual` decimal(10,2) NOT NULL DEFAULT '0.00',
  `stock_minimo` decimal(10,2) NOT NULL DEFAULT '0.00',
  `unidad_medida_idunidad_medida` int NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `precio_bruto` decimal(12,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`idpackaging`),
  KEY `fk_pkg_unidad` (`unidad_medida_idunidad_medida`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `packaging`
--

INSERT INTO `packaging` (`idpackaging`, `nombre`, `descripcion`, `stock_actual`, `stock_minimo`, `unidad_medida_idunidad_medida`, `activo`, `created_at`, `updated_at`, `precio_bruto`) VALUES
(1, 'caja chica', 'caja', 10.00, 5.00, 3, 1, '2026-04-17 19:37:46', NULL, 0.00),
(2, 'Caja individual premium', 'Caja de cartón rígido con ventana para 1 cookie', 50.00, 10.00, 3, 1, '2026-05-01 23:33:00', '2026-06-07 15:22:35', 0.00),
(3, 'Caja x6 cartón blanco', 'Caja para 6 cookies con separadores internos', 30.00, 5.00, 3, 1, '2026-05-01 23:33:00', NULL, 0.00),
(4, 'Caja x12 cartón kraft', 'Caja grande para 12 cookies, color natural', 20.00, 3.00, 3, 1, '2026-05-01 23:33:00', NULL, 0.00),
(5, 'Caja premium kraft con lazo', 'Caja especial con lazo y papel tissue interior', 25.00, 5.00, 3, 1, '2026-05-01 23:33:00', NULL, 0.00),
(6, 'Bolsa celofán chica', 'Para 1-2 cookies con cinta adhesiva', 100.00, 20.00, 3, 1, '2026-05-01 23:33:00', NULL, 0.00),
(7, 'Bolsa celofán mediana', 'Para 3-4 cookies con cinta de regalo', 80.00, 15.00, 3, 1, '2026-05-01 23:33:00', NULL, 0.00),
(8, 'Bolsa kraft x6', 'Bolsa de papel kraft con ventana para 6 unidades', 40.00, 10.00, 3, 1, '2026-05-01 23:33:00', NULL, 0.00),
(9, 'Caja San Valentín', 'Caja roja corazón para fecha especial', 15.00, 3.00, 3, 1, '2026-05-01 23:33:00', NULL, 0.00),
(10, 'Caja Navidad', 'Caja verde/roja con motivos navideños', 20.00, 3.00, 3, 1, '2026-05-01 23:33:00', NULL, 0.00),
(11, 'Cinta de regalo satinada', 'Cinta satinada Canetto para envolver', 200.00, 50.00, 3, 1, '2026-05-01 23:33:00', NULL, 0.00),
(12, 'Tarjeta de mensaje', 'Tarjeta A6 con espacio para dedicatoria', 150.00, 30.00, 3, 1, '2026-05-01 23:33:00', NULL, 0.00),
(13, 'Papel manteca estampado', 'Papel manteca con logo Canetto para forrar', 229.00, 80.00, 3, 1, '2026-05-01 23:33:00', '2026-06-07 15:22:35', 0.00),
(14, 'Sticker Canetto rollo', 'Rollo de stickers adhesivos con logo y colores marca', 429.00, 100.00, 3, 1, '2026-05-01 23:33:00', '2026-06-07 15:22:35', 0.00),
(15, 'Bolsa arpillera', 'Bolsa de arpillera natural con lazo de rafia', 35.00, 5.00, 3, 1, '2026-05-01 23:33:00', NULL, 0.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos_mercadopago`
--

DROP TABLE IF EXISTS `pagos_mercadopago`;
CREATE TABLE IF NOT EXISTS `pagos_mercadopago` (
  `idpagos_mercadopago` int NOT NULL AUTO_INCREMENT,
  `ventas_idventas` int NOT NULL,
  `mp_preference_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mp_payment_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado_mp` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `metodo_mp` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `monto` decimal(10,2) DEFAULT NULL,
  `fecha_pago` datetime DEFAULT NULL,
  `raw_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `payment_type` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `external_reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`idpagos_mercadopago`),
  UNIQUE KEY `mp_payment_id` (`mp_payment_id`),
  KEY `fk_pagos_mercadopago_ventas1_idx` (`ventas_idventas`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos_tarjeta`
--

DROP TABLE IF EXISTS `pagos_tarjeta`;
CREATE TABLE IF NOT EXISTS `pagos_tarjeta` (
  `idpagos_tarjeta` int NOT NULL AUTO_INCREMENT,
  `ventas_idventas` int NOT NULL,
  `proveedor_bancario_idproveedor_bancario` int NOT NULL,
  `ultimos_4` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `autorizacion_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha_pago` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`idpagos_tarjeta`),
  KEY `fk_pagos_tarjeta_ventas1_idx` (`ventas_idventas`),
  KEY `fk_pagos_tarjeta_proveedor_bancario1_idx` (`proveedor_bancario_idproveedor_bancario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `password_reset_tokens`
--

INSERT INTO `password_reset_tokens` (`id`, `usuario_id`, `token`, `expires_at`, `used`, `created_at`) VALUES
(2, 8, '9a376929bb73e457f308e95abd1a9f055dfb7df9ecce79e221a80632ca6030ff', '2026-04-14 05:26:19', 1, '2026-04-14 04:26:19');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `produccion`
--

DROP TABLE IF EXISTS `produccion`;
CREATE TABLE IF NOT EXISTS `produccion` (
  `idproduccion` int NOT NULL AUTO_INCREMENT,
  `recetas_idrecetas` int NOT NULL,
  `cantidad` decimal(10,2) DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `usuario_idusuario` int NOT NULL,
  `estado_produccion_idestado_produccion` int NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`idproduccion`),
  KEY `fk_produccion_recetas1_idx` (`recetas_idrecetas`),
  KEY `fk_produccion_usuario1_idx` (`usuario_idusuario`),
  KEY `fk_produccion_estado_produccion1_idx` (`estado_produccion_idestado_produccion`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `produccion`
--

INSERT INTO `produccion` (`idproduccion`, `recetas_idrecetas`, `cantidad`, `fecha`, `usuario_idusuario`, `estado_produccion_idestado_produccion`, `created_at`, `updated_at`) VALUES
(1, 6, 100.00, '2026-03-18 15:33:16', 1, 1, NULL, NULL),
(2, 7, 66.00, '2026-03-19 13:42:36', 1, 1, NULL, NULL),
(3, 6, 100.00, '2026-03-19 16:18:12', 1, 1, NULL, NULL),
(6, 6, 10.00, '2026-03-19 17:49:16', 1, 1, NULL, NULL),
(7, 6, 10.00, '2026-03-19 18:31:03', 1, 1, NULL, NULL),
(8, 6, 100.00, '2026-04-04 00:13:50', 1, 1, NULL, NULL),
(9, 10, 45.00, '2026-04-06 17:09:30', 1, 1, NULL, NULL),
(10, 11, 48.00, '2025-04-20 08:00:00', 1, 1, '2026-05-01 23:33:00', NULL),
(11, 12, 36.00, '2025-04-21 08:30:00', 1, 1, '2026-05-01 23:33:00', NULL),
(12, 13, 42.00, '2025-04-22 09:00:00', 1, 2, '2026-05-01 23:33:00', NULL),
(13, 14, 30.00, '2025-04-23 08:00:00', 1, 1, '2026-05-01 23:33:00', NULL),
(14, 15, 36.00, '2025-04-24 09:00:00', 1, 2, '2026-05-01 23:33:00', NULL),
(15, 16, 24.00, '2025-04-25 08:30:00', 1, 1, '2026-05-01 23:33:00', NULL),
(16, 17, 30.00, '2025-04-26 09:00:00', 1, 2, '2026-05-01 23:33:00', NULL),
(17, 18, 48.00, '2025-04-27 08:00:00', 1, 2, '2026-05-01 23:33:00', NULL),
(18, 19, 36.00, '2025-04-28 08:30:00', 1, 1, '2026-05-01 23:33:00', NULL),
(19, 20, 18.00, '2025-04-29 09:00:00', 1, 2, '2026-05-01 23:33:00', NULL),
(20, 21, 24.00, '2025-04-30 08:00:00', 1, 1, '2026-05-01 23:33:00', NULL),
(21, 22, 20.00, '2025-05-01 09:00:00', 1, 2, '2026-05-01 23:33:00', NULL),
(22, 11, 10.00, '2026-05-05 02:21:16', 1, 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

DROP TABLE IF EXISTS `productos`;
CREATE TABLE IF NOT EXISTS `productos` (
  `idproductos` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `recetas_idrecetas` int DEFAULT NULL,
  `activo` tinyint DEFAULT '1',
  `tipo` enum('producto','box') COLLATE utf8mb4_unicode_ci DEFAULT 'producto',
  `imagen` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `especificaciones` text COLLATE utf8mb4_unicode_ci,
  `orden` int DEFAULT NULL,
  PRIMARY KEY (`idproductos`),
  KEY `fk_producto_receta` (`recetas_idrecetas`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`idproductos`, `nombre`, `precio`, `recetas_idrecetas`, `activo`, `tipo`, `imagen`, `descripcion`, `especificaciones`, `orden`) VALUES
(1, 'Luciano juan', 1500.00, 4, 1, 'producto', NULL, NULL, NULL, NULL),
(2, 'Box Degustacion', 15000.00, NULL, 1, 'box', 'box_degustacion.jpg', NULL, NULL, NULL),
(3, 'pruebastock', 1500.00, 6, 1, 'producto', NULL, NULL, NULL, NULL),
(4, 'Receta mica', 1500.00, 10, 1, 'producto', NULL, NULL, NULL, NULL),
(5, 'Prueba cooki', 100.00, 10, 1, 'producto', 'prod_69ddc84566ba7.jpg', NULL, NULL, NULL),
(6, 'Cookie Classic Choco Chip', 1800.00, 11, 1, 'producto', 'cookie_chocochip.jpg', 'La cookie más amada de Canetto. Masa de manteca dorada con generosos chips de chocolate semiamargo en cada mordida.', 'harina, manteca, azúcar rubio, chips de chocolate semiamargo, esencia de vainilla, huevo, sal fina', NULL),
(7, 'Cookie Doble Chocolate', 2000.00, 12, 1, 'producto', 'cookie_doblechoco.jpg', 'Para los amantes del chocolate puro. Masa oscura de cacao con doble chips. Intensa, húmeda y perfecta.', 'harina, cacao amargo en polvo, manteca, azúcar, chips de chocolate semiamargo, huevo, bicarbonato de sodio', NULL),
(8, 'Cookie Mantequilla con Sal', 1600.00, 13, 1, 'producto', 'cookie_mantequilla.jpg', 'Un clásico refinado. La delicadeza de la manteca premium con toques de sal marina en escamas que la hacen única.', 'harina, manteca, azúcar impalpable, sal marina, esencia de vainilla, huevo', NULL),
(9, 'Cookie Dulce de Leche', 1900.00, 14, 1, 'producto', 'cookie_ddl.jpg', 'Swirls de dulce de leche repostero artesanal integrados en la masa. El sabor argentino hecho cookie.', 'harina, manteca, dulce de leche repostero, azúcar impalpable, esencia de vainilla, huevo', NULL),
(10, 'Cookie Maní y Chocolate', 2100.00, 15, 1, 'producto', 'cookie_mani.jpg', 'La combinación ganadora. Trozos de maní tostado y chips de chocolate semiamargo en una masa suave y húmeda.', 'harina, manteca, maní pelado tostado, chips de chocolate semiamargo, azúcar rubio, huevo, sal fina', NULL),
(11, 'Cookie Oreo Crumble', 2200.00, 16, 1, 'producto', 'cookie_oreo.jpg', 'Masa oscura de cacao con crumble de galletitas tipo Oreo y chips de chocolate blanco. Un festín de texturas.', 'harina, cacao amargo en polvo, manteca, azúcar, trozos oreo, chocolate blanco, huevo', NULL),
(12, 'Cookie Limón Glaseado', 1700.00, 17, 1, 'producto', 'cookie_limon.jpg', 'Fresca y aromática. Masa de limón natural con glaseado cítrico artesanal. Ideal para quien busca algo diferente.', 'harina, manteca, azúcar impalpable, extracto de limón, polvo para hornear, huevo', NULL),
(13, 'Cookie Vainilla Clásica', 1500.00, 18, 1, 'producto', 'cookie_vainilla.jpg', 'La pureza de la vainilla bourbon. Simple, suave y perfecta. La favorita de los más chicos.', 'harina, manteca, azúcar impalpable, esencia de vainilla, polvo para hornear, huevo', NULL),
(14, 'Cookie Cacao Intenso', 2000.00, 19, 1, 'producto', 'cookie_cacao.jpg', 'Alta concentración de cacao amargo para paladares exigentes. Oscura por fuera, húmeda y suave por dentro.', 'harina, cacao amargo en polvo, manteca, azúcar, chips de chocolate semiamargo, huevo, bicarbonato', NULL),
(15, 'Cookie Red Velvet', 2300.00, 20, 1, 'producto', 'cookie_redvelvet.jpg', 'La cookie más fotogénica. Masa roja con chips de chocolate blanco. Espectacular y deliciosa.', 'harina, cacao, manteca, azúcar rubio, chocolate blanco, colorante rojo natural, huevo', NULL),
(16, 'Box Clásico x6', 10800.00, NULL, 1, 'box', 'box_clasico.jpg', 'Selección de 6 cookies clásicas de nuestros sabores más populares. Ideal para regalar.', 'cookies: choco chip, doble chocolate, mantequilla, dulce de leche, maní, vainilla', NULL),
(17, 'Box Especial x10', 22000.00, NULL, 1, 'box', 'box_especial.jpg', 'El box completo. Los 10 sabores de Canetto en packaging premium. El regalo perfecto.', 'incluye todos los sabores de la línea clásica y premium de Canetto', NULL),
(18, 'Box San Valentín x4', 8000.00, NULL, 1, 'box', 'box_sanvalentin.jpg', 'Cuatro cookies de edición especial en packaging de San Valentín. Un regalo con amor.', 'cookies red velvet, dulce de leche, doble chocolate y oreo crumble', NULL),
(19, 'Box Navidad x8', 15000.00, NULL, 1, 'box', 'box_navidad.jpg', 'Ocho cookies de temporada navideña con packaging especial para las fiestas.', 'selección navideña: vainilla, choco chip, maní, cacao, limón, oreo, red velvet, mantequilla', NULL),
(20, 'Box Premium Mix x10', 20000.00, NULL, 1, 'box', 'box_premium.jpg', 'Diez cookies premium con los sabores especiales de la temporada. Packaging de lujo.', 'selección premium de todos los sabores incluyendo ediciones especiales', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos_imagenes`
--

DROP TABLE IF EXISTS `productos_imagenes`;
CREATE TABLE IF NOT EXISTS `productos_imagenes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `productos_idproductos` int NOT NULL,
  `archivo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `orden` int NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `productos_idproductos` (`productos_idproductos`)
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `productos_imagenes`
--

INSERT INTO `productos_imagenes` (`id`, `productos_idproductos`, `archivo`, `orden`, `created_at`) VALUES
(1, 2, 'box_degustacion.jpg', 0, '2026-05-14 21:18:54'),
(2, 5, 'prod_69ddc84566ba7.jpg', 0, '2026-05-14 21:18:54'),
(3, 6, 'cookie_chocochip.jpg', 0, '2026-05-14 21:18:54'),
(4, 7, 'cookie_doblechoco.jpg', 0, '2026-05-14 21:18:54'),
(5, 8, 'cookie_mantequilla.jpg', 0, '2026-05-14 21:18:54'),
(6, 9, 'cookie_ddl.jpg', 0, '2026-05-14 21:18:54'),
(7, 10, 'cookie_mani.jpg', 0, '2026-05-14 21:18:54'),
(8, 11, 'cookie_oreo.jpg', 0, '2026-05-14 21:18:54'),
(9, 12, 'cookie_limon.jpg', 0, '2026-05-14 21:18:54'),
(10, 13, 'cookie_vainilla.jpg', 0, '2026-05-14 21:18:54'),
(11, 14, 'cookie_cacao.jpg', 0, '2026-05-14 21:18:54'),
(12, 15, 'cookie_redvelvet.jpg', 0, '2026-05-14 21:18:54'),
(13, 16, 'box_clasico.jpg', 0, '2026-05-14 21:18:54'),
(14, 17, 'box_especial.jpg', 0, '2026-05-14 21:18:54'),
(15, 18, 'box_sanvalentin.jpg', 0, '2026-05-14 21:18:54'),
(16, 19, 'box_navidad.jpg', 0, '2026-05-14 21:18:54'),
(17, 20, 'box_premium.jpg', 0, '2026-05-14 21:18:54'),
(18, 14, 'cookie_cacao_gif.gif', 1, '2026-05-14 21:25:13'),
(19, 6, 'cookie_choco_gif.gif', 1, '2026-05-14 21:25:13');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto_packaging`
--

DROP TABLE IF EXISTS `producto_packaging`;
CREATE TABLE IF NOT EXISTS `producto_packaging` (
  `idproducto_packaging` int NOT NULL AUTO_INCREMENT,
  `productos_idproductos` int NOT NULL,
  `packaging_idpackaging` int NOT NULL,
  `cantidad` decimal(8,2) NOT NULL DEFAULT '1.00',
  PRIMARY KEY (`idproducto_packaging`),
  UNIQUE KEY `uk_prod_pkg` (`productos_idproductos`,`packaging_idpackaging`),
  KEY `fk_pp_pkg` (`packaging_idpackaging`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `producto_packaging`
--

INSERT INTO `producto_packaging` (`idproducto_packaging`, `productos_idproductos`, `packaging_idpackaging`, `cantidad`) VALUES
(1, 6, 2, 1.00),
(2, 6, 13, 1.00),
(3, 6, 14, 1.00),
(4, 7, 2, 1.00),
(5, 7, 13, 1.00),
(6, 7, 14, 1.00),
(7, 8, 2, 1.00),
(8, 8, 13, 1.00),
(9, 8, 14, 1.00),
(10, 9, 2, 1.00),
(11, 9, 13, 1.00),
(12, 9, 14, 1.00),
(13, 10, 2, 1.00),
(14, 10, 13, 1.00),
(15, 10, 14, 1.00),
(16, 11, 2, 1.00),
(17, 11, 13, 1.00),
(18, 11, 14, 1.00),
(19, 12, 2, 1.00),
(20, 12, 13, 1.00),
(21, 12, 14, 1.00),
(22, 13, 2, 1.00),
(23, 13, 13, 1.00),
(24, 13, 14, 1.00),
(25, 14, 2, 1.00),
(26, 14, 13, 1.00),
(27, 14, 14, 1.00),
(28, 15, 2, 1.00),
(29, 15, 13, 1.00),
(30, 15, 14, 1.00),
(31, 16, 3, 1.00),
(32, 16, 12, 1.00),
(33, 16, 14, 1.00),
(34, 17, 4, 1.00),
(35, 17, 12, 1.00),
(36, 17, 14, 1.00),
(37, 18, 9, 1.00),
(38, 18, 12, 1.00),
(39, 18, 14, 1.00),
(40, 19, 10, 1.00),
(41, 19, 12, 1.00),
(42, 19, 14, 1.00),
(43, 20, 5, 1.00),
(44, 20, 12, 1.00),
(45, 20, 14, 1.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto_toppings`
--

DROP TABLE IF EXISTS `producto_toppings`;
CREATE TABLE IF NOT EXISTS `producto_toppings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `productos_idproductos` int NOT NULL,
  `toppings_idtoppings` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pt` (`productos_idproductos`,`toppings_idtoppings`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedor`
--

DROP TABLE IF EXISTS `proveedor`;
CREATE TABLE IF NOT EXISTS `proveedor` (
  `idproveedor` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contacto_nombre` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contacto_telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observaciones` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`idproveedor`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `proveedor`
--

INSERT INTO `proveedor` (`idproveedor`, `nombre`, `telefono`, `email`, `direccion`, `contacto_nombre`, `contacto_telefono`, `observaciones`, `activo`, `created_at`, `updated_at`) VALUES
(1, 'mime', 'rwerewr', 'lucianogastonbarros@gmail.com', 'Ruiz De Montoya 2766', 'pedro', '23432423', 'me gustra', 1, '2026-03-31 22:49:45', '2026-03-31 22:49:45'),
(4, 'Molinos Río de la Plata', '3764100200', 'ventas@molinos.com.ar', 'Av. San Martín 1200, Posadas', 'Roberto Giménez', '3764555111', 'Proveedor principal de harinas y azúcares', 1, '2026-05-01 23:33:00', NULL),
(5, 'Lácteos del Norte', '3764200300', 'contacto@lacteosn.com.ar', 'Ruta 12 km 5, Posadas', 'María Fernández', '3764555222', 'Manteca y crema de primera calidad', 1, '2026-05-01 23:33:00', NULL),
(6, 'Dulciara SA', '3764300400', 'pedidos@dulciara.com.ar', 'Calle Colón 450, Posadas', 'Gustavo Pérez', '3764555333', 'Chocolates, coberturas y dulce de leche premium', 1, '2026-05-01 23:33:00', NULL),
(7, 'Distribuidora El Sabor', '3764400500', 'ventas@elsabor.com.ar', 'Av. Entre Ríos 890, Posadas', 'Ana López', '3764555444', 'Insumos generales para pastelería', 1, '2026-05-01 23:33:00', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedor_bancario`
--

DROP TABLE IF EXISTS `proveedor_bancario`;
CREATE TABLE IF NOT EXISTS `proveedor_bancario` (
  `idproveedor_bancario` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`idproveedor_bancario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `push_notificaciones`
--

DROP TABLE IF EXISTS `push_notificaciones`;
CREATE TABLE IF NOT EXISTS `push_notificaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `cuerpo` text NOT NULL,
  `url` varchar(512) NOT NULL DEFAULT '/canetto/tienda/mis-pedidos.php',
  `leida` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_uid_leida` (`usuario_id`,`leida`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `push_notificaciones`
--

INSERT INTO `push_notificaciones` (`id`, `usuario_id`, `titulo`, `cuerpo`, `url`, `leida`, `created_at`) VALUES
(1, 4, '🎉 ¡Pedido entregado!', 'Tu pedido fue entregado. ¡Gracias por elegirnos! (Pedido #35)', '/canetto/tienda/mis-pedidos.php', 0, '2026-05-04 03:01:39'),
(2, 1, '🛵 Tu pedido está en camino', 'El repartidor ya salió. ¡Pronto llegará! (Pedido #36)', '/canetto/tienda/mis-pedidos.php', 0, '2026-05-05 05:57:15'),
(3, 1, '📦 Listo para retirar', '¡Tu pedido está listo! Podés venir a buscarlo. (Pedido #36)', '/canetto/tienda/mis-pedidos.php', 0, '2026-05-05 05:57:20'),
(4, 1, '🛵 Tu pedido está en camino', 'El repartidor ya salió. ¡Pronto llegará! (Pedido #36)', '/canetto/tienda/mis-pedidos.php', 0, '2026-05-05 05:57:26'),
(5, 1, '🛵 Tu pedido está en camino', 'El repartidor ya salió. ¡Pronto llegará! (Pedido #37)', '/canetto/tienda/mis-pedidos.php', 0, '2026-05-05 06:30:25'),
(6, 1, '🛵 Tu pedido está en camino', 'El repartidor ya salió. ¡Pronto llegará! (Pedido #111)', '/canetto/tienda/mis-pedidos.php', 0, '2026-05-06 01:32:44'),
(7, 4, '📦 Listo para retirar', '¡Tu pedido está listo! Podés venir a buscarlo. (Pedido #118)', '/canetto/tienda/mis-pedidos.php', 0, '2026-05-15 23:37:39'),
(8, 4, '🛵 Tu pedido está en camino', 'El repartidor ya salió. ¡Pronto llegará! (Pedido #118)', '/canetto/tienda/mis-pedidos.php', 0, '2026-05-15 23:37:44'),
(9, 4, '✅ Pedido recibido', 'Recibimos tu pedido y lo estamos revisando. (Pedido #118)', '/canetto/tienda/mis-pedidos.php', 0, '2026-05-15 23:38:11'),
(10, 1, '🎉 ¡Pedido entregado!', 'Tu pedido fue entregado. ¡Gracias por elegirnos! (Pedido #111)', '/canetto/tienda/mis-pedidos.php', 0, '2026-05-15 23:38:37'),
(11, 4, '🛵 Tu pedido está en camino', 'El repartidor ya salió. ¡Pronto llegará! (Pedido #118)', '/canetto/tienda/mis-pedidos.php', 0, '2026-05-17 01:37:05');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `push_subscriptions`
--

DROP TABLE IF EXISTS `push_subscriptions`;
CREATE TABLE IF NOT EXISTS `push_subscriptions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `endpoint` text NOT NULL,
  `endpoint_hash` char(64) NOT NULL,
  `p256dh` varchar(512) NOT NULL,
  `auth_key` varchar(255) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_ep` (`endpoint_hash`),
  KEY `idx_uid` (`usuario_id`,`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recetas`
--

DROP TABLE IF EXISTS `recetas`;
CREATE TABLE IF NOT EXISTS `recetas` (
  `idrecetas` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observacion` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `masa_total` decimal(10,2) DEFAULT NULL,
  `cantidad_galletas` int DEFAULT NULL,
  `unidad_medida_idunidad_medida` int DEFAULT NULL,
  PRIMARY KEY (`idrecetas`),
  KEY `fk_receta_unidad` (`unidad_medida_idunidad_medida`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `recetas`
--

INSERT INTO `recetas` (`idrecetas`, `nombre`, `observacion`, `masa_total`, `cantidad_galletas`, `unidad_medida_idunidad_medida`) VALUES
(4, 'Doble choco', 'Preparandola con mucho amor', NULL, NULL, NULL),
(5, 'polentaaaaaa', '333', 40.00, 35, 1),
(6, 'juan', 'pedro', 50.00, 100, 3),
(7, 'Luciano', '55', 5.00, 66, 1),
(9, 'juan pedro', NULL, 5.00, 5, 1),
(10, 'Mica juany', 'Prueba de nueva receta', 5000.00, 45, 4),
(11, 'Cookie Classic Choco Chip', 'Clásica con chips de chocolate semiamargo', 35.00, 24, 3),
(12, 'Cookie Doble Chocolate', 'Masa de cacao con doble chips', 38.00, 22, 3),
(13, 'Cookie Mantequilla con Sal', 'Sal marina en escamas, manteca extra', 32.00, 20, 3),
(14, 'Cookie Dulce de Leche', 'Con swirls de dulce de leche repostero', 36.00, 24, 3),
(15, 'Cookie Maní y Chocolate', 'Maní triturado y chips semiamargo', 40.00, 22, 3),
(16, 'Cookie Oreo Crumble', 'Con trozos de galletita tipo Oreo', 34.00, 20, 3),
(17, 'Cookie Limón Glaseado', 'Masa de limón con glaseado cítrico', 30.00, 18, 3),
(18, 'Cookie Vainilla Clásica', 'Receta base con vainilla pura', 32.00, 24, 3),
(19, 'Cookie Cacao Intenso', 'Alta concentración de cacao amargo', 38.00, 22, 3),
(20, 'Cookie Red Velvet', 'Masa roja con chips de chocolate blanco', 36.00, 20, 3),
(21, 'Cookie Avena y Pasas', 'Avena laminada con pasas y canela', 42.00, 28, 3),
(22, 'Cookie Almendra Tostada', 'Almendras fileteadas con extracto', 34.00, 18, 3),
(23, 'Cookie Frambuesa', 'Con mermelada de frambuesa artesanal', 32.00, 20, 3),
(24, 'Cookie Canela Manzana', 'Chips de manzana deshidratada y canela', 38.00, 24, 3),
(25, 'Cookie Premium Mix', 'Combinación de todos los sabores', 40.00, 22, 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `receta_ingredientes`
--

DROP TABLE IF EXISTS `receta_ingredientes`;
CREATE TABLE IF NOT EXISTS `receta_ingredientes` (
  `idreceta_ingredientes` int NOT NULL AUTO_INCREMENT,
  `recetas_idrecetas` int NOT NULL,
  `materia_prima_idmateria_prima` int NOT NULL,
  `cantidad` decimal(10,2) DEFAULT NULL,
  `unidad_medida_idunidad_medida` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`idreceta_ingredientes`),
  KEY `fk_receta_ingredientes_recetas1_idx` (`recetas_idrecetas`),
  KEY `fk_receta_ingredientes_materia_prima1_idx` (`materia_prima_idmateria_prima`),
  KEY `fk_receta_ingredientes_unidad_medida1_idx` (`unidad_medida_idunidad_medida`)
) ENGINE=InnoDB AUTO_INCREMENT=93 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `receta_ingredientes`
--

INSERT INTO `receta_ingredientes` (`idreceta_ingredientes`, `recetas_idrecetas`, `materia_prima_idmateria_prima`, `cantidad`, `unidad_medida_idunidad_medida`, `created_at`, `updated_at`) VALUES
(8, 5, 1, 8.00, 3, '2026-03-08 10:24:22', '2026-03-08 10:24:22'),
(10, 7, 1, 5.00, 3, '2026-03-08 10:36:50', '2026-03-08 10:36:50'),
(11, 9, 1, 5.00, 1, '2026-03-08 10:53:07', '2026-03-08 10:53:07'),
(15, 6, 1, 5.00, 3, '2026-03-08 19:26:09', '2026-03-08 19:26:09'),
(16, 10, 2, 85.00, 4, '2026-04-06 17:01:04', '2026-04-06 17:01:04'),
(17, 10, 1, 5.00, 3, '2026-04-06 17:01:04', '2026-04-06 17:01:04'),
(18, 11, 4, 200.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(19, 11, 3, 150.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(20, 11, 6, 120.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(21, 11, 9, 200.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(22, 11, 8, 5.00, 2, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(23, 12, 4, 180.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(24, 12, 10, 40.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(25, 12, 3, 160.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(26, 12, 7, 130.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(27, 12, 9, 150.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(28, 13, 4, 220.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(29, 13, 3, 200.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(30, 13, 5, 100.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(31, 13, 13, 3.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(32, 13, 8, 5.00, 2, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(33, 14, 4, 200.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(34, 14, 3, 150.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(35, 14, 11, 120.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(36, 14, 5, 80.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(37, 14, 8, 5.00, 2, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(38, 15, 4, 190.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(39, 15, 3, 140.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(40, 15, 16, 150.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(41, 15, 9, 120.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(42, 15, 6, 110.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(43, 16, 4, 200.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(44, 16, 10, 30.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(45, 16, 3, 150.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(46, 16, 7, 120.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(47, 16, 18, 80.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(48, 17, 4, 200.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(49, 17, 3, 130.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(50, 17, 5, 150.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(51, 17, 17, 30.00, 2, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(52, 17, 14, 5.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(53, 18, 4, 200.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(54, 18, 3, 150.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(55, 18, 5, 100.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(56, 18, 8, 10.00, 2, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(57, 18, 14, 5.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(58, 19, 4, 160.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(59, 19, 10, 80.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(60, 19, 3, 170.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(61, 19, 7, 140.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(62, 19, 9, 100.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(63, 20, 4, 200.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(64, 20, 3, 150.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(65, 20, 10, 20.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(66, 20, 18, 120.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(67, 20, 6, 120.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(68, 21, 4, 150.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(69, 21, 3, 130.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(70, 21, 7, 110.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(71, 21, 8, 8.00, 2, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(72, 21, 14, 5.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(73, 22, 4, 200.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(74, 22, 3, 140.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(75, 22, 5, 110.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(76, 22, 8, 8.00, 2, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(77, 22, 14, 5.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(78, 23, 4, 200.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(79, 23, 3, 130.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(80, 23, 5, 120.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(81, 23, 8, 5.00, 2, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(82, 23, 14, 5.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(83, 24, 4, 200.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(84, 24, 3, 140.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(85, 24, 6, 110.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(86, 24, 8, 5.00, 2, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(87, 24, 14, 5.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(88, 25, 4, 200.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(89, 25, 3, 150.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(90, 25, 9, 100.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(91, 25, 10, 30.00, 4, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(92, 25, 8, 8.00, 2, '2026-05-01 23:33:00', '2026-05-01 23:33:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `idroles` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` tinyint DEFAULT NULL,
  PRIMARY KEY (`idroles`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`idroles`, `nombre`, `estado`) VALUES
(1, 'Administrador', 1),
(2, 'Cliente', 1),
(3, 'Repartidor', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `stock_productos`
--

DROP TABLE IF EXISTS `stock_productos`;
CREATE TABLE IF NOT EXISTS `stock_productos` (
  `idstock_productos` int NOT NULL AUTO_INCREMENT,
  `productos_idproductos` int NOT NULL,
  `tipo_stock` enum('CONGELADO','HECHO') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stock_actual` decimal(10,2) DEFAULT NULL,
  `stock_minimo` decimal(10,2) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`idstock_productos`),
  KEY `fk_stock_productos_productos1_idx` (`productos_idproductos`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `stock_productos`
--

INSERT INTO `stock_productos` (`idstock_productos`, `productos_idproductos`, `tipo_stock`, `stock_actual`, `stock_minimo`, `activo`, `created_at`, `updated_at`) VALUES
(1, 3, 'CONGELADO', 105.00, 7.00, 1, NULL, '2026-04-04 00:13:50'),
(2, 3, 'HECHO', 21.00, 5.00, 1, NULL, '2026-04-14 05:52:43'),
(3, 4, 'CONGELADO', 45.00, 10.00, 1, '2026-04-06 17:05:52', '2026-04-06 17:09:30'),
(4, 4, 'HECHO', 0.00, 10.00, 1, '2026-04-06 17:05:52', '2026-04-06 17:05:52'),
(5, 5, 'CONGELADO', 0.00, 3.00, 1, '2026-04-14 04:53:25', '2026-04-14 04:53:25'),
(6, 5, 'HECHO', 0.00, 0.00, 1, '2026-04-14 04:53:25', '2026-04-14 04:53:25'),
(7, 6, 'CONGELADO', 38.00, 10.00, 1, '2026-05-01 23:33:00', '2026-05-05 02:21:16'),
(8, 6, 'HECHO', 28.00, 6.00, 1, '2026-05-01 23:33:00', '2026-06-07 15:22:35'),
(9, 7, 'CONGELADO', 36.00, 10.00, 1, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(10, 7, 'HECHO', 12.00, 5.00, 1, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(11, 8, 'CONGELADO', 42.00, 10.00, 1, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(12, 8, 'HECHO', 15.00, 5.00, 1, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(13, 9, 'CONGELADO', 30.00, 10.00, 1, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(14, 9, 'HECHO', 10.00, 4.00, 1, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(15, 10, 'CONGELADO', 36.00, 10.00, 1, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(16, 10, 'HECHO', 14.00, 5.00, 1, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(17, 11, 'CONGELADO', 24.00, 10.00, 1, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(18, 11, 'HECHO', 8.00, 4.00, 1, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(19, 12, 'CONGELADO', 30.00, 10.00, 1, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(20, 12, 'HECHO', 10.00, 4.00, 1, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(21, 13, 'CONGELADO', 48.00, 10.00, 1, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(22, 13, 'HECHO', 20.00, 6.00, 1, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(23, 14, 'CONGELADO', 36.00, 10.00, 1, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(24, 14, 'HECHO', 12.00, 5.00, 1, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(25, 15, 'CONGELADO', 18.00, 10.00, 1, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(26, 15, 'HECHO', 6.00, 3.00, 1, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(27, 16, 'CONGELADO', 0.00, 0.00, 1, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(28, 16, 'HECHO', 5.00, 2.00, 1, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(29, 17, 'CONGELADO', 0.00, 0.00, 1, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(30, 17, 'HECHO', 5.00, 2.00, 1, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(31, 18, 'CONGELADO', 0.00, 0.00, 1, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(32, 18, 'HECHO', 5.00, 2.00, 1, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(33, 19, 'CONGELADO', 0.00, 0.00, 1, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(34, 19, 'HECHO', 5.00, 2.00, 1, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(35, 20, 'CONGELADO', 0.00, 0.00, 1, '2026-05-01 23:33:00', '2026-05-01 23:33:00'),
(36, 20, 'HECHO', 5.00, 2.00, 1, '2026-05-01 23:33:00', '2026-05-01 23:33:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `stock_productos_movimientos`
--

DROP TABLE IF EXISTS `stock_productos_movimientos`;
CREATE TABLE IF NOT EXISTS `stock_productos_movimientos` (
  `idstock_productos_movimientos` int NOT NULL AUTO_INCREMENT,
  `productos_idproductos` int NOT NULL,
  `produccion_idproduccion` int NOT NULL,
  `tipo_stock` enum('CONGELADO','HECHO') COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_movimiento` enum('ENTRADA','SALIDA') COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad` decimal(10,2) DEFAULT NULL,
  `stock_antes` decimal(10,2) DEFAULT NULL,
  `stock_despues` decimal(10,2) DEFAULT NULL,
  `motivo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `usuario_idusuario` int NOT NULL,
  PRIMARY KEY (`idstock_productos_movimientos`),
  KEY `fk_stock_productos_movimientos_productos1_idx` (`productos_idproductos`),
  KEY `fk_stock_productos_movimientos_produccion1_idx` (`produccion_idproduccion`),
  KEY `fk_stock_productos_movimientos_usuario1_idx` (`usuario_idusuario`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `stock_productos_movimientos`
--

INSERT INTO `stock_productos_movimientos` (`idstock_productos_movimientos`, `productos_idproductos`, `produccion_idproduccion`, `tipo_stock`, `tipo_movimiento`, `cantidad`, `stock_antes`, `stock_despues`, `motivo`, `fecha`, `usuario_idusuario`) VALUES
(1, 3, 3, 'CONGELADO', 'ENTRADA', 100.00, 0.00, 100.00, 'Producción', '2026-03-19 16:18:12', 1),
(2, 3, 8, 'CONGELADO', 'ENTRADA', 100.00, 5.00, 105.00, 'Producción', '2026-04-04 00:13:50', 1),
(3, 4, 9, 'CONGELADO', 'ENTRADA', 45.00, 0.00, 45.00, 'Producción', '2026-04-06 17:09:30', 1),
(4, 6, 0, 'CONGELADO', 'ENTRADA', 48.00, 0.00, 48.00, 'Carga inicial de stock', '2025-04-20 00:00:00', 1),
(5, 6, 0, 'HECHO', 'ENTRADA', 18.00, 0.00, 18.00, 'Carga inicial de stock', '2025-04-20 00:00:00', 1),
(6, 7, 0, 'CONGELADO', 'ENTRADA', 36.00, 0.00, 36.00, 'Carga inicial de stock', '2025-04-21 00:00:00', 1),
(7, 7, 0, 'HECHO', 'ENTRADA', 12.00, 0.00, 12.00, 'Carga inicial de stock', '2025-04-21 00:00:00', 1),
(8, 8, 0, 'CONGELADO', 'ENTRADA', 42.00, 0.00, 42.00, 'Carga inicial de stock', '2025-04-22 00:00:00', 1),
(9, 8, 0, 'HECHO', 'ENTRADA', 15.00, 0.00, 15.00, 'Carga inicial de stock', '2025-04-22 00:00:00', 1),
(10, 9, 0, 'CONGELADO', 'ENTRADA', 30.00, 0.00, 30.00, 'Carga inicial de stock', '2025-04-23 00:00:00', 1),
(11, 9, 0, 'HECHO', 'ENTRADA', 10.00, 0.00, 10.00, 'Carga inicial de stock', '2025-04-23 00:00:00', 1),
(12, 10, 0, 'CONGELADO', 'ENTRADA', 36.00, 0.00, 36.00, 'Carga inicial de stock', '2025-04-24 00:00:00', 1),
(13, 10, 0, 'HECHO', 'ENTRADA', 14.00, 0.00, 14.00, 'Carga inicial de stock', '2025-04-24 00:00:00', 1),
(14, 11, 0, 'CONGELADO', 'ENTRADA', 24.00, 0.00, 24.00, 'Carga inicial de stock', '2025-04-25 00:00:00', 1),
(15, 11, 0, 'HECHO', 'ENTRADA', 8.00, 0.00, 8.00, 'Carga inicial de stock', '2025-04-25 00:00:00', 1),
(16, 12, 0, 'CONGELADO', 'ENTRADA', 30.00, 0.00, 30.00, 'Carga inicial de stock', '2025-04-26 00:00:00', 1),
(17, 12, 0, 'HECHO', 'ENTRADA', 10.00, 0.00, 10.00, 'Carga inicial de stock', '2025-04-26 00:00:00', 1),
(18, 13, 0, 'CONGELADO', 'ENTRADA', 48.00, 0.00, 48.00, 'Carga inicial de stock', '2025-04-27 00:00:00', 1),
(19, 13, 0, 'HECHO', 'ENTRADA', 20.00, 0.00, 20.00, 'Carga inicial de stock', '2025-04-27 00:00:00', 1),
(20, 14, 0, 'CONGELADO', 'ENTRADA', 36.00, 0.00, 36.00, 'Carga inicial de stock', '2025-04-28 00:00:00', 1),
(21, 14, 0, 'HECHO', 'ENTRADA', 12.00, 0.00, 12.00, 'Carga inicial de stock', '2025-04-28 00:00:00', 1),
(22, 15, 0, 'CONGELADO', 'ENTRADA', 18.00, 0.00, 18.00, 'Carga inicial de stock', '2025-04-29 00:00:00', 1),
(23, 15, 0, 'HECHO', 'ENTRADA', 6.00, 0.00, 6.00, 'Carga inicial de stock', '2025-04-29 00:00:00', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sucursal`
--

DROP TABLE IF EXISTS `sucursal`;
CREATE TABLE IF NOT EXISTS `sucursal` (
  `idsucursal` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `provincia` varchar(100) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `latitud` decimal(10,8) DEFAULT NULL,
  `longitud` decimal(11,8) DEFAULT NULL,
  PRIMARY KEY (`idsucursal`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `sucursal`
--

INSERT INTO `sucursal` (`idsucursal`, `nombre`, `direccion`, `ciudad`, `provincia`, `telefono`, `email`, `activo`, `created_at`, `latitud`, `longitud`) VALUES
(3, 'Prueba nueva', 'Ruta Nacional 12, Martín Fierro, Municipio de Garupá, Departamento Capital, Provincia de Misiones, 3304, Argentina', 'Municipio de Garupá', 'Provincia de Misiones', '37654', 'lucianogastonbarros@gmail.com', 0, '2026-04-04 22:56:12', -27.45049006, -55.86734528),
(4, 'SEDE posadas', 'Noruega 2354', 'Municipio de Posadas', 'Provincia de Misiones', '3764558877', NULL, 1, '2026-04-06 20:55:04', -27.39506000, -55.90317970),
(5, 'Sucursal Centro', 'San Martín 456, Centro', 'Posadas', 'Misiones', '3764100100', 'centro@canetto.com', 0, '2026-05-02 02:33:00', -27.37100000, -55.89800000),
(6, 'Sucursal Norte', 'Av. Quaranta 2100, Km 5', 'Posadas', 'Misiones', '3764200200', 'norte@canetto.com', 0, '2026-05-02 02:33:00', -27.34500000, -55.91200000),
(7, 'Sucursal Villa Sarita', 'Calle 9 de Julio 780, Villa Sarita', 'Posadas', 'Misiones', '3764300300', 'sarita@canetto.com', 1, '2026-05-02 02:33:00', -27.39200000, -55.88100000);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tarifas_envio`
--

DROP TABLE IF EXISTS `tarifas_envio`;
CREATE TABLE IF NOT EXISTS `tarifas_envio` (
  `id` int NOT NULL AUTO_INCREMENT,
  `km_desde` decimal(5,1) NOT NULL DEFAULT '0.0',
  `km_hasta` decimal(5,1) NOT NULL DEFAULT '5.0',
  `precio` decimal(10,2) NOT NULL DEFAULT '0.00',
  `descripcion` varchar(100) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `tarifas_envio`
--

INSERT INTO `tarifas_envio` (`id`, `km_desde`, `km_hasta`, `precio`, `descripcion`, `activo`, `updated_at`) VALUES
(1, 0.0, 3.0, 2000.00, 'Zona cercana (0–3 km)', 1, '2026-05-15 20:33:43'),
(2, 3.0, 6.0, 2500.00, 'Zona media (3–6 km)', 1, '2026-05-15 20:33:43'),
(3, 6.0, 10.0, 3000.00, 'Zona media-lejana (6–10 km)', 1, '2026-05-15 20:33:43'),
(7, 11.0, 15.0, 3500.00, 'Zona Lejana', 1, '2026-05-15 20:33:43');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_panel`
--

DROP TABLE IF EXISTS `tipos_panel`;
CREATE TABLE IF NOT EXISTS `tipos_panel` (
  `id` int NOT NULL AUTO_INCREMENT,
  `clave` varchar(40) NOT NULL,
  `label` varchar(60) NOT NULL,
  `emoji` varchar(8) NOT NULL DEFAULT '?',
  `color` varchar(20) NOT NULL DEFAULT '#888888',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `sistema` tinyint(1) NOT NULL DEFAULT '0',
  `orden` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `clave` (`clave`)
) ENGINE=InnoDB AUTO_INCREMENT=97 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `tipos_panel`
--

INSERT INTO `tipos_panel` (`id`, `clave`, `label`, `emoji`, `color`, `activo`, `sistema`, `orden`) VALUES
(1, 'promo', 'Promo', '📢', '#c88e99', 0, 1, 0),
(2, 'bienvenida', 'Bienvenida', '👋', '#1d9e75', 0, 1, 1),
(3, 'regalo', 'Regalo', '🎁', '#7c3aed', 1, 1, 2),
(4, 'soporte', 'Soporte', '🛟', '#0891b2', 1, 1, 3),
(5, 'temporada', 'Temporada', '🌸', '#f59e0b', 1, 1, 4),
(6, 'descuento', 'Descuento', '💸', '#dc2626', 1, 1, 5),
(13, 'novedad', 'Novedad', '✨', '#8b5cf6', 1, 1, 6),
(14, 'anuncio', 'Anuncio', '📣', '#0ea5e9', 1, 1, 7),
(15, 'informativo', 'Informativo', 'ℹ️', '#64748b', 1, 1, 8),
(16, 'marketing', 'Marketing', '🚀', '#f97316', 1, 1, 9);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `toppings`
--

DROP TABLE IF EXISTS `toppings`;
CREATE TABLE IF NOT EXISTS `toppings` (
  `idtoppings` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `precio` decimal(10,2) NOT NULL DEFAULT '0.00',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idtoppings`)
) ENGINE=MyISAM AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `toppings`
--

INSERT INTO `toppings` (`idtoppings`, `nombre`, `precio`, `activo`, `created_at`) VALUES
(1, 'Salsa', 1300.00, 1, '2026-04-22 22:10:58'),
(2, 'Salsa de caramelo artesanal', 1200.00, 1, '2026-05-01 23:33:00'),
(3, 'Azúcar glass', 500.00, 1, '2026-05-01 23:33:00'),
(4, 'Chispas de colores', 700.00, 1, '2026-05-01 23:33:00'),
(5, 'Chips de chocolate extra', 900.00, 1, '2026-05-01 23:33:00'),
(6, 'Maní triturado', 800.00, 1, '2026-05-01 23:33:00'),
(7, 'Coco rallado tostado', 600.00, 1, '2026-05-01 23:33:00'),
(8, 'Salsa de dulce de leche', 1400.00, 1, '2026-05-01 23:33:00'),
(9, 'Oreo triturada', 1100.00, 1, '2026-05-01 23:33:00'),
(10, 'Crema chantilly', 1500.00, 1, '2026-05-01 23:33:00'),
(11, 'Almendras laminadas', 1300.00, 1, '2026-05-01 23:33:00'),
(12, 'Granola artesanal', 900.00, 1, '2026-05-01 23:33:00'),
(13, 'Sal marina en escamas', 500.00, 1, '2026-05-01 23:33:00'),
(14, 'Frutos rojos mixtos', 1600.00, 1, '2026-05-01 23:33:00'),
(15, 'Salsa de frutilla casera', 1200.00, 1, '2026-05-01 23:33:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `toppings_stock`
--

DROP TABLE IF EXISTS `toppings_stock`;
CREATE TABLE IF NOT EXISTS `toppings_stock` (
  `idtoppings_stock` int NOT NULL AUTO_INCREMENT,
  `toppings_idtoppings` int NOT NULL,
  `stock_actual` decimal(10,2) NOT NULL DEFAULT '0.00',
  `stock_minimo` decimal(10,2) NOT NULL DEFAULT '0.00',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`idtoppings_stock`),
  UNIQUE KEY `uq_tp` (`toppings_idtoppings`)
) ENGINE=MyISAM AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `toppings_stock`
--

INSERT INTO `toppings_stock` (`idtoppings_stock`, `toppings_idtoppings`, `stock_actual`, `stock_minimo`, `updated_at`) VALUES
(1, 11, 20.00, 5.00, '2026-05-10 11:54:11'),
(2, 3, 20.00, 5.00, '2026-05-10 11:54:11'),
(3, 5, 20.00, 5.00, '2026-05-10 11:54:11'),
(4, 4, 20.00, 5.00, '2026-05-10 11:54:11'),
(5, 7, 20.00, 5.00, '2026-05-10 11:54:11'),
(6, 10, 20.00, 5.00, '2026-05-10 11:54:11'),
(7, 14, 3.00, 5.00, '2026-05-10 11:54:11'),
(8, 12, 3.00, 5.00, '2026-05-10 11:54:11'),
(9, 6, 3.00, 5.00, '2026-05-10 11:54:11'),
(10, 9, 3.00, 5.00, '2026-05-10 11:54:11'),
(11, 13, 0.00, 5.00, '2026-05-10 11:54:11'),
(12, 1, 0.00, 5.00, '2026-05-10 11:54:11'),
(13, 2, 0.00, 5.00, '2026-05-10 11:54:11'),
(14, 8, 0.00, 5.00, '2026-05-10 11:54:11'),
(15, 15, 0.00, 5.00, '2026-05-10 11:54:11');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `unidad_medida`
--

DROP TABLE IF EXISTS `unidad_medida`;
CREATE TABLE IF NOT EXISTS `unidad_medida` (
  `idunidad_medida` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `abreviatura` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`idunidad_medida`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `unidad_medida`
--

INSERT INTO `unidad_medida` (`idunidad_medida`, `nombre`, `abreviatura`) VALUES
(1, 'Litros', 'L'),
(2, 'MiliLitros', 'ml'),
(3, 'Unidades', 'U'),
(4, 'Gramos', 'G'),
(5, 'KiloGramos', 'Kg'),
(6, 'Centímetros', 'cm'),
(7, 'Tazas', 'tz');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

DROP TABLE IF EXISTS `usuario`;
CREATE TABLE IF NOT EXISTS `usuario` (
  `idusuario` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `apellido` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dni` int DEFAULT NULL,
  `celular` bigint DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usuario` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `ubicacion_lat` decimal(10,8) DEFAULT NULL,
  `ubicacion_lng` decimal(11,8) DEFAULT NULL,
  `ubicacion_at` datetime DEFAULT NULL,
  `avatar_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`idusuario`),
  UNIQUE KEY `email_UNIQUE` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`idusuario`, `nombre`, `apellido`, `dni`, `celular`, `email`, `usuario`, `password_hash`, `avatar`, `activo`, `created_at`, `updated_at`, `ubicacion_lat`, `ubicacion_lng`, `ubicacion_at`, `avatar_path`) VALUES
(1, 'Luciano', NULL, 444, 3764820012, NULL, 'root', '$2y$10$mmKljorb6ByXGugZqrpbc.EHvrvlplJiCJiIFcvgQUirdWt6CtLXy', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 'Juancarlo', 'Chupapija', 1234, 123, NULL, '123', '$2y$10$hulOoCqn4UtTlsZE9y4IU.kLq0X49.2oNvwuIGocO9nJ2c.bQy8Zm', NULL, 1, '2026-04-04 07:50:41', '2026-04-26 18:08:40', NULL, NULL, NULL, NULL),
(8, 'Juany', 'garcia', 11111, 3764820012, 'lucianogastonbarros@gmail.com', 'juany', '$2y$10$m8dD4v.8qYp3dYCZOYBeJeukl4v3SxtdFa14oXTk4Jyp.hfi8LV4e', 'https://lh3.googleusercontent.com/a/ACg8ocKkyo0H2BqxASwQ3lkQJc33cEQiC_FtmGsnTsFXNci091lJJqCk=s96-c', 1, '2026-04-11 20:00:06', '2026-04-14 04:27:04', NULL, NULL, NULL, NULL),
(9, 'Juany Hilbert', NULL, NULL, NULL, 'juanyhilbert710@gmail.com', 'juanyhilbert710@gmail.com', '', 'https://lh3.googleusercontent.com/a/ACg8ocKILQnFTisxrdtudcEHqy9Rcdsug57OQ-Bsk0ykSsUYlPcVT2I=s96-c', 1, '2026-04-17 20:52:45', '2026-04-17 20:52:45', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios_roles`
--

DROP TABLE IF EXISTS `usuarios_roles`;
CREATE TABLE IF NOT EXISTS `usuarios_roles` (
  `idusuarios_roles` int NOT NULL AUTO_INCREMENT,
  `usuario_idusuario` int NOT NULL,
  `roles_idroles` int NOT NULL,
  PRIMARY KEY (`idusuarios_roles`),
  KEY `fk_usuarios_roles_usuario_idx` (`usuario_idusuario`),
  KEY `fk_usuarios_roles_roles1_idx` (`roles_idroles`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios_roles`
--

INSERT INTO `usuarios_roles` (`idusuarios_roles`, `usuario_idusuario`, `roles_idroles`) VALUES
(16, 8, 1),
(17, 8, 2),
(21, 9, 1),
(22, 9, 2),
(23, 9, 3),
(24, 4, 2),
(27, 1, 1),
(28, 1, 2),
(29, 1, 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario_auth`
--

DROP TABLE IF EXISTS `usuario_auth`;
CREATE TABLE IF NOT EXISTS `usuario_auth` (
  `idusuario_auth` int NOT NULL AUTO_INCREMENT,
  `usuario_idusuario` int NOT NULL,
  `provider` enum('local','google','facebook','apple') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`idusuario_auth`),
  KEY `fk_usuario_auth_usuario1_idx` (`usuario_idusuario`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuario_auth`
--

INSERT INTO `usuario_auth` (`idusuario_auth`, `usuario_idusuario`, `provider`, `provider_id`, `created_at`) VALUES
(1, 8, 'google', '104518905677678996752', '2026-04-13 05:02:26'),
(2, 8, 'google', '104518905677678996752', '2026-04-13 20:18:16'),
(3, 8, 'google', '104518905677678996752', '2026-04-17 19:15:14'),
(4, 8, 'google', '104518905677678996752', '2026-04-17 19:15:42'),
(5, 8, 'google', '104518905677678996752', '2026-04-17 19:16:20'),
(6, 8, 'google', '104518905677678996752', '2026-04-17 19:17:27'),
(7, 8, 'google', '104518905677678996752', '2026-04-17 19:18:30'),
(8, 8, 'google', '104518905677678996752', '2026-04-17 19:20:51'),
(9, 9, 'google', '108952279679444678319', '2026-04-17 20:52:45'),
(10, 9, 'google', '108952279679444678319', '2026-04-17 20:55:02'),
(11, 8, 'google', '104518905677678996752', '2026-04-18 11:12:22');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

DROP TABLE IF EXISTS `ventas`;
CREATE TABLE IF NOT EXISTS `ventas` (
  `idventas` int NOT NULL AUTO_INCREMENT,
  `usuario_idusuario` int NOT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `estado_venta_idestado_venta` int NOT NULL,
  `metodo_pago_idmetodo_pago` int NOT NULL,
  `fecha` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `origen` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pos',
  `sucursal_retiro_idsucursal` int DEFAULT NULL,
  `observacion_cliente` text COLLATE utf8mb4_unicode_ci,
  `tipo_entrega` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'retiro',
  `repartidor_idusuario` int DEFAULT NULL,
  `direccion_entrega` text COLLATE utf8mb4_unicode_ci,
  `lat_entrega` decimal(10,8) DEFAULT NULL,
  `lng_entrega` decimal(11,8) DEFAULT NULL,
  `via_uber` tinyint(1) NOT NULL DEFAULT '0',
  `toppings_json` text COLLATE utf8mb4_unicode_ci,
  `costo_envio` decimal(10,2) NOT NULL DEFAULT '0.00',
  `propina` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tarifa_servicio` decimal(10,2) NOT NULL DEFAULT '0.00',
  `repartidor_pendiente_idusuario` int DEFAULT NULL,
  `uber_link` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cancelacion_solicitada` tinyint(1) NOT NULL DEFAULT '0',
  `cancelacion_motivo` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cancelacion_detalle` text COLLATE utf8mb4_unicode_ci,
  `cancelacion_solicitada_at` datetime DEFAULT NULL,
  `cupon_codigo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descuento_cupon` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`idventas`),
  KEY `fk_ventas_usuario1_idx` (`usuario_idusuario`),
  KEY `fk_ventas_estado_venta1_idx` (`estado_venta_idestado_venta`),
  KEY `fk_ventas_metodo_pago1_idx` (`metodo_pago_idmetodo_pago`)
) ENGINE=InnoDB AUTO_INCREMENT=139 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `ventas`
--

INSERT INTO `ventas` (`idventas`, `usuario_idusuario`, `total`, `estado_venta_idestado_venta`, `metodo_pago_idmetodo_pago`, `fecha`, `created_at`, `updated_at`, `origen`, `sucursal_retiro_idsucursal`, `observacion_cliente`, `tipo_entrega`, `repartidor_idusuario`, `direccion_entrega`, `lat_entrega`, `lng_entrega`, `via_uber`, `toppings_json`, `costo_envio`, `propina`, `tarifa_servicio`, `repartidor_pendiente_idusuario`, `uber_link`, `cancelacion_solicitada`, `cancelacion_motivo`, `cancelacion_detalle`, `cancelacion_solicitada_at`, `cupon_codigo`, `descuento_cupon`) VALUES
(38, 1, 4800.00, 4, 1, '2026-01-03 10:15:00', '2026-01-03 10:15:00', '2026-01-03 10:45:00', 'pos', NULL, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(39, 1, 3200.00, 4, 2, '2026-01-04 11:30:00', '2026-01-04 11:30:00', '2026-01-04 12:00:00', 'tienda', NULL, NULL, 'envio', NULL, NULL, NULL, NULL, 0, NULL, 800.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(40, 1, 5600.00, 4, 1, '2026-01-06 09:20:00', '2026-01-06 09:20:00', '2026-01-06 09:50:00', 'pos', NULL, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(41, 1, 2400.00, 4, 3, '2026-01-07 14:00:00', '2026-01-07 14:00:00', '2026-01-07 14:30:00', 'tienda', NULL, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(42, 1, 6800.00, 4, 2, '2026-01-10 10:00:00', '2026-01-10 10:00:00', '2026-01-10 10:30:00', 'tienda', NULL, NULL, 'envio', NULL, NULL, NULL, NULL, 0, NULL, 800.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(43, 1, 3600.00, 4, 1, '2026-01-11 11:45:00', '2026-01-11 11:45:00', '2026-01-11 12:15:00', 'pos', NULL, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(44, 1, 4200.00, 4, 2, '2026-01-13 16:30:00', '2026-01-13 16:30:00', '2026-01-13 17:00:00', 'tienda', NULL, NULL, 'envio', NULL, NULL, NULL, NULL, 0, NULL, 800.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(45, 1, 5100.00, 4, 1, '2026-01-14 12:00:00', '2026-01-14 12:00:00', '2026-01-14 12:30:00', 'pos', NULL, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(46, 1, 7200.00, 4, 2, '2026-01-17 10:20:00', '2026-01-17 10:20:00', '2026-01-17 10:50:00', 'tienda', NULL, NULL, 'envio', NULL, NULL, NULL, NULL, 0, NULL, 800.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(47, 1, 2800.00, 4, 3, '2026-01-18 09:15:00', '2026-01-18 09:15:00', '2026-01-18 09:45:00', 'pos', NULL, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(48, 1, 4400.00, 4, 1, '2026-01-20 15:00:00', '2026-01-20 15:00:00', '2026-01-20 15:30:00', 'tienda', NULL, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(49, 1, 5900.00, 4, 2, '2026-01-21 11:10:00', '2026-01-21 11:10:00', '2026-01-21 11:40:00', 'tienda', NULL, NULL, 'envio', NULL, NULL, NULL, NULL, 0, NULL, 800.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(50, 1, 3300.00, 4, 1, '2026-01-24 14:30:00', '2026-01-24 14:30:00', '2026-01-24 15:00:00', 'pos', NULL, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(51, 1, 6100.00, 4, 2, '2026-01-25 10:45:00', '2026-01-25 10:45:00', '2026-01-25 11:15:00', 'tienda', NULL, NULL, 'envio', NULL, NULL, NULL, NULL, 0, NULL, 800.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(52, 1, 4700.00, 4, 3, '2026-01-27 12:20:00', '2026-01-27 12:20:00', '2026-01-27 12:50:00', 'pos', NULL, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(53, 1, 5500.00, 4, 1, '2026-01-28 09:30:00', '2026-01-28 09:30:00', '2026-01-28 10:00:00', 'tienda', NULL, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(54, 1, 8200.00, 4, 2, '2026-01-31 11:00:00', '2026-01-31 11:00:00', '2026-01-31 11:30:00', 'tienda', NULL, NULL, 'envio', NULL, NULL, NULL, NULL, 0, NULL, 800.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(55, 1, 3800.00, 4, 1, '2026-02-02 10:15:00', '2026-02-02 10:15:00', '2026-02-02 10:45:00', 'pos', NULL, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(56, 1, 5200.00, 4, 2, '2026-02-03 11:30:00', '2026-02-03 11:30:00', '2026-02-03 12:00:00', 'tienda', NULL, NULL, 'envio', NULL, NULL, NULL, NULL, 0, NULL, 800.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(57, 1, 4100.00, 4, 1, '2026-02-05 09:45:00', '2026-02-05 09:45:00', '2026-02-05 10:15:00', 'pos', NULL, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(58, 1, 6300.00, 4, 2, '2026-02-07 14:00:00', '2026-02-07 14:00:00', '2026-02-07 14:30:00', 'tienda', NULL, NULL, 'envio', NULL, NULL, NULL, NULL, 0, NULL, 800.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(59, 1, 2900.00, 4, 3, '2026-02-10 10:00:00', '2026-02-10 10:00:00', '2026-02-10 10:30:00', 'pos', NULL, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(60, 1, 7400.00, 4, 1, '2026-02-11 11:15:00', '2026-02-11 11:15:00', '2026-02-11 11:45:00', 'tienda', NULL, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(61, 1, 4600.00, 4, 2, '2026-02-13 16:00:00', '2026-02-13 16:00:00', '2026-02-13 16:30:00', 'tienda', NULL, NULL, 'envio', NULL, NULL, NULL, NULL, 0, NULL, 800.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(62, 1, 5800.00, 4, 1, '2026-02-14 12:30:00', '2026-02-14 12:30:00', '2026-02-14 13:00:00', 'pos', NULL, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(63, 1, 9100.00, 4, 2, '2026-02-14 13:00:00', '2026-02-14 13:00:00', '2026-02-14 13:30:00', 'tienda', NULL, NULL, 'envio', NULL, NULL, NULL, NULL, 0, NULL, 800.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(64, 1, 3500.00, 4, 3, '2026-02-17 10:45:00', '2026-02-17 10:45:00', '2026-02-17 11:15:00', 'pos', NULL, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(65, 1, 5000.00, 4, 1, '2026-02-18 09:20:00', '2026-02-18 09:20:00', '2026-02-18 09:50:00', 'tienda', NULL, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(66, 1, 4300.00, 4, 2, '2026-02-21 15:15:00', '2026-02-21 15:15:00', '2026-02-21 15:45:00', 'tienda', NULL, NULL, 'envio', NULL, NULL, NULL, NULL, 0, NULL, 800.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(67, 1, 6700.00, 4, 1, '2026-02-24 11:00:00', '2026-02-24 11:00:00', '2026-02-24 11:30:00', 'pos', NULL, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(68, 1, 3100.00, 4, 2, '2026-02-25 10:30:00', '2026-02-25 10:30:00', '2026-02-25 11:00:00', 'tienda', NULL, NULL, 'envio', NULL, NULL, NULL, NULL, 0, NULL, 800.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(69, 1, 5400.00, 4, 1, '2026-02-28 12:00:00', '2026-02-28 12:00:00', '2026-02-28 12:30:00', 'pos', NULL, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(70, 1, 4900.00, 4, 2, '2026-03-02 10:00:00', '2026-03-02 10:00:00', '2026-03-02 10:30:00', 'tienda', NULL, NULL, 'envio', NULL, NULL, NULL, NULL, 0, NULL, 800.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(71, 1, 3700.00, 4, 1, '2026-03-03 11:30:00', '2026-03-03 11:30:00', '2026-03-03 12:00:00', 'pos', NULL, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(72, 1, 5300.00, 4, 3, '2026-03-05 09:15:00', '2026-03-05 09:15:00', '2026-03-05 09:45:00', 'tienda', NULL, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(73, 1, 7800.00, 4, 2, '2026-03-07 14:30:00', '2026-03-07 14:30:00', '2026-03-07 15:00:00', 'tienda', NULL, NULL, 'envio', NULL, NULL, NULL, NULL, 0, NULL, 800.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(74, 1, 4000.00, 4, 1, '2026-03-10 10:15:00', '2026-03-10 10:15:00', '2026-03-10 10:45:00', 'pos', NULL, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(75, 1, 6500.00, 4, 2, '2026-03-11 11:00:00', '2026-03-11 11:00:00', '2026-03-11 11:30:00', 'tienda', NULL, NULL, 'envio', NULL, NULL, NULL, NULL, 0, NULL, 800.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(76, 1, 3200.00, 4, 1, '2026-03-13 15:45:00', '2026-03-13 15:45:00', '2026-03-13 16:15:00', 'pos', NULL, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(77, 1, 5700.00, 4, 2, '2026-03-14 12:00:00', '2026-03-14 12:00:00', '2026-03-14 12:30:00', 'tienda', NULL, NULL, 'envio', NULL, NULL, NULL, NULL, 0, NULL, 800.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(78, 1, 4800.00, 4, 3, '2026-03-17 10:30:00', '2026-03-17 10:30:00', '2026-03-17 11:00:00', 'pos', NULL, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(79, 1, 8500.00, 4, 1, '2026-03-18 09:45:00', '2026-03-18 09:45:00', '2026-03-18 10:15:00', 'tienda', NULL, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(80, 1, 3900.00, 4, 2, '2026-03-21 14:00:00', '2026-03-21 14:00:00', '2026-03-21 14:30:00', 'tienda', NULL, NULL, 'envio', NULL, NULL, NULL, NULL, 0, NULL, 800.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(81, 1, 6200.00, 4, 1, '2026-03-24 11:15:00', '2026-03-24 11:15:00', '2026-03-24 11:45:00', 'pos', NULL, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(82, 1, 4500.00, 4, 2, '2026-03-25 10:00:00', '2026-03-25 10:00:00', '2026-03-25 10:30:00', 'tienda', NULL, NULL, 'envio', NULL, NULL, NULL, NULL, 0, NULL, 800.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(83, 1, 7100.00, 4, 1, '2026-03-27 12:30:00', '2026-03-27 12:30:00', '2026-03-27 13:00:00', 'pos', NULL, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(84, 1, 5600.00, 4, 3, '2026-03-28 09:30:00', '2026-03-28 09:30:00', '2026-03-28 10:00:00', 'tienda', NULL, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(85, 1, 9300.00, 4, 2, '2026-03-31 11:45:00', '2026-03-31 11:45:00', '2026-03-31 12:15:00', 'tienda', NULL, NULL, 'envio', NULL, NULL, NULL, NULL, 0, NULL, 800.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(86, 1, 4200.00, 4, 1, '2026-04-01 10:00:00', '2026-04-01 10:00:00', '2026-04-01 10:30:00', 'pos', NULL, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(87, 1, 6800.00, 4, 2, '2026-04-02 11:30:00', '2026-04-02 11:30:00', '2026-04-02 12:00:00', 'tienda', NULL, NULL, 'envio', NULL, NULL, NULL, NULL, 0, NULL, 800.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(88, 1, 3600.00, 4, 1, '2026-04-04 09:15:00', '2026-04-04 09:15:00', '2026-04-04 09:45:00', 'pos', NULL, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(89, 1, 7500.00, 4, 2, '2026-04-05 14:00:00', '2026-04-05 14:00:00', '2026-04-05 14:30:00', 'tienda', NULL, NULL, 'envio', NULL, NULL, NULL, NULL, 0, NULL, 800.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(90, 1, 5100.00, 4, 3, '2026-04-07 10:30:00', '2026-04-07 10:30:00', '2026-04-07 11:00:00', 'pos', NULL, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(91, 1, 8900.00, 4, 1, '2026-04-08 11:00:00', '2026-04-08 11:00:00', '2026-04-08 11:30:00', 'tienda', NULL, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(92, 1, 4700.00, 4, 2, '2026-04-11 15:30:00', '2026-04-11 15:30:00', '2026-04-11 16:00:00', 'tienda', NULL, NULL, 'envio', NULL, NULL, NULL, NULL, 0, NULL, 800.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(93, 1, 6300.00, 4, 1, '2026-04-12 12:00:00', '2026-04-12 12:00:00', '2026-04-12 12:30:00', 'pos', NULL, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(94, 1, 3400.00, 4, 2, '2026-04-14 10:45:00', '2026-04-14 10:45:00', '2026-04-14 11:15:00', 'tienda', NULL, NULL, 'envio', NULL, NULL, NULL, NULL, 0, NULL, 800.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(95, 1, 5800.00, 4, 1, '2026-04-15 09:30:00', '2026-04-15 09:30:00', '2026-04-15 10:00:00', 'pos', NULL, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(96, 1, 7200.00, 4, 3, '2026-04-17 14:15:00', '2026-04-17 14:15:00', '2026-04-17 14:45:00', 'tienda', NULL, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(97, 1, 4400.00, 4, 2, '2026-04-18 11:00:00', '2026-04-18 11:00:00', '2026-04-18 11:30:00', 'pos', NULL, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(98, 1, 6600.00, 4, 1, '2026-04-21 10:00:00', '2026-04-21 10:00:00', '2026-04-21 10:30:00', 'tienda', NULL, NULL, 'envio', NULL, NULL, NULL, NULL, 0, NULL, 800.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(99, 1, 5000.00, 4, 2, '2026-04-22 11:45:00', '2026-04-22 11:45:00', '2026-04-22 12:15:00', 'tienda', NULL, NULL, 'envio', NULL, NULL, NULL, NULL, 0, NULL, 800.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(100, 1, 8100.00, 4, 1, '2026-04-24 09:00:00', '2026-04-24 09:00:00', '2026-04-24 09:30:00', 'pos', NULL, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(101, 1, 4100.00, 4, 2, '2026-04-25 13:00:00', '2026-04-25 13:00:00', '2026-04-25 13:30:00', 'tienda', NULL, NULL, 'envio', NULL, NULL, NULL, NULL, 0, NULL, 800.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(102, 1, 7700.00, 4, 3, '2026-04-28 10:30:00', '2026-04-28 10:30:00', '2026-04-28 11:00:00', 'tienda', NULL, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(103, 1, 5300.00, 4, 1, '2026-04-29 11:15:00', '2026-04-29 11:15:00', '2026-04-29 11:45:00', 'pos', NULL, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(104, 1, 9500.00, 4, 2, '2026-04-30 12:00:00', '2026-04-30 12:00:00', '2026-04-30 12:30:00', 'tienda', NULL, NULL, 'envio', NULL, NULL, NULL, NULL, 0, NULL, 800.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(105, 1, 5400.00, 4, 1, '2026-05-01 10:00:00', '2026-05-01 10:00:00', '2026-05-01 10:30:00', 'pos', NULL, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(106, 1, 7300.00, 4, 2, '2026-05-02 11:30:00', '2026-05-02 11:30:00', '2026-05-02 12:00:00', 'tienda', NULL, NULL, 'envio', NULL, NULL, NULL, NULL, 0, NULL, 800.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(107, 1, 4600.00, 4, 1, '2026-05-03 09:45:00', '2026-05-03 09:45:00', '2026-05-03 10:15:00', 'pos', NULL, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(108, 1, 6100.00, 4, 2, '2026-05-05 10:00:00', '2026-05-05 10:00:00', '2026-05-05 10:30:00', 'tienda', NULL, NULL, 'envio', NULL, NULL, NULL, NULL, 0, NULL, 800.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(111, 1, 13800.00, 4, 1, '2026-05-05 22:32:06', '2026-05-05 22:32:06', '2026-05-15 20:38:37', 'tienda', NULL, NULL, 'envio', 1, 'Ruiz de Montoya, Centro de Integración Territorial Riberas del Paraná, Municipio de Posadas, Provincia de Misiones', -27.36037850, -55.91367110, 0, '[{\"id\":11,\"nombre\":\"Almendras laminadas\",\"precio\":1300}]', 4500.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(112, 1, 11500.00, 1, 1, '2026-05-07 21:50:32', '2026-05-07 21:50:32', '2026-05-07 21:50:32', 'tienda', NULL, NULL, 'envio', NULL, 'Los Cardenales 6941, Municipio de Posadas, Provincia de Misiones', -27.41326102, -55.98421991, 0, '[{\"id\":11,\"nombre\":\"Almendras laminadas\",\"precio\":1300}]', 3000.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(113, 1, 9700.00, 1, 1, '2026-05-07 22:27:19', '2026-05-07 22:27:19', '2026-05-07 22:27:19', 'tienda', NULL, NULL, 'envio', NULL, 'Padre Serrano 2745, Municipio de Posadas, Provincia de Misiones', -27.37378188, -55.91553912, 0, '[{\"id\":3,\"nombre\":\"Azúcar glass\",\"precio\":500}]', 2000.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(114, 1, 9700.00, 1, 1, '2026-05-07 22:31:39', '2026-05-07 22:31:39', '2026-05-07 22:31:39', 'tienda', NULL, NULL, 'envio', NULL, 'Padre Serrano 2745, Municipio de Posadas, Provincia de Misiones', -27.37378188, -55.91553912, 0, '[{\"id\":3,\"nombre\":\"Azúcar glass\",\"precio\":500}]', 2000.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(115, 1, 8500.00, 1, 1, '2026-05-07 22:32:53', '2026-05-07 22:32:53', '2026-05-07 22:32:53', 'tienda', 5, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, '[{\"id\":3,\"nombre\":\"Azúcar glass\",\"precio\":500}]', 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(116, 1, 22300.00, 1, 1, '2026-05-07 22:41:28', '2026-05-07 22:41:28', '2026-05-07 22:41:28', 'tienda', NULL, NULL, 'envio', NULL, 'Padre Serrano 2745, Municipio de Posadas, Provincia de Misiones', -27.37378188, -55.91553912, 0, '[{\"id\":3,\"nombre\":\"Azúcar glass\",\"precio\":500}]', 2000.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(117, 4, 9600.00, 1, 1, '2026-05-08 21:09:06', '2026-05-08 21:09:06', '2026-05-08 21:09:06', 'tienda', NULL, NULL, 'envio', NULL, 'Gómez Portiño 3956, Municipio de Posadas, Provincia de Misiones', -27.38783334, -55.90856552, 0, NULL, 2000.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00),
(118, 4, 7700.00, 3, 1, '2026-05-08 21:12:19', '2026-05-08 21:12:19', '2026-05-16 22:37:05', 'tienda', 5, NULL, 'retiro', NULL, NULL, NULL, NULL, 0, '[{\"id\":3,\"nombre\":\"Azúcar glass\",\"precio\":500}]', 0.00, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `verificacion_token`
--

DROP TABLE IF EXISTS `verificacion_token`;
CREATE TABLE IF NOT EXISTS `verificacion_token` (
  `id` int NOT NULL AUTO_INCREMENT,
  `token` varchar(64) NOT NULL,
  `usuario_idusuario` int NOT NULL,
  `datos_nuevos` text NOT NULL,
  `tipo` varchar(30) DEFAULT 'merge_dni',
  `expira` datetime NOT NULL,
  `usado` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `box_productos`
--
ALTER TABLE `box_productos`
  ADD CONSTRAINT `box_productos_ibfk_1` FOREIGN KEY (`producto_box`) REFERENCES `productos` (`idproductos`),
  ADD CONSTRAINT `box_productos_ibfk_2` FOREIGN KEY (`producto_item`) REFERENCES `productos` (`idproductos`);

--
-- Filtros para la tabla `compra_materia_prima`
--
ALTER TABLE `compra_materia_prima`
  ADD CONSTRAINT `compra_materia_prima_ibfk_1` FOREIGN KEY (`proveedor_idproveedor`) REFERENCES `proveedor` (`idproveedor`) ON UPDATE CASCADE,
  ADD CONSTRAINT `compra_materia_prima_ibfk_2` FOREIGN KEY (`materia_prima_idmateria_prima`) REFERENCES `materia_prima` (`idmateria_prima`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_compra_cancelado_por` FOREIGN KEY (`cancelado_por`) REFERENCES `usuario` (`idusuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_compra_materia` FOREIGN KEY (`materia_prima_idmateria_prima`) REFERENCES `materia_prima` (`idmateria_prima`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_compra_proveedor` FOREIGN KEY (`proveedor_idproveedor`) REFERENCES `proveedor` (`idproveedor`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_compra_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`idusuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  ADD CONSTRAINT `fk_detalle_ventas_productos1` FOREIGN KEY (`productos_idproductos`) REFERENCES `productos` (`idproductos`),
  ADD CONSTRAINT `fk_detalle_ventas_ventas1` FOREIGN KEY (`ventas_idventas`) REFERENCES `ventas` (`idventas`);

--
-- Filtros para la tabla `direccion`
--
ALTER TABLE `direccion`
  ADD CONSTRAINT `fk_direccion_usuario1` FOREIGN KEY (`usuario_idusuario`) REFERENCES `usuario` (`idusuario`);

--
-- Filtros para la tabla `materia_prima`
--
ALTER TABLE `materia_prima`
  ADD CONSTRAINT `fk_materia_prima_unidad_medida1` FOREIGN KEY (`unidad_medida_idunidad_medida`) REFERENCES `unidad_medida` (`idunidad_medida`);

--
-- Filtros para la tabla `materia_prima_has_proveedor`
--
ALTER TABLE `materia_prima_has_proveedor`
  ADD CONSTRAINT `fk_materia_prima_has_proveedor_materia_prima1` FOREIGN KEY (`materia_prima_idmateria_prima`) REFERENCES `materia_prima` (`idmateria_prima`),
  ADD CONSTRAINT `fk_materia_prima_has_proveedor_proveedor1` FOREIGN KEY (`proveedor_idproveedor`) REFERENCES `proveedor` (`idproveedor`);

--
-- Filtros para la tabla `pagos_mercadopago`
--
ALTER TABLE `pagos_mercadopago`
  ADD CONSTRAINT `fk_pagos_mercadopago_ventas1` FOREIGN KEY (`ventas_idventas`) REFERENCES `ventas` (`idventas`);

--
-- Filtros para la tabla `pagos_tarjeta`
--
ALTER TABLE `pagos_tarjeta`
  ADD CONSTRAINT `fk_pagos_tarjeta_proveedor_bancario1` FOREIGN KEY (`proveedor_bancario_idproveedor_bancario`) REFERENCES `proveedor_bancario` (`idproveedor_bancario`),
  ADD CONSTRAINT `fk_pagos_tarjeta_ventas1` FOREIGN KEY (`ventas_idventas`) REFERENCES `ventas` (`idventas`);

--
-- Filtros para la tabla `produccion`
--
ALTER TABLE `produccion`
  ADD CONSTRAINT `fk_produccion_estado_produccion1` FOREIGN KEY (`estado_produccion_idestado_produccion`) REFERENCES `estado_produccion` (`idestado_produccion`),
  ADD CONSTRAINT `fk_produccion_recetas1` FOREIGN KEY (`recetas_idrecetas`) REFERENCES `recetas` (`idrecetas`),
  ADD CONSTRAINT `fk_produccion_usuario1` FOREIGN KEY (`usuario_idusuario`) REFERENCES `usuario` (`idusuario`);

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `fk_producto_receta` FOREIGN KEY (`recetas_idrecetas`) REFERENCES `recetas` (`idrecetas`);

--
-- Filtros para la tabla `recetas`
--
ALTER TABLE `recetas`
  ADD CONSTRAINT `fk_receta_unidad` FOREIGN KEY (`unidad_medida_idunidad_medida`) REFERENCES `unidad_medida` (`idunidad_medida`);

--
-- Filtros para la tabla `receta_ingredientes`
--
ALTER TABLE `receta_ingredientes`
  ADD CONSTRAINT `fk_receta_ingredientes_materia_prima1` FOREIGN KEY (`materia_prima_idmateria_prima`) REFERENCES `materia_prima` (`idmateria_prima`),
  ADD CONSTRAINT `fk_receta_ingredientes_recetas1` FOREIGN KEY (`recetas_idrecetas`) REFERENCES `recetas` (`idrecetas`),
  ADD CONSTRAINT `fk_receta_ingredientes_unidad_medida1` FOREIGN KEY (`unidad_medida_idunidad_medida`) REFERENCES `unidad_medida` (`idunidad_medida`);

--
-- Filtros para la tabla `stock_productos`
--
ALTER TABLE `stock_productos`
  ADD CONSTRAINT `fk_stock_productos_productos1` FOREIGN KEY (`productos_idproductos`) REFERENCES `productos` (`idproductos`);

--
-- Filtros para la tabla `stock_productos_movimientos`
--
ALTER TABLE `stock_productos_movimientos`
  ADD CONSTRAINT `fk_stock_productos_movimientos_produccion1` FOREIGN KEY (`produccion_idproduccion`) REFERENCES `produccion` (`idproduccion`),
  ADD CONSTRAINT `fk_stock_productos_movimientos_productos1` FOREIGN KEY (`productos_idproductos`) REFERENCES `productos` (`idproductos`),
  ADD CONSTRAINT `fk_stock_productos_movimientos_usuario1` FOREIGN KEY (`usuario_idusuario`) REFERENCES `usuario` (`idusuario`);

--
-- Filtros para la tabla `usuarios_roles`
--
ALTER TABLE `usuarios_roles`
  ADD CONSTRAINT `fk_usuarios_roles_roles1` FOREIGN KEY (`roles_idroles`) REFERENCES `roles` (`idroles`),
  ADD CONSTRAINT `fk_usuarios_roles_usuario` FOREIGN KEY (`usuario_idusuario`) REFERENCES `usuario` (`idusuario`);

--
-- Filtros para la tabla `usuario_auth`
--
ALTER TABLE `usuario_auth`
  ADD CONSTRAINT `fk_usuario_auth_usuario1` FOREIGN KEY (`usuario_idusuario`) REFERENCES `usuario` (`idusuario`);

--
-- Filtros para la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD CONSTRAINT `fk_ventas_estado_venta1` FOREIGN KEY (`estado_venta_idestado_venta`) REFERENCES `estado_venta` (`idestado_venta`),
  ADD CONSTRAINT `fk_ventas_metodo_pago1` FOREIGN KEY (`metodo_pago_idmetodo_pago`) REFERENCES `metodo_pago` (`idmetodo_pago`),
  ADD CONSTRAINT `fk_ventas_usuario1` FOREIGN KEY (`usuario_idusuario`) REFERENCES `usuario` (`idusuario`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
