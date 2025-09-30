## Propósito

Guía corta para agentes IA que harán cambios en este repositorio Laravel (PHP 8.1, Laravel 10). Contiene el panorama general, patrones específicos del código, comandos de desarrollo y puntos de atención concretos.

## Quick start (comandos que debe ejecutar un desarrollador humano)

- Instalar dependencias PHP: `composer install`
- Preparar entorno: copiar `.env.example -> .env` y ajustar credenciales de DB
- Generar clave: `php artisan key:generate`
- Servir (dev): `php artisan serve --host=127.0.0.1 --port=8000`
- Frontend: `npm install` y `npm run dev` (usa Vite)
- Tests: `./vendor/bin/phpunit` o `php artisan test`
- Linter: `./vendor/bin/pint` (laravel/pint está en require-dev)

## Big picture — arquitectura y flujos

- Aplicación Laravel monolítica que expone API REST en `routes/api.php`. Muchas rutas usan middleware `auth:sanctum` (autenticación por tokens Sanctum).
- El backend está estrechamente acoplado a un esquema de base de datos existente con tablas y vistas legacy (nombres en español y prefijos como `cat_`, `det_`). Ejemplos clave: `cat_usuarios`, `det_requisicion`, `requisiciones_view`, `products_details`.
- Los controladores en `app/Http/Controllers` manejan la lógica y frecuentemente usan Eloquent y consultas con `DB::table` o `whereRaw`. Las respuestas estándar se envían vía `App\Models\ApiResponse::success/error`.
- Autorización/roles: se usan campos en el usuario (por ejemplo `Rol`, `Usuario`, `IDDepartamento`) y lógica con `Auth::user()->Rol` para condicionar consultas y permisos.

## Patrones y convenciones del proyecto (útiles para editar o generar código)

- Modelos: casi todos los modelos definen `protected $table`, `protected $primaryKey` y `public $timestamps = false` (DB legacy). Evita eliminar esas configuraciones cuando modifiques modelos.
- Respuestas: usa siempre `ApiResponse::success(...)` y `ApiResponse::error(...)` para mantener el formato JSON uniforme.
- Transacciones: endpoints que crean/actualizan registros usan `DB::beginTransaction()`, `DB::commit()` y `DB::rollBack()` — conservar este patrón para operaciones compuestas.
- Consultas raw: varios endpoints aceptan `$request->sql` y usan `whereRaw`. Esto existe en rutas como `GET /api/users/index` o `POST /api/requisiciones/index`. Trátalo como código legacy: NO introducir automáticamente inputs sin sanitizar.
- Controladores llaman a otros controladores directamente, p.ej. `(new AutorizadoresController())->create($request)` — pattern a tener en cuenta al refactorizar.

## Archivos y rutas importantes (leer primero)

- `routes/api.php` — estructura de endpoints y middleware
- `app/Models/ApiResponse.php` — formato de respuesta JSON
- `app/Http/Controllers/UsersController.php` — login, creación de usuarios y ejemplos de lógica de roles
- `app/Http/Controllers/RequisicionesController.php` — transacciones, uso de vistas DB y manejo de `det_requisicion`
- `app/Models/*.php` — modelos están fuertemente mapeados a tablas legacy

## Puntos de atención (riesgos y atajos detectados)

- Seguridad: contraseñas se comparan en texto (`$user->Password === $credentials['Password']`). No cambiar el comportamiento sin plan de migración (hashing) y actualizar clientes.
- Inyección SQL: la API permite `sql` en requests y luego llama `whereRaw`. Cualquier cambio automático debe mitigar inyección y validar/escapar o migrar a parámetros enlazados.
- Nombres en español y convenciones de columnas (IDRequisicion, Ejercicio, Usuario, Rol). Use esos nombres exactos cuando modifique queries o serializaciones.
- Vistas y tablas derivadas: `requisiciones_view`, `products_details` son consultadas directamente; no suponer que exista migración para ellas.

## Cómo depurar y probar rápidamente

- Para depurar peticiones autenticadas: primero obtener token con `POST /api/auth/login` (cuerpo JSON con Usuario y Password). Luego usar el header `Authorization: Bearer <token>`.
- Logs: usar `storage/logs/laravel.log` y `Log::error()` ya presente en controladores.
- Aumentos temporales de memoria: `RequisicionesController::index` sube `memory_limit` — tenlo en cuenta al reproducir cargas.

## Cambios aceptables para PRs automáticos

- Corregir errores triviales (typos, nombres de variables inconsistentes) que no cambien el contrato de la API.
- Normalizar respuestas para que siempre usen `ApiResponse` si un endpoint nuevo debe añadirse.

## Cambios que requieren revisión humana

- Cambios en la gestión de contraseñas (migración a hash). Requiere coordinación con clientes y despliegue.
- Eliminar o restringir la posibilidad de enviar `sql` en la API: requiere rediseño de queries y pruebas.

---
Si algo aquí no está claro o faltan rutas/archivos que quieras que detalle, dime qué parte quieres que expanda y actualizo este archivo.
