-- Crea una base nueva dedicada a consultas rapidas de personas.
--
-- Flujo recomendado:
-- 1) Crear la BD:
--      CREATE DATABASE sdi_personas_fast CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- 2) Importar C:\Users\Jaime\Desktop\proyectos\BASE DE DATOS\persona.sql:
--      mysql -u root sdi_personas_fast -e "source C:/Users/Jaime/Desktop/proyectos/BASE DE DATOS/persona.sql"
-- 3) Ejecutar este archivo:
--      mysql -u root sdi_personas_fast < scripts/personas_base_rapida.sql
--
-- Resultado:
--   sdi_personas_fast.personas_rapidas queda preparada para:
--   - busqueda exacta por RUT normalizado
--   - busqueda por apellidos/nombres
--   - busqueda fulltext por nombre completo
--   - exclusion de RUT menores a 1.000.000
--   - ediciones/contacto/direccion cifrada en tabla separada

DROP TABLE IF EXISTS personas_rapidas;
DROP TABLE IF EXISTS personas_rapidas_ediciones;

CREATE TABLE personas_rapidas (
  id BIGINT UNSIGNED NOT NULL,
  rut_original VARCHAR(20) NULL,
  rut_normalizado VARCHAR(20) NOT NULL,
  rut_cuerpo INT UNSIGNED NULL,
  rut_dv CHAR(1) NULL,
  nombre1 VARCHAR(255) NULL,
  appaterno VARCHAR(255) NULL,
  apmaterno VARCHAR(255) NULL,
  nombre_completo VARCHAR(800) NOT NULL,
  estado VARCHAR(30) NOT NULL DEFAULT 'activo',
  origen VARCHAR(80) NOT NULL DEFAULT 'medsdi_medichile.persona',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_personas_rapidas_rut (rut_normalizado),
  KEY idx_personas_rapidas_rut_cuerpo (rut_cuerpo, rut_dv),
  KEY idx_personas_rapidas_apellidos (appaterno(80), apmaterno(80), nombre1(80)),
  FULLTEXT KEY ft_personas_rapidas_nombre (nombre_completo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO personas_rapidas (
  id,
  rut_original,
  rut_normalizado,
  rut_cuerpo,
  rut_dv,
  nombre1,
  appaterno,
  apmaterno,
  nombre_completo
)
SELECT
  p.id,
  NULLIF(TRIM(p.rut), ''),
  @rut := UPPER(REPLACE(REPLACE(REPLACE(TRIM(p.rut), '.', ''), '-', ''), ' ', '')) AS rut_normalizado,
  CAST(LEFT(@rut, CHAR_LENGTH(@rut) - 1) AS UNSIGNED) AS rut_cuerpo,
  RIGHT(@rut, 1) AS rut_dv,
  NULLIF(TRIM(p.nombre1), ''),
  NULLIF(TRIM(p.appaterno), ''),
  NULLIF(TRIM(p.apmaterno), ''),
  TRIM(CONCAT_WS(' ', NULLIF(TRIM(p.nombre1), ''), NULLIF(TRIM(p.appaterno), ''), NULLIF(TRIM(p.apmaterno), ''))) AS nombre_completo
FROM persona p
WHERE p.rut IS NOT NULL
  AND TRIM(p.rut) <> ''
  AND (@rut_filtro := UPPER(REPLACE(REPLACE(REPLACE(TRIM(p.rut), '.', ''), '-', ''), ' ', ''))) REGEXP '^[0-9]+[0-9K]$'
  AND CAST(LEFT(@rut_filtro, CHAR_LENGTH(@rut_filtro) - 1) AS UNSIGNED) >= 1000000;

CREATE TABLE personas_rapidas_ediciones (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  rut_normalizado VARCHAR(20) NOT NULL,
  nombre1 VARCHAR(255) NULL,
  appaterno VARCHAR(255) NULL,
  apmaterno VARCHAR(255) NULL,
  nombre_completo VARCHAR(800) NULL,
  email VARCHAR(255) NULL,
  telefono VARCHAR(50) NULL,
  direccion_encrypted TEXT NULL,
  origen VARCHAR(80) NOT NULL DEFAULT 'formulario_prueba',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY ux_personas_rapidas_ediciones_rut (rut_normalizado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ANALYZE TABLE personas_rapidas;

-- Consultas de prueba:
-- SELECT * FROM personas_rapidas WHERE rut_normalizado = '102115686' LIMIT 5;
-- SELECT * FROM personas_rapidas WHERE rut_cuerpo = 10211568 AND rut_dv = '6' LIMIT 5;
-- SELECT * FROM personas_rapidas WHERE MATCH(nombre_completo) AGAINST('+JAIME +KRIMAN' IN BOOLEAN MODE) LIMIT 10;
