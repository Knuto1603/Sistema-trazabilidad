# Integración Nubefact — Sistema de Trazabilidad InterFruits

> Documentación de referencia para integrar la API de Nubefact (emisor electrónico OSE/PSE
> autorizado por SUNAT) con el sistema de trazabilidad agrícola de InterFruits.
>
> Fuentes originales: _NUBEFACT DOC API JSON V1.docx_ y _API NUBEFACT - GUIA DE REMISIÓN.docx_

---

## Tabla de contenidos

1. [Visión general de la API](#1-visión-general-de-la-api)
2. [Autenticación](#2-autenticación)
3. [Comprobantes electrónicos (Facturas, Boletas, Notas)](#3-comprobantes-electrónicos)
4. [Guías de Remisión Electrónica (GRE)](#4-guías-de-remisión-electrónica-gre)
5. [Manejo de errores](#5-manejo-de-errores)
6. [Análisis de integración con el sistema actual](#6-análisis-de-integración-con-el-sistema-actual)
7. [Plan de implementación sugerido](#7-plan-de-implementación-sugerido)

---

## 1. Visión general de la API

Nubefact expone una **API REST** que acepta y devuelve JSON. Todas las operaciones se realizan
mediante `POST` a una URL única por cuenta. No existen endpoints distintos por operación; la
diferencia la marca el campo `operacion` dentro del body.

### Operaciones disponibles

| Operación              | Campo `operacion`      | Descripción                                            |
|------------------------|------------------------|--------------------------------------------------------|
| Emitir comprobante     | `generar_comprobante`  | Factura, Boleta, Nota de Crédito/Débito                |
| Consultar comprobante  | `consultar_comprobante`| Estado en SUNAT de un comprobante ya emitido           |
| Anular comprobante     | `generar_anulacion`    | Comunicación de baja (void) de un comprobante          |
| Consultar anulación    | `consultar_anulacion`  | Estado de una comunicación de baja                     |
| Emitir guía remisión   | `generar_guia`         | GRE Remitente (tipo 7) o Transportista (tipo 8)        |
| Consultar guía         | `consultar_guia`       | Estado y enlaces PDF/XML de una GRE ya enviada         |

### Tipos de comprobante (`tipo_de_comprobante`)

| Código | Documento                     | Serie empieza con |
|--------|-------------------------------|-------------------|
| 1      | Factura Electrónica           | `F`               |
| 2      | Boleta de Venta               | `B`               |
| 3      | Nota de Crédito               | `F` o `B`         |
| 4      | Nota de Débito                | `F` o `B`         |
| 7      | Guía Remisión Remitente (GRE) | `T`               |
| 8      | Guía Remisión Transportista   | `V`               |

### Flujo general

```
Sistema → POST /api/v1/{ruta-cuenta}
         Headers: Authorization: {token}, Content-Type: application/json
         Body: { "operacion": "...", ...campos }
       ← Respuesta JSON con estado SUNAT, enlaces PDF/XML/CDR
```

> **Importante:** Las Guías de Remisión requieren **dos pasos**: primero `generar_guia` (SUNAT
> puede tardar segundos/minutos en aceptarla), luego `consultar_guia` para obtener el PDF/CDR.

---

## 2. Autenticación

Cada cuenta Nubefact tiene una **RUTA única** y uno o más **TOKEN**. Ambos se obtienen desde el
panel de Nubefact en la sección _API (Integración)_.

```
RUTA  (Online):   https://api.nubefact.com/api/v1/{uuid-cuenta}
RUTA  (Offline):  http://localhost:8000/api/v1/{uuid-cuenta}
RUTA  (Reseller): https://api.pse.pe/api/v1/{uuid-cuenta}
TOKEN: cadena alfanumérica de ~50 caracteres
```

Headers requeridos en cada request:

```
Authorization: {TOKEN}
Content-Type: application/json
```

### Almacenamiento en el sistema

Guardar en la entidad `Parametro` (ya existente):

| Alias Parámetro    | Descripción                                      |
|--------------------|--------------------------------------------------|
| `NUBEFACT_RUTA`    | URL base de la cuenta (incluye el UUID)          |
| `NUBEFACT_TOKEN`   | Token de autenticación                           |
| `NUBEFACT_SERIE_F` | Serie para facturas, ej: `F001`                  |
| `NUBEFACT_SERIE_B` | Serie para boletas, ej: `B001`                   |
| `NUBEFACT_SERIE_T` | Serie para GRE Remitente, ej: `T001`             |
| `NUBEFACT_SERIE_V` | Serie para GRE Transportista, ej: `V001`         |

---

## 3. Comprobantes electrónicos

### 3.1 Emitir comprobante (`generar_comprobante`)

#### Cabecera del JSON

| Campo                            | Tipo    | Req.        | Notas clave                                                         |
|----------------------------------|---------|-------------|---------------------------------------------------------------------|
| `operacion`                      | String  | Obligatorio | Siempre `"generar_comprobante"`                                     |
| `tipo_de_comprobante`            | Integer | Obligatorio | 1=Factura, 2=Boleta, 3=N.Crédito, 4=N.Débito                      |
| `serie`                          | String  | Obligatorio | 4 chars exactos. `F` para facturas, `B` para boletas               |
| `numero`                         | Integer | Obligatorio | Correlativo sin ceros. Máx. 8 dígitos                              |
| `sunat_transaction`              | Integer | Obligatorio | 1=Venta interna, 2=Exportación, 30=Detracción (ver tabla completa) |
| `cliente_tipo_de_documento`      | String  | Obligatorio | 6=RUC, 1=DNI, 0=No domiciliado (exportación)                      |
| `cliente_numero_de_documento`    | String  | Obligatorio | RUC, DNI, etc.                                                     |
| `cliente_denominacion`           | String  | Obligatorio | Razón social o nombre                                              |
| `cliente_direccion`              | String  | Obligatorio | Opcional en boletas                                                |
| `cliente_email`                  | String  | Opcional    | Para envío automático al cliente                                   |
| `fecha_de_emision`               | Date    | Obligatorio | Formato `DD-MM-YYYY`                                               |
| `fecha_de_vencimiento`           | Date    | Opcional    | Formato `DD-MM-YYYY`, posterior a emisión                          |
| `moneda`                         | Integer | Obligatorio | 1=Soles, 2=Dólares, 3=Euros                                        |
| `tipo_de_cambio`                 | Numeric | Condicional | Requerido si moneda ≠ Soles                                        |
| `porcentaje_de_igv`              | Numeric | Obligatorio | Normalmente `18.00`                                                |
| `total_gravada`                  | Numeric | Condicional | Base imponible gravada con IGV                                     |
| `total_inafecta`                 | Numeric | Condicional | Monto inafecto                                                     |
| `total_exonerada`                | Numeric | Condicional | Monto exonerado                                                    |
| `total_igv`                      | Numeric | Condicional | Monto total de IGV                                                 |
| `total`                          | Numeric | Obligatorio | Total final del comprobante                                        |
| `enviar_automaticamente_a_la_sunat` | Boolean | Condicional | `true` para emisión inmediata                                  |
| `enviar_automaticamente_al_cliente` | Boolean | Condicional | `true` para enviar PDF al email del cliente                    |
| `detraccion`                     | Boolean | Condicional | `true` si la operación está sujeta a detracción                   |
| `observaciones`                  | Text    | Opcional    | Hasta 1000 chars. Acepta `<br>` para saltos de línea              |
| `documento_que_se_modifica_tipo` | Integer | Condicional | Para N. Crédito/Débito: tipo del doc. original                    |
| `documento_que_se_modifica_serie`| String  | Condicional | Serie del doc. original que se modifica                           |
| `documento_que_se_modifica_numero`| Integer| Condicional | Número del doc. original que se modifica                          |
| `tipo_de_nota_de_credito`        | Integer | Condicional | 1=Anulación, 3=Corrección descripción, 6=Devolución total, etc.   |
| `tipo_de_nota_de_debito`         | Integer | Condicional | 1=Intereses mora, 2=Aumento de valor, 3=Penalidades, etc.         |
| `condiciones_de_pago`            | String  | Opcional    | Ej: `"CRÉDITO 15 DÍAS"`                                           |
| `medio_de_pago`                  | String  | Opcional    | Ej: `"TRANSFERENCIA BANCARIA"`. Usar `venta_al_credito` para crédito|
| `orden_compra_servicio`          | String  | Opcional    | N° de orden de compra, máx. 20 chars                              |
| `formato_de_pdf`                 | String  | Opcional    | `"A4"`, `"A5"` o `"TICKET"`                                       |
| `codigo_unico`                   | String  | Opcional    | ID propio del sistema para control de duplicados (máx. 20 chars)  |

#### Items (líneas del comprobante)

| Campo                 | Tipo    | Req.        | Notas                                                                  |
|-----------------------|---------|-------------|------------------------------------------------------------------------|
| `unidad_de_medida`    | String  | Obligatorio | `NIU`=Producto, `ZZ`=Servicio                                         |
| `codigo`              | String  | Opcional    | Código interno del producto                                            |
| `descripcion`         | Text    | Obligatorio | Nombre del producto/servicio                                           |
| `cantidad`            | Numeric | Obligatorio | Hasta 10 decimales                                                     |
| `valor_unitario`      | Numeric | Obligatorio | Sin IGV, hasta 10 decimales                                            |
| `precio_unitario`     | Numeric | Obligatorio | Con IGV, hasta 10 decimales                                            |
| `descuento`           | Numeric | Opcional    | Descuento de la línea antes de impuestos                               |
| `subtotal`            | Numeric | Obligatorio | `valor_unitario × cantidad − descuento`                               |
| `tipo_de_igv`         | Integer | Obligatorio | 1=Gravado oneroso, 8=Exonerado, 9=Inafecto, 16=Exportación            |
| `igv`                 | Numeric | Obligatorio | Total IGV de la línea                                                  |
| `total`               | Numeric | Obligatorio | Total de la línea (subtotal + igv)                                     |
| `anticipo_regularizacion` | Boolean | Obligatorio | `false` en operaciones normales                                   |
| `codigo_producto_sunat` | String | Opcional   | Código SUNAT del producto (catálogo SUNAT)                            |

#### Guías relacionadas (array `guias`)

Permite adjuntar guías de remisión a la factura:

| Campo             | Tipo    | Notas                                        |
|-------------------|---------|----------------------------------------------|
| `guia_tipo`       | Integer | 1=GRE Remitente, 2=GRE Transportista         |
| `guia_serie_numero` | String| Serie y número separados por guión: `T001-1` |

#### Venta al crédito (array `venta_al_credito`)

| Campo           | Tipo    | Notas                            |
|-----------------|---------|----------------------------------|
| `cuota`         | Integer | Número de cuota (1, 2, 3…)       |
| `fecha_de_pago` | Date    | Formato `DD-MM-YYYY`             |
| `importe`       | Numeric | Monto de la cuota                |

#### Valores de `sunat_transaction`

| Código | Descripción                                       |
|--------|---------------------------------------------------|
| 1      | Venta interna (más común)                         |
| 2      | Exportación                                       |
| 4      | Venta interna con anticipos                       |
| 29     | Ventas a no domiciliados que no califican exportación |
| 30     | Operación sujeta a detracción                     |
| 34     | Operación sujeta a percepción                     |

> **Para InterFruits (exportación agrícola):** usar `sunat_transaction: 2` y
> `cliente_tipo_de_documento: 0` para clientes no domiciliados.

### 3.2 Consultar comprobante (`consultar_comprobante`)

```json
{
  "operacion": "consultar_comprobante",
  "tipo_de_comprobante": 1,
  "serie": "F001",
  "numero": 1
}
```

### 3.3 Anular comprobante (`generar_anulacion`)

```json
{
  "operacion": "generar_anulacion",
  "tipo_de_comprobante": 1,
  "serie": "F001",
  "numero": 1,
  "motivo": "ERROR DE SISTEMA"
}
```

> La anulación es asincrónica: SUNAT devuelve un `sunat_ticket_numero`. Luego hay que consultar
> con `consultar_anulacion` para verificar si `aceptada_por_sunat` es `true`.
>
> **Alternativa:** Las GRE solo pueden darse de baja directamente desde la SUNAT con Clave Sol.

### 3.4 Respuesta estándar de comprobante

```json
{
  "tipo_de_comprobante": 1,
  "serie": "F001",
  "numero": 1,
  "enlace": "https://www.nubefact.com/cpe/{uuid}",
  "enlace_del_pdf": "https://www.nubefact.com/cpe/{uuid}.pdf",
  "enlace_del_xml": "https://www.nubefact.com/cpe/{uuid}.xml",
  "enlace_del_cdr": "https://www.nubefact.com/cpe/{uuid}.cdr",
  "aceptada_por_sunat": true,
  "sunat_description": "La Factura numero F001-1, ha sido aceptada",
  "sunat_note": null,
  "sunat_responsecode": "0",
  "sunat_soap_error": "",
  "cadena_para_codigo_qr": "20xxxxxx | 01 | F001 | 000001 | ...",
  "codigo_hash": "xMLFMnbgp1/bHEy572RKRTE9hPY="
}
```

---

## 4. Guías de Remisión Electrónica (GRE)

### 4.1 Proceso de dos pasos (obligatorio)

```
Paso 1: POST {ruta} → { "operacion": "generar_guia", ... }
        Respuesta: aceptada_por_sunat: false, enlaces vacíos
        SUNAT puede tardar segundos o minutos en procesar.

Paso 2: POST {ruta} → { "operacion": "consultar_guia", "tipo_de_comprobante": 7, "serie": "T001", "numero": 1 }
        Respuesta: aceptada_por_sunat: true/false + enlaces PDF/XML/CDR si fue aceptada
```

### 4.2 GRE Remitente (`tipo_de_comprobante: 7`, serie empieza con `T`)

El **cliente** en este caso es el **destinatario** (a quién se entrega la mercadería).

#### Cabecera

| Campo                              | Tipo    | Req.        | Notas                                                        |
|------------------------------------|---------|-------------|--------------------------------------------------------------|
| `operacion`                        | String  | Obligatorio | `"generar_guia"`                                             |
| `tipo_de_comprobante`              | Integer | Obligatorio | `7`                                                          |
| `serie`                            | String  | Obligatorio | Empieza con `T`, ej: `T001`                                 |
| `numero`                           | Integer | Obligatorio | Correlativo sin ceros                                        |
| `cliente_tipo_de_documento`        | String  | Obligatorio | Tipo doc del **destinatario** (6=RUC, 1=DNI, 0=No domiciliado) |
| `cliente_numero_de_documento`      | String  | Obligatorio | Documento del destinatario                                   |
| `cliente_denominacion`             | String  | Obligatorio | Nombre/razón social del destinatario                        |
| `cliente_direccion`                | String  | Obligatorio | Dirección del destinatario                                  |
| `fecha_de_emision`                 | Date    | Obligatorio | `DD-MM-YYYY`. Máx. 1 día anterior a hoy                    |
| `motivo_de_traslado`               | String  | Obligatorio | Ver tabla de motivos abajo                                  |
| `peso_bruto_total`                 | Decimal | Obligatorio | En KG, mayor a 0                                            |
| `peso_bruto_unidad_de_medida`      | String  | Obligatorio | `KGM`=Kilogramos o `TNE`=Toneladas                         |
| `numero_de_bultos`                 | Decimal | Obligatorio | Entero, cantidad de bultos                                  |
| `tipo_de_transporte`               | String  | Obligatorio | `"01"`=Transporte público, `"02"`=Transporte privado        |
| `fecha_de_inicio_de_traslado`      | Date    | Obligatorio | `DD-MM-YYYY`                                                |
| `transportista_documento_tipo`     | Integer | Condicional | Solo si `tipo_de_transporte = "01"`. Siempre `6` (RUC)     |
| `transportista_documento_numero`   | String  | Condicional | RUC del transportista (11 exactos)                         |
| `transportista_denominacion`       | String  | Condicional | Razón social del transportista                             |
| `transportista_placa_numero`       | String  | Obligatorio | Placa del vehículo, sin guiones, 6-8 chars                 |
| `conductor_documento_tipo`         | Integer | Condicional | Solo si `tipo_de_transporte = "02"`. 1=DNI                 |
| `conductor_documento_numero`       | String  | Condicional | DNI del conductor                                          |
| `conductor_nombre`                 | String  | Condicional | Nombre del conductor (transporte privado)                  |
| `conductor_apellidos`              | String  | Condicional | Apellidos del conductor (transporte privado)               |
| `conductor_numero_licencia`        | String  | Condicional | Licencia de conducir, 9-10 chars                           |
| `punto_de_partida_ubigeo`          | String  | Obligatorio | Código SUNAT de 6 dígitos                                  |
| `punto_de_partida_direccion`       | String  | Obligatorio | Dirección exacta de origen, hasta 150 chars                |
| `punto_de_llegada_ubigeo`          | String  | Obligatorio | Código SUNAT de 6 dígitos                                  |
| `punto_de_llegada_direccion`       | String  | Obligatorio | Dirección exacta de destino, hasta 150 chars               |
| `observaciones`                    | Text    | Opcional    | Hasta 1000 chars                                           |
| `enviar_automaticamente_al_cliente`| Boolean | Opcional   | Solo si GRE fue aceptada                                   |
| `documento_relacionado`            | Array   | Opcional    | Facturas/Boletas relacionadas (ver sección 4.4)            |
| `vehiculos_secundarios`            | Array   | Opcional    | Hasta 2 vehículos adicionales                              |
| `conductores_secundarios`          | Array   | Opcional    | Hasta 2 conductores adicionales                            |

#### Motivos de traslado

| Código | Descripción                                          |
|--------|------------------------------------------------------|
| `"01"` | Venta                                                |
| `"02"` | Compra                                               |
| `"03"` | Venta con entrega a terceros                         |
| `"04"` | Traslado entre establecimientos de la misma empresa  |
| `"06"` | Devolución                                           |
| `"09"` | Exportación ← **relevante para InterFruits**        |
| `"13"` | Otros (requiere `motivo_de_traslado_otros_descripcion`) |
| `"14"` | Venta sujeta a confirmación del comprador            |
| `"17"` | Traslado de bienes para transformación               |
| `"18"` | Traslado emisor itinerante CP                        |

### 4.3 GRE Transportista (`tipo_de_comprobante: 8`, serie empieza con `V`)

El **cliente** en este caso es el **remitente** (quién contrata el transporte).
El campo `destinatario_*` indica a quién va dirigida la carga.

Diferencias clave respecto al Remitente:
- No tiene `motivo_de_traslado`, `numero_de_bultos` ni `tipo_de_transporte`.
- Requiere `destinatario_documento_tipo`, `destinatario_documento_numero`, `destinatario_denominacion`.
- El conductor es **obligatorio** siempre.
- Tiene campo `tuc_vehiculo_principal` (Tarjeta Única de Circulación).
- Tiene campo `sunat_envio_indicador` (pagador del flete: remitente, subcontratador, tercero).

### 4.4 Documentos relacionados en la GRE (array `documento_relacionado`)

Permite vincular facturas u otras guías a la GRE:

| Campo    | Tipo    | Notas                                                    |
|----------|---------|----------------------------------------------------------|
| `tipo`   | String  | `"01"`=Factura, `"03"`=Boleta, `"09"`=GRE Remitente, `"31"`=GRE Transportista |
| `serie`  | String  | Serie del documento relacionado, ej: `F001`             |
| `numero` | Integer | Número correlativo del documento relacionado            |

### 4.5 Items de la GRE

Más simples que en comprobantes. No llevan precios ni IGV:

| Campo              | Tipo    | Notas                                |
|--------------------|---------|--------------------------------------|
| `unidad_de_medida` | String  | `NIU`=Producto, `ZZ`=Servicio        |
| `codigo`           | String  | Código interno del producto (opcional)|
| `descripcion`      | Text    | Descripción del bien trasladado      |
| `cantidad`         | Numeric | Cantidad de unidades                 |

### 4.6 Respuesta de la GRE

Tras `generar_guia` la respuesta siempre tiene `aceptada_por_sunat: false` y enlaces vacíos.
Tras `consultar_guia` si fue aceptada:

```json
{
  "tipo_de_comprobante": 7,
  "serie": "T001",
  "numero": 1,
  "enlace": "http://www.nubefact.com/guia/{uuid}",
  "enlace_del_pdf": "http://www.nubefact.com/guia/{uuid}.pdf",
  "enlace_del_xml": "http://www.nubefact.com/guia/{uuid}.xml",
  "enlace_del_cdr": "http://www.nubefact.com/guia/{uuid}.cdr",
  "aceptada_por_sunat": true,
  "cadena_para_codigo_qr": "https://e-factura.sunat.gob.pe/v1/contribuyente/gre/..."
}
```

---

## 5. Manejo de errores

### Estructura de error

```json
{
  "errors": "El archivo enviado no cumple con el formato establecido",
  "codigo": 20
}
```

### Códigos de error Nubefact

| Código | Descripción                                                             |
|--------|-------------------------------------------------------------------------|
| 10     | Token incorrecto o eliminado                                            |
| 11     | Ruta o URL incorrecta                                                   |
| 12     | Header sin `Content-Type` correcto                                      |
| 20     | JSON no cumple el formato establecido                                   |
| 21     | No se pudo completar la operación (ver mensaje adjunto)                 |
| 22     | Documento enviado fuera del plazo permitido                             |
| 23     | El documento ya existe en Nubefact (número correlativo repetido)        |
| 24     | El documento indicado no existe en Nubefact                             |
| 40     | Error interno desconocido                                               |
| 50     | Cuenta suspendida                                                       |
| 51     | Cuenta suspendida por falta de pago                                     |

### Códigos HTTP

| HTTP | Descripción              |
|------|--------------------------|
| 200  | Operación exitosa        |
| 400  | Solicitud incorrecta     |
| 401  | No autorizado            |
| 500  | Error interno del servidor |

---

## 6. Análisis de integración con el sistema actual

### 6.1 Estado actual del sistema

El sistema de trazabilidad ya tiene implementado:

- **`Despacho`**: agrupa facturas, tiene número de guía (`numeroGuia`), archivos adjuntos y
  envío de correo al cliente.
- **`Factura`**: CRUD inline en despacho, parse manual de XML SUNAT, función de anulación
  (solo marca como anulada en la DB local, sin comunicar a SUNAT).
- **`Cliente`**: RUC con búsqueda en SUNAT, dirección, email.
- **`Parametro`**: entidad tipo clave-valor para configuración del sistema.
- **`Operacion`**: agrupa despachos por sede.

### 6.2 Lo que aportaría Nubefact

| Función actual (manual/local)              | Con Nubefact (automatizado/legal)                          |
|--------------------------------------------|------------------------------------------------------------|
| Parse XML subido manualmente               | XML generado automáticamente por Nubefact al emitir        |
| Anulación solo en DB local                 | Comunicación de baja real ante SUNAT                       |
| Sin PDF oficial                            | PDF con QR válido disponible en enlace de Nubefact         |
| Sin número correlativo controlado          | Nubefact valida correlatividad (error 23 si ya existe)     |
| Sin estado de aceptación SUNAT             | Campo `aceptada_por_sunat` con descripción del error       |
| Sin guías de remisión electrónicas         | GRE Remitente y Transportista integradas al despacho       |

### 6.3 Nuevas entidades / campos necesarios

#### Cambios en `Factura`

Campos nuevos a agregar via migración Doctrine:

```
nubefactSerie          string(4)   nullable  — serie usada en Nubefact (ej: F001)
nubefactNumero         integer     nullable  — número correlativo en Nubefact
nubefactEnlace         string(500) nullable  — enlace único asignado por Nubefact
nubefactEnlacePdf      string(500) nullable
nubefactEnlaceXml      string(500) nullable
nubefactEnlaceCdr      string(500) nullable
aceptadaPorSunat       boolean     nullable  — null=pendiente, true=ok, false=error
sunatDescription       text        nullable  — mensaje de SUNAT
sunatResponseCode      string(10)  nullable
nubefactEnviada        boolean     default false — si ya fue enviada a Nubefact
```

#### Nueva entidad `GuiaRemision`

La guía de remisión electrónica es un documento independiente vinculado al despacho:

```
id                     ULID Base58
despacho               ManyToOne(Despacho)
tipo                   integer  — 7=Remitente, 8=Transportista
serie                  string(4)
numero                 integer
fechaEmision           date
fechaInicioTraslado    date
motivoTraslado         string(2)   — "01"=Venta, "09"=Exportación, etc.
tipoTransporte         string(2)   — "01"=Público, "02"=Privado
pesoBrutoTotal         decimal
numeroBultos           integer
puntPartidaUbigeo      string(6)
puntoPartidaDireccion  string(150)
puntoLlegadaUbigeo     string(6)
puntoLlegadaDireccion  string(150)
transportistaRuc       string(11)  nullable
transportistaNombre    string(100) nullable
transportistaPlaca     string(8)
conductorDni           string(15)  nullable
conductorNombre        string(250) nullable
conductorApellidos     string(250) nullable
conductorLicencia      string(10)  nullable
— Respuesta Nubefact —
nubefactEnlace         string(500) nullable
nubefactEnlacePdf      string(500) nullable
nubefactEnlaceXml      string(500) nullable
nubefactEnlaceCdr      string(500) nullable
aceptadaPorSunat       boolean     nullable
sunatDescription       text        nullable
isActive               boolean     default true  (soft delete)
createdAt / updatedAt
```

#### Nuevos `Parametro` del sistema

| Alias              | Descripción                                  |
|--------------------|----------------------------------------------|
| `NUBEFACT_RUTA`    | URL de la cuenta Nubefact                    |
| `NUBEFACT_TOKEN`   | Token de autenticación                       |
| `NUBEFACT_SERIE_F` | Serie facturas (ej: `F001`)                  |
| `NUBEFACT_SERIE_B` | Serie boletas (ej: `B001`)                   |
| `NUBEFACT_SERIE_T` | Serie GRE Remitente (ej: `T001`)             |
| `NUBEFACT_SERIE_V` | Serie GRE Transportista (ej: `V001`)         |
| `NUBEFACT_RUC`     | RUC del emisor (InterFruits)                 |
| `NUBEFACT_IGV`     | Porcentaje IGV, normalmente `18.00`          |

### 6.4 Nuevos servicios backend (Symfony)

Todos en `backend/apps/core/src/Service/Nubefact/`:

| Servicio                        | Responsabilidad                                                     |
|---------------------------------|---------------------------------------------------------------------|
| `NubefactHttpClient`            | Encapsula el POST a Nubefact con RUTA + TOKEN. Maneja errores HTTP  |
| `MapFacturaToNubefactService`   | Convierte entidad `Factura`+`Cliente`+`Despacho` al JSON Nubefact  |
| `EmitirFacturaService`          | Llama al mapper + HttpClient + persiste respuesta en `Factura`      |
| `AnularFacturaService`          | Envía `generar_anulacion` + persiste respuesta                      |
| `ConsultarEstadoFacturaService` | Envía `consultar_comprobante` + actualiza campos SUNAT              |
| `MapDespachoToGREService`       | Convierte `Despacho`+`GuiaRemision` al JSON de GRE                 |
| `EmitirGREService`              | Paso 1: envía `generar_guia` + persiste en `GuiaRemision`          |
| `ConsultarGREService`           | Paso 2: consulta hasta obtener PDF o detectar error                 |

### 6.5 Nuevos endpoints en el backend

En `FacturaApi`:

| Método | Ruta                             | Acción                                          |
|--------|----------------------------------|-------------------------------------------------|
| POST   | `/facturas/{id}/emitir`          | Envía factura a Nubefact y persiste respuesta   |
| GET    | `/facturas/{id}/estado-sunat`    | Consulta estado actual en SUNAT                 |
| POST   | `/facturas/{id}/anular-sunat`    | Genera comunicación de baja en SUNAT            |

En nuevo `GuiaRemisionApi`:

| Método | Ruta                                  | Acción                                           |
|--------|---------------------------------------|--------------------------------------------------|
| GET    | `/despachos/{id}/guias`               | Lista GREs de un despacho                        |
| POST   | `/despachos/{id}/guias`               | Crea GRE y envía a Nubefact (paso 1)            |
| GET    | `/guias/{id}/consultar`               | Consulta estado SUNAT de la GRE (paso 2)         |
| GET    | `/guias/{id}`                         | Detalle de una GRE                               |

### 6.6 Integración en el flujo del Despacho

```
Despacho creado / confirmado
  │
  ├─► Crear Facturas → [botón "Emitir a SUNAT"] → EmitirFacturaService
  │       └─ Guarda enlace PDF + aceptada_por_sunat en Factura
  │
  ├─► Crear GRE → [formulario datos transporte] → EmitirGREService (Paso 1)
  │       └─ Estado: "Pendiente SUNAT"
  │       └─ [polling o botón "Actualizar"] → ConsultarGREService (Paso 2)
  │               └─ Estado: "Aceptada" + enlace PDF
  │
  └─► Anular Factura → AnularFacturaService → estado "Baja comunicada"
```

### 6.7 Casos especiales para InterFruits (exportación agrícola)

| Caso                           | Configuración Nubefact                                                  |
|--------------------------------|-------------------------------------------------------------------------|
| Factura exportación            | `sunat_transaction: 2`, `tipo_de_igv: 16` (Exportación), `total_igv: 0`|
| Cliente no domiciliado         | `cliente_tipo_de_documento: "0"`, sin RUC                              |
| GRE a puerto de exportación    | `motivo_de_traslado: "09"` (Exportación)                               |
| Traslado a planta procesadora  | `motivo_de_traslado: "17"` (Traslado para transformación)              |
| Traslado entre sedes           | `motivo_de_traslado: "04"` (Entre establecimientos misma empresa)      |
| Factura con guía adjunta       | Array `guias` con el número de GRE Remitente asociado                  |

---

## 7. Plan de implementación sugerido

### Fase 1 — Configuración base (sin cambios visibles al usuario)

1. Agregar los 8 `Parametro` de Nubefact desde el panel de Settings existente.
2. Crear `NubefactHttpClient` con manejo de errores y logging.
3. Crear migración Doctrine para nuevos campos en `Factura`.

### Fase 2 — Emisión de facturas electrónicas

4. Crear `MapFacturaToNubefactService` (el mapeo es la parte más delicada).
5. Crear `EmitirFacturaService` + endpoint `POST /facturas/{id}/emitir`.
6. Frontend: botón "Emitir a SUNAT" en detalle de despacho + indicador de estado
   (Pendiente / Aceptada / Error) + botón "Ver PDF".
7. Adaptar `AnularFacturaService` para comunicar baja real a SUNAT.

### Fase 3 — Guías de Remisión Electrónica

8. Migración Doctrine para nueva entidad `GuiaRemision`.
9. Crear `MapDespachoToGREService` + `EmitirGREService` + `ConsultarGREService`.
10. Crear `GuiaRemisionApi` con los endpoints listados.
11. Frontend: sección "Guías de Remisión" dentro del detalle de despacho.
    Formulario para datos de transporte (transportista, conductor, origen/destino).
    Botón "Consultar estado" para el paso 2 del proceso SUNAT.

### Fase 4 — Panel de monitoreo (opcional)

12. Vista de estado SUNAT de todas las facturas (aceptadas / con error / pendientes).
13. Reintento automático o manual de documentos rechazados.
14. Historial de respuestas SUNAT por documento.

---

## 8. Futuras implementaciones

### 8.1 Adjuntar PDF/XML a correos desde el sistema

En lugar de enviar solo un enlace al PDF de Nubefact, el sistema podría adjuntar el archivo real
al correo que ya gestiona `EnviarCorreoDespachoService`.

**Estrategia A — Descarga bajo demanda (simple):**
Al momento de enviar el correo, hacer un GET al `enlace_del_pdf` guardado en la `Factura`,
obtener los bytes y adjuntarlos con Symfony Mailer:

```php
$pdfContent = file_get_contents($factura->getNubefactEnlacePdf());
$email->attach($pdfContent, 'factura-F001-1.pdf', 'application/pdf');
```

No requiere configuración extra. Implica una petición HTTP al momento del envío.

**Estrategia B — Base64 en la respuesta de Nubefact (integrada al flujo de emisión):**
Activar `pdf_zip_base64` / `xml_zip_base64` desde el panel de Nubefact (_Configuración principal_).
Al emitir el comprobante, la propia respuesta trae el archivo comprimido en base64. Se decodifica,
se descomprime y se puede adjuntar al correo o persistir en Object Storage en el mismo flujo,
sin petición HTTP adicional:

```php
$zipContent = base64_decode($response['pdf_zip_base64']);
// extraer PDF del zip en memoria → adjuntar o subir a Object Storage
```

**Cuándo implementar:** Estrategia A en cuanto se necesite enviar el PDF adjunto.
Estrategia B cuando se migre al Object Storage de Contabo, para persistir los archivos
en el mismo paso de emisión (ver `docs/project_object_storage_migration.md`).

**Archivos involucrados:**
- `EnviarCorreoDespachoService` — agregar adjunto según estrategia elegida
- `EmitirFacturaService` — activar base64 en el payload si se elige Estrategia B
- `NubefactHttpClient` — opcionalmente devolver el contenido binario decodificado

---

### Consideraciones técnicas importantes

- **Correlativos:** Implementar un contador atómico por serie para evitar huecos o duplicados
  (error 23 de Nubefact). El campo `nubefactNumero` en `Factura` es la fuente de verdad.
- **Formato de fechas:** Nubefact exige `DD-MM-YYYY`. Las entidades Doctrine usan `DateTimeImmutable`.
  Transformar siempre en el mapper.
- **Moneda y tipo de cambio:** Si la factura está en dólares (frecuente en exportación),
  se debe enviar `moneda: 2` y `tipo_de_cambio` tomado de la entidad `TipoCambio` del sistema.
- **Ambiente demo:** Nubefact tiene modo DEMO con validaciones parciales. Agregar un
  `Parametro` `NUBEFACT_MODO` (`"demo"` / `"produccion"`) que seleccione la RUTA correcta.
- **GRE asincrónica:** El paso 2 de consulta puede tardar. Implementar polling desde el frontend
  (cada 5 segundos, máximo 3 intentos) o un botón manual "Verificar con SUNAT".
- **Anulación de GRE:** Las GRE **solo** pueden darse de baja desde la SUNAT con Clave Sol.
  No existe endpoint en la API de Nubefact para esto.
