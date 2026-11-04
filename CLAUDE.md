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
- Comandos: `php artisan test` · `php vendor\bin\pint` (estilo) · `php artisan migrate:fresh --seed` (borra la base de desarrollo y la rehace con datos de ejemplo).

## Livewire 4

- Componentes **de clase** (PHP en `app/Livewire`, vista en `resources/views/livewire`), sin prefijo ⚡ en los archivos: está configurado en `config/livewire.php` (`make_command`).
- El layout principal es `resources/views/layouts/app.blade.php`: en páginas Blade `<x-layouts::app :title="...">`; para páginas Livewire (`Route::livewire`) es el layout por defecto (`layouts::app`).
- `resources/js/app.js` importa Livewire desde `vendor/livewire/livewire/dist/livewire.esm` (Vite) y el layout usa `@livewireScriptConfig`. No cargar Alpine por separado.

## Modelo de datos

Tablas: `users`, `addresses`, `fiscal_data`, `categories` (árbol), `brands`, `products`, `product_images`, `product_attributes`, `carts`, `cart_items`, `orders`, `order_items`, `order_status_history`. El diagrama está en el README.

- **Dinero** en `decimal(12,2)`; **cantidades y stock** en `decimal(10,2)` (cable por metro). Los casts `decimal:2` devuelven **strings**: calcular con `bcmath` (`bcadd`, `bcmul`), nunca con floats.
- **Enums** en `app/Enums` (con `label()` en español), guardados como texto y no como `ENUM` de MySQL: `OrderStatus`, `UserRole`, `AccountType`, `ProfessionalStatus`, `ProductUnit` (`u`, `m`, `rollo`), `ShippingMethod`, `PaymentMethod` (incluye `MercadoPago`, todavía no ofrecido), `PaymentStatus`, `IvaCondition`.
- **Restricciones `CHECK`** en MySQL: stock ≥ 0, precios ≥ 0, cantidades > 0, importes del pedido ≥ 0. MySQL no admite `CHECK` sobre columnas con FK en cascada: por eso "un carrito es de un usuario **o** de una sesión" lo controla la aplicación (Etapa 5).
- **Claves foráneas**: al borrar un producto, `order_items.product_id` queda en `null` y el pedido conserva nombre, SKU, unidad y precio congelados. Un usuario con pedidos no se puede borrar; una categoría con hijos o con productos tampoco.
- Sin `password`: el acceso es solo con Google. `User` solo admite asignación masiva del perfil de Google y el teléfono; `role`, `account_type` y `professional_status` se setean explícitamente, nunca desde un request.
- **Precio profesional**: `Product::priceFor(?User)` es el único lugar que lo decide (cuenta `professional` o `company` con estado `approved`, y producto con `professional_price`). Usarlo en carrito y checkout.
- **Modelos estrictos** fuera de producción (`Model::shouldBeStrict`): el lazy loading lanza excepción (cargar con `with()`/`load()`) y asignar un atributo que no está en `#[Fillable]` también. La ficha técnica es `Product::specs()` (no se puede llamar `attributes`).
- `Category::descendantIds()` (CTE recursiva) devuelve la categoría y todo lo que cuelga de ella: el listado de una categoría principal tiene que incluir sus subcategorías. Los productos cuelgan solo de subcategorías.
- Los pedidos guardan la dirección como **copia** en las columnas `shipping_*` (vacías si es retiro). El número de pedido (`orders.number`) lo genera la Etapa 5.
- `App\Support\Cuit` valida y formatea el CUIT (se guarda con 11 dígitos, sin guiones).
- **Factories** con estados: `User::factory()->admin()`, `->professional()`, `->pendingProfessional()`; `Product::factory()->outOfStock()->withSpecs([...])`; `Order::factory()->pickup()->withItems()`; `OrderItem::factory()->forProduct($producto)`.
- **Seeders repetibles** (`updateOrCreate`, no duplican): categorías, marcas, productos (datos en `database/seeders/data/products.php`) y usuarios. El admin sale de `SHOP_ADMIN_EMAIL`; los usuarios de ejemplo solo se crean fuera de producción.

## Login con Google

- Solo se entra con Google (Socialite, flujo con `state`, el `code` se canjea en el servidor). Rutas: `/auth/google/redirect` y `/auth/google/callback` (`GoogleAuthController`), `/ingresar`, `/salir` (POST + CSRF), `/cuenta`, `/cuenta/telefono`. Variables: `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` (config en `services.google`).
- **Google no acepta `elenor.test`** (exige HTTPS salvo `localhost`, y dominio de la Public Suffix List). El login se prueba en `http://localhost:8000` con `php artisan serve`, abriendo exactamente ese host (no `127.0.0.1`). El resto del sitio sigue en `elenor.test`.
- `GoogleUserResolver`: busca por `google_id` y después por email, **solo si Google confirma `email_verified`**; si ese email ya tiene otro `google_id`, rechaza el ingreso (no se entrega la cuenta). Nunca escribe `role`, `account_type` ni `professional_status` (el admin sembrado se enlaza por email en su primer ingreso).
- `User::getAuthPassword()` devuelve `''`: Laravel lo pide en cada login y con modelos estrictos explotaría al leer una columna `password` que no existe.
- Al loguear: se lee el id de sesión **antes** de regenerarla y se llama `CartService::mergeGuestCart()`. Clientes con "recordarme"; personal sin él. Si no hay teléfono, se pide enseguida y la página que quería el cliente (`url.intended`) se abre después de guardarlo.
- Las rutas que necesitan un teléfono de contacto (el checkout, Etapa 5) usan `['auth', 'phone']`. Para exigir login en cualquier ruta, `auth`: Laravel guarda la página y vuelve a ella tras el login.
- Tests: Socialite no trae `fake()` en esta versión; se simula el driver con Mockery (`Socialite::shouldReceive('driver')`). Si una prueba usa `report()`, usar `Exceptions::fake()`.
- Mensajes de error del login: `redirect()->route('login')->withErrors(['google' => ...])`; el layout muestra avisos de una sola vez con `->with('status', ...)` (`<x-flash />`).
- El botón de Google va en verde eléctrico (el botón primario) y sin el logo de Google, para respetar la paleta; los lineamientos de marca de Google piden otros estilos si se usa su logo.

## Etapas

1. **Base del proyecto**: hecha.
2. **Modelo de datos**: hecha (ver "Modelo de datos").
3. **Login con Google**: hecha, a la espera del OK (ver "Login con Google"; la guía de Google Cloud Console está en el README).
4. **Catálogo**: inicio, listados con filtros por marca, precio y atributos (Livewire), buscador con autocompletado (nombre, marca, SKU, código de fabricante), ficha de producto con botón de WhatsApp.
5. **Carrito y checkout**: carrito persistente, cantidades por unidad o metro, checkout en 3 pasos (login con Google recién al confirmar), número de pedido, historial, email, "Mis pedidos" con "Repetir pedido".
6. **Panel administrativo** (`/admin` por rol): dashboard, ABM de productos/categorías/marcas, gestión de pedidos con aviso al cliente, clientes, aprobación de cuentas profesionales, importación de precios y stock por CSV/Excel con vista previa.

## Pendientes y cosas a tener presentes

- **"Presupuestos"** (ítem del menú inferior) no está definido en ninguna etapa: hoy es una página "Próximamente". Hay que definir qué es.
- **Páginas de Privacidad y Términos y condiciones**: Google las pide para publicar la app en producción (README, "Login con Google"). Todavía no existen.
- Dominio de producción todavía sin definir: el README usa `TU-DOMINIO` en las URI de redirección.
- El contador del carrito (`App\Livewire\CartCounter`) devuelve 0 hasta la Etapa 5.
- **Ofertas** (inicio de la Etapa 4: "ofertas y novedades"): el modelo no tiene nada para marcar un producto en oferta (precio anterior, indicador…). Definir cómo se manejan antes de armar el inicio.
- Los productos de ejemplo no tienen fotos (`product_images` está vacía): la Etapa 4 necesita una imagen de reemplazo.
- El logo es un placeholder y los datos de contacto del `.env` son ficticios.
