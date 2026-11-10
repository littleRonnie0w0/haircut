# Contrato de reproducción de plantilla DAS

## Referencia

- Archivo retenido: `C:\Users\CaNi517\Downloads\Documento Arquitectura Sistema(DAS).docx`
- SHA-256: `619C683E5689B612B0D5043A5D3A625B16BBDA43FFEA701CA9A2D902273F8D00`
- Render de referencia: `%TEMP%\codex-das-haircut\reference-render`
- Evidencia de paquete: `docs/.das-work/package-inventory.json`
- Páginas renderizadas: 23
- Secciones: 1

## Sistema de página

- Papel carta, 8.50 x 11.00 pulgadas, orientación vertical.
- Márgenes: izquierdo 1.22 pulgadas, derecho 1.00, superior 1.00, inferior 1.00.
- Encabezado y pie a 0.50 pulgadas.
- Primera página diferenciada; portada sin numeración visible.
- Pie recurrente: “Documento Confidencial” a la izquierda y número de página a la derecha.
- Saltos manuales separan portada, identificación, tabla de contenidos y capítulos principales.

## Tipografía y color

- Familia base: Arial.
- Título de portada: 18 pt, negrita, azul marino `#000080`, alineado a la derecha.
- Nombre del sistema: 14 pt, negrita, azul `#1F497D`, alineado a la derecha.
- Encabezado de capítulo: 14 pt, negrita, azul marino `#000080`, 6 pt antes y 12 pt después.
- Subtítulo: 12 pt, negrita, azul marino `#000080`, 6 pt antes y 3 pt después.
- Cuerpo: Arial 9–10 pt, negro, justificado, interlineado cercano a 1.2.
- Títulos de figuras: azul, centrados, inmediatamente sobre la imagen.

## Tablas y listas

- Tablas de identificación en dos columnas, bordes grises finos y texto compacto.
- Tabla de revisiones en cuatro columnas: Fecha, Versión, Descripción, Autor.
- Tablas de contenido técnico con encabezado azul oscuro o gris, bordes `#D9D9D9`, filas autoajustables y texto verticalmente centrado.
- Listas con viñeta circular negra y sangría moderada.

## Componentes recurrentes

- Portada con logo DuocUC preservado, identificación institucional superior y bloque de título inferior derecho.
- Página 2 con identificación del documento, mantenimiento, aprobación e historia de revisiones.
- Página 3 con tabla de contenidos.
- Capítulos numerados en azul marino.
- Diagramas centrados, dimensionados dentro del ancho útil, con título azul sobre cada figura.
- Pie confidencial y paginación preservados.

## Flujo de contenido

1. Portada.
2. Identificación e historia de revisiones.
3. Tabla de contenidos.
4. Introducción, propósito, alcance, definiciones, referencias, resumen y representación.
5. Metas, restricciones, antecedentes y consideraciones.
6. Casos de uso relevantes y escenarios de calidad.
7. Vista lógica.
8. Vista de procesos.
9. Vista de despliegue según nomenclatura de la asignatura.
10. Vista física.
11. Escenarios 4+1.
12. Decisiones de diseño y alternativas.

## Mapa de espacios editables

- `word/document.xml`, portada: reemplazar nombre del sistema, tipo de documento y versión; preservar logo y bloque institucional.
- Tablas 0–3: reemplazar identificación, proyecto, versión y fechas; preservar estructura y bordes.
- Desde el párrafo “Tabla de Contenidos”: reconstruir contenido y capítulos con los estilos visuales de la referencia.
- Diagramas de la solución anterior: retirar y sustituir por los PNG de `docs/diagramas`.
- Encabezados, pies, estilos, tema, numeración, logo, relaciones de portada y propiedades de sección: preservar.
- Comentarios, controles de contenido y elementos no utilizados: no introducir.

## Inventario y preservación

- Preservar `word/styles.xml`, `word/theme/*`, `word/header*.xml`, `word/footer*.xml`, `word/settings.xml`, propiedades de sección y medios de portada.
- Se permite modificar `word/document.xml`, relaciones de imágenes del documento y añadir medios para los diagramas.
- No modificar el archivo de referencia.

## Puertas de fidelidad

- La referencia debe conservar el SHA-256 indicado.
- El documento final debe mantener tamaño de página, márgenes, portada, identidad cromática, pie y jerarquía tipográfica.
- Renderizar todas las páginas y revisar a escala completa: sin texto cortado, tablas partidas de forma defectuosa, imágenes borrosas, diagramas fuera de márgenes ni páginas vacías accidentales.
- Confirmar que cada diagrama corresponde a Haircut Home Studio y que no permanece contenido de Código Trauma.
