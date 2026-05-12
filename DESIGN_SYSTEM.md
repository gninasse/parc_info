# Design System: Parc Info Management Platform

## 1. Creative North Star: "The Institutional Precision"

### Vision
This design system transforms enterprise resource management from bureaucratic complexity into **structured clarity**. Unlike consumer apps that prioritize delight, this system prioritizes **confidence, hierarchy, and operational efficiency**. We embrace the institutional nature of the domain while maintaining modern aesthetics.

**Core Principle:** Every interface element must communicate authority, structure, and data integrity. Users are professionals managing critical organizational assets—the UI must reflect this responsibility.

---

## 2. Color Architecture: Hierarchical Authority

### Primary Palette: Institutional Trust
```css
/* Primary - Deep Authority Blue */
--primary: #1e3a8a;           /* Main actions, primary CTAs */
--primary-hover: #1e40af;     /* Hover states */
--primary-light: #3b82f6;     /* Secondary emphasis */
--primary-subtle: #dbeafe;    /* Backgrounds, badges */

/* Secondary - Organizational Structure */
--secondary: #0891b2;         /* Organizational hierarchy (Sites, Directions) */
--secondary-hover: #0e7490;
--secondary-light: #06b6d4;
--secondary-subtle: #cffafe;

/* Tertiary - Operational Actions */
--tertiary: #7c3aed;          /* Workflow actions, status changes */
--tertiary-hover: #6d28d9;
--tertiary-light: #8b5cf6;
--tertiary-subtle: #ede9fe;
```

### Semantic Colors: Status Communication
```css
/* Success - Validated/Active */
--success: #059669;
--success-light: #10b981;
--success-subtle: #d1fae5;

/* Warning - Attention Required */
--warning: #d97706;
--warning-light: #f59e0b;
--warning-subtle: #fef3c7;

/* Danger - Critical/Inactive */
--danger: #dc2626;
--danger-light: #ef4444;
--danger-subtle: #fee2e2;

/* Info - Informational */
--info: #0284c7;
--info-light: #0ea5e9;
--info-subtle: #e0f2fe;
```

### Neutral Palette: Data Hierarchy
```css
/* Surface Architecture */
--surface-base: #ffffff;
--surface-elevated: #f9fafb;
--surface-container: #f3f4f6;
--surface-container-high: #e5e7eb;

/* Text Hierarchy */
--text-primary: #111827;      /* Headers, critical data */
--text-secondary: #4b5563;    /* Body text, descriptions */
--text-tertiary: #6b7280;     /* Meta information, labels */
--text-disabled: #9ca3af;     /* Disabled states */

/* Borders & Dividers */
--border-subtle: #e5e7eb;
--border-default: #d1d5db;
--border-emphasis: #9ca3af;
```

### Module-Specific Colors
```css
/* Core Module - User Management */
--module-core: #1e3a8a;
--module-core-light: #3b82f6;

/* Organisation Module - Structural Entities */
--module-organisation: #0891b2;
--module-organisation-light: #06b6d4;
```

---

## 3. Typography: Institutional Clarity

### Font Stack
```css
/* Primary: Inter - Data-Dense Interfaces */
--font-primary: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;

/* Monospace: JetBrains Mono - Codes & Technical Data */
--font-mono: 'JetBrains Mono', 'Courier New', monospace;
```

### Type Scale: Information Hierarchy
```css
/* Display - Module Headers, Dashboard Titles */
--text-display-lg: 2.25rem;   /* 36px - Main dashboard title */
--text-display-md: 1.875rem;  /* 30px - Module section headers */
--text-display-sm: 1.5rem;    /* 24px - Card headers */

/* Heading - Section Organization */
--text-heading-lg: 1.25rem;   /* 20px - Table headers, form sections */
--text-heading-md: 1.125rem;  /* 18px - Card titles */
--text-heading-sm: 1rem;      /* 16px - Subsection headers */

/* Body - Content & Data */
--text-body-lg: 1rem;         /* 16px - Primary content, form inputs */
--text-body-md: 0.875rem;     /* 14px - Table cells, descriptions */
--text-body-sm: 0.8125rem;    /* 13px - Meta information */

/* Label - UI Elements */
--text-label-lg: 0.875rem;    /* 14px - Form labels, buttons */
--text-label-md: 0.8125rem;   /* 13px - Badges, tags */
--text-label-sm: 0.75rem;     /* 12px - Timestamps, footnotes */

/* Code - Technical Data */
--text-code: 0.875rem;        /* 14px - Codes, IDs, technical refs */
```

### Font Weights
```css
--font-regular: 400;
--font-medium: 500;
--font-semibold: 600;
--font-bold: 700;
```

### Line Heights
```css
--leading-tight: 1.25;        /* Headers */
--leading-normal: 1.5;        /* Body text */
--leading-relaxed: 1.75;      /* Descriptions */
```

---

## 4. Spacing System: Structural Rhythm

### Base Unit: 4px
```css
--space-0: 0;
--space-1: 0.25rem;   /* 4px - Tight spacing */
--space-2: 0.5rem;    /* 8px - Icon padding */
--space-3: 0.75rem;   /* 12px - Button padding */
--space-4: 1rem;      /* 16px - Standard gap */
--space-5: 1.25rem;   /* 20px - Section spacing */
--space-6: 1.5rem;    /* 24px - Card padding */
--space-8: 2rem;      /* 32px - Large sections */
--space-10: 2.5rem;   /* 40px - Module separation */
--space-12: 3rem;     /* 48px - Major divisions */
--space-16: 4rem;     /* 64px - Page sections */
```

### Component-Specific Spacing
```css
/* Form Elements */
--form-input-padding-y: var(--space-3);
--form-input-padding-x: var(--space-4);
--form-group-gap: var(--space-5);

/* Cards */
--card-padding: var(--space-6);
--card-gap: var(--space-4);

/* Tables */
--table-cell-padding-y: var(--space-3);
--table-cell-padding-x: var(--space-4);
--table-row-gap: var(--space-2);

/* Modals */
--modal-padding: var(--space-8);
--modal-header-padding: var(--space-6);
```

---

## 5. Elevation & Depth: Tonal Layering

### Shadow System (Subtle, Not Heavy)
```css
/* Level 0 - Flat Surface */
--shadow-none: none;

/* Level 1 - Slight Elevation (Cards) */
--shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);

/* Level 2 - Moderate Elevation (Dropdowns) */
--shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1),
             0 2px 4px -1px rgba(0, 0, 0, 0.06);

/* Level 3 - High Elevation (Modals) */
--shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1),
             0 4px 6px -2px rgba(0, 0, 0, 0.05);

/* Level 4 - Maximum Elevation (Overlays) */
--shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1),
             0 10px 10px -5px rgba(0, 0, 0, 0.04);
```

### Surface Hierarchy
```
Base Layer (--surface-base)
  ↓
Container Layer (--surface-container)
  ↓
Card Layer (--surface-elevated + --shadow-sm)
  ↓
Dropdown Layer (--surface-base + --shadow-md)
  ↓
Modal Layer (--surface-base + --shadow-xl)
```

---

## 6. Border Radius: Structured Softness

```css
/* Minimal Rounding - Maintains Authority */
--radius-none: 0;
--radius-sm: 0.25rem;    /* 4px - Badges, tags */
--radius-md: 0.375rem;   /* 6px - Buttons, inputs */
--radius-lg: 0.5rem;     /* 8px - Cards */
--radius-xl: 0.75rem;    /* 12px - Modals */
--radius-full: 9999px;   /* Avatars, status dots */
```

### Component Mapping
- **Buttons:** `--radius-md`
- **Form Inputs:** `--radius-md`
- **Cards:** `--radius-lg`
- **Modals:** `--radius-xl`
- **Badges:** `--radius-sm`
- **Avatars:** `--radius-full`


---

## 7. Component Library: Institutional Patterns

### 7.1 Buttons

#### Primary Button (Main Actions)
```html
<button class="btn btn-primary">
  <i class="bi bi-plus-circle"></i>
  Créer un Service
</button>
```

**Specifications:**
- Background: `--primary` with gradient to `--primary-hover` on hover
- Text: White, `--font-semibold`, `--text-label-lg`
- Padding: `--space-3` vertical, `--space-6` horizontal
- Border Radius: `--radius-md`
- Icon: 16px, `--space-2` gap from text
- Transition: 150ms ease-in-out

#### Secondary Button (Alternative Actions)
```html
<button class="btn btn-secondary">
  <i class="bi bi-filter"></i>
  Filtrer
</button>
```

**Specifications:**
- Background: `--surface-container-high`
- Text: `--text-primary`, `--font-medium`
- Border: 1px solid `--border-default`
- Hover: Background to `--surface-container`, border to `--border-emphasis`

#### Tertiary Button (Subtle Actions)
```html
<button class="btn btn-tertiary">
  Annuler
</button>
```

**Specifications:**
- Background: Transparent
- Text: `--text-secondary`, `--font-medium`
- Hover: Background to `--surface-container`

#### Danger Button (Destructive Actions)
```html
<button class="btn btn-danger">
  <i class="bi bi-trash"></i>
  Supprimer
</button>
```

**Specifications:**
- Background: `--danger`
- Text: White, `--font-semibold`
- Hover: Background to `--danger-light`

---

### 7.2 Form Elements

#### Text Input
```html
<div class="form-group">
  <label for="code" class="form-label">
    Code <span class="text-danger">*</span>
  </label>
  <input 
    type="text" 
    id="code" 
    name="code" 
    class="form-input"
    placeholder="SRV-001"
    required
  >
  <span class="form-hint">Format: XXX-000</span>
</div>
```

**Specifications:**
- Background: `--surface-base`
- Border: 1px solid `--border-default`
- Border Radius: `--radius-md`
- Padding: `--form-input-padding-y` `--form-input-padding-x`
- Font: `--text-body-lg`, `--font-regular`
- Focus: Border to `--primary`, shadow `0 0 0 3px rgba(30, 58, 138, 0.1)`
- Error: Border to `--danger`, background to `--danger-subtle`

#### Select Dropdown (with Select2)
```html
<div class="form-group">
  <label for="direction_id" class="form-label">Direction</label>
  <select 
    id="direction_id" 
    name="direction_id" 
    class="form-select select2"
    data-placeholder="Sélectionner une direction"
  >
    <option></option>
    <option value="1">Direction Générale</option>
  </select>
</div>
```

**Specifications:**
- Same as text input
- Select2 theme: Custom with `--primary` accent
- Dropdown: `--shadow-md`, `--radius-md`

#### Checkbox/Radio
```html
<div class="form-check">
  <input type="checkbox" id="actif" name="actif" class="form-check-input">
  <label for="actif" class="form-check-label">Actif</label>
</div>
```

**Specifications:**
- Size: 18px × 18px
- Border: 2px solid `--border-default`
- Border Radius: `--radius-sm` (checkbox), `--radius-full` (radio)
- Checked: Background `--primary`, border `--primary`
- Checkmark: White, 12px

---

### 7.3 Tables (Bootstrap Table)

#### Standard Data Table
```html
<div class="table-container">
  <table 
    id="servicesTable"
    data-toggle="table"
    data-search="true"
    data-pagination="true"
    data-page-size="25"
    class="table table-hover"
  >
    <thead>
      <tr>
        <th data-field="code" data-sortable="true">Code</th>
        <th data-field="libelle" data-sortable="true">Libellé</th>
        <th data-field="direction">Direction</th>
        <th data-field="actif" data-formatter="statusFormatter">Statut</th>
        <th data-field="actions" data-formatter="actionsFormatter">Actions</th>
      </tr>
    </thead>
  </table>
</div>
```

**Specifications:**
- Header Background: `--surface-container`
- Header Text: `--text-primary`, `--font-semibold`, `--text-heading-sm`
- Row Background: `--surface-base`
- Row Hover: `--surface-elevated`
- Cell Padding: `--table-cell-padding-y` `--table-cell-padding-x`
- Border: Bottom only, `--border-subtle`
- Striped: Alternate rows with `--surface-elevated`

#### Table Actions
```javascript
function actionsFormatter(value, row) {
  return `
    <div class="btn-group btn-group-sm">
      <button class="btn btn-icon btn-secondary" onclick="viewItem(${row.id})">
        <i class="bi bi-eye"></i>
      </button>
      <button class="btn btn-icon btn-primary" onclick="editItem(${row.id})">
        <i class="bi bi-pencil"></i>
      </button>
      <button class="btn btn-icon btn-danger" onclick="deleteItem(${row.id})">
        <i class="bi bi-trash"></i>
      </button>
    </div>
  `;
}
```

---

### 7.4 Cards

#### Standard Card
```html
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Informations Générales</h3>
    <div class="card-actions">
      <button class="btn btn-sm btn-secondary">
        <i class="bi bi-pencil"></i>
      </button>
    </div>
  </div>
  <div class="card-body">
    <div class="info-grid">
      <div class="info-item">
        <span class="info-label">Code</span>
        <span class="info-value">SRV-001</span>
      </div>
      <div class="info-item">
        <span class="info-label">Libellé</span>
        <span class="info-value">Service Informatique</span>
      </div>
    </div>
  </div>
</div>
```

**Specifications:**
- Background: `--surface-base`
- Border: 1px solid `--border-subtle`
- Border Radius: `--radius-lg`
- Shadow: `--shadow-sm`
- Padding: `--card-padding`
- Header: Border-bottom `--border-subtle`, padding-bottom `--space-4`

#### Stats Card (Dashboard)
```html
<div class="stats-card">
  <div class="stats-icon" style="background: var(--module-organisation-light);">
    <i class="bi bi-building"></i>
  </div>
  <div class="stats-content">
    <div class="stats-value">24</div>
    <div class="stats-label">Services Actifs</div>
  </div>
  <div class="stats-trend stats-trend-up">
    <i class="bi bi-arrow-up"></i>
    <span>+12%</span>
  </div>
</div>
```

**Specifications:**
- Background: `--surface-base`
- Border: None
- Shadow: `--shadow-md`
- Border Radius: `--radius-lg`
- Icon: 48px circle, module color background, white icon
- Value: `--text-display-md`, `--font-bold`, `--text-primary`
- Label: `--text-body-md`, `--text-secondary`

---

### 7.5 Modals

#### Standard Modal
```html
<div class="modal fade" id="serviceModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="bi bi-building"></i>
          Nouveau Service
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="serviceForm">
          <!-- Form content -->
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-tertiary" data-bs-dismiss="modal">
          Annuler
        </button>
        <button type="submit" class="btn btn-primary" form="serviceForm">
          <i class="bi bi-check-circle"></i>
          Enregistrer
        </button>
      </div>
    </div>
  </div>
</div>
```

**Specifications:**
- Background: `--surface-base`
- Border Radius: `--radius-xl`
- Shadow: `--shadow-xl`
- Backdrop: rgba(0, 0, 0, 0.5)
- Header: Border-bottom `--border-subtle`, padding `--modal-header-padding`
- Body: Padding `--modal-padding`
- Footer: Border-top `--border-subtle`, padding `--space-6`

---

### 7.6 Badges & Status Indicators

#### Status Badge
```html
<span class="badge badge-success">
  <i class="bi bi-check-circle-fill"></i>
  Actif
</span>

<span class="badge badge-danger">
  <i class="bi bi-x-circle-fill"></i>
  Inactif
</span>

<span class="badge badge-warning">
  <i class="bi bi-exclamation-triangle-fill"></i>
  En attente
</span>
```

**Specifications:**
- Padding: `--space-1` `--space-3`
- Border Radius: `--radius-sm`
- Font: `--text-label-md`, `--font-medium`
- Icon: 12px, `--space-1` gap
- Success: Background `--success-subtle`, text `--success`, border `--success-light`
- Danger: Background `--danger-subtle`, text `--danger`, border `--danger-light`
- Warning: Background `--warning-subtle`, text `--warning`, border `--warning-light`

#### Module Badge
```html
<span class="badge badge-module" data-module="organisation">
  Organisation
</span>
```

**Specifications:**
- Background: Module-specific light color
- Text: Module-specific primary color
- Border: 1px solid module-specific primary color

---

### 7.7 Navigation

#### Sidebar Navigation
```html
<aside class="sidebar">
  <div class="sidebar-header">
    <img src="/logo.svg" alt="Logo" class="sidebar-logo">
    <h2 class="sidebar-title">Parc Info</h2>
  </div>
  
  <nav class="sidebar-nav">
    <div class="nav-section">
      <div class="nav-section-title">Core</div>
      <a href="/dashboard" class="nav-item active">
        <i class="bi bi-speedometer2"></i>
        <span>Tableau de bord</span>
      </a>
      <a href="/users" class="nav-item">
        <i class="bi bi-people"></i>
        <span>Utilisateurs</span>
      </a>
    </div>
    
    <div class="nav-section">
      <div class="nav-section-title">Organisation</div>
      <a href="/organisation/services" class="nav-item">
        <i class="bi bi-building"></i>
        <span>Services</span>
      </a>
    </div>
  </nav>
</aside>
```

**Specifications:**
- Width: 260px
- Background: `--surface-base`
- Border-right: 1px solid `--border-subtle`
- Nav Item Padding: `--space-3` `--space-4`
- Nav Item Hover: Background `--surface-elevated`
- Nav Item Active: Background `--primary-subtle`, text `--primary`, border-left 3px `--primary`
- Icon: 20px, `--space-3` gap from text


---

## 8. Layout Patterns: Modular Architecture

### 8.1 Master Layout Structure
```
┌─────────────────────────────────────────────────────┐
│ Header (Fixed)                                      │
│ - Logo, Module Name, User Menu                     │
├──────────┬──────────────────────────────────────────┤
│          │                                          │
│ Sidebar  │ Main Content Area                        │
│ (Fixed)  │ - Breadcrumb                             │
│          │ - Page Header                            │
│ - Core   │ - Content Cards/Tables                   │
│ - Org    │                                          │
│          │                                          │
│          │                                          │
│          │                                          │
└──────────┴──────────────────────────────────────────┘
```

**Specifications:**
- Header Height: 64px
- Sidebar Width: 260px
- Content Max Width: 1400px
- Content Padding: `--space-8`
- Gap between sections: `--space-6`

---

### 8.2 Page Header Pattern
```html
<div class="page-header">
  <div class="page-header-content">
    <div class="page-breadcrumb">
      <a href="/organisation">Organisation</a>
      <i class="bi bi-chevron-right"></i>
      <span>Services</span>
    </div>
    <h1 class="page-title">
      <i class="bi bi-building"></i>
      Gestion des Services
    </h1>
    <p class="page-description">
      Gérez les services organisationnels rattachés aux directions
    </p>
  </div>
  <div class="page-header-actions">
    <button class="btn btn-secondary">
      <i class="bi bi-download"></i>
      Exporter
    </button>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#serviceModal">
      <i class="bi bi-plus-circle"></i>
      Nouveau Service
    </button>
  </div>
</div>
```

**Specifications:**
- Background: `--surface-elevated`
- Border-bottom: 1px solid `--border-subtle`
- Padding: `--space-6` `--space-8`
- Title: `--text-display-md`, `--font-bold`
- Description: `--text-body-md`, `--text-secondary`

---

### 8.3 Filter Bar Pattern
```html
<div class="filter-bar">
  <div class="filter-group">
    <label class="filter-label">Site</label>
    <select class="form-select select2" id="filter-site">
      <option value="">Tous les sites</option>
    </select>
  </div>
  
  <div class="filter-group">
    <label class="filter-label">Direction</label>
    <select class="form-select select2" id="filter-direction">
      <option value="">Toutes les directions</option>
    </select>
  </div>
  
  <div class="filter-group">
    <label class="filter-label">Statut</label>
    <select class="form-select" id="filter-status">
      <option value="">Tous</option>
      <option value="1">Actif</option>
      <option value="0">Inactif</option>
    </select>
  </div>
  
  <div class="filter-actions">
    <button class="btn btn-secondary" id="applyFilters">
      <i class="bi bi-funnel"></i>
      Appliquer
    </button>
    <button class="btn btn-tertiary" id="resetFilters">
      <i class="bi bi-x-circle"></i>
      Réinitialiser
    </button>
  </div>
</div>
```

**Specifications:**
- Background: `--surface-base`
- Border: 1px solid `--border-subtle`
- Border Radius: `--radius-lg`
- Padding: `--space-5`
- Gap: `--space-4`
- Display: Flex, wrap

---

### 8.4 Detail View Pattern (Show Page)
```html
<div class="detail-view">
  <!-- Header Section -->
  <div class="detail-header">
    <div class="detail-header-content">
      <div class="detail-breadcrumb">
        <a href="/organisation/services">Services</a>
        <i class="bi bi-chevron-right"></i>
        <span>SRV-001</span>
      </div>
      <h1 class="detail-title">Service Informatique</h1>
      <div class="detail-meta">
        <span class="badge badge-success">Actif</span>
        <span class="meta-item">
          <i class="bi bi-calendar"></i>
          Créé le 15/03/2024
        </span>
      </div>
    </div>
    <div class="detail-actions">
      <button class="btn btn-secondary">
        <i class="bi bi-printer"></i>
        Imprimer
      </button>
      <button class="btn btn-primary">
        <i class="bi bi-pencil"></i>
        Modifier
      </button>
    </div>
  </div>
  
  <!-- Content Grid -->
  <div class="detail-grid">
    <!-- Main Column (2/3) -->
    <div class="detail-main">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Informations Générales</h3>
        </div>
        <div class="card-body">
          <div class="info-grid">
            <div class="info-item">
              <span class="info-label">Code</span>
              <span class="info-value">SRV-001</span>
            </div>
            <div class="info-item">
              <span class="info-label">Libellé</span>
              <span class="info-value">Service Informatique</span>
            </div>
            <div class="info-item">
              <span class="info-label">Direction</span>
              <span class="info-value">
                <a href="/organisation/directions/1">Direction Générale</a>
              </span>
            </div>
            <div class="info-item">
              <span class="info-label">Chef de Service</span>
              <span class="info-value">
                <div class="user-inline">
                  <img src="/avatars/user.jpg" alt="User" class="user-avatar-sm">
                  <span>Jean Dupont</span>
                </div>
              </span>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Related Entities -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Unités Rattachées</h3>
          <button class="btn btn-sm btn-primary">
            <i class="bi bi-plus"></i>
            Ajouter
          </button>
        </div>
        <div class="card-body">
          <table class="table table-sm">
            <!-- Table content -->
          </table>
        </div>
      </div>
    </div>
    
    <!-- Sidebar Column (1/3) -->
    <div class="detail-sidebar">
      <!-- Stats Card -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Statistiques</h3>
        </div>
        <div class="card-body">
          <div class="stat-item">
            <div class="stat-icon" style="background: var(--info-subtle);">
              <i class="bi bi-people" style="color: var(--info);"></i>
            </div>
            <div class="stat-content">
              <div class="stat-value">12</div>
              <div class="stat-label">Unités</div>
            </div>
          </div>
          <div class="stat-item">
            <div class="stat-icon" style="background: var(--success-subtle);">
              <i class="bi bi-person-badge" style="color: var(--success);"></i>
            </div>
            <div class="stat-content">
              <div class="stat-value">45</div>
              <div class="stat-label">Postes de Travail</div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Activity Timeline -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Activité Récente</h3>
        </div>
        <div class="card-body">
          <div class="timeline">
            <div class="timeline-item">
              <div class="timeline-marker"></div>
              <div class="timeline-content">
                <div class="timeline-title">Modification</div>
                <div class="timeline-description">
                  Chef de service modifié
                </div>
                <div class="timeline-meta">
                  Il y a 2 heures
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
```

**Specifications:**
- Detail Grid: 2fr 1fr (main/sidebar)
- Gap: `--space-6`
- Info Grid: 2 columns on desktop, 1 on mobile
- Info Item: Label above value, `--space-2` gap

---

## 9. Interaction Patterns

### 9.1 Loading States

#### Skeleton Loader
```html
<div class="skeleton-card">
  <div class="skeleton-header">
    <div class="skeleton-line skeleton-line-title"></div>
  </div>
  <div class="skeleton-body">
    <div class="skeleton-line"></div>
    <div class="skeleton-line"></div>
    <div class="skeleton-line skeleton-line-short"></div>
  </div>
</div>
```

**Specifications:**
- Background: Linear gradient animation
- Colors: `--surface-container` to `--surface-container-high`
- Animation: 1.5s ease-in-out infinite

#### Spinner
```html
<div class="spinner">
  <div class="spinner-border" role="status">
    <span class="visually-hidden">Chargement...</span>
  </div>
</div>
```

---

### 9.2 Toast Notifications (SweetAlert2)

```javascript
// Success
Swal.fire({
  icon: 'success',
  title: 'Succès',
  text: 'Le service a été créé avec succès',
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 3000,
  timerProgressBar: true,
  customClass: {
    popup: 'toast-success'
  }
});

// Error
Swal.fire({
  icon: 'error',
  title: 'Erreur',
  text: 'Une erreur est survenue',
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 3000,
  customClass: {
    popup: 'toast-error'
  }
});

// Confirmation
Swal.fire({
  title: 'Êtes-vous sûr?',
  text: "Cette action est irréversible",
  icon: 'warning',
  showCancelButton: true,
  confirmButtonText: 'Oui, supprimer',
  cancelButtonText: 'Annuler',
  customClass: {
    confirmButton: 'btn btn-danger',
    cancelButton: 'btn btn-tertiary'
  }
});
```

**Specifications:**
- Toast Position: top-end
- Toast Width: 350px
- Toast Padding: `--space-4`
- Icon Size: 32px
- Animation: Slide in from right

---

### 9.3 Form Validation

#### Inline Validation
```html
<div class="form-group">
  <label for="code" class="form-label">Code *</label>
  <input 
    type="text" 
    id="code" 
    name="code" 
    class="form-input is-invalid"
    value="SRV"
  >
  <div class="invalid-feedback">
    <i class="bi bi-exclamation-circle"></i>
    Le code doit contenir au moins 5 caractères
  </div>
</div>
```

**Specifications:**
- Invalid Border: `--danger`
- Invalid Background: `--danger-subtle`
- Feedback Text: `--danger`, `--text-body-sm`
- Icon: 14px, `--space-1` gap

---

## 10. Responsive Behavior

### Breakpoints
```css
--breakpoint-sm: 640px;   /* Mobile landscape */
--breakpoint-md: 768px;   /* Tablet */
--breakpoint-lg: 1024px;  /* Desktop */
--breakpoint-xl: 1280px;  /* Large desktop */
--breakpoint-2xl: 1536px; /* Extra large */
```

### Mobile Adaptations
- **Sidebar:** Collapsible drawer on mobile
- **Tables:** Horizontal scroll with sticky first column
- **Filter Bar:** Stack vertically
- **Detail Grid:** Single column
- **Page Header Actions:** Stack vertically, full width buttons

---

## 11. Accessibility Standards

### WCAG 2.1 AA Compliance
- **Color Contrast:** Minimum 4.5:1 for text, 3:1 for UI components
- **Focus Indicators:** 2px solid `--primary` outline with 2px offset
- **Keyboard Navigation:** All interactive elements accessible via Tab
- **Screen Reader:** Proper ARIA labels and roles
- **Form Labels:** Always associated with inputs

### Focus States
```css
.btn:focus,
.form-input:focus,
.form-select:focus {
  outline: 2px solid var(--primary);
  outline-offset: 2px;
  box-shadow: 0 0 0 4px rgba(30, 58, 138, 0.1);
}
```

---

## 12. Animation & Transitions

### Timing Functions
```css
--ease-in: cubic-bezier(0.4, 0, 1, 1);
--ease-out: cubic-bezier(0, 0, 0.2, 1);
--ease-in-out: cubic-bezier(0.4, 0, 0.2, 1);
```

### Standard Transitions
```css
/* Hover States */
transition: all 150ms var(--ease-out);

/* Modal/Drawer */
transition: transform 250ms var(--ease-in-out);

/* Fade In/Out */
transition: opacity 200ms var(--ease-in-out);
```

### Micro-interactions
- **Button Click:** Scale down to 0.98, 100ms
- **Card Hover:** Lift shadow from `--shadow-sm` to `--shadow-md`, 200ms
- **Checkbox Toggle:** Checkmark slide-in, 150ms
- **Dropdown Open:** Fade + slide down 10px, 200ms


---

## 13. Module-Specific Patterns

### 13.1 Core Module (User & Permission Management)

#### User Card Component
```html
<div class="user-card">
  <div class="user-card-avatar">
    <img src="/avatars/user.jpg" alt="User Avatar">
    <span class="user-status user-status-online"></span>
  </div>
  <div class="user-card-content">
    <h4 class="user-card-name">Jean Dupont</h4>
    <p class="user-card-email">jean.dupont@example.com</p>
    <div class="user-card-roles">
      <span class="badge badge-primary">Admin</span>
      <span class="badge badge-secondary">Manager</span>
    </div>
  </div>
  <div class="user-card-actions">
    <button class="btn btn-icon btn-sm btn-secondary">
      <i class="bi bi-pencil"></i>
    </button>
  </div>
</div>
```

**Specifications:**
- Avatar: 64px circle, border 2px `--border-subtle`
- Status Indicator: 12px circle, positioned bottom-right of avatar
  - Online: `--success`
  - Offline: `--text-disabled`
  - Away: `--warning`

#### Permission Matrix
```html
<div class="permission-matrix">
  <table class="matrix-table">
    <thead>
      <tr>
        <th>Module</th>
        <th>Voir</th>
        <th>Créer</th>
        <th>Modifier</th>
        <th>Supprimer</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td class="matrix-module">
          <span class="badge badge-module" data-module="organisation">
            Organisation
          </span>
        </td>
        <td>
          <input type="checkbox" class="form-check-input" checked>
        </td>
        <td>
          <input type="checkbox" class="form-check-input" checked>
        </td>
        <td>
          <input type="checkbox" class="form-check-input">
        </td>
        <td>
          <input type="checkbox" class="form-check-input">
        </td>
      </tr>
    </tbody>
  </table>
</div>
```

**Specifications:**
- Matrix Cell: 60px width, centered
- Checkbox: 20px, centered in cell
- Module Column: Left-aligned, `--font-medium`

---

### 13.2 Organisation Module (Hierarchical Structures)

#### Hierarchy Breadcrumb
```html
<div class="hierarchy-breadcrumb">
  <a href="/organisation/sites/1" class="hierarchy-item">
    <i class="bi bi-geo-alt"></i>
    <span>Site Principal</span>
  </a>
  <i class="bi bi-chevron-right hierarchy-separator"></i>
  <a href="/organisation/directions/5" class="hierarchy-item">
    <i class="bi bi-diagram-3"></i>
    <span>Direction Générale</span>
  </a>
  <i class="bi bi-chevron-right hierarchy-separator"></i>
  <span class="hierarchy-item hierarchy-item-current">
    <i class="bi bi-building"></i>
    <span>Service Informatique</span>
  </span>
</div>
```

**Specifications:**
- Background: `--surface-container`
- Padding: `--space-3` `--space-4`
- Border Radius: `--radius-md`
- Item Hover: Background `--surface-container-high`
- Current Item: `--font-semibold`, no hover

#### Organizational Tree View
```html
<div class="org-tree">
  <div class="org-node org-node-root">
    <div class="org-node-content">
      <div class="org-node-icon" style="background: var(--module-organisation);">
        <i class="bi bi-building"></i>
      </div>
      <div class="org-node-info">
        <div class="org-node-title">Direction Générale</div>
        <div class="org-node-meta">DG-001 • 5 services</div>
      </div>
      <button class="org-node-toggle">
        <i class="bi bi-chevron-down"></i>
      </button>
    </div>
    
    <div class="org-node-children">
      <div class="org-node org-node-child">
        <div class="org-node-content">
          <div class="org-node-icon" style="background: var(--module-organisation-light);">
            <i class="bi bi-diagram-2"></i>
          </div>
          <div class="org-node-info">
            <div class="org-node-title">Service Informatique</div>
            <div class="org-node-meta">SRV-001 • 12 unités</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
```

**Specifications:**
- Node Padding: `--space-4`
- Node Border: 1px solid `--border-subtle`
- Node Border Radius: `--radius-md`
- Node Hover: Shadow `--shadow-sm`
- Children Indent: 32px
- Connection Line: 2px solid `--border-default`, left side

---

## 14. Data Visualization

### 14.1 Dashboard Charts (ApexCharts)

#### Chart Configuration
```javascript
const chartOptions = {
  chart: {
    fontFamily: 'Inter, sans-serif',
    toolbar: {
      show: false
    }
  },
  colors: ['#1e3a8a', '#0891b2'],
  dataLabels: {
    enabled: false
  },
  stroke: {
    curve: 'smooth',
    width: 2
  },
  grid: {
    borderColor: '#e5e7eb',
    strokeDashArray: 4
  },
  xaxis: {
    labels: {
      style: {
        colors: '#6b7280',
        fontSize: '13px'
      }
    }
  },
  yaxis: {
    labels: {
      style: {
        colors: '#6b7280',
        fontSize: '13px'
      }
    }
  },
  tooltip: {
    theme: 'light',
    style: {
      fontSize: '13px'
    }
  }
};
```

---

## 15. Icon System

### Primary Icon Library: Bootstrap Icons
- **Size Scale:** 16px (sm), 20px (md), 24px (lg), 32px (xl)
- **Color:** Inherit from parent or use semantic colors
- **Spacing:** `--space-2` gap from adjacent text

### Common Icon Mappings
```javascript
const iconMap = {
  // Actions
  create: 'bi-plus-circle',
  edit: 'bi-pencil',
  delete: 'bi-trash',
  view: 'bi-eye',
  save: 'bi-check-circle',
  cancel: 'bi-x-circle',
  
  // Entities
  user: 'bi-person',
  users: 'bi-people',
  site: 'bi-geo-alt',
  building: 'bi-building',
  direction: 'bi-diagram-3',
  service: 'bi-diagram-2',
  unit: 'bi-box',
  
  // Status
  active: 'bi-check-circle-fill',
  inactive: 'bi-x-circle-fill',
  pending: 'bi-clock-fill',
  warning: 'bi-exclamation-triangle-fill',
  
  // Navigation
  dashboard: 'bi-speedometer2',
  settings: 'bi-gear',
  logout: 'bi-box-arrow-right',
  
  // Assets
  computer: 'bi-laptop',
  printer: 'bi-printer',
  phone: 'bi-phone',
  network: 'bi-router'
};
```

---

## 16. Code Standards & Best Practices

### 16.1 CSS Architecture (BEM Methodology)
```css
/* Block */
.card { }

/* Element */
.card__header { }
.card__body { }
.card__footer { }

/* Modifier */
.card--elevated { }
.card--bordered { }
```

### 16.2 JavaScript Patterns
```javascript
// Module Pattern for Controllers
const ServiceController = {
  init() {
    this.bindEvents();
    this.loadData();
  },
  
  bindEvents() {
    $('#createBtn').on('click', () => this.openModal());
    $('#serviceForm').on('submit', (e) => this.handleSubmit(e));
  },
  
  async loadData() {
    try {
      const response = await $.ajax({
        url: '/api/services',
        method: 'GET'
      });
      this.renderTable(response.data);
    } catch (error) {
      this.showError(error.message);
    }
  },
  
  showError(message) {
    Swal.fire({
      icon: 'error',
      title: 'Erreur',
      text: message,
      toast: true,
      position: 'top-end'
    });
  }
};

// Initialize on document ready
$(document).ready(() => {
  ServiceController.init();
});
```

### 16.3 Blade Component Structure
```php
// resources/views/components/organisation/service-card.blade.php
@props([
    'service',
    'showActions' => true
])

<div class="card service-card">
    <div class="card-body">
        <div class="service-header">
            <h4 class="service-title">{{ $service->libelle }}</h4>
            <span class="badge badge-{{ $service->actif ? 'success' : 'danger' }}">
                {{ $service->actif ? 'Actif' : 'Inactif' }}
            </span>
        </div>
        
        <div class="service-meta">
            <span class="meta-item">
                <i class="bi bi-hash"></i>
                {{ $service->code }}
            </span>
            <span class="meta-item">
                <i class="bi bi-diagram-3"></i>
                {{ $service->direction->libelle }}
            </span>
        </div>
        
        @if($showActions)
            <div class="service-actions">
                <a href="{{ route('organisation.services.show', $service) }}" 
                   class="btn btn-sm btn-secondary">
                    <i class="bi bi-eye"></i>
                    Voir
                </a>
            </div>
        @endif
    </div>
</div>
```

---

## 17. Performance Guidelines

### 17.1 Asset Loading
- **Critical CSS:** Inline above-the-fold styles
- **Fonts:** Preload Inter font files
- **Images:** Use WebP format with fallbacks
- **Icons:** Use SVG sprites for common icons

### 17.2 JavaScript Optimization
- **Lazy Loading:** Load tables and charts on demand
- **Debouncing:** Search inputs debounced at 300ms
- **Pagination:** Server-side pagination for tables > 100 rows
- **Caching:** Cache Select2 dropdown data for 5 minutes

---

## 18. Documentation & Maintenance

### 18.1 Component Documentation Template
```markdown
## Component Name

### Purpose
Brief description of what the component does.

### Usage
```html
<div class="component-class">
  <!-- Example markup -->
</div>
```

### Props/Options
- `prop1`: Description
- `prop2`: Description

### Variants
- Default
- Variant 1
- Variant 2

### Accessibility
- ARIA labels required
- Keyboard navigation support

### Dependencies
- Bootstrap 5
- jQuery (if applicable)
```

### 18.2 Version Control
- **Major Version:** Breaking changes to component API
- **Minor Version:** New components or features
- **Patch Version:** Bug fixes and refinements

---

## 19. Migration Guide (From Current to New System)

### Phase 1: Foundation (Week 1-2)
1. Implement CSS custom properties
2. Update color palette across all modules
3. Standardize spacing system

### Phase 2: Components (Week 3-4)
1. Refactor buttons and form elements
2. Update card components
3. Standardize table styles

### Phase 3: Layouts (Week 5-6)
1. Implement new page header pattern
2. Update sidebar navigation
3. Refactor detail view layouts

### Phase 4: Module-Specific (Week 7-8)
1. Update Core module components
2. Update Organisation module components

---

## 20. Quick Reference

### Color Usage Matrix
| Element | Color Token | Use Case |
|---------|-------------|----------|
| Primary Button | `--primary` | Main actions |
| Secondary Button | `--surface-container-high` | Alternative actions |
| Success Badge | `--success-subtle` | Active status |
| Danger Badge | `--danger-subtle` | Inactive/error status |
| Card Background | `--surface-base` | Content containers |
| Page Background | `--surface-elevated` | Main layout |

### Spacing Quick Reference
| Use Case | Token | Value |
|----------|-------|-------|
| Button Padding | `--space-3` `--space-6` | 12px 24px |
| Card Padding | `--card-padding` | 24px |
| Form Group Gap | `--form-group-gap` | 20px |
| Section Gap | `--space-8` | 32px |

### Typography Quick Reference
| Element | Size | Weight | Color |
|---------|------|--------|-------|
| Page Title | `--text-display-md` | `--font-bold` | `--text-primary` |
| Card Title | `--text-heading-md` | `--font-semibold` | `--text-primary` |
| Body Text | `--text-body-lg` | `--font-regular` | `--text-secondary` |
| Label | `--text-label-lg` | `--font-medium` | `--text-secondary` |

---

**Document Version:** 1.0.0  
**Last Updated:** April 22, 2026  
**Maintained By:** Development Team  
**Contact:** dev@parcinfo.com
