# Ride-Sharing Transport API

Una API RESTful construida con Laravel para la gestión de un sistema de transporte privado (estilo Uber o Ridery). Este backend administra toda la lógica central de la plataforma, incluyendo el manejo de choferes, clientes, vehículos, traslados y transacciones financieras.

## 🚀 Características Principales

*   **Gestión de Usuarios y Roles:** Autenticación y autorización para Clientes, Choferes y Personal Administrativo.
*   **Módulo de Clientes:** Gestión de perfiles, saldos (`SaldoCliente`) y solicitudes de traslados.
*   **Módulo de Choferes:** Perfiles de conductores (`Chofer`), verificación de pruebas (`PruebaChofer`), contactos de emergencia y cuentas bancarias asociadas (`BancoChofer`).
*   **Gestión de Flota:** Registro y control de vehículos (`Vehiculo`), incluyendo pruebas e inspecciones (`PruebaVehiculo`).
*   **Logística de Traslados:** Control del ciclo de vida de los viajes (`Traslado`) y ubicaciones/rutas (`Lugar`).
*   **Finanzas:** Gestión de bancos y saldos tanto para la empresa como para los choferes y clientes.

## 🛠️ Stack Tecnológico

*   **Framework:** Laravel (PHP)
*   **Base de Datos:** MySQL / PostgreSQL (Agnóstico gracias a Eloquent ORM)
*   **Autenticación:** Laravel Sanctum (Manejo de tokens para la API)
*   **Testing:** PHPUnit

## ⚙️ Requisitos Previos

Asegúrate de tener instalados los siguientes componentes en tu entorno local:

*   PHP >= 8.0
*   Composer
*   MySQL o PostgreSQL

## 📦 Instalación y Configuración Local

Sigue estos pasos para levantar el proyecto en tu entorno local:

1. **Clonar el repositorio**
   ```bash
   git clone <url-del-repositorio>
   cd <nombre-del-repo>
   ```

2. **Instalar dependencias de PHP**
   ```bash
   composer install
   ```

3. **Configurar las variables de entorno**
   Copia el archivo de ejemplo para crear tu propio archivo `.env`:
   ```bash
   cp .env.example .env
   ```
   *Nota: Abre el archivo `.env` y configura tus credenciales de base de datos (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).*

4. **Generar la clave de la aplicación**
   ```bash
   php artisan key:generate
   ```

5. **Ejecutar las migraciones de la base de datos**
   Esto creará todas las tablas necesarias (clientes, choferes, traslados, etc.).
   ```bash
   php artisan migrate
   ```

6. **Iniciar el servidor de desarrollo**
   ```bash
   php artisan serve
   ```
   La API estará disponible en `http://localhost:8000`.

## 📄 Licencia

Este proyecto es de código abierto y está disponible bajo la licencia [MIT](https://opensource.org/licenses/MIT).
