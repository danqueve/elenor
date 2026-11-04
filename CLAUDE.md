# Elenor

Tienda online (webapp) de materiales eléctricos y herramientas para Tucumán y el NOA (Argentina). Hay un solo desarrollador (danqueve). Repo: <https://github.com/danqueve/elenor>

## Cómo se trabaja

- **Por etapas.** Al terminar cada una: frenar, mostrar un resumen de lo hecho y qué hay que probar, y **esperar el OK** antes de seguir.
- Antes de escribir código de una etapa, mostrar en pocas líneas el plan y la estructura de carpetas.
- Hablar en español rioplatense (voseo). Commits en español, con un mensaje claro.
- No hacer `git push` ni nada que salga de la máquina sin que se pida.
- No instalar Laravel Boost: el `AGENTS.md` del esqueleto lo sugiere, pero no fue pedido.

## Stack

Laravel 13 · PHP 8.3 · Blade + Livewire 4 + Alpine.js (el que trae Livewire, único) · Tailwind CSS 4 (Vite 8) · MySQL 8 · Laravel Socialite (Google, Etapa 3).

- Código, tablas, modelos y variables en **inglés**. Todo texto visible para el usuario en **español rioplatense** ("Buscá", "Ingresá", "Escribinos").
- URLs en español (`/catalogo`, `/carrito`), nombres de ruta en inglés (`catalog.index`, `cart.index`).
- Moneda ARS, formato `$ 12.345,67`: usar siempre `money($valor)` (`App\Support\Money`; el espacio es no cortable). Solo para mostrar, nunca para calcular.
- Zona horaria `America/Argentina/Tucuman` (UTC-3, sin horario de verano) en la app y en la sesión de MySQL. Locale `es`, faker `es_AR`.
- Textos de Laravel (validación, paginación…) en `lang/es`, publicados con `laravel-lang/common` (dev).

## Reglas que no se negocian

- Precio, stock y totales se calculan **siempre en el servidor**; el navegador solo manda IDs de producto y cantidades. Nada de propiedades públicas de Livewire para datos que el cliente no deba poder cambiar (usar `#[Computed]` o métodos).
- `order_items` guarda **nombre y precio del producto congelados** al momento de la compra.
- Al confirmar un pedido, el descuento de stock va dentro de **una transacción con bloqueo de filas** (`lockForUpdate`), para no vender la última unidad dos veces.
- El login con Google valida el token **del lado del servidor** (Socialite).
- **Nada de credenciales en el código**: todo en `.env`, y `.env.example` siempre actualizado con cada variable nueva.
- **CSRF** en todos los formularios (`@csrf`) y validación con **Form Requests**.

## Identidad visual

Colores propios en `resources/css/app.css` (`@theme`), como utilidades `bg-brand-*`, `text-brand-*`, etc.:

| Token | Hex | Uso |
| --- | --- | --- |
| `brand-deep` | `#0B1F17` | header, footer, fondos oscuros |
| `brand-electric` | `#A9F43D` | botón principal, precios destacados, estado activo |
| `brand-industrial` | `#2E3B3F` | textos secundarios, bordes, íconos (sobre fondo claro) |
| `brand-neutral` | `#E9EFED` | fondo de catálogo y tarjetas |

- Tipografía **Montserrat**: Bold en títulos, SemiBold en botones y precios, Regular en texto. Autohospedada en `resources/fonts` y cargada con el plugin de fuentes de Vite (`@fonts`). Solo woff2.
- **Un solo botón verde eléctrico por pantalla** (`<x-button>` es el primario; el resto, `variant="secondary"`).
- El verde eléctrico **no se usa como color de texto sobre fondos claros** (no tiene contraste): ahí va como fondo con texto `brand-deep`. Sobre `brand-deep` sí puede ser texto. Los íconos y bordes sobre fondo oscuro van en `brand-neutral`/`brand-industrial` según contraste.
- Tarjetas: blancas sobre el fondo `brand-neutral`, para que se despeguen.
- Mobile first. En móvil, menú inferior fijo: Inicio, Catálogo, Presupuestos, Pedidos y Cuenta. Barra de categorías solo en desktop.
- Foco de teclado: clase `focus-ring` (anillo eléctrico con relleno oscuro, se ve sobre cualquier fondo). Íconos: Heroicons con `<x-heroicon-o-*>` / `<x-heroicon-s-*>`.

## Entorno local (Windows + WAMP64)

- Carpeta `C:\wamp64\www\elenor`, sitio en `http://elenor.test` (virtual host, ver README). También sirve `php artisan serve --port=8000`.
- **El `php` del PATH es el 8.0.30.** Antes de correr `composer` o `php artisan`, en PowerShell: `$env:Path = "C:\wamp64\bin\php\php8.3.28;" + $env:Path`.
- MySQL 8.4.7 en el 3306, usuario `root` sin contraseña (MariaDB en el 3307, no se usa). Bases `elenor` y `elenor_test`. El cliente no está en el PATH: `C:\wamp64\bin\mysql\mysql8.4.7\bin\mysql.exe`. Hay otras bases de otros proyectos en el mismo servidor: no tocarlas.
- **El MySQL de WAMP tiene `default_storage_engine=MYISAM` y `sql_mode` vacío.** Por eso `config/database.php` fuerza `'engine' => 'InnoDB'` y modo estricto. No sacarlo: sin eso no hay transacciones ni bloqueo de filas. `DatabaseTest` lo verifica.
- Los tests corren contra MySQL real (`elenor_test`, definido en `phpunit.xml`), no SQLite: `lockForUpdate` no hace nada en SQLite.
- `public/build` no se versiona: `npm run build` antes de probar en el navegador (o `npm run dev`). Los tests no lo necesitan (`withoutVite()`).
- Comandos: `php artisan test` · `php vendor\bin\pint` (estilo) · `php artisan migrate:fresh --seed` (desde Etapa 2).

## Livewire 4

- Componentes **de clase** (PHP en `app/Livewire`, vista en `resources/views/livewire`), sin prefijo ⚡ en los archivos: está configurado en `config/livewire.php` (`make_command`).
- El layout principal es `resources/views/layouts/app.blade.php`: en páginas Blade `<x-layouts::app :title="...">`; para páginas Livewire (`Route::livewire`) es el layout por defecto (`layouts::app`).
- `resources/js/app.js` importa Livewire desde `vendor/livewire/livewire/dist/livewire.esm` (Vite) y el layout usa `@livewireScriptConfig`. No cargar Alpine por separado.

## Etapas

1. **Base del proyecto**: hecha, a la espera del OK.
2. **Modelo de datos**: migraciones, modelos, factories y seeders (usuarios con `google_id`, direcciones, datos fiscales, categorías en árbol, marcas, productos, imágenes, atributos, carritos, pedidos, items, historial; estados de pedido `received`, `confirmed`, `preparing`, `shipped`, `ready_for_pickup`, `delivered`, `cancelled` con etiquetas en español; ~40 productos de ejemplo). Se va a reescribir la migración original de `users` (todavía no hay nada en producción).
3. **Login con Google**: Socialite, `/auth/google/redirect` y `/auth/google/callback`, fusión del carrito de invitado, pedir teléfono antes del checkout, y la guía exacta de Google Cloud Console (local y producción).
4. **Catálogo**: inicio, listados con filtros por marca, precio y atributos (Livewire), buscador con autocompletado (nombre, marca, SKU, código de fabricante), ficha de producto con botón de WhatsApp.
5. **Carrito y checkout**: carrito persistente, cantidades por unidad o metro, checkout en 3 pasos (login con Google recién al confirmar), número de pedido, historial, email, "Mis pedidos" con "Repetir pedido".
6. **Panel administrativo** (`/admin` por rol): dashboard, ABM de productos/categorías/marcas, gestión de pedidos con aviso al cliente, clientes, aprobación de cuentas profesionales, importación de precios y stock por CSV/Excel con vista previa.

## Pendientes y cosas a tener presentes

- **"Presupuestos"** (ítem del menú inferior) no está definido en ninguna etapa: hoy es una página "Próximamente". Hay que definir qué es.
- **Google OAuth no acepta `.test`** como URI de redirección (exige HTTPS con dominio público, salvo `localhost`): en la Etapa 3 hay que resolver cómo probar el login en local. Verificarlo contra la documentación de Google antes de decidir.
- La barra de categorías usa una lista fija (`App\View\Components\Layout\CategoryBar`) hasta que existan las categorías en base de datos; el contador del carrito (`App\Livewire\CartCounter`) devuelve 0 hasta la Etapa 5.
- El logo es un placeholder y los datos de contacto del `.env` son ficticios.
