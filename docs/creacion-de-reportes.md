# Creación de reportes

Guía paso a paso para diseñar un reporte, darle formato y generarlo (incluido PDF).

El diseño lo hace un usuario con rol **Sistemas**. La generación la puede hacer cualquier usuario autenticado a quien se le haya dado visibilidad.

---

## 1. Entrar al diseñador

1. Inicia sesión con un usuario de **Sistemas**.
2. En el menú abre **Diseñar reportes**.
3. Pulsa **Nuevo reporte**.

---

## 2. Datos básicos

1. Escribe el **nombre del reporte** (así lo verán los demás y así se llamará el PDF).
2. Elige la **tabla** (fuente de datos). Al cambiar de tabla se vacían los campos ya añadidos.

Fuentes disponibles hoy:

| Tabla | Campos |
| --- | --- |
| Jornadas | Nombre, Descripción |
| Modalidades | Nombre, Descripción |
| Niveles | Nombre, Descripción |
| Usuarios | Nombre, Correo, Roles (relación) |

---

## 3. Elegir el diseño del resultado

En **Diseño del resultado** hay dos opciones:

- **Hoja libre**: cada registro ocupa una hoja y colocas descripciones y valores donde quieras (paso 5).
- **Tabla de resultados**: todos los registros salen en filas y columnas, una columna por campo (paso 6).

---

## 4. Elegir campos

1. A la izquierda aparecen los **campos disponibles**, agrupados (tabla principal y relaciones).
2. Pulsa **Añadir** en cada campo que quieras mostrar.
3. A la derecha, en **Descripciones**, puedes:
   - Cambiar el texto de la etiqueta (por ejemplo, “Nombre” → “Jornada”). En modo tabla esa etiqueta es el título de la columna.
   - **Quitar** un campo.

Hace falta **al menos un campo** para guardar.

---

## 5. Hoja libre: colocar el diseño en la vista previa

En **Vista previa** hay una hoja en blanco.

- El bloque amarillo (**Desc.**) es la descripción (etiqueta).
- El bloque azul (**Campo**) es el valor. En el diseñador se ve un texto de ejemplo; al generar el reporte se sustituye por el dato real.

Arrastra cada bloque a donde quieras. Las dos piezas se mueven por separado.

Si editas el texto de la descripción a la derecha, el bloque amarillo se actualiza.

---

## 6. Tabla: formato de la tabla

Con el diseño **Tabla de resultados** aparece el bloque **Formato de la tabla**:

| Opción | Qué hace |
| --- | --- |
| Grosor del borde | Ancho del borde en píxeles. `0` deja la tabla sin bordes. |
| Color del borde | Color de las líneas. |
| Fondo del encabezado | Color de fondo de la fila de títulos. |
| Tamaño de letra | Entre 8 y 20 píxeles. |
| Espacio interior | Relleno de cada celda, entre 0 y 24 píxeles. |
| Mostrar encabezado | Oculta o muestra la fila de títulos. |
| Filas alternas | Sombrea una fila sí y otra no. |

Debajo hay una **vista previa** con filas de ejemplo que se actualiza al cambiar cada opción. Los datos reales se ven al generar el reporte.

---

## 7. Quién puede ver y generar el reporte

1. Marca **Disponible para generar** si debe aparecer en el listado de reportes.
2. Elige la visibilidad:
   - **Visible para todos los usuarios autenticados**, o
   - desmarca esa opción y selecciona **al menos un rol**.

Sin roles y sin “visible para todos”, no se puede guardar.

Quien tiene rol **Sistemas** puede abrir cualquier reporte desde el diseñador, aunque esté oculto o publicado para otros roles. En el menú **Reportes** cada usuario ve solo lo que le corresponde.

---

## 8. Guardar

Pulsa **Guardar reporte**.

Quedas en la lista **Diseñar reportes**, con el reporte creado. Desde ahí puedes **Editar**, **Eliminar** o **Ver** (generar).

---

## 9. Generar el reporte

1. En el menú abre **Reportes** (disponible para quien tenga visibilidad).
2. Elige el reporte.
3. Los resultados salen según el diseño: una hoja por registro (hoja libre) o una tabla con todos los registros.
4. Si hay muchos registros, usa la paginación al pie.

---

## 10. Descargar PDF

En la pantalla del reporte generado, pulsa **Descargar PDF**.

El PDF usa el mismo diseño, incluido el formato de la tabla. El archivo se nombra con el nombre del reporte.

---

## Editar o borrar un diseño

Desde **Diseñar reportes**:

- **Editar**: cambia nombre, tabla, diseño, campos, posiciones, formato de tabla y visibilidad. Al guardar, el diseño anterior de campos se reemplaza por el nuevo.
- **Eliminar**: pide confirmación y borra la definición.

---

## Notas

- Solo se pueden usar campos de la lista permitida. No se aceptan columnas arbitrarias de la base de datos.
- El PDF incluye hasta 500 registros.
- Para añadir otra tabla al diseñador hay que declararla en `config/reports.php` (modelo, campos y, si aplica, `relation` + `attribute`).
