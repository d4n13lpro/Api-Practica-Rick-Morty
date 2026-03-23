# API Practice — Laravel 12 + DDD + Arquitectura Hexagonal

> Integración con la API pública de Rick & Morty usando Domain-Driven Design y Arquitectura Hexagonal en Laravel 12.

**Stack:** PHP 8.2 · Laravel 12 · MySQL · XAMPP

---

## Tabla de Contenidos

1. [¿Qué es este proyecto?](#1-qué-es-este-proyecto)
2. [Prerequisitos](#2-prerequisitos)
3. [Instalación paso a paso](#3-instalación-paso-a-paso)
4. [Secuencia completa de comandos — Laravel 12](#4-secuencia-completa-de-comandos--laravel-12)
5. [Estructura de carpetas](#5-estructura-de-carpetas)
6. [Teoría: DDD y Arquitectura Hexagonal](#6-teoría-ddd-y-arquitectura-hexagonal)
7. [Explicación de cada archivo](#7-explicación-de-cada-archivo)
8. [Inversión de Dependencias](#8-inversión-de-dependencias)
9. [Comandos de referencia](#9-comandos-de-referencia)
10. [Endpoints de la API](#10-endpoints-de-la-api)
11. [Cómo cambiar la fuente de datos](#11-cómo-cambiar-la-fuente-de-datos)
12. [Principios SOLID aplicados](#12-principios-solid-aplicados)
13. [Qué sigue](#13-qué-sigue)
14. [Errores comunes](#14-errores-comunes)
15. [.gitignore recomendado](#15-gitignore-recomendado)
16. [Glosario](#16-glosario)

---

## 1. ¿Qué es este proyecto?

Este proyecto es una API REST construida con Laravel 12 que consume la API pública de Rick & Morty, almacena los personajes en MySQL y los expone mediante un endpoint propio. Su propósito principal no es el producto en sí, sino aprender a estructurar código PHP con los patrones más demandados en la industria:

- **Domain-Driven Design (DDD):** organizar el código alrededor del negocio, no alrededor del framework.
- **Arquitectura Hexagonal (Ports & Adapters):** aislar la lógica de negocio de los detalles técnicos (HTTP, MySQL, APIs externas).
- **Inversión de Dependencias:** el dominio no depende de la infraestructura; la infraestructura depende del dominio.

> **¿Por qué importa?**
> Imagina que mañana decides cambiar de MySQL a MongoDB, o de Rick & Morty API a otra fuente de datos.
> Con esta arquitectura, solo cambias **un archivo** en `Infrastructure`. El dominio y los casos de uso no se tocan.

---

## 2. Prerequisitos

| Herramienta | Versión mínima                    |
| ----------- | --------------------------------- |
| XAMPP       | PHP 8.2+, Apache, MySQL 5.7+      |
| Composer    | v2.x                              |
| Git         | Cualquier versión reciente        |
| PHP CLI     | Debe estar en el PATH del sistema |

### Verificar que PHP está en el PATH (Windows)

```bash
php -v
composer -V
```

Si PHP no se reconoce, agrega al PATH: `C:\xampp\php`

> Panel de Control → Sistema → Variables de entorno → Path → Nueva → `C:\xampp\php`

---

## 3. Instalación paso a paso

### Paso 1 — Crear el proyecto Laravel

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

Edita `.env` con tus datos de MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=api_practice
DB_USERNAME=root
DB_PASSWORD=
```

### Paso 3 — Crear la base de datos

```sql
-- Desde phpMyAdmin o MySQL CLI
CREATE DATABASE api_practice CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Paso 4 — Instalar la API (exclusivo Laravel 12)

```bash
php artisan install:api
```

> **¿Por qué este comando?** En Laravel 12, `routes/api.php` no existe por defecto. Este comando lo crea, instala Sanctum y registra el middleware de API. Sin él, obtendrás 404 en todos tus endpoints.

### Paso 5 — Crear la estructura de carpetas DDD

```bash
mkdir app\Domain\Characters\Contracts
mkdir app\Domain\Characters\Entities
mkdir app\Application\GetCharacters
mkdir app\Infrastructure\ExternalApis\RickAndMorty
mkdir app\Infrastructure\Persistence\Mysql
```

### Paso 6 — Crear la migración

```bash
php artisan make:migration create_characters_table
```

Edita el archivo generado en `database/migrations/`:

```php
Schema::create('characters', function (Blueprint $table) {
    $table->unsignedBigInteger('id')->primary(); // ID de la API como PK
    $table->string('name');
    $table->string('status');
    $table->string('species');
    $table->string('image');
    $table->timestamps();
});
```

### Paso 7 — Generar clases con Artisan

```bash
php artisan make:command SyncCharactersCommand
php artisan make:provider RepositoryServiceProvider
php artisan make:controller Api/CharacterController
```

### Paso 8 — Registrar el Service Provider

Edita `bootstrap/providers.php`:

```php
return [
    AppServiceProvider::class,
    App\Providers\RepositoryServiceProvider::class,
];
```

### Paso 9 — Limpiar cache y recargar autoloader

```bash
composer dump-autoload
php artisan optimize:clear
```

### Paso 10 — Ejecutar migraciones

```bash
php artisan migrate
```

### Paso 11 — Sincronizar y levantar el servidor

```bash
php artisan characters:sync
php artisan serve
# → http://localhost:8000/api/characters
```

---

## 4. Secuencia completa de comandos — Laravel 12

Copia y ejecuta en orden:

```bash
# ── FASE 1: PROYECTO ──────────────────────────────────────────
composer create-project laravel/laravel api-practice
cd api-practice

# ── FASE 2: ENTORNO ───────────────────────────────────────────
copy .env.example .env        # Windows
# cp .env.example .env        # Mac/Linux
php artisan key:generate
# (editar .env con datos de MySQL)

# ── FASE 3: API (EXCLUSIVO LARAVEL 12) ────────────────────────
php artisan install:api

# ── FASE 4: MIGRACIONES ───────────────────────────────────────
php artisan make:migration create_characters_table
# (editar el archivo con las columnas)

# ── FASE 5: ESTRUCTURA DDD ────────────────────────────────────
mkdir app\Domain\Characters\Contracts
mkdir app\Domain\Characters\Entities
mkdir app\Application\GetCharacters
mkdir app\Infrastructure\ExternalApis\RickAndMorty
mkdir app\Infrastructure\Persistence\Mysql
# (crear los archivos PHP en cada carpeta)

# ── FASE 6: GENERAR CLASES ────────────────────────────────────
php artisan make:command SyncCharactersCommand
php artisan make:provider RepositoryServiceProvider
php artisan make:controller Api/CharacterController
# (registrar RepositoryServiceProvider en bootstrap/providers.php)

# ── FASE 7: LIMPIAR CACHE ─────────────────────────────────────
composer dump-autoload
php artisan optimize:clear

# ── FASE 8: BASE DE DATOS ─────────────────────────────────────
php artisan migrate

# ── FASE 9: DATOS Y SERVIDOR ──────────────────────────────────
php artisan characters:sync
php artisan serve
# → http://localhost:8000/api/characters
```

---

## 5. Estructura de carpetas

```
api-practice/
├── app/
│   ├── Domain/                         ← NÚCLEO. Sin dependencias externas.
│   │   └── Characters/
│   │       ├── Contracts/
│   │       │   └── CharacterRepositoryInterface.php  ← PUERTO (interfaz)
│   │       └── Entities/
│   │           └── Character.php       ← ENTIDAD de dominio
│   │
│   ├── Application/                    ← CASOS DE USO. Orquesta el dominio.
│   │   └── GetCharacters/
│   │       └── GetCharactersUseCase.php
│   │
│   ├── Infrastructure/                 ← ADAPTADORES. Implementaciones concretas.
│   │   ├── ExternalApis/
│   │   │   └── RickAndMorty/
│   │   │       └── RickAndMortyRepository.php   ← Adaptador API externa
│   │   └── Persistence/
│   │       └── Mysql/
│   │           └── MysqlCharacterRepository.php ← Adaptador base de datos
│   │
│   ├── Http/                           ← ENTRADA HTTP (Adaptador de entrada)
│   │   └── Controllers/
│   │       └── Api/
│   │           └── CharacterController.php
│   │
│   ├── Console/                        ← ENTRADA CLI (Adaptador de entrada)
│   │   └── Commands/
│   │       └── SyncCharactersCommand.php
│   │
│   └── Providers/
│       ├── AppServiceProvider.php
│       └── RepositoryServiceProvider.php  ← Conecta puertos con adaptadores
│
├── bootstrap/
│   ├── app.php
│   └── providers.php
│
├── database/
│   └── migrations/
│       └── xxxx_create_characters_table.php
│
└── routes/
    ├── api.php                         ← Rutas de la API
    └── web.php
```

---

## 6. Teoría: DDD y Arquitectura Hexagonal

### 6.1 Domain-Driven Design (DDD)

DDD es una filosofía de diseño creada por Eric Evans. Su idea central: **organiza tu código alrededor del problema de negocio, no alrededor del framework o la base de datos.**

| Capa             | Responsabilidad                                                                             |
| ---------------- | ------------------------------------------------------------------------------------------- |
| `Domain`         | Modela el problema de negocio. Entidades, Contratos. **NUNCA** depende de Laravel ni MySQL. |
| `Application`    | Orquesta el dominio para ejecutar casos de uso. Solo habla con el Domain.                   |
| `Infrastructure` | Implementaciones concretas: MySQL, API externa, etc. Depende de tecnologías específicas.    |

### 6.2 Arquitectura Hexagonal (Ports & Adapters)

El núcleo de tu aplicación (dominio + casos de uso) **no debe saber nada** sobre cómo llegan los datos ni cómo se guardan.

> **Analogía — Tomacorriente eléctrico:**
> El tomacorriente es la **interfaz** (`CharacterRepositoryInterface`).
> No le importa si conectas una lámpara o un televisor (MySQL o RickAndMorty API).
> Solo define el contrato: "dame 110V y 2 pines".
> El enchufe concreto (`RickAndMortyRepository` o `MysqlCharacterRepository`) es el **adaptador**.

- **Puertos (Ports):** las interfaces en `Domain/Characters/Contracts`. Definen **QUÉ** se puede hacer.
- **Adaptadores (Adapters):** las implementaciones en `Infrastructure`. Definen **CÓMO** se hace.

### 6.3 El flujo completo de una petición

```
HTTP Request
     ↓
CharacterController          ← Adaptador de ENTRADA (HTTP)
     ↓ llama a
GetCharactersUseCase         ← Caso de Uso (Application)
     ↓ usa la INTERFAZ
CharacterRepositoryInterface ← Puerto (Domain/Contracts)
     ↓ resuelto por DI como
RickAndMortyRepository       ← Adaptador de SALIDA (Infrastructure)
     ↓ retorna
Character[]                  ← Entidades de Dominio
     ↓
JSON Response
```

---

## 7. Explicación de cada archivo

### 7.1 `Character.php` — La Entidad

```php
// app/Domain/Characters/Entities/Character.php
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

`readonly class` (PHP 8.2+): una vez creado el objeto, sus propiedades no pueden cambiar. Esto garantiza **inmutabilidad** — un principio fundamental de DDD.

> Esta clase no importa nada de Laravel, ni de Illuminate, ni de MySQL. Es PHP puro. Podrías copiarla a cualquier framework y funcionaría igual.

### 7.2 `CharacterRepositoryInterface.php` — El Puerto

```php
// app/Domain/Characters/Contracts/CharacterRepositoryInterface.php
interface CharacterRepositoryInterface
{
    public function findAll(): array;
}
```

Esta interfaz es el **contrato**. Define que cualquier repositorio de personajes DEBE poder listarlos todos. No dice cómo. Solo dice qué.

> **Regla de oro:** el dominio define los contratos. La infraestructura los cumple. Nunca al revés.

### 7.3 `GetCharactersUseCase.php` — El Caso de Uso

```php
// app/Application/GetCharacters/GetCharactersUseCase.php
class GetCharactersUseCase
{
    public function __construct(
        private CharacterRepositoryInterface $repository  // inyecta la INTERFAZ
    ) {}

    public function execute(): array
    {
        return $this->repository->findAll();
    }
}
```

Aquí es donde en el mundo real agregarías: validación de permisos, cache, filtros de negocio, logging, disparo de eventos.

Recibe `CharacterRepositoryInterface`, **no** `RickAndMortyRepository`. El UseCase no sabe de dónde vienen los datos. Eso lo decide el Service Provider.

### 7.4 `RickAndMortyRepository.php` — Adaptador API Externa

```php
class RickAndMortyRepository implements CharacterRepositoryInterface
{
    public function findAll(): array
    {
        $response = Http::withoutVerifying()->get("{$this->baseUrl}/character");

        return collect($data['results'])->map(function (array $item) {
            return new Character(       // convierte JSON → Entidad de Dominio
                id: (int) $item['id'],
                name: (string) $item['name'],
                // ...
            );
        })->all();
    }
}
```

Transforma datos crudos de la API (array PHP) en objetos `Character` del dominio. Ese proceso se llama **hidratación de entidades**.

### 7.5 `MysqlCharacterRepository.php` — Adaptador Base de Datos

```php
class MysqlCharacterRepository implements CharacterRepositoryInterface
{
    public function findAll(): array
    {
        return DB::table($this->table)->get()->map(function ($item) {
            return new Character(id: (int) $item->id, /* ... */);
        })->all();
    }

    public function save(Character $character): void
    {
        DB::table($this->table)->updateOrInsert(  // upsert: inserta o actualiza
            ['id' => $character->id],
            ['name' => $character->name, /* ... */]
        );
    }
}
```

`save()` no está en la interfaz porque solo este adaptador tiene capacidad de persistir. El dominio no sabe que existe, y está bien así.

### 7.6 `RepositoryServiceProvider.php` — El Conector

```php
// app/Providers/RepositoryServiceProvider.php
public function register(): void
{
    $this->app->bind(
        CharacterRepositoryInterface::class,  // cuando alguien pida esto...
        RickAndMortyRepository::class         // ...dale esto.
    );
}
```

Este archivo **conecta los cables**. Le dice al contenedor de Laravel: "cuando alguien pida una instancia de `CharacterRepositoryInterface`, crea un `RickAndMortyRepository` y dáselo."

### 7.7 `CharacterController.php` — Adaptador de Entrada HTTP

```php
class CharacterController extends Controller
{
    public function __construct(
        private GetCharactersUseCase $getCharactersUseCase
    ) {}

    public function __invoke(): JsonResponse  // un solo método = una sola responsabilidad
    {
        $characters = $this->getCharactersUseCase->execute();

        return response()->json([
            'success' => true,
            'data'    => $characters,
        ]);
    }
}
```

`__invoke()` — Controller invokable: un controller, una acción. Perfecto para el principio de Responsabilidad Única (SRP).

---

## 8. Inversión de Dependencias

La **D** de SOLID: _"Los módulos de alto nivel no deben depender de módulos de bajo nivel. Ambos deben depender de abstracciones."_

```
SIN inversión (MAL):
  GetCharactersUseCase → RickAndMortyRepository
  Si cambias la API, tocas el UseCase. FRÁGIL.

CON inversión (BIEN):
  GetCharactersUseCase → CharacterRepositoryInterface ← RickAndMortyRepository
  Cambias el adaptador sin tocar el UseCase. SÓLIDO.
```

El `RepositoryServiceProvider` es quien une la abstracción con la implementación en tiempo de ejecución.

---

## 9. Comandos de referencia

### Desarrollo diario

| Comando                        | Qué hace                                      |
| ------------------------------ | --------------------------------------------- |
| `php artisan serve`            | Levanta servidor en `http://localhost:8000`   |
| `php artisan migrate`          | Ejecuta migraciones pendientes                |
| `php artisan migrate:fresh`    | Borra todas las tablas y re-migra             |
| `php artisan migrate:rollback` | Deshace la última migración                   |
| `php artisan migrate:status`   | Muestra estado de cada migración              |
| `php artisan characters:sync`  | Sincroniza personajes de Rick & Morty a MySQL |
| `php artisan route:list`       | Lista todas las rutas registradas             |
| `php artisan config:clear`     | Limpia cache de configuración                 |
| `php artisan cache:clear`      | Limpia cache de la aplicación                 |
| `php artisan route:clear`      | Limpia cache de rutas                         |
| `php artisan optimize:clear`   | Limpia TODA la cache de una vez               |
| `composer dump-autoload`       | Regenera el mapa de clases de Composer        |
| `php artisan tinker`           | REPL interactivo de Laravel                   |
| `php artisan pail`             | Logs en tiempo real                           |
| `php artisan about`            | Info general del proyecto                     |

### Generadores `make:`

| Comando                                                    | Crea                              |
| ---------------------------------------------------------- | --------------------------------- |
| `php artisan make:controller NombreController --invokable` | Controller de un solo método      |
| `php artisan make:model NombreModel`                       | Modelo Eloquent                   |
| `php artisan make:migration nombre_migracion`              | Archivo de migración              |
| `php artisan make:provider NombreProvider`                 | Service Provider                  |
| `php artisan make:command NombreCommand`                   | Comando Artisan                   |
| `php artisan make:request NombreRequest`                   | Form Request (validación)         |
| `php artisan make:resource NombreResource`                 | API Resource (transformador JSON) |
| `php artisan make:interface Ruta/NombreInterface`          | Interfaz PHP                      |
| `php artisan make:class Ruta/NombreClase`                  | Clase PHP simple                  |
| `php artisan make:test NombreTest --unit`                  | Test unitario                     |

### Debugging

```bash
# Ver rutas de la API solamente
php artisan route:list --path=api

# Verificar qué implementación está activa para la interfaz
php artisan tinker
>>> app(App\Domain\Characters\Contracts\CharacterRepositoryInterface::class)

# Ver logs en tiempo real
php artisan pail
```

---

## 10. Endpoints de la API

| Método | Ruta              | Descripción                                              |
| ------ | ----------------- | -------------------------------------------------------- |
| `GET`  | `/api/characters` | Retorna todos los personajes desde la fuente configurada |

### Ejemplo de respuesta

```json
{
    "success": true,
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

---

## 11. Cómo cambiar la fuente de datos

Este es el momento donde la arquitectura demuestra su valor. Para cambiar de "leer desde la API" a "leer desde MySQL", editas **un solo archivo**:

```php
// app/Providers/RepositoryServiceProvider.php

// ANTES — Lee desde la API externa
$this->app->bind(
    CharacterRepositoryInterface::class,
    RickAndMortyRepository::class
);

// DESPUÉS — Lee desde MySQL
$this->app->bind(
    CharacterRepositoryInterface::class,
    MysqlCharacterRepository::class
);
```

El Controller, el UseCase, la Entidad — **nada más cambia**.

> **Caso de uso real:**
>
> 1. Ejecutas `php artisan characters:sync` para cargar datos en MySQL.
> 2. Cambias el binding a `MysqlCharacterRepository`.
> 3. Tu API ahora lee desde tu propia base de datos, no desde la API externa.
> 4. Ventajas: más rápido, funciona offline, puedes filtrar y modificar los datos.

---

## 12. Principios SOLID aplicados

| Principio                     | Cómo se aplica                                                                                                                   |
| ----------------------------- | -------------------------------------------------------------------------------------------------------------------------------- |
| **S** — Single Responsibility | `CharacterController` solo maneja HTTP. `GetCharactersUseCase` solo ejecuta la lógica. `MysqlCharacterRepository` solo persiste. |
| **O** — Open/Closed           | Puedes agregar un `MongoRepository` sin modificar el UseCase ni el Controller.                                                   |
| **L** — Liskov Substitution   | `RickAndMortyRepository` y `MysqlCharacterRepository` son intercambiables porque ambos implementan la misma interfaz.            |
| **I** — Interface Segregation | La interfaz solo tiene `findAll()`. No fuerza métodos que no todos los adaptadores necesiten.                                    |
| **D** — Dependency Inversion  | `GetCharactersUseCase` depende de la interfaz, no de implementaciones concretas. El Service Provider resuelve en runtime.        |

---

## 13. Qué sigue

### Nuevos casos de uso

```bash
# Crear el archivo
php artisan make:class Application/GetCharacters/GetCharacterByIdUseCase
```

```php
// Agregar el método al puerto
interface CharacterRepositoryInterface
{
    public function findAll(): array;
    public function findById(int $id): ?Character;       // nuevo
    public function findByStatus(string $status): array; // nuevo
}
```

### Value Objects en el Dominio

```php
// En lugar de: public string $status
// Crear un Value Object que valide en construcción:
class CharacterStatus
{
    private const VALID = ['Alive', 'Dead', 'unknown'];

    public function __construct(public readonly string $value)
    {
        if (!in_array($value, self::VALID)) {
            throw new \InvalidArgumentException("Status inválido: {$value}");
        }
    }
}
```

### API Resources para controlar la respuesta JSON

```bash
php artisan make:resource CharacterResource
```

```php
// En CharacterController:
return CharacterResource::collection($characters);
```

### Tests

```bash
php artisan make:test GetCharactersUseCaseTest --unit
php artisan test
php artisan test --coverage
```

---

## 14. Errores comunes

| Error                                    | Solución                                                                           |
| ---------------------------------------- | ---------------------------------------------------------------------------------- |
| `Target [Interface] is not instantiable` | `RepositoryServiceProvider` no está registrado en `bootstrap/providers.php`        |
| `Class not found`                        | El namespace no coincide con la ruta de carpetas. Ejecuta `composer dump-autoload` |
| `SQLSTATE: No such table`                | No ejecutaste `php artisan migrate`                                                |
| `404 en /api/characters`                 | No ejecutaste `php artisan install:api` (Laravel 12)                               |
| `SSL certificate error`                  | Usa `Http::withoutVerifying()` en desarrollo local (ya implementado)               |
| `500 Internal Server Error`              | Revisa `storage/logs/laravel.log` para ver el error real                           |
| Cambio en Provider no surte efecto       | Ejecuta `php artisan optimize:clear` y `composer dump-autoload`                    |

> **Tip:** el archivo `storage/logs/laravel.log` contiene todos los errores. También puedes usar `php artisan pail` para verlos en tiempo real, o agregar `dd($variable)` temporalmente para inspeccionar valores.

---

## 15. .gitignore recomendado

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

# Testing
.phpunit.result.cache
/coverage/
```

### Cómo agregar algo al .gitignore

```gitignore
# Archivo específico
secreto.txt

# Extensión (cualquier archivo)
*.log
*.zip

# Carpeta en la raíz del proyecto
/exports/

# Carpeta en cualquier nivel
logs/

# Archivo dentro de una carpeta específica
storage/app/private/config.json
```

> **Importante:** si ya subiste un archivo antes de agregarlo al `.gitignore`, git lo sigue rastreando. Debes quitarlo del índice sin borrarlo del disco:
>
> ```bash
> git rm --cached nombre-del-archivo
> git rm --cached -r nombre-de-carpeta/
> ```

---

## 16. Glosario

| Término                       | Definición                                                                                    |
| ----------------------------- | --------------------------------------------------------------------------------------------- |
| **DDD**                       | Domain-Driven Design. Filosofía de organización del código centrada en el dominio de negocio. |
| **Arquitectura Hexagonal**    | Patrón que aísla el núcleo de la app de sus puertos de entrada/salida.                        |
| **Entidad**                   | Objeto con identidad única y ciclo de vida. `Character` tiene un ID que lo identifica.        |
| **Value Object**              | Objeto sin identidad, definido por sus atributos. Ej: `CharacterStatus("Alive")`.             |
| **Puerto (Port)**             | Interfaz que define un contrato de comunicación. Ej: `CharacterRepositoryInterface`.          |
| **Adaptador (Adapter)**       | Implementación concreta de un puerto. Ej: `RickAndMortyRepository`.                           |
| **Caso de Uso**               | Clase que orquesta la lógica de una operación específica. Ej: `GetCharactersUseCase`.         |
| **Service Provider**          | Clase de Laravel que registra bindings en el contenedor de dependencias.                      |
| **Inyección de Dependencias** | Patrón donde las dependencias se pasan al constructor en lugar de instanciarse dentro.        |
| **Contenedor IoC**            | El "cerebro" de Laravel que resuelve automáticamente las dependencias.                        |
| **Binding**                   | Registro en el contenedor que dice "cuando pidan X, da Y".                                    |
| **Inmutabilidad**             | Propiedad de un objeto que no puede cambiar después de ser creado (`readonly`).               |
| **Upsert**                    | Operación de BD: actualiza si existe, inserta si no existe.                                   |
| **Hidratación**               | Proceso de convertir datos crudos (array/stdClass) en objetos del dominio.                    |

---

_Laravel 12 · PHP 8.2 · DDD · Arquitectura Hexagonal · Rick & Morty API_
