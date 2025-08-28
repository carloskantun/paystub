# paystub

Lo hacemos en PHP “puro” + MySQL + Composer, sin Laravel, pensado para hosting compartido (cPanel) y reutilizable para varios dominios. Te explico con mentalidad de arquitecto senior/PhD: cómo, cuándo, dónde y qué archivo crear; cómo generar PDF con marca de agua en el preview, cómo elegir plantillas y n meses de pay stubs, y cómo cobrar y entregar.

Además, investigué paystubcreator.net para calcar la lógica de flujo (no el branding): 1) llenar datos → 2) previsualizar (con opción de cambiar plantilla) → 3) elegir # de stubs → 4) pagar → 5) descargar/recibir por email. Su UI muestra “Enter info → Preview → Download”, plantillas seleccionables, y pedido de n stubs por orden; venden por período (cada “pay period” es un stub independiente). 


Tus PDFs reales confirman la distribución interna: encabezado (empleador/empleado), Earnings (Regular/OT/Holiday/PTO/Tips…), Deductions (Medical/Dental/Accident/Meals…), Taxes (Federal/SS/Medicare/State/Local), Pay Summary (Gross / FIT Taxable / Taxes / Deductions / Net), y Net Pay Distribution (cuenta bancaria). 

1) Estructura del proyecto (sin framework; Composer sí)

Cuándo: al iniciar.
Dónde: raíz del docroot del dominio (p.ej. public_html/).

Carpetas/archivos (qué crear):

/public_html/
  /public/                     ← ÚNICA carpeta pública (index.php, assets)
    index.php
    .htaccess                  ← rewrite a index.php
    /assets/                   ← css/js/imagenes demo (plantillas jpg/png de preview)
  /app/
    /Controllers/              ← archivos PHP por endpoint
      FormController.php       ← render form + preview
      OrderController.php      ← crear/editar order (draft)
      CheckoutController.php   ← crear sesión de pago (Stripe/MP)
      WebhookController.php    ← recibir confirmación de pago
      PdfController.php        ← generación/entrega de PDFs
      TemplateController.php   ← catálogo de plantillas
    /Services/
      PdfService.php           ← HTML→PDF (dompdf/mpdf), marca de agua on/off
      PricingService.php       ← precios por #stubs/plantilla
      PeriodsService.php       ← generar N períodos (weekly/biweekly/semi-monthly/monthly)
      PaymentsService.php      ← Stripe/Mercado Pago
      EmailService.php         ← envío con adjuntos/enlaces presignados
      OrdersService.php        ← orquestración: guardar breakdown, folio, versiones
      TenantsService.php       ← multi-dominio (branding/moneda/precio claves)
      TokenService.php         ← enlaces temporales de descarga
    /Models/
      Order.php, EarningItem.php, DeductionItem.php, TaxItem.php, Payment.php, Tenant.php
    /Views/                    ← plantillas HTML (Blade-like o Twig opcional; puede ser PHP nativo)
      form.php
      preview.php              ← mismo layout del PDF pero con watermark visible
      pdf-layout-*.php         ← 1 archivo por plantilla (Horizontal Blue, Black, etc.)
      email-receipt.php
    /Config/
      app.php                  ← modo multi-tenant o instalable
      payments.php             ← stripe/mp
      templates.php            ← catálogo de plantillas disponibles
  /storage/
    /paystubs/                 ← PDFs finales (NO público)
    /tmp/                      ← temporales
  /vendor/                     ← Composer
  composer.json
  .env                         ← DB, Stripe/MP, SMTP, OPENAI (si usas IA)


En cPanel: si no puedes usar SSH para composer install, sube /vendor ya generado. Protege /storage/paystubs con .htaccess (deny all) y sirve PDFs vía PdfController.php con token y expiración (no expongas ruta directa).

2) Paquetes Composer (lista; Copilot te genera el composer.json)

dompdf/dompdf o mpdf/mpdf → HTML→PDF.

guzzlehttp/guzzle → llamadas a Stripe/MP y (si quieres) OpenAI.

stripe/stripe-php, mercadopago/dx-php → pagos.

phpmailer/phpmailer → email SMTP.

vlucas/phpdotenv → .env.

(Opcional) altorouter/altorouter → routing sin framework.

ramsey/uuid → folios.

monolog/monolog → logs.

3) Esquema MySQL mínimo (sin SQL literal; nombres de tablas/campos)

Cuándo: antes de programar controladores.
Dónde: prepara un .sql de migración inicial.

tenants (si multi-dominio): id, domain, brand_name, logo_url, currency, watermark_text, price_per_stub, stripe_pk/sk, mp_keys, email_from.

orders: id (folio UUID), tenant_id, email, status (draft|pending|paid), template_key, pay_schedule (weekly|biweekly|semi-monthly|monthly), count_stubs, bundle_mode (combined|separate), period_start, period_end, pay_date, gross, net, fit_taxable_wages, taxes_total, deductions_total, employee_json, employer_json, pdf_path (si combined), version_of (nullable), created_at.

earnings: order_id, stub_index (0..N-1), label (Regular|OT|Holiday|PTO|Tips|Training|…), hours, rate, current_amount, ytd_amount, sort_order.

deductions: order_id, stub_index, label (Medical|Dental|Vision|Accident|Meals|…), pretax bool, current_amount, ytd_amount, sort_order.

taxes: order_id, stub_index, label (Federal|SS|Medicare|State|Local|Unemployment), current_amount, ytd_amount, sort_order.

payments: order_id, provider (stripe|mp), session_id, status, currency, amount_total, webhook_payload_json, created_at.

audit_logs: id, actor (user|bot), action (create|regenerate|resend), order_id, meta_json, created_at.

Estos bloques reflejan lo que ves en los ejemplos reales: Earnings, Deductions, Taxes, Pay Summary, Net Distribution, etc. 

4) Flujo funcional (calcado de paystubcreator.net, sin copiar branding)

A. Captura & Preview (marca de agua)

Dónde: /app/Controllers/FormController.php + /Views/form.php y /Views/preview.php.

Qué: Form con datos de empleador/empleado, pay schedule, horas/rate o salary, opción de plantilla (catálogo) y # de stubs (n meses / n períodos).

Lógica de períodos: si el usuario elige “3 meses” y pay schedule = biweekly, PeriodsService calcula 6 períodos hacia atrás o hacia adelante (según UI).

Preview: se renderiza HTML idéntico al layout del PDF (misma plantilla) pero con overlay “PREVIEW ONLY” (CSS), sin botón de descarga.

Esto coincide con el paso “Enter info → Preview → Download” que exponen (descarga sólo al final). 
PaystubCreator

B. Selección de plantilla

Dónde: /app/Controllers/TemplateController.php + /Config/templates.php.

Qué: Galería con miniaturas (Horizontal Blue, Black, etc.), botón “Use this template” y “Change template” sobre el preview. 

C. Cantidad de stubs / carrito

Dónde: OrderController.php + PricingService.php.

Qué: Igual que su lógica de vender por pay period (cada stub cuenta/vale). Muestra “Order n × Stubs” y el precio total. 

D. Pago (Stripe / Mercado Pago)

Dónde: CheckoutController.php (crea sesión) y WebhookController.php (confirma).

Qué: Redirige a Checkout; al volver, NO confíes en la redirección del navegador: la fuente de verdad es el webhook firmado.

Al confirmar pago (webhook): marca orders.status = paid y encola generación de PDFs.

E. Generación de PDFs

Dónde: PdfController.php + PdfService.php.

Qué: Por cada stub (1..N), renderiza Views/pdf-layout-PLANTILLA.php → HTML → PDF.

Modo combined: 1 PDF con N páginas.

Modo separate: N PDFs individuales (tu caso: “dividido por cada uno”).

Guardado: /storage/paystubs/{folio}/….

Entrega:

Email: adjuntar (si son pocos) o enviar link con token (expira).

Descarga: desde dashboard de orden (requiere folio + email o token).

El PDF lleva folio impreso (encabezado).

Tip: si N grande, zipea y manda un .zip con todos (o links).

F. Reenvío/Regeneración

Dónde: OrderController.php (resend), PdfController.php (regenerate).

Qué: Con folio, reenvía; si hay corrección, crea nueva versión (no edites retroactivamente). Tu FAQ y UX pueden prometer correcciones sin costo (ellos lo dicen), si decides copiar esa política de negocio. 

5) Generación de PDF (detalle “doctorado”: precisión y performance)

Objetivo: que preview y pdf final sean idénticos en maquetación.

Plantillas

Dónde: /Views/pdf-layout-*.php (una por diseño).

Slots obligatorios (basados en tus PDFs):

Encabezado: empresa (logo, nombre, dirección), empleado (nombre, dirección, SSN últimos 4, número, puesto). 

Periodo: start/end/pay date, frecuencia.

Earnings: filas (Regular/OT/Holiday/PTO/Tips/Training…) con columnas Pay Type/Hours/Rate/Current/YTD. 

Deductions: filas (Medical/Dental/Vision/Accident/Meals…) con Current/YTD y si es pre-tax. 

Taxes: Federal/SS/Medicare/State/Local/Unemployment con Current/YTD. 

Pay Summary: Gross / FIT Taxable Wages / Taxes / Deductions / Net Pay (current y/o YTD). 

Net Pay Distribution (si aplica). 

Preview (marca de agua)

Dónde: /Views/preview.php.

Cómo: mismo HTML que el PDF, pero con overlay CSS position: fixed; opacity; pointer-events:none con texto “PREVIEW ONLY – NOT FOR USE”.

No generes PDF en preview; sólo HTML. Bloquea botón derecho si quieres (sólo frena al casual; screenshots siempre se pueden).

PDF final

Dónde: PdfService.php.

Cómo:

Construye el HTML con la plantilla elegida.

Dompdf/mPDF lo convierten a PDF (fuentes embebidas WOFF/TTF).

Inyecta folio visible y fecha de emisión.

Guarda en /storage/paystubs/{folio}/{index}.pdf (separate) o {folio}.pdf (combined).

Performance:

Mantén CSS simple (tablas o flex básicos).

Embebe 1–2 fuentes máximo.

1–2 páginas por stub para tiempos sub-segundo.

Si el host limita CPU, genera asíncrono tras webhook y notifica por email.

6) Lógica para “n meses” (y/o n pay periods)

Dónde: PeriodsService.php.
Qué hace (sin código):

Input: pay_schedule (weekly/biweekly/semi-monthly/monthly), start_or_end_anchor (p.ej. fecha de pago o fecha fin), n_months o n_periods.

Algoritmo:

weekly: cada 7 días; biweekly: cada 14; semi-monthly: 1–15 y 16–fin de mes; monthly: fin de mes.

Si el usuario pide “3 meses” y schedule biweekly → calcula ~6 períodos contiguos.

Genera para cada período: period_start, period_end, pay_date.

UI: tabla editable con los períodos calculados para que el usuario ajuste fechas si quiere.
Esto concuerda con su enfoque de pedir varios stubs juntos (con ofertas tipo “agrega 4 y obtén 1 gratis”, “n stubs por orden”). 

7) Pagos, precios y entrega

Precios

Dónde: PricingService.php.

Qué: precio por stub (como ellos: cobran por pay period; el FAQ lo expresa explícito). 

Descuento por volumen (ej. 5× y uno free). 

Pagos

Dónde: CheckoutController.php y WebhookController.php.

Stripe: Checkout Session con cantidad = n stubs.

MP: Preferencia de pago.

Webhook (fuente de verdad): valida firma; si paid → dispara generación de PDFs.

Entrega

Email (SMTP) con:

Adjuntos si son pocos (p.ej. ≤5).

Link con token y expiración para descarga (si son muchos o pesados).

Descarga web: GET /orders/{folio} (pide email o token).

Reenvío/Corrección: “¿error técnico?” → nueva versión (mantener histórico). Su FAQ dice que corrigen sin costo; lo puedes replicar como política. 

8) Multi-dominio (misma base reutilizable)

Modo A (multi-tenant)

Dónde: TenantsService.php + tabla tenants.

Detecta $_SERVER['HTTP_HOST'] y carga branding, moneda, precio, watermark, claves Stripe/MP.

Un solo deploy, varios Addon Domains en cPanel.

Modo B (instalable)

Empaqueta ZIP con installer (pide DB/SMTP/Stripe/MP).

Un proyecto por dominio, aislados entre sí.

9) Por dónde empezar (checklist “cuando/qué/dónde”)

Inicialización (hoy)

Dónde: raíz del proyecto.

Crea estructura de carpetas; prepara composer.json (paquetes listados).

.env con credenciales de MySQL, SMTP, Stripe/MP.

Base de datos (hoy)

Dónde: script .sql inicial.

Crea tablas listadas (orders/earnings/deductions/taxes/payments/[tenants]).

Catálogo de plantillas (hoy)

Dónde: /Config/templates.php + /Views/pdf-layout-*.php + /public/assets/plantillas/*.png.

Sube 2–3 diseños (p.ej. “Horizontal Blue/Black”) con miniaturas (preview). 

Form & Preview (día 1)

Dónde: FormController.php, preview.php, TemplateController.php.

Implementa overlay watermark en preview (sin PDF).

Lógica de períodos (día 1)

Dónde: PeriodsService.php.

Genera N períodos según schedule (editable).

Cálculo y breakdown (día 2)

Dónde: OrdersService.php (guarda), PricingService.php (precio total).

Earnings/Deductions/Taxes por stub (estructura inspirada en tus PDFs). 

Checkout & Webhook (día 2)

Dónde: CheckoutController.php, WebhookController.php.

Al webhook → status=paid → encolar generación.

Generación de PDF (día 3)

Dónde: PdfService.php, PdfController.php.

Modo separado o combinado; guarda en /storage/paystubs/{folio}/….

Entrega (email/descarga) (día 3)

Dónde: EmailService.php, PdfController.php.

Adjuntos o links temporales (token + expiración).

Reenvío/Regeneración (día 4)

Dónde: OrderController.php.

Reenvío por folio; nueva versión para correcciones.

Multi-dominio (día 4)

Dónde: TenantsService.php.

Insertar filas por dominio y probar Addon Domains.

10) Notas de producto que replican su “feel”

Promesa de rapidez: “llena, previsualiza gratis, y descarga” (ellos lo destacan). 

Elección de plantilla con botón “Use this template / Change template” (galería). 


Compra por pay period (precio unitario por stub; total = n stubs). 

Packs/Promos (ej. “agrega 4 y 1 gratis”). 


Email inmediato y “puedes volver a editar” (política de corrección). 
