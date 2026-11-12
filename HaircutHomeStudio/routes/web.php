<?php

use App\Http\Controllers\Admin\AvailabilityController;
use App\Http\Controllers\Admin\CatalogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HoursController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\SolicitudController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AiStudioController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\StaffWelcomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])
    ->middleware('session.cache')
    ->name('home');

Route::get('/productos', [ProductController::class, 'index'])
    ->middleware('session.cache')
    ->name('productos.index');

Route::middleware(['guest', 'session.cache'])->group(function (): void {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1')->name('login.store');
    Route::post('/registro', [AuthController::class, 'register'])->middleware('throttle:6,1')->name('register.store');

    Route::get('/contrasena/olvidada', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/contrasena/olvidada', [PasswordResetController::class, 'sendLink'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/contrasena/restablecer/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/contrasena/restablecer', [PasswordResetController::class, 'update'])->middleware('throttle:5,1')->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware(['auth', 'session.cache'])
    ->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/mi-perfil', [ProfileController::class, 'edit'])->name('perfil.edit');
    Route::put('/mi-perfil', [ProfileController::class, 'update'])->name('perfil.update');
});

Route::get('/bienvenida', StaffWelcomeController::class)->middleware('auth')->name('staff.welcome');

Route::middleware(['auth', 'ai.access', 'session.cache'])->group(function (): void {
    Route::get('/simulador', [AiStudioController::class, 'index'])->name('ai.index');
    Route::post('/simulador', [AiStudioController::class, 'store'])->middleware('throttle:ai-generations')->name('ai.store');
    Route::get('/simulador/{generation}', [AiStudioController::class, 'show'])->name('ai.show');
    Route::get('/simulador/{generation}/estado', [AiStudioController::class, 'status'])->name('ai.status');
    Route::delete('/simulador/{generation}/imagenes', [AiStudioController::class, 'destroyMedia'])->name('ai.media.destroy');
});

Route::middleware(['auth', 'role:cliente', 'session.cache'])->group(function (): void {
    Route::get('/reservar', [ReservationController::class, 'create'])->name('reservas.create');
    Route::post('/reservar', [ReservationController::class, 'store'])->name('reservas.store');
    Route::get('/mis-reservas', [ReservationController::class, 'index'])->name('reservas.index');
    Route::patch('/mis-reservas/{reserva}/cancelar', [ReservationController::class, 'cancel'])->name('reservas.cancel');
});

Route::get('/simulador/{generation}/imagen/{kind}', [AiStudioController::class, 'image'])
    ->middleware('auth')
    ->whereIn('kind', ['input', 'output'])
    ->name('ai.image');

Route::get('/reservas/{reserva}/imagen-simulada', [ReservationController::class, 'simulatedImage'])
    ->middleware('auth')
    ->name('reservas.simulated-image');

Route::middleware(['auth', 'role:admin', 'session.cache'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/servicios', [ServiceController::class, 'index'])->name('servicios.index');
    Route::post('/categorias', [ServiceController::class, 'storeCategory'])->name('categorias.store');
    Route::delete('/categorias/{categoria}', [ServiceController::class, 'destroyCategory'])->name('categorias.destroy');
    Route::post('/servicios', [ServiceController::class, 'store'])->name('servicios.store');
    Route::put('/servicios/{servicio}', [ServiceController::class, 'update'])->name('servicios.update');
    Route::delete('/servicios/{servicio}', [ServiceController::class, 'destroy'])->name('servicios.destroy');

    Route::get('/catalogo', [CatalogController::class, 'index'])->name('catalogo.index');
    Route::post('/catalogo', [CatalogController::class, 'store'])->name('catalogo.store');
    Route::put('/catalogo/{producto}', [CatalogController::class, 'update'])->name('catalogo.update');
    Route::delete('/catalogo/{producto}', [CatalogController::class, 'destroy'])->name('catalogo.destroy');

    Route::get('/usuarios', [UserController::class, 'index'])->name('usuarios.index');
    Route::post('/usuarios', [UserController::class, 'store'])->name('usuarios.store');
    Route::patch('/usuarios/{usuario}/activo', [UserController::class, 'toggleActivo'])->name('usuarios.toggle');
    Route::delete('/usuarios/{usuario}', [UserController::class, 'destroy'])->name('usuarios.destroy');
});

Route::middleware(['auth', 'role:peluquero', 'session.cache'])->prefix('admin')->name('peluquero.')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/disponibilidad', [AvailabilityController::class, 'edit'])->name('disponibilidad.edit');
    Route::put('/disponibilidad', [AvailabilityController::class, 'update'])->name('disponibilidad.update');
    Route::get('/solicitudes', [SolicitudController::class, 'index'])->name('solicitudes.index');
    Route::patch('/solicitudes/{reserva}', [SolicitudController::class, 'update'])->name('solicitudes.update');
    Route::get('/horas', [HoursController::class, 'index'])->name('horas.index');
    Route::patch('/horas', [HoursController::class, 'update'])->name('horas.update');
    Route::get('/reportes', [ReportController::class, 'export'])->name('reportes.export');
});


Route::redirect('/index.php', '/');
Route::redirect('/usuario/productos.php', '/productos');
Route::redirect('/usuario/nueva-reserva.php', '/reservar');
Route::redirect('/usuario/mis-reservas.php', '/mis-reservas');
Route::redirect('/auth/login.php', '/login');
Route::redirect('/admin/servicios.php', '/admin/servicios');
Route::redirect('/admin/catalogo.php', '/admin/catalogo');
Route::redirect('/admin/dashboard.php', '/admin/dashboard');
Route::redirect('/admin/disponibilidad.php', '/admin/disponibilidad');
Route::redirect('/admin/solicitudes.php', '/admin/solicitudes');
Route::redirect('/admin/horas.php', '/admin/horas');
Route::redirect('/admin/reporte-export.php', '/admin/reportes');
