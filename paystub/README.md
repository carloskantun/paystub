# Paystub

Generador de recibos de pago en PHP puro y MySQL.

## Instalación

1. **Clona el repositorio**
   ```bash
   git clone <URL> && cd paystub
   ```
2. **Instala las dependencias con Composer**
   ```bash
   composer install
   ```
3. **Configura las variables de entorno**
   - Copia `.env.example` a `.env` y completa las credenciales de MySQL, Stripe/Mercado Pago y SMTP.
4. **Importa la base de datos**
   - Ejecuta el script `database.sql` en tu servidor MySQL.
5. **Configura el directorio público**
   - En hosting compartido, apunta el docroot a la carpeta `public/`.
6. **Inicia el servidor local (opcional)**
   ```bash
   php -S localhost:8000 -t public
   ```

Para detalles de arquitectura y diseño consulta [README.design.md](README.design.md).

## Configuración de .env

Crea un archivo `.env` en la raíz (misma carpeta que `composer.json`). Ejemplo mínimo:

```
# App
APP_BASE_PATH=""

# Base de datos
DB_HOST=127.0.0.1
DB_NAME=paystub
DB_USER=root
DB_PASS=secret

# Stripe (usar llaves de prueba mientras desarrollas)
# Preferido: STRIPE_SECRET (acepta STRIPE_SK como fallback) y STRIPE_PK para frontend si se necesitara.
STRIPE_SECRET=sk_test_xxxxxxxxxxxxxxxxxxxxx
# Alternativa legacy aceptada: STRIPE_SK=sk_test_xxxxxxxxxxxxx
STRIPE_PK=pk_test_xxxxxxxxxxxxxxxxxxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxxxxxx

# Opcional
# APP_URL=https://midominio.com
```

Si `STRIPE_SECRET` falta:
- En Step 3 aparecerá una franja amarilla avisando que no hay configuración de pago.
- La creación de la sesión devolverá `Stripe not configured` en lugar de redirigir.

Tras agregar las llaves, recarga el navegador. Para webhooks ejecuta:
```
stripe listen --forward-to localhost:8000/webhook/stripe
```

