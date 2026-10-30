# 📚 Documentación del Sistema CSS Modular - SGP

## 🎯 Objetivo

Este documento describe la nueva estructura modular del CSS del Sistema de Gestión de Pasantías (SGP). El sistema se ha reorganizado para mejorar la mantenibilidad, escalabilidad y claridad del código.

---

## 📁 Estructura de Archivos

```
public/css/
├── variables.css       # Variables CSS globales (colores, espaciado, sombras)
├── base.css           # Reset, tipografía, elementos HTML base
├── animations.css     # Animaciones @keyframes y utility classes
├── notifications.css  # Sistema de notificaciones completo
├── style.css          # Estilos legacy (componentes, layout)
├── sidebar.css        # Estilos específicos del sidebar
├── topbar.css         # Estilos específicos del topbar
└── loading.css        # Estilos de loading spinners
```

---

## 🔧 Orden de Carga

**IMPORTANTE:** Los archivos deben cargarse en este orden específico en `main_layout.php`:

```html
<!-- 1. Frameworks externos -->
<link rel="stylesheet" href="<?= URLROOT ?>/css/bootstrap.min.css">
<link rel="stylesheet" href="<?= URLROOT ?>/css/tabler-icons.min.css">
<link rel="stylesheet" href="<?= URLROOT ?>/css/sweetalert2.min.css">
<link rel="stylesheet" href="<?= URLROOT ?>/css/notyf.min.css">

<!-- 2. Sistema CSS Modular (NUEVO) -->
<link rel="stylesheet" href="<?= URLROOT ?>/css/variables.css">
<link rel="stylesheet" href="<?= URLROOT ?>/css/base.css">
<link rel="stylesheet" href="<?= URLROOT ?>/css/animations.css">
<link rel="stylesheet" href="<?= URLROOT ?>/css/notifications.css">

<!-- 3. Estilos legacy (sobrescriben módulos si es necesario) -->
<link rel="stylesheet" href="<?= URLROOT ?>/css/style.css">
<link rel="stylesheet" href="<?= URLROOT ?>/css/sidebar.css">
<link rel="stylesheet" href="<?= URLROOT ?>/css/topbar.css">
<link rel="stylesheet" href="<?= URLROOT ?>/css/loading.css">
```

---

## 📖 Descripción de Módulos

### 1. `variables.css` - Sistema de Diseño

**Propósito:** Definir todas las variables CSS globales del sistema.

**Contenido:**
- Variables de layout (sidebar-width, topbar-height)
- Colores principales y de estado
- Border radius
- Spacing (xs, sm, md, lg, xl)
- Shadows
- Transitions
- Z-index layers

**Ejemplo de uso:**
```css
.mi-componente {
    background: var(--color-primary);
    padding: var(--spacing-md);
    border-radius: var(--card-radius);
    box-shadow: var(--smart-shadow);
}
```

**Modificar variables:**
- ✅ Cambiar valores en `variables.css`
- ❌ NO hardcodear valores en otros archivos

---

### 2. `base.css` - Reset y Fundamentos

**Propósito:** Estilos base del sistema (reset, tipografía, elementos HTML).

**Contenido:**
- Reset CSS (*, ::before, ::after)
- Estilos de HTML y body
- Tipografía (h1-h6, p, a)
- Listas (ul, ol)
- Imágenes
- Botones e inputs
- Scrollbar personalizado
- Utilidades globales (text-center, d-flex, etc.)

**Características:**
- Font: 'Inter', sans-serif
- Antialiasing activado
- Scrollbar personalizado (8px, colores institucionales)
- Selección de texto con color de marca

---

### 3. `animations.css` - Animaciones

**Propósito:** Todas las animaciones @keyframes y utility classes.

**Animaciones disponibles:**

#### Notificaciones
- `bellShake` - Shake de campana
- `badgePulse` - Pulse del badge
- `badgeRipple` - Efecto ripple
- `bounceIn` - Entrada con bounce

#### Fade
- `fadeIn` / `fadeOut`
- `fadeInUp` / `fadeInDown`

#### Slide
- `slideInRight` / `slideInLeft`

#### Spin
- `spin` / `spinSlow`

**Utility Classes:**
```html
<div class="animate-shake">Shake!</div>
<div class="animate-pulse">Pulse!</div>
<div class="animate-bounce-in">Bounce!</div>
<div class="hover-lift">Hover me!</div>
<div class="hover-scale">Scale on hover!</div>
```

**Performance:**
- Todas las animaciones usan `transform` y `opacity` (GPU-accelerated)
- Sin animaciones pesadas que afecten rendimiento

---

### 4. `notifications.css` - Sistema de Notificaciones

**Propósito:** Estilos completos para el sistema de notificaciones del topbar.

**Componentes:**

#### Botón de Notificaciones
```css
.header-icon-btn
.header-icon-btn.has-notifications  /* Con animación shake */
```

#### Badge
```css
.notification-badge  /* Con animaciones pulse + ripple */
```

#### Dropdown
```css
#notificationsDropdown
#notificationList
```

#### Items de Notificación
```css
.notification-item
.notification-item.unread
.notification-icon.success / .error / .warning / .info
.notification-content
.notification-title
.notification-message
.notification-time
```

#### Estados
```css
.notification-empty  /* Sin notificaciones */
.dropdown-footer     /* Footer con "Marcar todas" */
```

**Animaciones integradas:**
- Shake de campana cuando hay notificaciones
- Shake continuo en hover
- Pulse + ripple en badge
- Hover suave en items
- Transiciones fluidas

---

## 🎨 Sistema de Colores

### Colores Principales
```css
--color-primary: #162660    /* Azul Institucional */
--color-bg: #F0F5FA         /* Fondo General */
--color-card: #FFFFFF       /* Fondo de Tarjetas */
--color-accent: #F1E4D1     /* Color de Acento */
```

### Colores de Estado
```css
--color-success: #10B981    /* Verde - Éxito */
--color-error: #EF4444      /* Rojo - Error */
--color-warning: #F59E0B    /* Amarillo - Advertencia */
--color-info: #3B82F6       /* Azul - Información */
```

### Colores de Texto
```css
--text-body: #64748B        /* Texto Normal */
--text-muted: #94A3B8       /* Texto Secundario */
--text-dark: #1E293B        /* Texto Oscuro */
```

---

## 📏 Sistema de Espaciado

```css
--spacing-xs: 4px
--spacing-sm: 8px
--spacing-md: 16px
--spacing-lg: 24px
--spacing-xl: 32px
--spacing-internal: 28px    /* Padding interno de componentes */
--spacing-gap: 24px         /* Gap entre elementos */
```

---

## 🎭 Sombras

```css
--smart-shadow: 0px 12px 24px -10px rgba(22, 38, 96, 0.08)
--hover-shadow: 0px 20px 40px -10px rgba(22, 38, 96, 0.15)
--dropdown-shadow: 0 10px 40px rgba(0, 0, 0, 0.15)
--card-shadow: 0 1px 3px rgba(0, 0, 0, 0.05)
```

---

## ⚡ Transiciones

```css
--transition-speed: 0.3s
--transition-fast: 0.2s
--transition-slow: 0.5s
--transition-ease: cubic-bezier(0.4, 0, 0.2, 1)
```

**Uso:**
```css
.mi-elemento {
    transition: all var(--transition-fast) var(--transition-ease);
}
```

---

## 📊 Z-Index Layers

```css
--z-dropdown: 1000
--z-sidebar: 1040
--z-topbar: 1050
--z-modal: 1060
--z-tooltip: 1070
```

**Jerarquía visual:**
1. Tooltip (más alto)
2. Modal
3. Topbar
4. Sidebar
5. Dropdown (más bajo)

---

## 🔄 Migración desde style.css

### Paso 1: Identificar Componente
Encuentra el componente en `style.css` que quieres modularizar.

### Paso 2: Crear Módulo
Crea un nuevo archivo CSS en `public/css/` (ej: `buttons.css`).

### Paso 3: Mover Estilos
Mueve los estilos del componente al nuevo módulo.

### Paso 4: Usar Variables
Reemplaza valores hardcodeados con variables CSS.

**Antes:**
```css
.btn-primary {
    background: #162660;
    padding: 12px 24px;
    border-radius: 12px;
}
```

**Después:**
```css
.btn-primary {
    background: var(--color-primary);
    padding: var(--spacing-md) var(--spacing-lg);
    border-radius: var(--btn-radius);
}
```

### Paso 5: Agregar a Layout
Agrega el nuevo módulo a `main_layout.php`.

```html
<link rel="stylesheet" href="<?= URLROOT ?>/css/buttons.css">
```

---

## 🧪 Testing

### Verificar Carga de Módulos
1. Abrir DevTools (F12)
2. Network → CSS
3. Verificar que todos los módulos carguen correctamente
4. Sin errores 404

### Verificar Orden de Carga
1. Inspeccionar elemento
2. Computed → Mostrar origen
3. Verificar que variables CSS se resuelvan correctamente

### Verificar Animaciones
1. Abrir dashboard
2. Verificar campana de notificaciones
3. Debe hacer shake si hay notificaciones
4. Badge debe tener pulse + ripple

---

## 📝 Mejores Prácticas

### ✅ DO
- Usar variables CSS para todos los valores
- Documentar componentes nuevos
- Mantener archivos pequeños y enfocados
- Usar nombres de clase descriptivos
- Agrupar estilos relacionados

### ❌ DON'T
- Hardcodear colores, espaciado, sombras
- Crear archivos CSS gigantes
- Duplicar estilos entre archivos
- Usar `!important` (excepto casos muy específicos)
- Mezclar responsabilidades en un archivo

---

## 🚀 Próximos Pasos

### Módulos Pendientes de Crear
- [ ] `buttons.css` - Sistema de botones
- [ ] `forms.css` - Formularios y inputs
- [ ] `tables.css` - Tablas y DataTables
- [ ] `modals.css` - Modales y diálogos
- [ ] `cards.css` - Smart cards y componentes de tarjeta
- [ ] `utilities.css` - Clases de utilidad adicionales

### Refactorización de style.css
- [ ] Identificar componentes en `style.css`
- [ ] Mover a módulos específicos
- [ ] Reducir `style.css` a mínimo necesario
- [ ] Documentar cada módulo

---

## 📞 Soporte

**Documentación creada:** 2026-02-08  
**Última actualización:** 2026-02-08  
**Versión:** 1.0.0

Para preguntas o sugerencias sobre el sistema CSS modular, contactar al equipo de desarrollo.
