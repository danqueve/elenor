# Elenor

Tienda online de materiales eléctricos y herramientas para Tucumán y el NOA (Argentina).

## Stack

- Laravel 13 sobre PHP 8.3
- Blade + Livewire 4 + Alpine.js (Alpine viene incluido en Livewire; no se carga aparte)
- Tailwind CSS 4 con Vite 8, tipografía Montserrat autohospedada
- MySQL 8 (InnoDB)
- Laravel Socialite para el login con Google (Etapa 3)

## Requisitos

- Windows con WAMP64: PHP **8.3**, MySQL **8** (puerto 3306) y Apache con `mod_rewrite`
- Composer 2
- Node 22 o superior (trae npm)
- Git

### Ojo: el PHP de la consola

Apache usa el PHP que elegís en el menú de WAMP (PHP → Versión → 8.3.x), pero **Composer y Artisan usan el `php` del PATH**, que puede ser otro. Laravel 13 necesita 8.3. Antes de trabajar, en PowerShell:

```powershell
$env:Path = "C:\wamp64\bin\php\php8.3.28;" + $env:Path
php -v   # tiene que decir 8.3.x
```

Para que quede fijo, poné `C:\wamp64\bin\php\php8.3.28` al principio del PATH del sistema (ajustá la versión si WAMP trae otra).

## Puesta en marcha en WAMP

Todos los comandos se corren desde la carpeta del proyecto (`C:\wamp64\www\elenor`), con PHP 8.3 en el PATH.

### 1. Dependencias

```powershell
composer install
npm install
```

### 2. Configuración

```powershell
copy .env.example .env
php artisan key:generate
```

El `.env.example` ya viene con los valores de WAMP: MySQL en `127.0.0.1:3306`, usuario `root` sin contraseña, base `elenor`. Los datos de contacto del sitio (`SHOP_*`) son ficticios: reemplazalos por los reales.

### 3. Bases de datos

Creá dos bases (desde phpMyAdmin o la consola de MySQL). `elenor_test` es la que usan los tests, así nunca tocan tus datos:

```sql
CREATE DATABASE elenor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE elenor_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Después:

```powershell
php artisan migrate
```

### 4. Assets (CSS y JS)

`public/build` no se versiona: hay que generarlo.

```powershell
npm run build   # una vez, para probar
npm run dev     # mientras desarrollás (recarga al guardar)
```

### 5. Virtual host `elenor.test`

**Opción A, con la herramienta de WAMP:** abrí `http://localhost/add_vhost.php`, usá el nombre `elenor.test` y la ruta `c:/wamp64/www/elenor/public`, y después reiniciá el DNS y los servicios desde el menú de WAMP.

**Opción B, a mano:**

1. Abrí el Bloc de notas **como administrador** y agregá esta línea al archivo `C:\Windows\System32\drivers\etc\hosts`:

   ```text
   127.0.0.1 elenor.test
   ```

2. Agregá este bloque al final de `C:\wamp64\bin\apache\apache2.4.65\conf\extra\httpd-vhosts.conf` (la carpeta cambia con la versión de Apache):

   ```apache
   <VirtualHost *:80>
     ServerName elenor.test
     DocumentRoot "c:/wamp64/www/elenor/public"
     <Directory "c:/wamp64/www/elenor/public/">
       Options +FollowSymLinks
       AllowOverride All
       Require all granted
     </Directory>
   </VirtualHost>
   ```

3. Clic derecho en el ícono de WAMP → **Reiniciar todos los servicios**.

Listo: abrí <http://elenor.test>.

> Sin virtual host también se puede probar con `php artisan serve --port=8000` (abrí <http://127.0.0.1:8000>). Los links se arman con el host del pedido, así que funcionan igual.

## Día a día

```powershell
npm run dev                 # assets con recarga automática
php artisan test            # tests (usan la base elenor_test)
php vendor\bin\pint         # formato de código (estilo Laravel)
php artisan migrate:fresh --seed   # rehacer la base de desarrollo (desde la Etapa 2)
```

## Particularidades del MySQL de WAMP

El MySQL de WAMP viene con `default_storage_engine=MYISAM` y `sql_mode` vacío. MyISAM no tiene transacciones, bloqueo de filas ni claves foráneas, y los pedidos necesitan las tres cosas. Por eso `config/database.php` fuerza **InnoDB** y modo estricto en la conexión de la app (y un test lo verifica). No hace falta tocar el `my.ini`, pero cualquier tabla creada por fuera de Laravel (a mano, desde phpMyAdmin) saldría MyISAM si no se indica `ENGINE=InnoDB`.

## Estructura

```text
app/
├─ Livewire/                    componentes Livewire (clase + vista)
├─ Support/                     Money y helpers (money())
└─ View/Components/Layout/      componentes de layout con lógica (barra de categorías, menú móvil)
config/shop.php                 moneda ARS y datos de contacto
lang/es/                        textos de Laravel en español
resources/
├─ css/app.css                  Tailwind + colores y tipografía de la marca (@theme)
├─ fonts/                       Montserrat (woff2) y su licencia
├─ js/app.js                    arranque de Livewire (que arranca Alpine)
└─ views/
   ├─ layouts/app.blade.php     layout principal: <x-layouts::app>
   ├─ components/layout/        header, footer, menú móvil, barra de categorías, logo
   ├─ components/button.blade.php
   └─ livewire/                 vistas de los componentes Livewire
routes/web.php                  URLs en español, nombres de ruta en inglés
tests/                          Unit y Feature (contra MySQL)
```

## Etapas

- [x] **1. Base del proyecto**: Laravel, Livewire, Tailwind, layout principal
- [ ] 2. Modelo de datos (usuarios, categorías, productos, carritos, pedidos) y seeders
- [ ] 3. Login con Google
- [ ] 4. Catálogo (inicio, listados con filtros, buscador, ficha de producto)
- [ ] 5. Carrito y checkout
- [ ] 6. Panel administrativo e importación de precios

Las reglas y convenciones del proyecto están en [CLAUDE.md](CLAUDE.md).
