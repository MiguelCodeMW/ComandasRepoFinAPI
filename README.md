# README.md

# Backend de Sistema de Gestión de Comandas y Mesas

Este proyecto es el backend de una aplicación para la gestión de comandas, productos, categorías, mesas y configuraciones de un restaurante o bar. Está construido con **Laravel** y utiliza **Laravel Sanctum** para la autenticación basada en tokens, ofreciendo una API RESTful robusta y segura.

---

## 🚀 Características Principales

-   **Autenticación de Usuarios:** Registro y login de usuarios con roles (`admin` y `camarero`).
-   **Gestión de Roles:** Control de acceso a ciertas funcionalidades basado en roles (solo administradores pueden realizar operaciones CRUD en productos, categorías, eliminar comandas y modificar configuraciones).
-   **Gestión de Productos y Categorías:** CRUD completo para productos (con precio y asociados a una categoría) y categorías.
-   **Gestión de Comandas:**
    -   Creación, visualización y actualización de comandas.
    -   Asociación de productos con cantidades y precios en cada comanda.
    -   Cálculo automático de subtotal, IVA y total final.
    -   Cambio de estado de la comanda (`abierta` / `cerrada`).
    -   Marcado de comandas como pagadas, incluyendo la aplicación del IVA.
    -   Integración con mesas para gestionar su estado (`libre` / `ocupada`).
-   **Gestión de Mesas:** CRUD para las mesas, incluyendo la capacidad de establecer un número total de mesas, que las crea o elimina automáticamente (con validación si están ocupadas).
-   **Configuración Dinámica:** Gestión de valores globales como el **IVA** y el **símbolo de la moneda**.

---

## 🛠️ Tecnologías Utilizadas

-   **Laravel Framework**: El potente framework PHP para la construcción de APIs.
-   **Laravel Sanctum**: Para la autenticación API basada en tokens ligeros.
-   **SQLite (o cualquier base de datos compatible con Eloquent)**: Sistema de gestión de bases de datos.
-   **Composer**: Gestor de dependencias para PHP.

---

## 🗄️ Estructura de la Base de Datos

El backend utiliza las siguientes tablas principales:

-   **`users`**: Almacena la información de los usuarios (nombre, email, contraseña hasheada, rol).
    -   `id`
    -   `name`
    -   `email` (único)
    -   `password`
    -   `role` (enum: 'admin', 'camarero', por defecto 'admin' para el primer usuario)
-   **`categorias`**: Almacena las categorías de productos (ej. "Bebidas", "Comida", "Postres").
    -   `id`
    -   `nombre`
-   **`productos`**: Almacena la información de los productos disponibles.
    -   `id`
    -   `nombre`
    -   `precio`
    -   `categoria_id` (clave foránea a `categorias`)
-   **`mesas`**: Almacena el número y el estado de las mesas.
    -   `id`
    -   `numero` (único)
    -   `estado` (enum: 'libre', 'ocupada')
-   **`comandas`**: Almacena los registros de las comandas.
    -   `id`
    -   `user_id` (clave foránea a `users`, el usuario que creó/atendió la comanda)
    -   `mesa_id` (clave foránea a `mesas`, puede ser nula)
    -   `fecha`
    -   `estado` (enum: 'abierta', 'cerrada')
    -   `iva` (porcentaje de IVA aplicado en el momento de cierre/pago)
    -   `total_con_iva` (total final con IVA aplicado)
-   **`comanda_detalles`**: Tabla pivote para los productos dentro de cada comanda.
    -   `id`
    -   `comanda_id` (clave foránea a `comandas`)
    -   `producto_id` (clave foránea a `productos`)
    -   `cantidad`
    -   `precio_unitario` (precio del producto en el momento de añadirlo a la comanda)
    -   `total` (cantidad \* precio_unitario)
-   **`settings`**: Almacena configuraciones globales como el IVA y la moneda.
    -   `key` (clave primaria, ej. 'global_iva', 'global_currency')
    -   `value`

---

## 🚀 Instalación y Configuración

Sigue estos pasos para poner en marcha el proyecto en tu entorno local:

1.  **Clona el repositorio:**

    ```bash
    git clone https://github.com/MiguelCodeMW/ComandasRepoFinAPI
    cd ComandasRepoFinAPI
    ```

2.  **Instala las dependencias de Composer:**

    ```bash
    composer install
    ```

3.  **Copia el archivo de entorno y genera la clave de aplicación:**

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

4.  **Configura tu base de datos en el archivo `.env`:**
    Asegúrate de que las siguientes variables estén configuradas con tus credenciales de base de datos:

    ```dotenv
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=your_database_name
    DB_USERNAME=your_username
    DB_PASSWORD=your_password
    ```

5.  **Ejecuta las migraciones de la base de datos:**
    Esto creará todas las tablas necesarias.

    ```bash
    php artisan migrate
    ```

6.  **Inicia el servidor de desarrollo de Laravel:**
    ```bash
    php artisan serve
    ```
    El API estará disponible en `http://127.0.0.1:8000` (o el puerto que te indique la consola).

---

## 🔑 Autenticación y Roles

Este backend utiliza **Laravel Sanctum** para la autenticación.

### Registro de Usuario

-   **Endpoint:** `POST /api/create`
-   **Body (JSON):**
    ```json
    {
        "name": "Nombre de Usuario",
        "email": "correo@example.com",
        "password": "su_contraseña"
    }
    ```
-   **Notas:**
    -   El **primer usuario** que se registra automáticamente obtiene el rol de `admin`.
    -   Los usuarios posteriores que se registren a través de esta ruta tendrán el rol de `camarero`.
    -   Se devuelve un `token` de API que debe usarse para las solicitudes autenticadas.

### Inicio de Sesión

-   **Endpoint:** `POST /api/login`
-   **Body (JSON):**
    ```json
    {
        "email": "correo@example.com",
        "password": "su_contraseña"
    }
    ```
-   **Notas:**
    -   Se devuelve un `token` de API si las credenciales son correctas.

### Roles y Permisos

-   **`admin`**: Acceso completo a todas las rutas, incluyendo CRUD para categorías, productos, eliminación de comandas y gestión de configuración (IVA, moneda, total mesas).
-   **`camarero`**: Acceso de lectura a categorías, productos, mesas, y CRUD para comandas (excepto eliminar).

---

## 📄 Endpoints de la API

Todas las rutas requieren autenticación con un token de Sanctum (excepto `/create` y `/login`). Debes enviar el token en la cabecera `Authorization` como `Bearer <tu_token>`.

### Rutas Generales (Autenticado - `admin` o `camarero`)

-   **`GET /api/user`**: Obtiene la información del usuario autenticado.
-   **`GET /api/categorias`**: Lista todas las categorías.
-   **`GET /api/categorias/{id}`**: Muestra una categoría específica.
-   **`GET /api/productos`**: Lista todos los productos (incluye categoría).
-   **`GET /api/productos/{id}`**: Muestra un producto específico (incluye categoría).
-   **`GET /api/mesas`**: Lista todas las mesas.
-   **`GET /api/mesas/{id}`**: Muestra una mesa específica.
-   **`POST /api/mesas`**: Crea una nueva mesa.
    -   **Body:** `{"numero": 10, "estado": "libre"}` (estado opcional, por defecto 'libre')
-   **`PUT /api/mesas/{id}`**: Actualiza una mesa.
    -   **Body:** `{"numero": 10, "estado": "ocupada"}`
-   **`DELETE /api/mesas/{id}`**: Elimina una mesa (solo si está `libre`).
-   **`GET /api/configuracion/iva`**: Obtiene el valor del IVA global.
-   **`GET /api/configuracion/moneda`**: Obtiene el símbolo de la moneda global.
-   **`GET /api/dashboard`**: Lista todas las comandas (probablemente para una vista de dashboard).
-   **`GET /api/comandas`**: Lista todas las comandas.
-   **`POST /api/comandas`**: Crea una nueva comanda.
    -   **Body (JSON):**
        ```json
        {
            "user_id": 1,
            "estado": "abierta",
            "fecha": "2024-06-05",
            "mesa_id": 1, // Opcional
            "iva": 0.21, // Opcional, si no se envía, toma el global_iva o 0.21 por defecto
            "productos": [
                {
                    "producto_id": 1,
                    "cantidad": 2
                },
                {
                    "producto_id": 3,
                    "cantidad": 1
                }
            ]
        }
        ```
-   **`GET /api/comandas/{id}`**: Muestra una comanda específica con sus detalles y cálculos de IVA/total.
-   **`PUT /api/comandas/{id}`**: Actualiza una comanda existente.
    -   **Body (JSON):** similar al `POST`, puedes omitir campos que no quieras actualizar.
-   **`PUT /api/comandas/{id}/pagar`**: Marca una comanda como pagada, aplica el IVA final y libera la mesa si ya no tiene comandas abiertas.
    -   **Body (JSON)::** `{"iva": 0.21}` (requerido para el cálculo final)

### Rutas de Administrador (`admin` role requerido)

-   **`POST /api/categorias`**: Crea una nueva categoría.
    -   **Body:** `{"nombre": "Nueva Categoría"}`
-   **`PUT /api/categorias/{id}`**: Actualiza una categoría.
    -   **Body:** `{"nombre": "Nombre Actualizado"}`
-   **`DELETE /api/categorias/{id}`**: Elimina una categoría.
-   **`POST /api/productos`**: Crea un nuevo producto.
    -   **Body:** `{"nombre": "Nuevo Producto", "precio": 12.50, "categoria_id": 1}`
-   **`PUT /api/productos/{id}`**: Actualiza un producto.
    -   **Body:** `{"nombre": "Producto Actualizado", "precio": 15.00, "categoria_id": 2}`
-   **`DELETE /api/productos/{id}`**: Elimina un producto.
-   **`DELETE /api/comandas/{id}`**: Elimina una comanda por completo.
-   **`POST /api/configuracion/iva`**: Establece el valor del IVA global.
    -   **Body:** `{"iva": 0.10}` (para 10%)
-   **`POST /api/configuracion/moneda`**: Establece el símbolo de la moneda global.
    -   **Body:** `{"currency": "USD"}`
-   **`POST /api/configuracion/total-mesas`**: Configura el número total de mesas.
    -   **Body:** `{"total_mesas": 20}` (crea o elimina mesas para llegar a este total, si es posible)

---

## 💡 Notas Adicionales

-   Las contraseñas de usuario se almacenan hasheadas para mayor seguridad.
-   La lógica de negocio para el IVA en las comandas intenta ser flexible: prioriza el IVA guardado en la comanda (si existe y está cerrada), luego el IVA guardado en la comanda (si está abierta), después la configuración global, y finalmente un valor por defecto (0.21) si nada más está definido. Al `pagar` una comanda, se exige el IVA para el cálculo final.
-   La gestión del estado de las mesas se actualiza automáticamente al crear, actualizar o cerrar/pagar comandas. Al eliminar mesas, se valida que no estén ocupadas.
