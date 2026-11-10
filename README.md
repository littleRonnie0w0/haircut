# 2.7 Haircut Docker

Versión actual de **HairCut Home Studio**: Laravel 12 + Docker (app, MySQL, Redis).

La aplicación vive en `HaircutHomeStudio/`. Instrucciones de arranque, cuentas demo, IA y flujo de reservas: ver `HaircutHomeStudio/README.md`.

## Estado

Versión en uso para el capstone. Incluye la base de datos nueva del proyecto y los cambios de UI, roles, correos y reservas descritos abajo.

## Contenido

- `compose.yaml` — orquestación Docker (`haircut_app`, `hhs_db`, `hhs_redis`)
- `HaircutHomeStudio/` — aplicación Laravel

App local típica: `http://localhost:8000`

---

## Cambios de esta versión (2.7)

Listado de lo que quedó incorporado. No se documentan idas y vueltas temporales (cosas quitadas y vueltas a poner en la misma sesión).

### Infraestructura y base

- Proyecto Docker con Laravel 12, MySQL y Redis.
- Adaptación al esquema de la BD del proyecto (sin rediseñar el modelo de negocio).
- Corrección de `imagen_simulada` en `RESERVA` (ruta de archivo, no fecha).
- Organización de evidencias: esta carpeta como **2.7_haircut_docker**.

### Autenticación y perfiles

- Login por correo o nombre de usuario (atajos `admin` / `ricardo`; resolución por `nombre_usuario`).
- Registro de clientas con nombre por defecto **Clienta** si no se indica otro.
- Restablecimiento de contraseña por correo (token en archivo JSON, sin tabla nueva).
- Perfil editable; el rol no se muestra a las clientas.
- Pantallas de **bienvenida** para admin y peluquero al iniciar sesión.

### Roles y navegación

- Middleware `role` activo: cliente, peluquero y admin solo entran a sus rutas.
- Menú distinto por rol; logo y “inicio” llevan al hogar de ese rol.
- Botón volver atrás respeta el historial solo si la página anterior es permitida para el rol.
- Staff (admin/peluquero) no usa la portada pública como panel; van a bienvenida/panel.
- Excepción **Admin Test Imagen** (`admin.imagen@haircut.cl`): admin con simulador ilimitado y opción de reservar.

### Correos

- Servicio `MessagingService` + plantilla HTML de marca (`emails/brand`).
- Envíos: bienvenida al registrarse, enlace de restablecer contraseña, confirmación o rechazo de solicitud de hora.
- Configuración por `.env` (`MESSAGING_*`).

### Reservas y fotos

- Asistente de reserva por pasos (servicio → fecha/hora → referencia → lugar → resumen).
- Al **cambiar de mes** en el calendario se deselecciona el día y la hora; no se muestran slots de un mes distinto al visible.
- Hasta **2 imágenes de referencia** por reserva (campo `foto` compatible con 1 o 2 rutas).
- Modal de galería para ver referencia(s) y simulación IA en **Mis reservas** (cliente) y **Solicitudes** (peluquero).
- Sin doble reserva del mismo bloque; cancelación hasta 10 minutos antes.

### Panel admin / peluquero

- Admin: servicios, catálogo, personas.
- Peluquero: estadísticas, disponibilidad (JSON + autosave), solicitudes, horas, reportes.
- Modales “¿Seguro que quiere eliminar/cancelar?” en acciones destructivas.
- Disponibilidad: calendario con persistencia de días libres y edición de horas.

### Simulador IA

- Acceso para clientas autenticadas y Admin Test Imagen.
- Cuotas habituales; Admin Test Imagen sin límite de cupo (sin columna nueva en BD).
- Enlace desde simulación hacia reservar con servicio precargado.

### UI / UX

- Cabecera con nombre de usuaria, menú lateral, cerrar sesión en el menú.
- Botón circular de volver atrás en la barra superior.
- Loader de navegación; reseñas con enlace a Google Maps.
- Ajustes responsive (Personas, footer, selectores de hora en móvil).

### Mensajería / config de ejemplo

- Variables de mensajería documentadas en `.env.example`.

---

## Notas

- No subir API keys reales al repositorio; usar `.env` local (ignorado por Git).
