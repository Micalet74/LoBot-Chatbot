# OPTIBOT Chatbot Plugin
**Versión:** 1.0.0  
**Desarrollado para:** Colegio de Ópticos-Optometristas de la Comunitat Valenciana

---

## Requisitos

- WordPress 5.8 o superior
- PHP 7.4 o superior
- Clave API de Anthropic Claude (https://console.anthropic.com)
- HTTPS activo en el servidor (recomendado)

---

## Instalación

1. **Subir el plugin**
   - En el panel de WordPress: *Plugins → Añadir nuevo → Subir plugin*
   - Selecciona el archivo `optibot-chatbot.zip`
   - Haz clic en **Instalar ahora** y luego **Activar**

   — O bien —

   - Descomprime la carpeta `optibot-chatbot` en `/wp-content/plugins/`
   - Ve a *Plugins* y actívalo

2. **Configurar la API**
   - Ve a **OPTIBOT → Configuración** en el menú de administración
   - Introduce tu **API Key de Anthropic** (empieza por `sk-ant-...`)
   - Guarda los cambios

3. **Personalizar la apariencia**
   - Ve a **OPTIBOT → Apariencia**
   - Ajusta colores, tamaños y posición con vista previa en tiempo real

4. **Importar colegiados**
   - Ve a **OPTIBOT → Colegiados**
   - Descarga la **Plantilla CSV** de ejemplo
   - Rellénala con los datos del colegio (separador: `;`)
   - Importa el archivo CSV

---

## Columnas del CSV de colegiados

| Columna         | Descripción                     | Obligatorio |
|-----------------|---------------------------------|-------------|
| `num_colegiado` | Número de colegiado (ej: COL-001) | ✅ |
| `nombre`        | Nombre                          | ✅ |
| `apellidos`     | Apellidos                       | ✅ |
| `especialidad`  | Ej: Optometría, Audiología      | ✗ |
| `email`         | Correo profesional              | ✗ |
| `telefono`      | Teléfono                        | ✗ |
| `localidad`     | Ciudad                          | ✗ |
| `provincia`     | Provincia (por defecto: Valencia) | ✗ |
| `codigo_postal` | CP                              | ✗ |
| `estado`        | `activo` / `inactivo` / `baja`  | ✗ |

---

## Funcionalidades principales

### 🤖 Chatbot IA
- Impulsado por Airos74 Diseño
- Especializado en óptica, optometría y audiología
- Conocimiento de legislación española y europea del sector
- Contexto de la página web actual en cada respuesta
- Sugerencias rápidas de consultas comunes

### 👥 Directorio de colegiados
- Base de datos interna gestionable desde WordPress
- OPTIBOT puede buscar y verificar colegiados por nombre o número
- Solo comparte información profesional pública (no datos sensibles)

### 🎨 Personalización completa
- Colores, tamaños, posición (derecha/izquierda)
- Selector de icono del avatar
- Mensaje de bienvenida personalizado
- Visibilidad: todas las páginas, solo portada, o desactivado

### 📊 Estadísticas
- Mensajes totales y sesiones únicas
- Consultas del día
- Resumen de colegiados activos

---

## Estructura de archivos

```
optibot-chatbot/
├── optibot-chatbot.php          # Plugin principal
├── includes/
│   ├── class-optibot-settings.php   # Configuración y system prompt
│   ├── class-optibot-api.php        # Comunicación con Claude API
│   ├── class-optibot-colegiados.php # Gestión del directorio
│   └── class-optibot-crawler.php    # Indexación del sitio web
├── admin/
│   ├── class-optibot-admin.php  # Páginas de administración
│   ├── admin.css                # Estilos del panel admin
│   └── admin.js                 # JavaScript del panel admin
└── public/
    ├── class-optibot-public.php # Renderizado del widget
    ├── css/optibot-chat.css     # Estilos del chatbot
    └── js/optibot-chat.js       # Lógica del chatbot
```

---

## Personalización avanzada del System Prompt

Para modificar el comportamiento de OPTIBOT puedes editar el método  
`Optibot_Settings::get_system_prompt()` en `includes/class-optibot-settings.php`.

---

## Soporte y actualizaciones

Para soporte técnico o personalizaciones adicionales, contactar con el  
equipo de desarrollo de Airos74 Diseño.

---

## Notas de seguridad

- La API Key se almacena en la tabla `wp_options` de WordPress.
- Se recomienda usar un plugin como *WP Encryption* o configurar HTTPS.
- Los datos de colegiados se almacenan localmente en la base de datos de WordPress.
- Solo los administradores de WordPress pueden gestionar colegiados o ver estadísticas.
