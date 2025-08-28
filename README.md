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
