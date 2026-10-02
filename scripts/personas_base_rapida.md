# Base de personas rapida

El archivo `C:\Users\Jaime\Desktop\proyectos\BASE DE DATOS\persona.sql` tiene una sola tabla grande, `persona`, con cerca de 11,36 millones de registros. La tabla solo trae indice por `id`; por eso buscar por RUT, nombre o apellido sera lento si el sistema consulta esa tabla directamente.

## Estrategia recomendada

1. Importar el dump en una base separada: `sdi_personas_fast`.
2. Crear una tabla optimizada en esa misma base: `sdi_personas_fast.personas_rapidas`.
3. Guardar el RUT en formato normalizado, sin puntos ni guion: `102115686`.
4. Agregar indices para las consultas reales:
   - `rut_normalizado` para busqueda exacta.
   - `rut_cuerpo, rut_dv` para busqueda por numero y digito.
   - `appaterno, apmaterno, nombre1` para listados por apellidos.
   - `FULLTEXT(nombre_completo)` para busquedas por texto.
5. Eliminar automaticamente registros con RUT sucio o menor a 1.000.000.
6. Guardar email, telefono y direccion cifrada en `personas_rapidas_ediciones`.
7. Desde Laravel, consultar `personas_rapidas`, no el dump original.

## Comandos

Crear la base temporal:

```sql
CREATE DATABASE sdi_personas_fast CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Importar el archivo grande:

```powershell
mysql -u root sdi_personas_fast -e "source C:/Users/Jaime/Desktop/proyectos/BASE DE DATOS/persona.sql"
```

Crear la tabla rapida:

```powershell
cd "C:\Users\Jaime\Desktop\proyectos\sistema de voucher\vouchers-unificado\central"
mysql -u root sdi_personas_fast < scripts\personas_base_rapida.sql
```

## Consultas que deberia usar el sistema

Busqueda exacta por RUT:

```sql
SELECT id, rut_original, nombre_completo
FROM personas_rapidas
WHERE rut_normalizado = '102115686'
LIMIT 5;
```

Busqueda por nombre:

```sql
SELECT id, rut_original, nombre_completo
FROM personas_rapidas
WHERE MATCH(nombre_completo) AGAINST('+JAIME +KRIMAN' IN BOOLEAN MODE)
LIMIT 10;
```

## Nota de seguridad

El sistema ya tiene tablas `voucher_base_usuarios` con RUT hasheado y cifrado. Para emision de bonos y autorizaciones conviene seguir usando esas tablas seguras. `personas_rapidas` queda como base auxiliar de busqueda/validacion, idealmente restringida a usuarios autorizados.

La busqueda rapida usa `personas_rapidas.rut_normalizado`, que esta indexado. La direccion no se usa para buscar y se guarda cifrada en `personas_rapidas_ediciones.direccion_encrypted` usando el cifrado de Laravel.
