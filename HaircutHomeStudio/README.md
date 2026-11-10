# Haircut Home Studio - Laravel

Migración del proyecto PHP ubicado en `../haircut` a Laravel 12. El proyecto anterior permanece intacto como respaldo y referencia.

> **Actualización 27 oct 2026:** correos de marca (bienvenida, reset, confirmación de hora), 2 fotos de referencia al reservar, calendario que limpia día/hora al cambiar de mes, modales de confirmación, menú/roles separados y bienvenida de staff. Detalle en el README de la carpeta `2.7_haircut_docker`.

## Estado de la migración

Se trasladaron:

- Portada, collage, productos y reseñas.
- Inicio de sesión, registro, cierre de sesión y roles.
- Reservas, calendario, bloques horarios, fotos y cancelación.
- Regla de solapamiento entre la última hora de un color largo y un corte de una hora.
- Panel del peluquero, disponibilidad, solicitudes, agenda y reportes CSV/PDF.
- Administración de categorías, servicios y productos.
- Simulador privado con Gemini, servicios sincronizados con la reserva y cuotas de uso.
- CSS, imágenes, iconos y comportamiento JavaScript del proyecto original.
- Esquema SQL y datos iniciales mediante migraciones y seeders.

## Requisitos
- PHP 8.2 o superior. En este equipo: `C:\xampp\php\php.exe`.
- Node.js y npm. En este equipo: `C:\Program Files\nodejs\npm.cmd`.
- SQLite, incluido con la instalación actual de PHP, o MySQL de XAMPP.

## Primera ejecución

Desde esta carpeta, en PowerShell:

```powershell
& 'C:\xampp\php\php.exe' artisan migrate:fresh --seed
& 'C:\xampp\php\php.exe' artisan storage:link
$env:Path = 'C:\Program Files\nodejs;' + $env:Path
& 'C:\Program Files\nodejs\npm.cmd' install
& 'C:\Program Files\nodejs\npm.cmd' run build
& 'C:\xampp\php\php.exe' artisan serve
```

La aplicación queda disponible normalmente en `http://127.0.0.1:8000`.

No es necesario ejecutar `storage:link` otra vez si responde que el enlace ya existe.

## Base de datos

### Tarifario del profesional (octubre de 2026)

El catálogo contiene los 17 servicios de HairCut HomeStudio, agrupados en **Cortes y lavado**, **Coloración e iluminación** y **Alisados y tratamientos**. Los importes variables se guardan con `precio_desde` y muestran “Desde” en la selección, el resumen y la administración. Pueden variar según el largo, la cantidad de cabello y la técnica requerida.

Para actualizar una base existente, sin borrar usuarios, reservas ni fotos:

```powershell
php artisan migrate
php artisan storage:link
npm run build
```

La migración actualiza servicios equivalentes conservando sus IDs, imágenes y bloques de agenda. Los servicios fuera del tarifario se desactivan, no se eliminan; las reservas previas mantienen su vínculo. Los nuevos bloques son referenciales y deben confirmarse con el peluquero: el tarifario no especifica duraciones. El rollback elimina el campo `precio_desde`, pero no restaura el catálogo anterior. No uses `migrate:fresh` para actualizar datos existentes.

El simulador incluye cortes, secado y coloración; excluye el lavado solo (sin cambio visual) y los tratamientos. Hay prompts específicos para los nombres nuevos, incluidos Babylights, Reflejos y Colores fantasía. La muestra de fantasía utiliza violeta/magenta como ejemplo, no como restricción del servicio. Las pruebas usan el proveedor `fake`, sin enviar fotos ni consumir Gemini.

Las fotos de referencia de reserva admiten JPG, PNG, WEBP y GIF hasta 3 MB, con selección, arrastre, vista previa y eliminación. Se valida también en el servidor. `storage:link` permite que el peluquero consulte las fotos guardadas.

En otros equipos, comprueba que PHP permita al menos 6 MB por archivo (`upload_max_filesize`) y una petición mayor que ese tamaño (`post_max_size`), para cubrir tanto reservas como IA. No cambies los límites de la aplicación para compensar una configuración de PHP menor.

El entorno local usa MySQL/MariaDB de XAMPP mediante una base independiente llamada `haircut_studio_laravel`. La conexión activa de `.env` es:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=haircut_studio_laravel
DB_USERNAME=root
DB_PASSWORD=
```

La base se puede inspeccionar desde phpMyAdmin en `http://localhost/phpmyadmin`, seleccionando `haircut_studio_laravel` en la barra lateral.

Para construir sus tablas y cargar los datos iniciales ejecuta:

```powershell
& 'C:\xampp\php\php.exe' artisan migrate:fresh --seed
```

> No ejecutes `migrate:fresh` apuntando a la base original `haircut_studio`: ese comando elimina todas las tablas antes de reconstruirlas. El seeder replica los datos versionados en `haircut/database/haircut_studio.sql`, pero no puede recuperar cambios locales que no estén en ese archivo.

Las pruebas automatizadas continúan usando SQLite en memoria, configurado en `phpunit.xml`; por tanto no borran ni alteran la base de XAMPP.

## Cuentas de demostración

| Rol | Usuario | Contraseña |
|---|---|---|
| Administrador | `admin` o `admin@admin.cl` | `admin123` |
| Admin de pruebas IA | `admin.imagen@haircut.cl` | `ImagenTest2026!` |
| Peluquero | `ricardo` o `ricardo@me.com` | `kako123` |
| Cliente | `ana` o `ana@haircut.cl` | `ana123` |

Los hashes bcrypt originales se conservaron, por lo que estas credenciales siguen siendo compatibles.

`Admin Test Imagen` tiene el indicador `ai_sin_limite` y se usa solo para probar generaciones: no consume las cuotas por usuario, IP ni los topes globales. Mantiene una sola generación simultánea para evitar envíos duplicados. Cambia su contraseña o elimina esta cuenta antes de publicar el proyecto.

## Simulador con Gemini

El módulo está disponible para clientes autenticados en `/simulador`. Sus opciones se obtienen automáticamente de los servicios activos de las categorías de cortes y coloración, excepto el lavado solo; los tratamientos no se muestran. Incluye comparación antes/después, historial privado y la opción de reservar directamente el mismo servicio simulado.

Para probar el flujo sin consumir API, configura explícitamente el modo demostración:

```dotenv
AI_ENABLED=true
AI_PROVIDER=fake
AI_PROCESS_SYNC=true
```

En este modo se prueba el flujo completo, pero el resultado repite la fotografía original. No lo confundas con una generación real. Para activar Gemini real:

1. Crea una clave en Google AI Studio.
2. Edita únicamente tu archivo `.env`; nunca publiques ni envíes la clave por chat.
3. Cambia estas variables:

```dotenv
AI_ENABLED=true
AI_PROVIDER=gemini
AI_PROCESS_SYNC=false
GEMINI_API_KEY=tu_clave_privada
GEMINI_IMAGE_MODEL=gemini-3.1-flash-image
```

4. Limpia la configuración e inicia el worker:

```powershell
& 'C:\xampp\php\php.exe' artisan optimize:clear
& 'C:\xampp\php\php.exe' artisan queue:work --queue=ai,default
```

El servidor web y el worker se ejecutan en terminales separadas. Para una prueba rápida se puede usar `AI_PROCESS_SYNC=true`, pero la petición del navegador esperará a que Gemini termine.

El entorno local puede usar `AI_PROVIDER=gemini` con `AI_PROCESS_SYNC=true` para llamar directamente a Gemini sin worker. La clave debe permanecer solo en `.env` (ignorado por Git); no se copia automáticamente al cambiar de carpeta de proyecto. Comprueba también que `GEMINI_IMAGE_MODEL` sea un modelo de imágenes disponible para tu clave. Las pruebas automatizadas usan proveedores falsos aunque el entorno local esté en modo real.

### Límites y privacidad
- Las instrucciones conservan el largo, tipo, densidad, línea de nacimiento y zonas de cuero cabelludo visibles; no deben inventar cabello ni rellenar entradas o calvicie. Solo los cortes pueden reducir el largo. La coloración y el secado mantienen el cabello disponible, adaptando el resultado si es corto o escaso. Estas reglas orientan a Gemini, pero no garantizan fidelidad perfecta: revisa siempre el antes/después.
- La reutilización de resultados incluye la huella del prompt. Al cambiar las instrucciones, una nueva solicitud genera una simulación nueva y consume el cupo habitual; el historial anterior se conserva, pero no se reutiliza como resultado actualizado.
- 2 solicitudes por minuto por usuario y 4 por minuto por IP.
- 3 solicitudes durante 24 horas y 5 durante 30 días por usuario.
- Topes globales de 50 al día y 500 al mes.
- Los intentos aceptados consumen cuota aunque el proveedor falle.
- Las fotografías se guardan en `storage/app/private/ai`, nunca en `public`.
- Se eliminan metadatos EXIF/textuales de JPG y PNG antes de enviarlos.
- Originales y resultados vencen a las 72 horas; al vincularlos a una reserva permanecen hasta 24 horas después de la cita.
- El prompt real está en `config/ai.php`; el navegador solo envía el ID de un servicio permitido.

Para ejecutar la limpieza manualmente:

```powershell
& 'C:\xampp\php\php.exe' artisan ai:purge
```

En un servidor, `php artisan schedule:run` debe ejecutarse cada minuto mediante el programador del sistema. Durante desarrollo también se puede usar `php artisan schedule:work`.

### Cuenta de prueba sin límite

La cuenta `Admin Test Imagen` se creó para probar el simulador sin consumir los límites normales. El indicador se guarda en `usuarios.ai_sin_limite` y `AiQuotaService` lo consulta antes de aplicar las cuotas. Esta excepción no elimina la protección contra envíos duplicados ni la validación del servicio seleccionado.

Antes de publicar el sistema, cambia la contraseña de esta cuenta, desactívala o elimínala del seeder y de la base de datos de producción.

## Flujo actual de reservas

En disponibilidad del peluquero, los días de descanso recibidos del formulario se validan y convierten a enteros antes de modificar fechas con Carbon. Guardar reemplaza únicamente los descansos del mes seleccionado; conserva los de otros meses y bloquea esos días para nuevas reservas.

La ruta `/reservar` utiliza un asistente de cinco pasos:

1. **Servicio:** se elige una categoría (`Cortes y lavado`, `Coloración e iluminación` o `Alisados y tratamientos`) y luego un servicio.
2. **Fecha y hora:** se selecciona un día y un bloque horario disponible.
3. **Referencia:** se pueden adjuntar hasta **2 imágenes** opcionales y/o utilizar la simulación de Gemini vinculada.
4. **Lugar:** se elige entre el salón y la atención a domicilio.
5. **Confirmación:** se revisa el resumen y se envía la solicitud.

La selección de categorías y servicios ocurre dentro de la misma página. `resources/js/app.js` cambia los paneles, marca la opción seleccionada y muestra animaciones suaves, sin navegar al hacer clic. Al pulsar `Siguiente` desde el paso de servicio se consulta nuevamente la ruta con el servicio elegido para que el servidor calcule el calendario, la duración, los bloques ocupados y las reglas de disponibilidad correctas.

El formulario conserva el estado del asistente mediante el parámetro `paso` en la URL cuando existe una actualización del servidor. La transición entre pasos anima la altura del formulario para reducir los saltos visuales; además, `resources/css/app.css` respeta `prefers-reduced-motion`.

La interfaz mejora la experiencia, pero no reemplaza la seguridad del servidor. `ReservationController` vuelve a validar el servicio, la fecha, la hora, el lugar, la imagen y la generación de IA antes de crear la reserva. `AvailabilityService` comprueba nuevamente que el bloque siga libre, incluyendo duración y solapamientos entre servicios.

### Archivos del asistente de reservas

| Responsabilidad | Archivo |
|---|---|
| Datos iniciales del catálogo, calendario y disponibilidad | `app/Http/Controllers/ReservationController.php` |
| Reglas de horarios, duración, bloqueos y solapamientos | `app/Services/AvailabilityService.php` |
| Vista de los cinco pasos y paneles de categorías | `resources/views/reservas/create.blade.php` |
| Cambio local de categorías/servicios y navegación del asistente | `resources/js/app.js`, función `initBookingWizard()` |
| Animaciones, altura estable y diseño responsive | `resources/css/app.css`, bloque `Reserva por pasos` |
| Validación final y creación de la reserva | `app/Http/Controllers/ReservationController.php`, método `store()` |

### Relación entre simulador y reservas

El simulador y las reservas utilizan los mismos registros activos de `categorias` y `servicios`:

- El simulador muestra los servicios activos de `Cortes` y `Color`.
- La reserva muestra todas las categorías que tengan servicios activos, incluyendo `Tratamientos`.
- Una generación de IA queda asociada a `servicio_id`.
- Al reservar desde una simulación, el servidor exige que la generación pertenezca al usuario, esté completada, no haya sido usada y corresponda al mismo servicio de la reserva.
- Los cambios del catálogo se reflejan automáticamente en ambos módulos después de actualizar la página.

No se deben duplicar manualmente los nombres de servicios en las vistas del simulador o de reservas. Para agregar o modificar una opción, usa el panel de administración de servicios o actualiza el seeder/migración correspondiente.

## Dónde quedó cada cosa

| Antes, PHP | Ahora, Laravel |
|---|---|
| `index.php` | `app/Http/Controllers/HomeController.php` y `resources/views/home.blade.php` |
| `auth/login.php` | `app/Http/Controllers/AuthController.php` y `resources/views/auth/login.blade.php` |
| `usuario/*.php` | `app/Http/Controllers/ReservationController.php` y `resources/views/reservas/` |
| `admin/servicios.php` | `app/Http/Controllers/Admin/ServiceController.php` y `resources/views/admin/servicios/` |
| `admin/catalogo.php` | `app/Http/Controllers/Admin/CatalogController.php` y `resources/views/admin/catalogo/` |
| `admin/disponibilidad.php` | `app/Http/Controllers/Admin/AvailabilityController.php` y `resources/views/admin/disponibilidad.blade.php` |
| `admin/solicitudes.php` | `app/Http/Controllers/Admin/SolicitudController.php` y `resources/views/admin/solicitudes.blade.php` |
| `admin/horas.php` | `app/Http/Controllers/Admin/HoursController.php` y `resources/views/admin/horas.blade.php` |
| `admin/dashboard.php` | `app/Http/Controllers/Admin/DashboardController.php` y `resources/views/admin/dashboard.blade.php` |
| `admin/reporte-export.php` | `app/Http/Controllers/Admin/ReportController.php` |
| Módulo de simulación IA | `app/Http/Controllers/AiStudioController.php`, `app/Jobs/GenerateHairPreview.php` y `resources/views/ai/` |
| `includes/funciones.php` | Modelos de `app/Models/` y servicios de `app/Services/` |
| `includes/header.php` y `footer.php` | `resources/views/layouts/app.blade.php` |
| `style.css` | `resources/css/app.css` |
| JavaScript incrustado | `resources/js/app.js` |
| `img/` | `public/img/` |
| `uploads/fotos/` | `storage/app/public/fotos/`, visible mediante `public/storage` |
| `database/haircut_studio.sql` | `database/migrations/` y `database/seeders/DatabaseSeeder.php` |

Las rutas HTTP están centralizadas en `routes/web.php`. `app/Http/Middleware/EnsureRole.php` restringe cada panel a `cliente`, `admin` o `peluquero`.

## Cómo trabajar el proyecto

### Cambiar contenido o diseño
- Edita Blade en `resources/views/` para cambiar HTML o textos.
- Edita `resources/css/app.css` para estilos.
- Edita `resources/js/app.js` para interacciones.
- Durante desarrollo ejecuta `npm run dev`; para una compilación final usa `npm run build`.

### Cambiar lógica de reservas
- `app/Services/AvailabilityService.php`: días, meses visibles, horarios, duración y solapamientos.
- `app/Http/Controllers/ReservationController.php`: flujo del cliente y creación de reservas.
- `app/Services/ReservationWorkflowService.php`: transición automática a `realizada`.
- `app/Models/Reserva.php`: relaciones y regla de cancelación hasta diez minutos antes.

### Cambiar el simulador
- `config/ai.php`: modelos, cuotas, retención y prompts privados por servicio.
- `categorias` y `servicios` en MySQL: catálogo que alimenta tanto Reserva como Simulador.
- `app/Services/GeminiImageProvider.php`: integración REST con Gemini.
- `app/Services/AiQuotaService.php`: límites persistentes.
- `app/Jobs/GenerateHairPreview.php`: procesamiento y almacenamiento del resultado.
- `resources/views/ai/index.blade.php`: interfaz del cliente.

### Cambiar tablas o datos iniciales
- Crea una migración nueva; no edites una migración ya aplicada en un ambiente compartido.
- Usa `database/seeders/DatabaseSeeder.php` solo para datos iniciales o demostrativos.
- En desarrollo puedes reconstruir SQLite con `artisan migrate:fresh --seed`.

### Archivos subidos

Laravel guarda imágenes nuevas en `storage/app/public/fotos`. En las vistas se accede a ellas mediante `/storage/fotos/...`. El enlace se crea con `artisan storage:link`.

## Comandos habituales

```powershell
# Ejecutar pruebas
& 'C:\xampp\php\php.exe' artisan test

# Formatear PHP
& 'C:\xampp\php\php.exe' vendor\bin\pint

# Limpiar cachés después de cambiar configuración o vistas
& 'C:\xampp\php\php.exe' artisan optimize:clear

# Compilar CSS y JavaScript
$env:Path = 'C:\Program Files\nodejs;' + $env:Path
& 'C:\Program Files\nodejs\npm.cmd' run build
```

Las pruebas cubren esquema y relaciones, disponibilidad, solapamientos, autenticación con hashes antiguos, separación de roles, reservas duplicadas, confirmación de solicitudes, renderizado de páginas y exportación CSV/PDF.