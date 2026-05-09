# Slim PHP Api Framework Boilerplate

API HTTP w PHP oparte na Slim 4 w układzie modularnego monolitu (PHP-DI, PDO/MySQL, JWT, OpenAPI, Monolog).

## Stack technologiczny

| Obszar | Technologia |
|--------|-------------|
| Język | PHP 8.2+ (`declare(strict_types=1)`, silne typowanie) |
| Framework HTTP | [Slim 4](https://www.slimframework.com/) |
| Kontener IoC | [PHP-DI](https://php-di.org/) + integracja [php-di/slim-bridge](https://github.com/PHP-DI/Slim-Bridge) |
| HTTP (PSR-7 / PSR-17) | [Nyholm PSR-7](https://github.com/Nyholm/psr7), [nyholm/psr7-server](https://github.com/Nyholm/psr7-server) |
| Konfiguracja | [vlucas/phpdotenv](https://github.com/vlucas/phpdotenv) |
| Logowanie | [Monolog](https://github.com/Seldaek/monolog) |
| Dokumentacja API | [zircote/swagger-php](https://github.com/zircote/swagger-php), Swagger UI pod `/docs` |
| Uwierzytelnianie | JWT ([tuupola/slim-jwt-auth](https://github.com/tuupola/slim-jwt-auth)), token generowany po `/v1/auth/login` |
| Baza danych | MySQL przez **PDO** (bez ORM; zapytania w klasach repozytoriów) |
| Ograniczanie ruchu | Middleware rate limit z licznikami w MySQL (`rate_limit_hits`) |
| Standardy | PSR-4, PSR-7, PSR-11, PSR-15 |

## Wymagania

- PHP 8.2 lub nowszy z rozszerzeniami: `pdo_mysql`, `mbstring`, `json`
- Composer 2.x
- Serwer MySQL 5.7+ / 8.x

## Instalacja

1. **Sklonuj repozytorium** i przejdź do katalogu projektu.

2. **Zainstaluj zależności:**

   ```bash
   composer install
   ```

3. **Konfiguracja środowiska** — skopiuj plik przykładu i uzupełnij dane bazy oraz sekret JWT:

   ```bash
   copy .env.example .env
   ```

   Na systemach Unixowych: `cp .env.example .env`

   W pliku `.env` ustaw m.in. `DB_*`, `JWT_SECRET` (długi, losowy ciąg w produkcji).

4. **Baza danych** — utwórz pustą bazę (np. `slim_api`), następnie wykonaj skrypt:

   ```bash
   mysql -u USER -p DATABASE_NAME < database/schema.sql
   ```

   Skrypt tworzy tabele `users`, `rate_limit_hits` oraz użytkownika demonstracyjnego (`demo@example.com`, hasło: `password`).

5. **Dokumentacja OpenAPI** (opcjonalnie po zmianach w kodzie):

   ```bash
   composer openapi:generate
   ```

   Powstanie plik `public/openapi.json`, serwowany przez aplikację pod `/openapi.json`.

## Uruchomienie lokalne

**Wbudowany serwer PHP** (katalog public jako document root):

```bash
php -S 127.0.0.1:8080 -t public
```

API będzie dostępne pod bazowym adresem `http://127.0.0.1:8080`.

### Przydatne ścieżki

| Ścieżka | Opis |
|---------|------|
| `GET /v1/health` | Sprawdzenie działania aplikacji (bez bazy przy samym health; endpointy z bazą wymagają MySQL) |
| `GET /docs` | Swagger UI |
| `GET /openapi.json` | Specyfikacja OpenAPI 3.0 (JSON) |
| `POST /v1/auth/login` | Logowanie JSON: `email`, `password` → token JWT |
| `GET /v1/users`, `POST /v1/users`, `GET /v1/users/{id}` | Operacje na użytkownikach (fragment modułu User) |
| `GET /v1/users/me` | Profil zalogowanego użytkownika — nagłówek `Authorization: Bearer <token>` |

Logi aplikacji (domyślnie): `logs/app.log` — upewnij się, że katalog `logs/` jest zapisywalny.

## Instrukcja dla Postmana

### 1. Import kolekcji z OpenAPI

1. Uruchom aplikację lokalnie (np. `http://127.0.0.1:8080`).
2. W Postmanie: **Import** → zakładka **Link** (lub **File**).
3. Pod adres specyfikacji:

   `http://127.0.0.1:8080/openapi.json`

   albo wybierz z dysku plik `public/openapi.json`.

4. Postman zaproponuje wygenerowanie kolekcji żądań na podstawie ścieżek OpenAPI — zaakceptuj import.

### 2. Zmienna środowiskowa `base_url`

1. Utwórz **Environment** (np. „Local”).
2. Dodaj zmienną:

   - **Variable:** `base_url`  
   - **Initial / Current value:** `http://127.0.0.1:8080`

3. W żądaniach ustaw URL jako `{{base_url}}/v1/...` (jeśli wygenerowana kolekcja używa pełnych ścieżek, możesz zamienić prefix na `{{base_url}}` przez edycję kolekcji).

### 3. Logowanie i token Bearer

1. Wyślij **POST** `{{base_url}}/v1/auth/login` z ciałem JSON:

   ```json
   {
     "email": "demo@example.com",
     "password": "password"
   }
   ```

2. Z odpowiedzi skopiuj wartość pola **`access_token`**.

3. Dla chronionych żądań (np. **GET** `/v1/users/me`):

   - Zakładka **Authorization** → typ **Bearer Token** → wklej skopiowany token,

   **lub**

   - Nagłówek ręcznie: `Authorization` = `Bearer <access_token>`.

### 4. Nagłówek X-Request-ID (opcjonalnie)

Można przekazać własny identyfikator śledzenia żądania:

`X-Request-ID: dowolny-unikalny-ciąg`

Jeśli go brakuje, serwer wygeneruje identyfikator i zwróci go w odpowiedzi pod tym samym nagłówkiem.

---

Repozytorium: [https://github.com/gmaxsoft/SlimApiFramework](https://github.com/gmaxsoft/SlimApiFramework)
