# Plan de Trabajo / Tareas Pendientes

Estado actual: Wizard funcional (3 pasos), selección de plantilla con carrusel + watermark, cálculo preliminar en preview usando `CalculationOrchestrator` (no persistido aún), tablas fiscales cargadas en BD.

## Prioridades (Alta → Baja)
1. Persistencia automática de líneas calculadas al avanzar a Step 3.
2. Columnas `origin` (auto|manual) en earnings / taxes / deductions y lógica de recálculo selectivo.
3. Integrar cálculo en generación PDF (fallback a cálculo si no hay líneas guardadas).
4. Reemplazar `seedStubData` por orquestador.
5. Panel Admin mínimo (login + lectura parámetros fiscales).
6. CRUD completo de parámetros fiscales (años, FICA, brackets, tasas estatales, clone year).
7. UI de edición manual línea por línea (earnings / taxes / deductions) con protección ante recálculo.
8. Distribución / método de pago (split net pay) editable.
9. Multi‑tenant overrides (por `tenant_id`).
10. Auditoría ampliada (log de cambios fiscales y ediciones manuales).
11. Tests automatizados (cálculo, repositorio fiscal, rutas críticas).
12. Hardening seguridad (rate limiting preview, sanitización extra, headers).

## Fase 1 – Cálculo Persistido (MVP real)
- [ ] Añadir columnas:
  - ALTER TABLE earnings ADD origin ENUM('auto','manual') DEFAULT 'auto';
  - (idem para deductions, taxes).
- [ ] Hook en Step 3 (cuando se pasa de Step 2 → 3):
  - Si no existen filas earnings/taxes/deductions para la orden (o flag de recalcular) ⇒ persistir batch.
- [ ] Refactor `OrdersService::seedStubData` → usar `CalculationOrchestrator`.
- [ ] PDF (PdfService) recupera items; si vacío y orden en draft ⇒ recalcular en memoria para vista exacta.
- [ ] Campos resumen (gross, taxes_total, deductions_total, net) actualizarse en `orders` tras persistir batch.
- [ ] Validación de redondeos (sumatoria de líneas == totals).

## Fase 2 – Panel Admin (Config Fiscal)
- [ ] Rutas /admin/login, /admin, middleware auth (credenciales .env ADMIN_USER / ADMIN_PASS).
- [ ] Vistas de sólo lectura: año vigente, botones editar.
- [ ] CRUD tax_years (create/update, no delete duro; soft block si usado).
- [ ] CRUD federal_brackets (tabla editable ordenada por `bracket_order`).
- [ ] CRUD fica_limits (un registro por año, upsert simple).
- [ ] CRUD state_tax_rates (grid con edición inline + bulk import CSV).
- [ ] Acción “Clone Year”: duplica tax_years, federal_brackets, fica_limits, state_tax_rates a YEAR+1 si no existen.
- [ ] Logs en audit_logs (actor=bot/user; action=create|update; meta con tabla y clave).

## Fase 3 – Edición Manual y Re‑cálculo Seguro
- [ ] UI Step 2: panel secundario mostrando earnings/taxes calculados.
- [ ] Botón “Convertir a editable” ⇒ clona valores a inputs; cambia origin a manual.
- [ ] Regla de recálculo: solo filas origin=auto se vuelven a recalcular si cambian base (rate, hours, schedule, pay_type, state).
- [ ] Indicador visual de filas manuales (icono lock / badge Manual).
- [ ] Opción “Revertir a automático” por fila (resetea a valor actual de nuevo cálculo).
- [ ] Deducciones manuales (beneficios) se suman a deductions_total y net.

## Fase 4 – Mejoras de Cálculo / Exactitud
- [ ] Federal: soportar filing_status (single/married) seleccionable.
- [ ] Distribuir deducción estándar por periodo de forma proporcional (ya aproximado: revisar).
- [ ] Ajustar Social Security cuando cruce wage base en mitad de un stub (prorrateo real, no corte abrupto).
- [ ] Additional Medicare: aplicar solo por segmento real dentro del periodo.
- [ ] Future: tablas estatales progresivas (state_tax_brackets) en lugar de tasa plana.
- [ ] Pre‑tax deductions: recalcular base imponible para FICA/Federal.
- [ ] YTD previos (carga inicial) para simular stub intermedio del año.

## Fase 5 – Panel Admin Avanzado
- [ ] Roles (admin, auditor).
- [ ] Historial de cambios por registro (calc_overrides versioning o tabla log separada).
- [ ] Export / Import JSON completa de un año fiscal.
- [ ] Validaciones cruzadas: bracket sequence sin solapamientos, thresholds ascendentes.

## Fase 6 – Multi‑Tenant y Overrides
- [ ] Extender consultas a considerar tenant_id primero luego fallback global.
- [ ] UI admin: seleccionar tenant y ver overrides (calc_overrides).
- [ ] Herencia: mostrar qué valores vienen de global y cuáles son override.

## Fase 7 – Calidad y Seguridad
- [ ] Tests unitarios: federalTax(), cálculo FICA con tope, Additional Medicare.
- [ ] Tests integración: preview -> persist -> pdf (números consistentes).
- [ ] Sanitización extra (whitelist state codes, trimming, numeric casts estrictos).
- [ ] Rate limit endpoint /create/preview (evitar abuso rendering).
- [ ] Security headers (Content-Security-Policy, X-Frame-Options, etc.).

## Fase 8 – UX / UI Extras
- [ ] Mostrar breakdown por stub (tooltip o tabla expandible) en Step 2.
- [ ] Loading skeletons para preview.
- [ ] Notificación toast al recalcular.
- [ ] Dark / Light toggle.

## Métricas / Aceptación (por prioridad)
- Persistencia: crear orden y avanzar a Step 3 ⇒ BD contiene líneas con origin=auto y totals correctos (dif < 0.01).
- Panel admin: editar tasa federal y ver cambio reflejado en nuevo cálculo preview (cache invalidado).
- Re‑cálculo seguro: fila manual permanece inalterada tras cambiar horas y recalcular resto.
- PDF/Preview paridad: mostrar mismos totales (diff <= 0.01).

## Scripts / Utilidades Futuras
- CLI: `php bin/clone-year.php 2025` duplica config a 2026.
- CLI: `php bin/recalc-order.php <order_id>` fuerza recálculo (auto filas).

## Riesgos / Mitigaciones
- Desfase montos preview vs PDF → Unificar método único (orchestrator) para ambos.
- Ediciones manuales sobrescritas → flag origin + confirm modal.
- Inconsistencias redondeo → centralizar helper `money_round()`.

## Quick Win Siguiente (Recomendado)
1. Columnas origin.
2. Persistir batch al entrar Step 3.
3. PDF fallback cálculo.
4. Refactor seedStubData.

Tras eso: iniciar Panel Admin mínimo.

---
Última actualización: {pendiente actualizar manualmente}
