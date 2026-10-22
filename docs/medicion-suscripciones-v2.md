# Medición de Vendly: cobros y activación (versión 2)

## Qué cuenta cada evento

| Evento de Meta | Acción real | Identificador |
| --- | --- | --- |
| `Subscribe` | Primer cobro de suscripción confirmado de un cliente | `subscription-payment-{id}` |
| `SubscriptionRenewed` | Cobro posterior confirmado, incluso después de vencer o en otra tienda del mismo cliente | `subscription-payment-{id}` |
| `StoreConfigured` | Primera observación de tienda disponible con nombre, slug, WhatsApp de 7–15 dígitos y producto con nombre, imagen principal y precio positivo | `store-configured-v2-{store_id}` |

Los tres llevan `measurement_version=2`. Los cobros incluyen `payment_kind`, `payment_id`, `value` real, `currency=COP` y plan cobrado. No se envían referencias bancarias a Meta. No se supone que el cobro equivale al precio de lista.

Se usa el píxel SaaS `LANDING_META_PIXEL_ID`, no el píxel configurado por cada tienda. Los pedidos de compradores, Mercado Pago y Wompi de las tiendas no alimentan este registro de suscripciones. El dato externo de identidad continúa siendo el de la tienda para mantener compatibilidad con los registros anteriores; la clasificación de primer pago/renovación se hace por cliente (`user_id`).

## Registrar un pago

En **Usuarios → Editar → Registrar cobro recibido de Vendly**:

1. Comprobar que el dinero se recibió y que corresponde a Vendly, no a un pedido de una tienda.
2. Seleccionar una sola tienda asociada al cobro y el plan cobrado. Premium con varias tiendas se registra una vez por cobro.
3. Escribir el importe real en COP, medio, referencia única y fecha/hora real de Colombia.
4. Si es el primer recibo del historial nuevo, confirmar si es el primer pago real de ese cliente o una renovación de un cliente antiguo. No inferirlo del acceso activo.
5. Confirmar recepción y guardar. Si ya existen recibos, el siguiente se clasifica como renovación automáticamente.
6. Si corresponde habilitar acceso, usar la acción habitual de activar/extender. Registrar dinero no suma días y sumar días no registra dinero.

Reenviar el mismo formulario o la misma referencia no agrega otro recibo ni evento. Reutilizarlo con datos distintos se rechaza. Registrar comprobantes en orden cronológico. Los importes son brutos recibidos; no representan beneficio ni descuentan devoluciones, costos o impuestos. No hay modificación o eliminación de comprobantes desde esta interfaz.

**No se convierten los 17 estados activos ni las extensiones anteriores en pagos comprobados.** Conciliar con comprobantes reales. El historial nuevo puede empezar con una renovación sin inventar un primer cliente pagador.

## Activación y límite de la comprobación

Guardar ajustes o terminar el wizard ya no emite `StoreConfigured` desde el navegador. Un observador comprueba Store/Product al guardar y conserva el evento una sola vez en base de datos. También se detecta la configuración al corregir un producto existente.

Se comprueba que la tienda y su propietario tengan acceso activo, pero no se hace una petición HTTP externa ni se prueba el envío de WhatsApp. La imagen debe estar registrada; esta comprobación no verifica físicamente que el archivo exista. El evento demuestra que se cumplen los campos mínimos, no que se haya recibido una venta ni que el propietario haya compartido el enlace.

Los cambios masivos con SQL o `query()->update()` no disparan observadores de Eloquent. Importaciones futuras deben invocar `VendlyMeasurementService::storeConfigured()` después de terminar cada tienda. La nueva medición no reconstruye fechas de activación históricas.

## Entrega, reintentos y duplicados

Los eventos se guardan en una bandeja persistente, con identidad de cliente cifrada y datos de contacto normalizados/hash para CAPI. Los cobros no usan la IP ni el navegador del administrador. No se emiten también en Pixel: cada evento de esta versión tiene una sola fuente, el servidor.

Una activación realizada por el propietario conserva URL, agente de usuario y señales de coincidencia de su solicitud web. Si un administrador prepara la tienda, se registra como acción del sistema sin utilizar su IP o navegador como si fueran del cliente. El registro de prueba también incluye su URL de origen en CAPI.

`vendly:send-measurements` usa bloqueos de fila, conserva ID y fecha originales, verifica `events_received` y marca `sent_at` solo tras recepción confirmada. Errores HTTP, falta de token y respuestas sin recepción quedan pendientes con espera entre reintentos. Una respuesta recibida por Meta no garantiza atribución publicitaria.

Si una respuesta se pierde después de recibirla Meta, el reintento conserva el mismo ID. No se afirma entrega exactamente una vez fuera de la base local. Los eventos de más de seis días se conservan como históricos sin enviarlos ni cambiar su fecha para aparentar conversiones actuales.

Los jobs antiguos `SubscriptionActivated`/`SubscriptionRenewed` que quedaron en cola se descartan: no tienen comprobante. El histórico ya recibido por Meta no se puede corregir con este cambio. Para comparar resultados nuevos, usar la fecha de corte y `measurement_version=2`, sin mezclar los antiguos `StoreConfigured`.

## Aplicación en servidor

Este cambio de código **no despliega ni migra automáticamente producción**. Hacer copia de seguridad y seguir el despliegue habitual; no ejecutar `migrate:fresh` en producción.

Estado comprobado del entorno local el 22 de octubre de 2026: migración aplicada; tres tiendas existentes marcadas como base histórica; cero eventos enviados a Meta y cero cobros registrados en el historial nuevo. Pixel SaaS y token CAPI no están configurados localmente. Esto no describe la base ni la configuración del servidor de producción.

Durante el despliegue, antes de habilitar el scheduler de la medición nueva:

```powershell
php artisan down
php artisan migrate --force
php artisan vendly:baseline-measurements
php artisan config:cache
php artisan queue:restart
php artisan up
php artisan vendly:measurement-status
```

La base histórica marca tiendas que ya estaban listas sin enviar nuevas conversiones. Ejecutarla una sola vez en el corte inicial, mientras no se aceptan cambios. No ejecutarla después como reparación general: podría ocultar activaciones nuevas.

Configurar el píxel SaaS y token válidos en el servidor, sin mostrarlos en logs. El scheduler habitual de Laravel (`schedule:run` cada minuto) entrega hasta 50 pendientes cada cinco minutos. Sin scheduler, se guardan localmente pero no salen a Meta. Puede ejecutarse manualmente:

```powershell
php artisan vendly:send-measurements --limit=50
php artisan vendly:measurement-status
```

`META_TEST_EVENT_CODE` habilitado bloquea la entrega de estos eventos reales, dejándolos pendientes. No quitarlo sin confirmar que el píxel/token son los correctos. No utilizar comprobantes ficticios en producción para probar conversiones: usar el entorno de pruebas y Eventos de prueba de Meta.

## Validación antes de invertir

Validación local realizada: **46 pruebas aprobadas, 232 aserciones**, con SQLite en memoria y HTTP simulado:

```powershell
php vendor/bin/pest tests/Feature/VendlyMeasurementTest.php tests/Feature/StoreOnboardingWizardTest.php --compact
php vendor/bin/pest tests/Feature/StoreAvailabilityTest.php --filter='subscription|extend|meta|dashboard|trial signup' --compact
```

Se ajustó una expectativa anterior del checklist: su fixture ya completa información básica e identidad, dos tareas de cinco (40 %, no 20 %). No se cambió el cálculo del onboarding.

- Ejecutar la suite `tests/Feature/VendlyMeasurementTest.php` con SQLite en memoria y HTTP simulado.
- Desplegar y verificar migración, base histórica, caché de configuración y scheduler.
- En entorno de pruebas, comprobar los tres nombres y parámetros en Eventos de prueba de Meta, sin crear cobros falsos en producción. El acceso a la cuenta Meta no se comprueba con pruebas locales.
- En producción, conciliar el siguiente cobro real con su recibo local y evento recibido. Una extensión gratuita no debe crear `Subscribe` ni `SubscriptionRenewed`.
- Revisar pendientes y errores. Configurar la optimización de campaña solo después de verificar recepción y definición de primer pago; no optimizar por todos los cobros incluyendo renovaciones.

Las UTM y la atribución persistente del origen no forman parte de este cambio; seguirán siendo necesarias para calcular CAC por anuncio de extremo a extremo.

Referencias oficiales consultadas: https://developers.facebook.com/docs/marketing-api/conversions-api/parameters/server-event/ y https://developers.facebook.com/docs/marketing-api/conversions-api/deduplicate-pixel-and-server-events/. En esta sesión respondieron HTTP 429; no se considera comprobada la disponibilidad actual de opciones de la cuenta Meta.
