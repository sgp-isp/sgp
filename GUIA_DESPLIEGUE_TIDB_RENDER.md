# 🚀 Guía de Despliegue en la Nube: SGP con TiDB Cloud + Render.com
> **Instituto de Salud Pública del Estado Bolívar (ISPEB)**  
> **Objetivo:** Poner el sistema en internet 24/7 con HTTPS nativo para habilitar la autenticación por huella dactilar en teléfonos móviles sin depender de la PC local ni de XAMPP.

---

## 📦 Archivos Ya Preparados en el Proyecto
El código del SGP ya fue actualizado y optimizado para la nube:
1. **`app/core/Database.php`**: Ya soporta el puerto `4000` y cifrado SSL requerido por TiDB Cloud.
2. **`app/config/config.php`**: Lee automáticamente las variables de entorno de Render (`MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLSSL`).
3. **`Dockerfile`**: Contenedor con Apache, PHP 8.2, extensiones de base de datos (`pdo_mysql`), compresión y `mod_rewrite`.
4. **`.dockerignore`**: Ignora archivos pesados locales (`.exe`, instaladores, backups) para que la subida sea ligera y rápida.
5. **`render.yaml`**: Especificación de servicio automático para Render.
6. **`database/backup_tidb_cloud.sql`**: Respaldo completo actual de la base de datos listo para importar en TiDB Cloud.

---

## 🛠️ PASO 1: Crear la Base de Datos en TiDB Cloud (100% Gratis)

1. Ingresa a **[tidbcloud.com](https://tidbcloud.com/)** y crea una cuenta (puedes registrarte con Google o GitHub).
2. Haz clic en **Create Cluster** y selecciona el plan **Serverless** (gratuito, 5 GB de almacenamiento).
3. Asigna un nombre al clúster (ejemplo: `sgp-db`) y selecciona una región cercana (ej. **AWS / US East - N. Virginia** o **Oregon**).
4. Guarda la contraseña de usuario `root` que genera la plataforma.
5. En la sección **Overview** del clúster, haz clic en **Connect**:
   - Tipo de conexión: **General** / **MySQL CLI**.
   - Anota los siguientes 5 datos que te mostrará:
     - **Host:** (ejemplo: `gateway01.us-east-1.prod.aws.tidbcloud.com`)
     - **Port:** `4000`
     - **User:** (ejemplo: `xxxxxx.root`)
     - **Password:** (la contraseña generada)
     - **Database:** `proyecto_sgp` (si no existe, se creará al importar el archivo).

### Importar la Base de Datos en TiDB Cloud:
1. En el menú lateral de TiDB Cloud, ve a **SQL Editor**.
2. Ejecuta:
   ```sql
   CREATE DATABASE IF NOT EXISTS proyecto_sgp;
   USE proyecto_sgp;
   ```
3. O en la pestaña **Import**, sube directamente el archivo:
   `c:\xampp\htdocs\SGP\database\backup_tidb_cloud.sql`
4. ¡Listo! Todas las tablas, roles, usuarios y asistencias estarán cargadas en la nube.

---

## 🐙 PASO 2: Subir el Proyecto a GitHub

Si aún no tienes el código en GitHub:
1. Abre tu terminal o GitHub Desktop.
2. Crea un repositorio (puede ser privado):
   ```bash
   git init
   git add .
   git commit -m "SGP Cloud ready para TiDB y Render con HTTPS"
   git branch -M main
   git remote add origin https://github.com/TU_USUARIO/SGP.git
   git push -u origin main
   ```

---

## 🌐 PASO 3: Desplegar la Aplicación en Render.com

1. Ingresa a **[render.com](https://render.com/)** y crea una cuenta o inicia con GitHub.
2. Haz clic en **New +** y selecciona **Web Service**.
3. Conecta tu repositorio de GitHub `SGP`.
4. Render detectará automáticamente el archivo `Dockerfile`.
5. Configuración básica:
   - **Name:** `sgp-ispeb` (o el nombre que prefieras).
   - **Region:** Elige la misma región de tu base de datos (ej. *Oregon* u *Ohio*).
   - **Instance Type:** **Free**.
6. En la sección **Environment Variables** (Variables de Entorno), añade las credenciales de TiDB Cloud:

| Clave (Key) | Valor (Value) |
|---|---|
| `MYSQLHOST` | *El host de tu clúster TiDB* |
| `MYSQLPORT` | `4000` |
| `MYSQLDATABASE` | `proyecto_sgp` |
| `MYSQLUSER` | *El usuario de TiDB (ej. xxxxxx.root)* |
| `MYSQLPASSWORD` | *La contraseña de TiDB* |
| `MYSQLSSL` | `true` |
| `SMTP_HOST` | `smtp.gmail.com` *(o tu servidor de correo)* |
| `SMTP_USER` | `tu_correo@gmail.com` |
| `SMTP_PASS` | `tu_contraseña_de_aplicacion` |
| `SMTP_PORT` | `587` |

7. Haz clic en **Create Web Service**.
8. Render construirá el contenedor Docker e iniciará el servicio. En 3 a 5 minutos verás el mensaje **Live** con tu URL pública:
   👉 `https://sgp-ispeb.onrender.com`

---

## 📱 PASO 4: Uso de la Huella Móvil (WebAuthn)
1. Abre `https://tu-servicio.onrender.com` en tu teléfono inteligente (Android o iPhone).
2. Verás el candado verde 🔒 (HTTPS activo).
3. Inicia sesión con cualquier pasante o tutor y ve a la opción de vincular biometría.
4. El navegador solicitará la huella o el sensor facial del teléfono y quedará registrado inmediatamente mediante el estándar FIDO2 / WebAuthn.
5. El sistema ahora funciona 24/7 sin necesidad de que ninguna computadora en la oficina esté encendida.

---

## 💡 Consejo Pro: Evitar el retraso de "Cold Start" en Render Free
En el plan gratuito de Render, los servidores se duermen tras 15 minutos sin visitas y tardan unos 50 segundos en despertar cuando alguien entra.
Para evitar que se duerma y que siempre responda al instante:
1. Regístrate gratis en **[cron-job.org](https://cron-job.org/)** o **[uptimerobot.com](https://uptimerobot.com/)**.
2. Crea un monitoreo a tu URL: `https://sgp-ispeb.onrender.com/` cada 10 minutos.
3. Esto mantendrá el servidor despierto de forma ininterrumpida y gratuita.
