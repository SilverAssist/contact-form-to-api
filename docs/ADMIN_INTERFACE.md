# WordPress Admin Interface - API Logs

## Overview
Esta es la interfaz de administración implementada con el enfoque híbrido, combinando el rendimiento de la tabla de base de datos con la familiaridad de la interfaz de WordPress.

## Página Principal de Logs

### Ubicación
**WordPress Admin → Contact Form 7 → API Logs**

### Componentes Principales

#### 1. Panel de Estadísticas (Superior)
```
┌─────────────────────────────────────────────────────────────────┐
│                      Estadísticas Generales                      │
├──────────────┬──────────────┬──────────────┬──────────────────────┤
│  Total       │  Exitosos    │  Fallidos    │  Tiempo Promedio   │
│  Requests    │              │              │  de Respuesta      │
│              │              │              │                    │
│    1,234     │    1,156     │      78      │     0.342s        │
│              │  (93.7%)     │              │                    │
└──────────────┴──────────────┴──────────────┴──────────────────────┘
```

#### 2. Filtros de Estado (Pestañas)
```
All (1,234) | Success (1,156) | Errors (78)
```

#### 3. Barra de Búsqueda y Acciones Bulk
```
┌─ Acciones Bulk ─┐  [Aplicar]           [ Buscar logs... ] [Buscar]
```

#### 4. Tabla de Logs (WP_List_Table)
```
┌──┬──────────────┬────────────────────────────┬────────┬─────────┬──────────┬────────┬────────┬─────────────────┐
│☐ │ Formulario   │ Endpoint                   │ Método │ Estado  │ Response │ Tiempo │ Retry  │ Fecha           │
├──┼──────────────┼────────────────────────────┼────────┼─────────┼──────────┼────────┼────────┼─────────────────┤
│☐ │ Contacto     │ https://api.example.com/v1 │  POST  │ Success │   200    │ 0.245s │   0    │ 2026-01-02 10:15│
│  │              │ Ver Detalles | Eliminar    │        │         │          │        │        │                 │
├──┼──────────────┼────────────────────────────┼────────┼─────────┼──────────┼────────┼────────┼─────────────────┤
│☐ │ Registro     │ https://api.service.io/api │  POST  │ Error   │   500    │ 1.234s │   3    │ 2026-01-02 09:45│
│  │              │ Ver Detalles | Reintentar  │        │         │          │        │        │                 │
│  │              │ Eliminar                    │        │         │          │        │        │                 │
└──┴──────────────┴────────────────────────────┴────────┴─────────┴──────────┴────────┴────────┴─────────────────┘

       Mostrando 1-20 de 1,234                                           [← 1 2 3 ... 62 →]
```

### Características de la Tabla

#### Columnas Ordenables
- ✅ Formulario (por ID)
- ✅ Estado
- ✅ Response Code
- ✅ Tiempo de Ejecución
- ✅ Retry Count
- ✅ Fecha (orden por defecto: DESC)

#### Filtros Disponibles
- **Por Estado**: All, Success, Errors (incluye client_error, server_error)
- **Por Formulario**: Click en nombre del formulario para filtrar
- **Búsqueda**: Busca en endpoint y mensajes de error

#### Acciones por Fila
- **Ver Detalles**: Muestra vista completa del log
- **Reintentar**: Re-ejecuta el request (solo para errores)
- **Eliminar**: Elimina el log

#### Acciones Bulk
- **Eliminar**: Elimina logs seleccionados
- **Reintentar**: Reintenta requests seleccionados

## Página de Detalle de Log

### Navegación
Click en "Ver Detalles" o en el endpoint → Muestra vista detallada

### Estructura

```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
                         API Log Detail
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

[← Volver a Logs]

┌─────────────────────────────────────────────────────────────────┐
│                   Información del Request                        │
├──────────────────────┬──────────────────────────────────────────┤
│ Endpoint             │ https://api.example.com/v1/submit        │
│ Método               │ [POST]                                   │
│ Estado               │ [Success]                                │
│ Fecha                │ 02/01/2026 10:15:23                      │
│ Tiempo de Ejecución  │ 0.245s                                   │
│ Reintentos           │ 0                                        │
└──────────────────────┴──────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                      Request Headers                             │
├─────────────────────────────────────────────────────────────────┤
│ {                                                                │
│   "Content-Type": "application/json",                           │
│   "Authorization": "***REDACTED***",                            │
│   "User-Agent": "WordPress/6.5; https://example.com"            │
│ }                                                                │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                       Request Data                               │
├─────────────────────────────────────────────────────────────────┤
│ {                                                                │
│   "name": "John Doe",                                           │
│   "email": "john@example.com",                                  │
│   "message": "Hello, this is a test message",                   │
│   "form_id": "123"                                              │
│ }                                                                │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                   Información de Respuesta                       │
├──────────────────────┬──────────────────────────────────────────┤
│ Response Code        │ 200                                      │
└──────────────────────┴──────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                      Response Headers                            │
├─────────────────────────────────────────────────────────────────┤
│ {                                                                │
│   "Content-Type": "application/json",                           │
│   "X-Request-Id": "abc123xyz",                                  │
│   "Date": "Thu, 02 Jan 2026 10:15:23 GMT"                      │
│ }                                                                │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                       Response Data                              │
├─────────────────────────────────────────────────────────────────┤
│ {                                                                │
│   "status": "success",                                          │
│   "id": "456789",                                               │
│   "message": "Data received successfully"                       │
│ }                                                                │
└─────────────────────────────────────────────────────────────────┘
```

## Características de UX

### Indicadores Visuales

#### Estados con Colores
- **Success**: Verde claro (#d7f5d7)
- **Error**: Rojo claro (#ffd6d6)
- **Client Error**: Rojo suave (#ffe5e5)
- **Server Error**: Rojo claro (#ffebe8)
- **Pending**: Amarillo (#fff2c7)

#### Métodos HTTP con Colores
- **GET**: Azul (#cce5ff)
- **POST**: Verde (#d4edda)
- **PUT/PATCH**: Amarillo (#fff3cd)
- **DELETE**: Rojo (#ffd6d6)

### Privacidad y Seguridad
- **Datos Sensibles Anonimizados**: 
  - Passwords, tokens, API keys → `***REDACTED***`
  - Campos sensibles detectados automáticamente
- **Pretty-Printed JSON**: Formato legible para debugging
- **Confirmaciones**: Diálogos de confirmación para acciones destructivas

### Responsive Design
- **Desktop**: Grid completo con todas las columnas
- **Tablet**: Columnas esenciales visibles
- **Mobile**: Lista vertical con información clave

## Flujo de Uso Típico

### Caso 1: Detectar Problemas
1. Usuario navega a **Contact Form 7 → API Logs**
2. Ve estadísticas: 78 errores de 1,234 requests
3. Click en pestaña **"Errors"** para filtrar
4. Ve lista de requests fallidos con códigos 500
5. Click en **"Ver Detalles"** de uno de ellos
6. Revisa error message y response data
7. Identifica problema en API endpoint
8. Opcionalmente, click en **"Reintentar"** después de fix

### Caso 2: Monitorear Formulario Específico
1. Click en nombre del formulario en la tabla
2. Ve solo logs de ese formulario
3. Revisa estadísticas filtradas
4. Verifica tasa de éxito

### Caso 3: Búsqueda de Endpoint Específico
1. Usa barra de búsqueda
2. Escribe parte del endpoint: "api.service.io"
3. Ve solo logs que coinciden
4. Analiza problemas específicos de ese endpoint

### Caso 4: Limpieza de Logs Antiguos
1. Filtra logs por fecha (usando ordenamiento)
2. Selecciona múltiples logs antiguos
3. Usa acción bulk **"Eliminar"**
4. Confirma eliminación

## Performance

### Optimizaciones Implementadas
- **Índices DB**: form_id, status, created_at
- **Paginación**: 20 items por página (configurable)
- **Queries Directas**: Sin ORM, consultas SQL optimizadas
- **Carga Lazy**: Solo carga detalles cuando se accede a vista individual
- **Cache Ready**: Estructura preparada para WordPress Object Cache

### Escalabilidad
- ✅ Maneja >100K logs sin problemas
- ✅ Queries optimizadas con LIMIT/OFFSET
- ✅ Filtros indexados para búsqueda rápida
- ✅ No impacta performance del frontend

## Próximas Mejoras Posibles

1. **AJAX Live Refresh**: Actualización automática de estadísticas
2. **Exportación**: Botón para exportar logs a CSV/JSON
3. **Dashboard Widget**: Resumen en WordPress Dashboard
4. **Alertas**: Notificaciones cuando tasa de error > umbral
5. **Gráficos**: Visualización de tendencias con Chart.js
6. **Filtros Avanzados**: Por rango de fechas, múltiples formularios
