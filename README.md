# Api-Practica-Rick-Morty

> API REST con Laravel 12, DDD, Arquitectura Hexagonal y patrón CQRS.
> Consume la API pública de Rick & Morty y soporta múltiples motores de persistencia (MySQL / MongoDB) intercambiables con un solo cambio de configuración.

**Stack:** PHP 8.2 · Laravel 12 · MySQL · MongoDB · XAMPP

---

## Tabla de Contenidos

1. [¿Qué es este proyecto?](#1-qué-es-este-proyecto)
2. [Prerequisitos](#2-prerequisitos)
3. [Instalación paso a paso](#3-instalación-paso-a-paso)
4. [Secuencia completa de comandos — Laravel 12](#4-secuencia-completa-de-comandos--laravel-12)
5. [Estructura de carpetas](#5-estructura-de-carpetas)
6. [Arquitectura — DDD + Hexagonal + CQRS](#6-arquitectura--ddd--hexagonal--cqrs)
7. [Explicación de cada archivo](#7-explicación-de-cada-archivo)
8. [Cambiar la fuente de datos](#8-cambiar-la-fuente-de-datos)
9. [Configuración de MongoDB](#9-configuración-de-mongodb)
10. [Endpoints de la API](#10-endpoints-de-la-api)
11. [Comandos de referencia](#11-comandos-de-referencia)
12. [Principios SOLID aplicados](#12-principios-solid-aplicados)
13. [Errores comunes](#13-errores-comunes)
14. [.gitignore recomendado](#14-gitignore-recomendado)
15. [Glosario](#15-glosario)

---

## 1. ¿Qué es este proyecto?

Este proyecto es una API REST en Laravel 12 que integra la API pública de Rick & Morty y permite persistir los personajes en **MySQL o MongoDB**, intercambiables sin tocar una sola línea de lógica de negocio.

Los patrones implementados:

- **DDD (Domain-Driven Design):** el código se organiza alrededor del dominio, no del framework.
- **Arquitectura Hexagonal (Ports & Adapters):** el núcleo de la aplicación está aislado de los detalles técnicos.
- **CQRS (Command Query Responsibility Segregation):** lectura y escritura tienen interfaces separadas en el dominio.
- **Inversión de Dependencias:** el dominio define contratos; la infraestructura los cumple.

> **¿Por qué importa?**
> Cambiar de MySQL a MongoDB no requiere tocar el Controller, el UseCase ni la Entidad.
> Solo cambias `DB_SOURCE=mongo` en el `.env`. Eso es lo que hace valiosa esta arquitectura.

---

## 2. Prerequisitos

| Herramienta | Versión mínima / Notas                  |
| ----------- | --------------------------------------- |
| XAMPP       | PHP 8.2+, Apache, MySQL 5.7+            |
| Composer    | v2.x                                    |
| Git         | Cualquier versión reciente              |
| PHP CLI     | Debe estar en el PATH del sistema       |
| MongoDB     | Instancia local o Atlas (si usas mongo) |
| ext-mongodb | DLL instalada en XAMPP (ver sección 9)  |

### Verificar PHP en el PATH (Windows)

```bash
php -v
composer -V
```

Si PHP no se reconoce, agrega `C:\xampp\php` al PATH del sistema:

> Panel de Control → Sistema → Variables de entorno → Path → Nueva

---

## 3. Instalación paso a paso

### Paso 1 — Crear el proyecto

```bash
cd C:\xampp\htdocs
composer create-project laravel/laravel api-practice
cd api-practice
```

### Paso 2 — Configurar el entorno

```bash
copy .env.example .env
php artisan key:generate
```

Edita `.env` con tus datos:

```env
# Base de datos MySQL
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=api_practice
DB_USERNAME=root
DB_PASSWORD=

# MongoDB
MONGODB_URI=mongodb://localhost:27017
MONGODB_DATABASE=api_practice

# Fuente activa: mysql | mongo
DB_SOURCE=mongo

# URL de la API externa
RICKANDMORTY_BASE_URL=https://rickandmortyapi.com/api
```

### Paso 3 — Crear la base de datos MySQL

```sql
CREATE DATABASE api_practice CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Paso 4 — Instalar la API (exclusivo Laravel 12)

```bash
php artisan install:api
```

> En Laravel 12 `routes/api.php` no existe por defecto. Este comando lo crea, instala Sanctum y registra el middleware de API. Sin él obtendrás 404 en todos los endpoints.

### Paso 5 — Instalar MongoDB (si lo usas)

```bash
composer require mongodb/laravel-mongodb
```

> Requiere la extensión `ext-mongodb` instalada en XAMPP. Ver [sección 9](#9-configuración-de-mongodb).

### Paso 6 — Crear la estructura de carpetas DDD

```bash
mkdir app\Domain\Characters\Contracts
mkdir app\Domain\Characters\Entities
mkdir app\Application\GetCharacters
mkdir app\Infrastructure\ExternalApis\RickAndMorty
mkdir app\Infrastructure\Persistence\Mysql
mkdir app\Infrastructure\Persistence\Mongo
mkdir app\Infrastructure\Support
mkdir app\Http\Resources
```

### Paso 7 — Configurar `config/database.php`

Agrega la conexión MongoDB y la variable de fuente activa:

```php
// En el array 'connections':
'mongodb' => [
    'driver'   => 'mongodb',
    'dsn'      => env('MONGODB_URI', 'mongodb://localhost:27017'),
    'database' => env('MONGODB_DATABASE', 'api_practice'),
],

// Al final del archivo, fuera de 'connections':
'character_source' => env('DB_SOURCE', 'mongo'),
```

### Paso 8 — Configurar `config/services.php`

```php
'rickandmorty' => [
    'base_url' => env('RICKANDMORTY_BASE_URL', 'https://rickandmortyapi.com/api'),
],
```

### Paso 9 — Generar clases con Artisan

```bash
php artisan make:command SyncCharactersCommand
php artisan make:provider RepositoryServiceProvider
php artisan make:controller Api/CharacterController
php artisan make:resource CharacterResource
```

### Paso 10 — Registrar el Service Provider

Edita `bootstrap/providers.php`:

```php
return [
    AppServiceProvider::class,
    App\Providers\RepositoryServiceProvider::class,
];
```

### Paso 11 — Limpiar cache y recargar autoloader

```bash
composer dump-autoload
php artisan optimize:clear
```

### Paso 12 — Ejecutar migraciones (solo MySQL)

```bash
php artisan migrate
```

> MongoDB no usa migraciones. Las colecciones se crean automáticamente al hacer el primer `save()`.

### Paso 13 — Sincronizar y levantar

```bash
php artisan characters:sync
php artisan serve
# → http://localhost:8000/api/characters
```

---

## 4. Secuencia completa de comandos — Laravel 12

```bash
# ── FASE 1: PROYECTO ──────────────────────────────────────────
composer create-project laravel/laravel api-practice
cd api-practice

# ── FASE 2: ENTORNO ───────────────────────────────────────────
copy .env.example .env
php artisan key:generate
# (editar .env con DB, MongoDB, DB_SOURCE, RICKANDMORTY_BASE_URL)

# ── FASE 3: API (EXCLUSIVO LARAVEL 12) ────────────────────────
php artisan install:api

# ── FASE 4: MONGODB (opcional) ────────────────────────────────
composer require mongodb/laravel-mongodb
# (instalar ext-mongodb.dll en XAMPP si no está — ver sección 9)

# ── FASE 5: MIGRACIONES ───────────────────────────────────────
php artisan make:migration create_characters_table
# (editar el archivo con las columnas)

# ── FASE 6: ESTRUCTURA DDD ────────────────────────────────────
mkdir app\Domain\Characters\Contracts
mkdir app\Domain\Characters\Entities
mkdir app\Application\GetCharacters
mkdir app\Infrastructure\ExternalApis\RickAndMorty
mkdir app\Infrastructure\Persistence\Mysql
mkdir app\Infrastructure\Persistence\Mongo
mkdir app\Infrastructure\Support
mkdir app\Http\Resources
# (crear los archivos PHP en cada carpeta)

# ── FASE 7: GENERAR CLASES ────────────────────────────────────
php artisan make:command SyncCharactersCommand
php artisan make:provider RepositoryServiceProvider
php artisan make:controller Api/CharacterController
php artisan make:resource CharacterResource
# (registrar RepositoryServiceProvider en bootstrap/providers.php)
# (configurar config/database.php y config/services.php)

# ── FASE 8: LIMPIAR CACHE ─────────────────────────────────────
composer dump-autoload
php artisan optimize:clear

# ── FASE 9: BASE DE DATOS ─────────────────────────────────────
php artisan migrate   # solo si usas MySQL

# ── FASE 10: DATOS Y SERVIDOR ─────────────────────────────────
php artisan characters:sync
php artisan serve
# → http://localhost:8000/api/characters
```

---

## 5. Estructura de carpetas

```
api-practice/
├── app/
│   ├── Domain/                              ← NÚCLEO. PHP puro, sin dependencias externas.
│   │   └── Characters/
│   │       ├── Contracts/
│   │       │   ├── CharacterQueryRepository.php    ← PUERTO de lectura (CQRS)
│   │       │   └── CharacterCommandRepository.php  ← PUERTO de escritura (CQRS)
│   │       └── Entities/
│   │           └── Character.php           ← ENTIDAD de dominio (readonly)
│   │
│   ├── Application/                         ← CASOS DE USO. Orquesta el dominio.
│   │   └── GetCharacters/
│   │       └── GetCharactersUseCase.php
│   │
│   ├── Infrastructure/                      ← ADAPTADORES. Implementaciones concretas.
│   │   ├── ExternalApis/
│   │   │   └── RickAndMorty/
│   │   │       └── RickAndMortyRepository.php  ← Adaptador API externa (solo lectura)
│   │   ├── Persistence/
│   │   │   ├── Mysql/
│   │   │   │   └── MysqlCharacterRepository.php   ← Adaptador MySQL (lectura + escritura)
│   │   │   └── Mongo/
│   │   │       └── MongoCharacterRepository.php   ← Adaptador MongoDB (lectura + escritura)
│   │   └── Support/
│   │       └── CharacterMeta.php           ← Metadata de la fuente activa
│   │
│   ├── Http/                                ← ADAPTADOR DE ENTRADA HTTP
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       └── CharacterController.php
│   │   └── Resources/
│   │       └── CharacterResource.php       ← Transformador JSON
│   │
│   ├── Console/                             ← ADAPTADOR DE ENTRADA CLI
│   │   └── Commands/
│   │       └── SyncCharactersCommand.php
│   │
│   └── Providers/
│       ├── AppServiceProvider.php
│       └── RepositoryServiceProvider.php   ← Conecta puertos con adaptadores dinámicamente
│
├── bootstrap/
│   ├── app.php
│   └── providers.php
│
├── config/
│   ├── database.php                        ← Conexiones MySQL + MongoDB + character_source
│   └── services.php                        ← URL de la API externa
│
├── database/
│   └── migrations/
│       └── xxxx_create_characters_table.php
│
└── routes/
    └── api.php
```

---

## 6. Arquitectura — DDD + Hexagonal + CQRS

### 6.1 Las tres capas de DDD

| Capa             | Responsabilidad                                                               |
| ---------------- | ----------------------------------------------------------------------------- |
| `Domain`         | Modela el negocio. Entidades y contratos. **Sin imports de Laravel o MySQL.** |
| `Application`    | Orquesta el dominio en casos de uso concretos.                                |
| `Infrastructure` | Implementaciones técnicas: MySQL, MongoDB, HTTP externo.                      |

### 6.2 CQRS — Separación de lectura y escritura

La evolución más importante del proyecto: en lugar de una sola interfaz `CharacterRepositoryInterface`, ahora hay **dos puertos separados**:

```php
// Solo lectura
interface CharacterQueryRepository {
    public function findAll(): array;
}

// Solo escritura
interface CharacterCommandRepository {
    public function save(Character $character): void;
}
```

**¿Por qué separar?**

- `GetCharactersUseCase` solo necesita leer → depende de `CharacterQueryRepository`.
- `SyncCharactersCommand` solo necesita escribir → depende de `CharacterCommandRepository`.
- `RickAndMortyRepository` es **read-only** → implementa solo `CharacterQueryRepository` y lanza `LogicException` si alguien intenta `save()`.
- MySQL y MongoDB implementan **ambas** interfaces porque pueden leer y escribir.

### 6.3 El flujo completo de una petición HTTP

```
GET /api/characters
        ↓
CharacterController          ← Adaptador entrada HTTP
        ↓ ejecuta
GetCharactersUseCase         ← Caso de uso (Application)
        ↓ usa
CharacterQueryRepository     ← Puerto de lectura (Domain)
        ↓ resuelto por DI según DB_SOURCE
MysqlCharacterRepository     ← Adaptador MySQL
  o MongoCharacterRepository ← Adaptador MongoDB
        ↓ retorna
Character[]                  ← Entidades de dominio
        ↓ transformadas por
CharacterResource            ← API Resource (Http layer)
        ↓
JSON Response con meta + data
```

### 6.4 El flujo del comando de sincronización

```
php artisan characters:sync
        ↓
SyncCharactersCommand        ← Adaptador entrada CLI
        ↓ lee con
RickAndMortyRepository       ← CharacterQueryRepository (API externa)
        ↓ persiste con
CharacterCommandRepository   ← Resuelto como MySQL o Mongo según DB_SOURCE
```

### 6.5 Resolución dinámica en el Service Provider

```php
// Lee DB_SOURCE del .env
$source = config('database.character_source', 'mongo');

// Crea la instancia correcta
$repo = match ($source) {
    'mysql' => $app->make(MysqlCharacterRepository::class),
    'mongo' => $app->make(MongoCharacterRepository::class),
    default => throw new \InvalidArgumentException("Invalid DB_SOURCE: {$source}"),
};

// Registra la MISMA instancia para ambas interfaces
$this->app->bind(CharacterQueryRepository::class,   fn() => $repo);
$this->app->bind(CharacterCommandRepository::class, fn() => $repo);
```

---

## 7. Explicación de cada archivo

### 7.1 `Character.php` — La Entidad

```php
readonly class Character
{
    public function __construct(
        public int $id,
        public string $name,
        public string $status,
        public string $species,
        public string $image,
    ) {}
}
```

`readonly` garantiza inmutabilidad total. Una vez creado, no puede modificarse. PHP puro — ningún import de Laravel.

### 7.2 `CharacterQueryRepository.php` — Puerto de lectura

```php
interface CharacterQueryRepository
{
    /** @return Character[] */
    public function findAll(): array;
}
```

### 7.3 `CharacterCommandRepository.php` — Puerto de escritura

```php
interface CharacterCommandRepository
{
    public function save(Character $character): void;
}
```

### 7.4 `GetCharactersUseCase.php`

```php
class GetCharactersUseCase
{
    public function __construct(
        private CharacterQueryRepository $repository  // solo necesita leer
    ) {}

    public function execute(): array
    {
        return $this->repository->findAll();
        // Aquí puedes agregar: permisos, cache, logging, eventos...
    }
}
```

### 7.5 `RickAndMortyRepository.php` — Adaptador API externa

```php
class RickAndMortyRepository implements CharacterQueryRepository
{
    public function __construct(
        private HttpFactory $http,   // inyectado, no Facade
        private string $baseUrl      // desde config/services.php
    ) {}

    public function findAll(): array
    {
        $response = $this->http
            ->baseUrl($this->baseUrl)
            ->acceptJson()
            ->get('/character')
            ->throw();  // falla rápido si la API falla

        if (!isset($data['results']) || !is_array($data['results'])) {
            throw new \UnexpectedValueException('Invalid API response structure');
        }

        return collect($data['results'])
            ->map(fn(array $item) => $this->toDomain($item))
            ->all();
    }

    public function save(Character $character): void
    {
        throw new \LogicException('RickAndMortyRepository is read-only');
    }

    private function toDomain(array $item): Character  // mapper centralizado
    {
        return new Character(
            id:      (int)    ($item['id']      ?? 0),
            name:    (string) ($item['name']    ?? ''),
            status:  (string) ($item['status']  ?? ''),
            species: (string) ($item['species'] ?? ''),
            image:   (string) ($item['image']   ?? ''),
        );
    }
}
```

**Mejoras clave vs versión anterior:**

- `Http::withoutVerifying()` eliminado — `HttpFactory` inyectada limpia.
- `baseUrl` viene de `config/services.php`, no hardcodeado.
- `.throw()` en lugar de `if (!$successful) return []` — fail fast explícito.
- Mapper `toDomain()` extraído a método privado reutilizable.

### 7.6 `MysqlCharacterRepository.php` y `MongoCharacterRepository.php`

Ambos siguen el mismo patrón — implementan las dos interfaces y usan mappers centralizados:

```php
class MysqlCharacterRepository implements CharacterQueryRepository, CharacterCommandRepository
{
    public function __construct(
        private Connection $db  // inyectado, no Facade DB::
    ) {}

    public function findAll(): array
    {
        return $this->db->table($this->table)->get()
            ->map(fn($row) => $this->toDomain($row))
            ->all();
    }

    public function save(Character $character): void
    {
        $this->db->table($this->table)->updateOrInsert(
            ['id' => $character->id],
            $this->toPersistence($character)
        );
    }

    private function toDomain(object $row): Character { /* Infra → Domain */ }
    private function toPersistence(Character $c): array { /* Domain → Infra */ }
}
```

**Diferencia clave vs versión anterior:** se inyecta `Connection $db` en lugar de la Facade `DB::`. Esto hace los repositorios completamente **testeables con mocks**.

### 7.7 `CharacterController.php`

```php
class CharacterController extends Controller
{
    public function __construct(
        private GetCharactersUseCase $useCase,
        private CharacterMeta $meta          // metadata de la fuente activa
    ) {}

    public function __invoke(): JsonResponse
    {
        try {
            $characters = $this->useCase->execute();

            return response()->json([
                'meta' => $this->buildMeta(),
                'data' => CharacterResource::collection($characters),
            ], 200);

        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'meta'  => ['source' => $this->meta->source],
                'error' => [
                    'message' => 'Unexpected error',
                    'details' => config('app.debug') ? $e->getMessage() : null,
                ],
            ], 500);
        }
    }
}
```

**Novedades vs versión anterior:**

- `try/catch` con `report($e)` para logs reales en producción.
- `CharacterResource::collection()` transforma las entidades antes de enviarlas.
- `meta` en la respuesta indica qué fuente de datos está activa.
- `details` solo se expone en modo debug — no se filtran errores internos en producción.

### 7.8 `CharacterResource.php` — Transformador JSON

```php
class CharacterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'      => $this->id,
            'name'    => $this->name,
            'status'  => $this->status,
            'species' => $this->species,
            'image'   => $this->image,
        ];
    }
}
```

Controla exactamente qué campos ve el cliente. Si `Character` crece a 20 propiedades internas, el Resource sigue exponiendo solo estas 5.

### 7.9 `CharacterMeta.php` — Metadata de infraestructura

```php
class CharacterMeta
{
    public function __construct(
        public string $source,
        public string $host = 'unknown',
        public string $database = 'unknown',
        public ?string $dsn = null,
    ) {}
}
```

Objeto simple que el Controller incluye en la respuesta para indicar qué motor está sirviendo los datos. Útil para confirmar que el cambio de `DB_SOURCE` funcionó.

### 7.10 `SyncCharactersCommand.php`

```php
public function handle(
    RickAndMortyRepository $apiRepo,
    CharacterCommandRepository $persistenceRepo  // resuelto según DB_SOURCE
): void {
    try {
        $characters = $apiRepo->findAll();
        foreach ($characters as $character) {
            $persistenceRepo->save($character);
        }
        $this->info('✅ Sincronización completada: ' . count($characters) . ' personajes procesados.');
    } catch (\Throwable $e) {
        report($e);
        $this->error('❌ Error durante la sincronización.');
        if (config('app.debug')) {
            $this->line($e->getMessage());
        }
    }
}
```

**Mejoras:** manejo de errores con `try/catch`, `report($e)` para producción, y mensaje de error condicional según modo debug.

---

## 8. Cambiar la fuente de datos

Con esta arquitectura, cambiar el motor de persistencia es **una línea en el `.env`**:

```env
# Usar MongoDB
DB_SOURCE=mongo

# Usar MySQL
DB_SOURCE=mysql
```

Luego limpia la cache:

```bash
php artisan optimize:clear
```

El Controller, el UseCase y la Entidad no se tocan. El `RepositoryServiceProvider` lee `DB_SOURCE` y conecta automáticamente el adaptador correcto.

### Árbol de adaptadores

```
CharacterQueryRepository (lectura)
    ├── RickAndMortyRepository   → API externa (read-only)
    ├── MysqlCharacterRepository → MySQL
    └── MongoCharacterRepository → MongoDB

CharacterCommandRepository (escritura)
    ├── MysqlCharacterRepository → MySQL
    └── MongoCharacterRepository → MongoDB
```

---

## 9. Configuración de MongoDB

### Paso 1 — Instalar la extensión PHP (XAMPP Windows)

Ve a **https://pecl.php.net/package/mongodb** y descarga el `.zip` correcto:

| Tu configuración    | Qué elegir         |
| ------------------- | ------------------ |
| PHP 8.2             | `8.2` en el nombre |
| Windows 64 bits     | `x64`              |
| XAMPP (Thread Safe) | `ts` (no `nts`)    |

Ejemplo: `php_mongodb-1.21.0-8.2-ts-vs16-x64.zip`

### Paso 2 — Copiar la DLL

```
C:\xampp\php\ext\php_mongodb.dll
```

### Paso 3 — Activar en `php.ini`

```ini
extension=mongodb
```

### Paso 4 — Reiniciar Apache y verificar

```bash
php -m | findstr mongodb
```

Si aparece `mongodb`, está activo.

### Paso 5 — Instalar el paquete Laravel

```bash
composer require mongodb/laravel-mongodb
```

### Paso 6 — Agregar conexión en `config/database.php`

```php
'mongodb' => [
    'driver'   => 'mongodb',
    'dsn'      => env('MONGODB_URI', 'mongodb://localhost:27017'),
    'database' => env('MONGODB_DATABASE', 'api_practice'),
],
```

> MongoDB **no necesita migraciones**. Las colecciones se crean automáticamente al ejecutar `php artisan characters:sync`.

---

## 10. Endpoints de la API

| Método | Ruta              | Descripción                                         |
| ------ | ----------------- | --------------------------------------------------- |
| `GET`  | `/api/characters` | Retorna todos los personajes desde la fuente activa |

### Respuesta exitosa

```json
{
    "meta": {
        "source": "mongo",
        "database": "api_practice",
        "dsn": "mongodb://localhost:27017"
    },
    "data": [
        {
            "id": 1,
            "name": "Rick Sanchez",
            "status": "Alive",
            "species": "Human",
            "image": "https://rickandmortyapi.com/api/character/avatar/1.jpeg"
        }
    ]
}
```

### Respuesta de error (modo debug activo)

```json
{
    "meta": { "source": "mongo" },
    "error": {
        "message": "Unexpected error",
        "details": "Connection refused mongodb://localhost:27017"
    }
}
```

---

## 11. Comandos de referencia

### Desarrollo diario

| Comando                             | Qué hace                                    |
| ----------------------------------- | ------------------------------------------- |
| `php artisan serve`                 | Levanta servidor en `http://localhost:8000` |
| `php artisan migrate`               | Ejecuta migraciones pendientes (solo MySQL) |
| `php artisan migrate:fresh`         | Borra todas las tablas y re-migra           |
| `php artisan migrate:rollback`      | Deshace la última migración                 |
| `php artisan migrate:status`        | Muestra estado de cada migración            |
| `php artisan characters:sync`       | Sincroniza personajes hacia el motor activo |
| `php artisan route:list --path=api` | Lista rutas de la API                       |
| `php artisan optimize:clear`        | Limpia TODA la cache de una vez             |
| `composer dump-autoload`            | Regenera el mapa de clases                  |
| `php artisan tinker`                | REPL interactivo                            |
| `php artisan pail`                  | Logs en tiempo real                         |
| `php artisan about`                 | Info general del proyecto                   |

### Generadores `make:`

| Comando                                                    | Crea                         |
| ---------------------------------------------------------- | ---------------------------- |
| `php artisan make:controller NombreController --invokable` | Controller de un solo método |
| `php artisan make:migration nombre_migracion`              | Archivo de migración         |
| `php artisan make:provider NombreProvider`                 | Service Provider             |
| `php artisan make:command NombreCommand`                   | Comando Artisan              |
| `php artisan make:resource NombreResource`                 | API Resource                 |
| `php artisan make:request NombreRequest`                   | Form Request (validación)    |
| `php artisan make:interface Ruta/Nombre`                   | Interfaz PHP                 |
| `php artisan make:class Ruta/Nombre`                       | Clase PHP simple             |
| `php artisan make:test NombreTest --unit`                  | Test unitario                |

### Debugging

```bash
# Verificar qué implementación está activa
php artisan tinker
>>> app(App\Domain\Characters\Contracts\CharacterQueryRepository::class)

# Ver qué valor tiene DB_SOURCE
php artisan tinker
>>> config('database.character_source')

# Ver logs en tiempo real
php artisan pail
```

---

## 12. Principios SOLID aplicados

| Principio                     | Cómo se aplica                                                                                                        |
| ----------------------------- | --------------------------------------------------------------------------------------------------------------------- |
| **S** — Single Responsibility | Cada clase tiene una sola razón para cambiar. Controller = HTTP. UseCase = lógica. Repositorios = persistencia.       |
| **O** — Open/Closed           | Puedes agregar `PostgresCharacterRepository` sin modificar nada existente.                                            |
| **L** — Liskov Substitution   | `MysqlCharacterRepository` y `MongoCharacterRepository` son intercambiables porque implementan las mismas interfaces. |
| **I** — Interface Segregation | CQRS separa lectura de escritura. `RickAndMortyRepository` solo implementa la que necesita.                           |
| **D** — Dependency Inversion  | Todo depende de interfaces del dominio. El `RepositoryServiceProvider` resuelve implementaciones en runtime.          |

---

## 13. Errores comunes

| Error                                    | Solución                                                                            |
| ---------------------------------------- | ----------------------------------------------------------------------------------- |
| `Target [Interface] is not instantiable` | `RepositoryServiceProvider` no registrado en `bootstrap/providers.php`              |
| `Class not found`                        | Namespace incorrecto o falta `composer dump-autoload`                               |
| `404 en /api/characters`                 | Falta ejecutar `php artisan install:api` (Laravel 12)                               |
| `SQLSTATE: No such table`                | Falta ejecutar `php artisan migrate`                                                |
| `ext-mongodb missing`                    | Instalar `php_mongodb.dll` en `C:\xampp\php\ext\` y activar en `php.ini`            |
| `Connection refused mongodb`             | MongoDB no está corriendo. Inícialo o cambia a `DB_SOURCE=mysql`                    |
| `Invalid DB_SOURCE`                      | El valor en `.env` no es `mysql` ni `mongo`                                         |
| `RickAndMortyRepository is read-only`    | Estás llamando `save()` en el adaptador de API. Solo MySQL y Mongo pueden escribir. |
| Cambio de `DB_SOURCE` no surte efecto    | Ejecuta `php artisan optimize:clear`                                                |
| `500` sin detalle en respuesta           | Activa `APP_DEBUG=true` en `.env` para ver el mensaje real                          |

---

## 14. .gitignore recomendado

```gitignore
# Dependencias
/vendor/
/node_modules/

# Entorno (NUNCA subir al repo)
.env
.env.backup
.env.*.local

# Base de datos local
*.sqlite
*.sqlite-journal

# Cache y compilados de Laravel
/bootstrap/cache/*.php
/storage/*.key
/storage/logs/
/storage/framework/cache/
/storage/framework/sessions/
/storage/framework/views/

# Subidas de usuarios
/public/uploads/
/storage/app/public/

# IDE y sistema operativo
.vscode/
.idea/
*.DS_Store
Thumbs.db

# Scripts de exportación/utilidad local
export_code.ps1
estructura_codigo.txt

# Testing
.phpunit.result.cache
/coverage/
```

### Quitar un archivo ya subido a git

```bash
git rm --cached nombre-del-archivo
git rm --cached -r nombre-de-carpeta/
git commit -m "chore: remove unnecessary files and add to gitignore"
git push
```

---

## 15. Glosario

| Término                       | Definición                                                                          |
| ----------------------------- | ----------------------------------------------------------------------------------- |
| **DDD**                       | Domain-Driven Design. Organiza el código alrededor del dominio de negocio.          |
| **Arquitectura Hexagonal**    | Aísla el núcleo de la app de sus puertos de entrada/salida.                         |
| **CQRS**                      | Command Query Responsibility Segregation. Separa interfaces de lectura y escritura. |
| **Entidad**                   | Objeto con identidad única. `Character` tiene un `id` que lo identifica.            |
| **Puerto (Port)**             | Interfaz del dominio que define un contrato.                                        |
| **Adaptador (Adapter)**       | Implementación concreta de un puerto.                                               |
| **Caso de Uso**               | Clase que orquesta una operación de negocio.                                        |
| **Service Provider**          | Clase de Laravel que registra bindings en el contenedor de dependencias.            |
| **Singleton**                 | Binding donde Laravel crea solo una instancia y la reutiliza.                       |
| **Binding**                   | Registro que dice "cuando pidan X, entrega Y".                                      |
| **Inyección de Dependencias** | Las dependencias se reciben en el constructor, no se instancian dentro.             |
| **Inmutabilidad**             | Un objeto `readonly` no puede cambiar después de crearse.                           |
| **Mapper**                    | Método que transforma entre representaciones: `toDomain()` y `toPersistence()`.     |
| **Hidratación**               | Convertir datos crudos (array/stdClass) en objetos del dominio.                     |
| **Upsert**                    | Operación de BD: actualiza si existe, inserta si no (`updateOrInsert`).             |
| **Fail Fast**                 | Detectar y lanzar errores lo antes posible en lugar de silenciarlos.                |
| **API Resource**              | Clase de Laravel que controla la forma exacta del JSON de respuesta.                |

---

_Laravel 12 · PHP 8.2 · DDD · Arquitectura Hexagonal · CQRS · MySQL · MongoDB · Rick & Morty API_
