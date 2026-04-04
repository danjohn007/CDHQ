# Sistema CDHQ
## Sistema de Afiliación – Consejo de Derechos Humanos de Querétaro A.C.

![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue) ![MySQL](https://img.shields.io/badge/MySQL-5.7-orange) ![Tailwind CSS](https://img.shields.io/badge/Tailwind-CDN-38bdf8) ![License](https://img.shields.io/badge/License-MIT-green)

---

## 📋 Descripción

Sistema web completo para la gestión de afiliados, membresías, finanzas, eventos y prospectos del Consejo de Derechos Humanos de Querétaro. Construido con arquitectura MVC en PHP puro.

---

## ⚙️ Requisitos

| Componente | Versión mínima |
|---|---|
| PHP | 7.4+ |
| MySQL | 5.7+ |
| Apache | 2.4+ con `mod_rewrite` |
| Extensiones PHP | pdo, pdo_mysql, mbstring, json, openssl, session |

---

## 🚀 Instalación

### 1. Clonar o copiar el proyecto

```bash
git clone https://github.com/tu-org/cdhq.git /var/www/html/cdhq
```

### 2. Configurar la base de datos

```bash
mysql -u root -p < sql/schema.sql
```

Esto crea la base de datos `cdhq` con todas las tablas y datos de ejemplo.

### 3. Configurar credenciales

Edita `config/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'cdhq');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');
```

> **BASE_URL** se detecta automáticamente a partir de `$_SERVER`. No necesitas configurarla manualmente.

### 4. Configurar Apache

#### Opción A: Virtual Host dedicado

```apache
<VirtualHost *:80>
    ServerName cdhq.tudominio.com
    DocumentRoot /var/www/html/cdhq
    
    <Directory /var/www/html/cdhq>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/cdhq_error.log
    CustomLog ${APACHE_LOG_DIR}/cdhq_access.log combined
</VirtualHost>
```

#### Opción B: Subdirectorio

Si instalas en `/var/www/html/cdhq`, edita `.htaccess` y cambia:

```apache
RewriteBase /cdhq
```

### 5. Habilitar mod_rewrite

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### 6. Verificar instalación

Navega a `http://tu-dominio/test_connection.php` para verificar que todo funciona.

---

## 🔑 Credenciales por defecto

| Rol | Correo | Contraseña |
|---|---|---|
| Super Administrador | admin@cdhq.org | Admin123! |
| Admin Membresías | membresias@cdhq.org | Admin123! |
| Admin Financiero | finanzas@cdhq.org | Admin123! |
| Coord. Eventos | eventos@cdhq.org | Admin123! |
| Ejecutivo Prospectos | prospectos@cdhq.org | Admin123! |

> ⚠️ **Cambia estas contraseñas después del primer inicio de sesión.**

---

## 🏗️ Arquitectura

```
/
├── index.php              # Front controller + enrutador
├── .htaccess              # Reglas de reescritura de URLs
├── config/config.php      # Configuración global (DB, BASE_URL, roles)
├── core/
│   ├── Database.php       # Singleton PDO
│   ├── Router.php         # Enrutador de URLs amigables
│   ├── Controller.php     # Controlador base
│   ├── Model.php          # Modelo base (CRUD genérico)
│   └── Auth.php           # Autenticación, sesiones, CSRF, bitácora
├── app/
│   ├── controllers/       # Controladores de módulos
│   ├── models/            # Modelos de datos
│   └── views/             # Vistas PHP con Tailwind CSS
└── sql/schema.sql         # Esquema completo + datos de ejemplo
```

---

## 📦 Módulos

| Módulo | Roles con acceso | Descripción |
|---|---|---|
| **Dashboard** | Todos | Estadísticas, gráficas (Chart.js), actividad reciente |
| **Usuarios** | SUPER_ADMIN | CRUD de usuarios del sistema, roles |
| **Afiliados** | SA, AM, EP | Registro, búsqueda, perfil completo, cambios de estado |
| **Membresías** | SA, AM | Planes, asignación, renovación, vigencia |
| **Finanzas** | SA, AF | Pagos, ingresos por tipo, historial |
| **Eventos** | SA, AM, CE, AFI | Creación, asistentes, estados del evento |
| **Prospectos** | SA, AM, EP | Registro, seguimiento, notas, conversión a afiliado |
| **Reportes** | SA, AM, AF | Gráficas y tablas de afiliados, ingresos, eventos |
| **Credenciales** | Todos | Credencial digital con QR para cada afiliado |
| **Consejo** | SA, AM | Organigrama, cargos, asignaciones |
| **Configuración** | SUPER_ADMIN | SMTP, PayPal, IoT (Shelly, HikVision), GPS, WhatsApp |

**Abreviaciones de roles:** SA=SUPER_ADMIN, AM=ADMIN_MEMBRESIAS, AF=ADMIN_FINANCIERO, CE=COORD_EVENTOS, EP=EJECUTIVO_PROSPECTOS, AFI=AFILIADO

---

## 🔒 Seguridad

- Contraseñas hasheadas con `password_hash()` / `password_verify()` (bcrypt)
- Protección CSRF en todos los formularios POST
- Prepared statements en todas las consultas SQL
- Regeneración de ID de sesión en cada login
- Control de acceso basado en roles (RBAC)
- Timeout de sesión configurable (default: 2 horas)
- Bitácora de actividad completa

---

## 🛠️ Tecnologías

- **Backend:** PHP 7.4+ (sin framework)
- **Base de datos:** MySQL 5.7 con PDO
- **Frontend:** Tailwind CSS (CDN), Chart.js (CDN)
- **Servidor:** Apache 2.4 + mod_rewrite

---

## 📝 Datos de ejemplo incluidos

- 5 usuarios con diferentes roles
- 10 afiliados con estados variados (ACTIVO, SUSPENDIDO, VENCIDO, PROSPECTO)
- 3 planes de membresía
- 8 suscripciones y 9 pagos
- 5 eventos (con registros de asistencia)
- 5 prospectos en diferentes estados
- Estructura del consejo con 8 cargos y 6 asignaciones
- Configuraciones del sistema predefinidas

---

## 📞 Soporte

**Consejo de Derechos Humanos de Querétaro A.C.**  
📧 contacto@cdhq.org  
📞 +52 442 123 4567  
🕐 Lunes a Viernes 9:00 – 18:00 hrs

---

*Sistema CDHQ v1.0.0 – © 2024 Consejo de Derechos Humanos de Querétaro A.C.*
