<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Centro de Mando Urbano C2 | H. Ayuntamiento de Álamo Temapache (2026-2029)</title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Leaflet GIS 1.9.4 (Oficial) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Estilos Institucionales Modulares Álamo Temapache (Modo Noche / Modo Claro) -->
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
</head>
<body class="min-h-screen flex flex-col">

    <!-- ====================================================================== -->
    <!-- BARRA SUPERIOR INSTITUCIONAL (ÁLAMO TEMAPACHE 2026-2029)               -->
    <!-- ====================================================================== -->
    <header class="gov-header">
        <div class="gov-header-inner">
            <div class="gov-brand">
                <div class="gov-brand-icon">
                    <i class="fa-solid fa-landmark-dome"></i>
                </div>
                <div>
                    <div class="gov-badge-group">
                        <span class="gov-badge-municipio">
                            Álamo Temapache, Ver.
                        </span>
                    </div>
                    <h1 class="gov-title">
                        ÁLAMO TEMAPACHE
                    </h1>
                    <span class="gov-subtitle">
                        GOBIERNO MUNICIPAL 2026-2029 • CENTRO DE MANDO URBANO C2
                    </span>
                </div>
            </div>

            <div class="gov-actions">
                <!-- Reloj de Sincronización en Vivo -->
                <div class="gov-sync-box hidden sm:block">
                    <span class="gov-sync-label">Última Sincronización</span>
                    <span id="sync-clock" class="gov-sync-clock">{{ now()->format('H:i:s') }}</span>
                </div>

                <!-- Conmutador de Modo Noche / Modo Claro -->
                <button type="button" id="theme-toggle-btn" class="gov-theme-toggle" title="Cambiar a Modo Claro" aria-label="Cambiar tema">
                    <i class="fa-solid fa-sun"></i>
                </button>

                <!-- Acceso a la App Móvil Ciudadana Oficial -->
                <a href="https://alamoapp.gestionmaplingps.com" target="_blank" rel="noopener noreferrer" class="gov-btn-app">
                    <i class="fa-solid fa-mobile-screen-button"></i>
                    <span>Abrir App Ciudadana</span>
                </a>
            </div>
        </div>
    </header>

    <!-- ====================================================================== -->
    <!-- CONTENEDOR PRINCIPAL CON DATOS INICIALES INYECTADOS VÍA DATA-ATTRIBUTE -->
    <!-- ====================================================================== -->
    <main id="dashboard-app" class="max-w-[1680px] w-full mx-auto px-4 md:px-6 py-6 flex-1 space-y-6" data-reports='@json($reports)'>

        <!-- TARJETAS DE INDICADORES EJECUTIVOS (KPIS) -->
        <section class="gov-kpi-grid">
            <div class="gov-kpi-card kpi-total">
                <div class="gov-kpi-header">
                    <span>Total Reportes</span>
                    <i class="fa-solid fa-layer-group gov-kpi-icon"></i>
                </div>
                <p id="kpi-total" class="gov-kpi-value">{{ $stats['total'] }}</p>
                <span class="gov-kpi-caption">Folios registrados</span>
            </div>

            <div class="gov-kpi-card kpi-pendientes">
                <div class="gov-kpi-header">
                    <span>Pendientes</span>
                    <i class="fa-solid fa-bell gov-kpi-icon"></i>
                </div>
                <p id="kpi-pendientes" class="gov-kpi-value">{{ $stats['pendientes'] }}</p>
                <span class="gov-kpi-caption">Requieren despacho</span>
            </div>

            <div class="gov-kpi-card kpi-proceso">
                <div class="gov-kpi-header">
                    <span>En Proceso</span>
                    <i class="fa-solid fa-person-digging gov-kpi-icon"></i>
                </div>
                <p id="kpi-proceso" class="gov-kpi-value">{{ $stats['en_proceso'] }}</p>
                <span class="gov-kpi-caption">Cuadrillas trabajando</span>
            </div>

            <div class="gov-kpi-card kpi-resueltos">
                <div class="gov-kpi-header">
                    <span>Resueltos</span>
                    <i class="fa-solid fa-circle-check gov-kpi-icon"></i>
                </div>
                <p id="kpi-resueltos" class="gov-kpi-value">{{ $stats['resueltos'] }}</p>
                <span class="gov-kpi-caption">Con evidencia fotográfica</span>
            </div>

            <div class="gov-kpi-card kpi-criticos">
                <div class="gov-kpi-header">
                    <span>Emergencias / Vial</span>
                    <i class="fa-solid fa-triangle-exclamation gov-kpi-icon"></i>
                </div>
                <p id="kpi-criticos" class="gov-kpi-value">{{ $stats['criticos'] }}</p>
                <span class="gov-kpi-caption">Prioridad crítica activa</span>
            </div>

            <div class="gov-kpi-card kpi-votos">
                <div class="gov-kpi-header">
                    <span>Impacto Vecinal</span>
                    <i class="fa-solid fa-users gov-kpi-icon"></i>
                </div>
                <p id="kpi-votos" class="gov-kpi-value">+{{ $stats['votos_ciudadanos'] }}</p>
                <span class="gov-kpi-caption">Votos ciudadanos ("Me afecta")</span>
            </div>
        </section>

        <!-- BLOQUE CENTRAL: MAPA GIS OFICIAL + FILTROS POR DIRECCIÓN MUNICIPAL -->
        <section class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <!-- Mapa Cartográfico Geo-referenciado (8 Columnas) -->
            <div class="lg:col-span-8 gov-panel-card flex flex-col">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-2">
                    <div>
                        <h2 class="gov-panel-title">
                            <i class="fa-solid fa-map-location-dot" style="color: var(--accent-gold);"></i>
                            Cartografía Geo-referenciada de Álamo Temapache
                        </h2>
                        <p class="gov-panel-desc">Haz clic en cualquier marcador para inspeccionar la evidencia fotográfica y despachar cuadrilla.</p>
                    </div>
                    <div class="flex items-center gap-3 text-xs flex-wrap">
                        <span class="flex items-center gap-1.5" style="color: var(--status-pending);">
                            <span class="w-2.5 h-2.5 rounded-full inline-block" style="background-color: var(--status-pending);"></span> Pendiente
                        </span>
                        <span class="flex items-center gap-1.5" style="color: var(--status-process);">
                            <span class="w-2.5 h-2.5 rounded-full inline-block" style="background-color: var(--status-process);"></span> En Proceso
                        </span>
                        <span class="flex items-center gap-1.5" style="color: var(--status-resolved);">
                            <span class="w-2.5 h-2.5 rounded-full inline-block" style="background-color: var(--status-resolved);"></span> Resuelto
                        </span>
                        <button type="button" onclick="resetMapCenter()" class="px-2.5 py-1 rounded-lg text-xs font-semibold flex items-center gap-1" style="background: var(--bg-surface); border: 1px solid var(--border-color); color: var(--text-primary);">
                            <i class="fa-solid fa-crosshairs"></i> Centrar Álamo
                        </button>
                    </div>
                </div>

                <!-- Contenedor del Mapa Leaflet -->
                <div id="map"></div>
            </div>

            <!-- Panel Lateral: Filtros por Dirección Municipal y Estatus (4 Columnas) -->
            <div class="lg:col-span-4 gov-panel-card flex flex-col justify-between space-y-4">
                <div>
                    <h3 class="gov-panel-title mb-1">
                        <i class="fa-solid fa-sliders" style="color: var(--accent-gold);"></i>
                        Filtro por Dirección Municipal
                    </h3>
                    <p class="gov-panel-desc mb-4">Enrutamiento inteligente según tipo de incidente</p>

                    <div class="space-y-2">
                        <button type="button" onclick="filterByCategory('all')" id="btn-cat-all" class="cat-btn active">
                            <span class="flex items-center gap-2.5">
                                <i class="fa-solid fa-globe w-4" style="color: var(--accent-gold);"></i>
                                Todas las Direcciones Municipales
                            </span>
                            <span class="cat-badge-active">Activo</span>
                        </button>

                        <button type="button" onclick="filterByCategory('bache')" id="btn-cat-bache" class="cat-btn">
                            <span class="flex items-center gap-2.5">
                                <i class="fa-solid fa-road w-4" style="color: #F59E0B;"></i>
                                Obras Públicas (Bacheo y Pavimento)
                            </span>
                            <i class="fa-solid fa-chevron-right text-[10px] opacity-40"></i>
                        </button>

                        <button type="button" onclick="filterByCategory('drenaje')" id="btn-cat-drenaje" class="cat-btn">
                            <span class="flex items-center gap-2.5">
                                <i class="fa-solid fa-water w-4" style="color: #38BDF8;"></i>
                                Drenaje y Alcantarillado (CAEV)
                            </span>
                            <i class="fa-solid fa-chevron-right text-[10px] opacity-40"></i>
                        </button>

                        <button type="button" onclick="filterByCategory('alumbrado')" id="btn-cat-alumbrado" class="cat-btn">
                            <span class="flex items-center gap-2.5">
                                <i class="fa-solid fa-lightbulb w-4" style="color: #FACC15;"></i>
                                Servicios Municipales (Alumbrado)
                            </span>
                            <i class="fa-solid fa-chevron-right text-[10px] opacity-40"></i>
                        </button>

                        <button type="button" onclick="filterByCategory('agua')" id="btn-cat-agua" class="cat-btn">
                            <span class="flex items-center gap-2.5">
                                <i class="fa-solid fa-faucet-drip w-4" style="color: #06B6D4;"></i>
                                CMAS / Fugas de Agua Potable
                            </span>
                            <i class="fa-solid fa-chevron-right text-[10px] opacity-40"></i>
                        </button>

                        <button type="button" onclick="filterByCategory('basura')" id="btn-cat-basura" class="cat-btn">
                            <span class="flex items-center gap-2.5">
                                <i class="fa-solid fa-trash-can w-4" style="color: #10B981;"></i>
                                Limpia Pública y Recolección
                            </span>
                            <i class="fa-solid fa-chevron-right text-[10px] opacity-40"></i>
                        </button>

                        <button type="button" onclick="filterByCategory('accidente')" id="btn-cat-accidente" class="cat-btn">
                            <span class="flex items-center gap-2.5">
                                <i class="fa-solid fa-car-burst w-4" style="color: #F43F5E;"></i>
                                Protección Civil y Tránsito Vial
                            </span>
                            <i class="fa-solid fa-chevron-right text-[10px] opacity-40"></i>
                        </button>
                    </div>
                </div>

                <!-- Filtro por Estatus Operativo -->
                <div class="pt-4" style="border-top: 1px solid var(--border-color);">
                    <label for="status-filter" class="block text-xs font-bold uppercase mb-2" style="color: var(--text-muted); letter-spacing: 0.05em;">
                        Filtrar por Estatus Operativo
                    </label>
                    <select id="status-filter" onchange="applyFilters()" class="gov-select">
                        <option value="all">Todos los Estatus</option>
                        <option value="pendiente">🔴 Pendientes de Atención</option>
                        <option value="en_proceso">🟡 En Proceso (Cuadrilla Asignada)</option>
                        <option value="resuelto">🟢 Resueltos con Evidencia</option>
                        <option value="rechazado">⚪ Rechazados / Reportes Inválidos</option>
                    </select>
                </div>
            </div>
        </section>

        <!-- BANDEJA DE DESPACHO Y AUDITORÍA DE REPORTES CIUDADANOS -->
        <section class="gov-panel-card p-0 overflow-hidden">
            <div class="p-4 md:p-5 flex flex-wrap items-center justify-between gap-4" style="border-bottom: 1px solid var(--border-color);">
                <div>
                    <h2 class="gov-panel-title">
                        <i class="fa-solid fa-list-check" style="color: var(--accent-gold);"></i>
                        Bandeja de Despacho y Auditoría de Reportes Ciudadanos
                    </h2>
                    <p class="gov-panel-desc">Los reportes enviados desde la App Ciudadana se sincronizan en vivo sin recargar la página.</p>
                </div>
                <div class="relative w-full sm:w-80">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-xs" style="color: var(--text-muted);"></i>
                    <input type="text" id="search-input" oninput="applyFilters()" placeholder="Buscar por folio, colonia, calle..." class="gov-input pl-9">
                </div>
            </div>

            <div class="gov-table-wrapper">
                <table class="gov-table">
                    <thead>
                        <tr>
                            <th>Folio / Hora</th>
                            <th>Evidencia</th>
                            <th>Incidente y Dirección Asignada</th>
                            <th>Colonia / Ubicación</th>
                            <th class="text-center">Apoyos (+1)</th>
                            <th>Prioridad</th>
                            <th>Estatus</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="reports-tbody">
                        <!-- Renderizado dinámicamente por dashboard.js -->
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- ====================================================================== -->
    <!-- MODAL DE AUDITORÍA Y RESOLUCIÓN ("ANTES Y DESPUÉS")                    -->
    <!-- ====================================================================== -->
    <div id="report-modal" class="gov-modal-backdrop">
        <div class="gov-modal-dialog">
            
            <!-- Encabezado del Modal -->
            <div class="gov-modal-header">
                <div class="flex items-center gap-3">
                    <span id="modal-folio" class="folio-tag text-xs px-2.5 py-1 rounded-md" style="background: var(--bg-surface); border: 1px solid var(--border-color);">
                        ALM-2026-0001
                    </span>
                    <h3 id="modal-title" class="text-base font-bold" style="color: var(--text-primary);">
                        Detalle del Reporte
                    </h3>
                </div>
                <button type="button" onclick="closeModal()" class="gov-modal-close" aria-label="Cerrar modal">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Cuerpo del Modal -->
            <div class="p-5 md:p-6 space-y-5">
                
                <!-- Comparativa Visual: ANTES (Ciudadano) vs DESPUÉS (Municipio) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="gov-evidence-container">
                        <span class="gov-evidence-label" style="color: var(--status-pending);">
                            <i class="fa-solid fa-camera"></i> Evidencia Inicial (Ciudadano)
                        </span>
                        <img id="modal-img-initial" src="" alt="Evidencia inicial" class="gov-evidence-img">
                    </div>

                    <div class="gov-evidence-container">
                        <span class="gov-evidence-label" style="color: var(--status-resolved);">
                            <i class="fa-solid fa-check-double"></i> Evidencia de Reparación (Municipio)
                        </span>
                        <div id="modal-after-container">
                            <img id="modal-img-resolution" src="" alt="Evidencia de cierre" class="gov-evidence-img hidden">
                            <div id="modal-no-resolution" class="gov-evidence-empty">
                                <i class="fa-solid fa-helmet-safety text-3xl mb-2" style="opacity: 0.6;"></i>
                                <p class="text-xs font-semibold">Aún sin foto de cierre de cuadrilla</p>
                                <span class="text-[11px] opacity-75 mt-1">Adjunta la imagen abajo para validar el trabajo</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Datos de Geolocalización y Ciudadano -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 p-4 rounded-xl text-xs" style="background: var(--bg-surface); border: 1px solid var(--border-color);">
                    <div>
                        <span class="block" style="color: var(--text-muted);">Dirección Municipal Responsable:</span>
                        <strong id="modal-department" class="block mt-0.5" style="color: var(--text-primary);"></strong>
                    </div>
                    <div>
                        <span class="block" style="color: var(--text-muted);">Colonia y Referencia:</span>
                        <strong id="modal-address" class="block mt-0.5" style="color: var(--text-primary);"></strong>
                    </div>
                    <div>
                        <span class="block" style="color: var(--text-muted);">Reportado por:</span>
                        <strong id="modal-citizen" class="block mt-0.5" style="color: var(--text-primary);"></strong>
                        <a id="modal-gmaps" href="#" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 font-semibold mt-1" style="color: var(--accent-gold);">
                            <i class="fa-solid fa-location-arrow"></i> Abrir en Google Maps para Cuadrilla
                        </a>
                    </div>
                </div>

                <div>
                    <span class="text-xs font-bold uppercase block mb-1" style="color: var(--text-muted);">Descripción del Ciudadano:</span>
                    <p id="modal-description" class="text-xs p-3.5 rounded-xl" style="background: var(--bg-surface); border: 1px solid var(--border-color); color: var(--text-primary);"></p>
                </div>

                <!-- Formulario de Despacho y Cierre Municipal -->
                <form id="update-report-form" class="space-y-4 pt-4" style="border-top: 1px solid var(--border-color);">
                    <input type="hidden" id="modal-report-id">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="modal-status-select" class="block text-xs font-bold uppercase mb-2" style="color: var(--text-primary);">
                                Cambiar Estatus Operativo
                            </label>
                            <select id="modal-status-select" name="status" class="gov-select">
                                <option value="pendiente">🔴 Pendiente (En espera de cuadrilla)</option>
                                <option value="en_proceso">🟡 En Proceso (Cuadrilla trabajando en sitio)</option>
                                <option value="resuelto">🟢 Resuelto (Trabajo concluido)</option>
                                <option value="rechazado">⚪ Rechazado / Reporte Falso</option>
                            </select>
                        </div>

                        <div>
                            <label for="modal-file-input" class="block text-xs font-bold uppercase mb-2" style="color: var(--text-primary);">
                                Subir Foto del "Después" (Evidencia Municipal)
                            </label>
                            <input type="file" id="modal-file-input" name="evidence_resolution" accept="image/*" class="gov-input">
                        </div>
                    </div>

                    <div>
                        <label for="modal-notes" class="block text-xs font-bold uppercase mb-2" style="color: var(--text-primary);">
                            Dictamen / Nota Oficial de Atención
                        </label>
                        <textarea id="modal-notes" name="resolution_notes" rows="2" placeholder="Ej: Se envió cuadrilla #3 de Obras Públicas, se reparó registro con concreto hidráulico..." class="gov-textarea"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" onclick="closeModal()" class="px-4 py-2.5 rounded-xl text-xs font-bold" style="background: var(--bg-surface); border: 1px solid var(--border-color); color: var(--text-muted);">
                            Cancelar
                        </button>
                        <button type="submit" id="btn-save-modal" class="px-5 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2 shadow-lg" style="background: var(--primary-wine); color: #FFFFFF; border: 1px solid var(--accent-gold);">
                            <i class="fa-solid fa-floppy-disk"></i> Guardar y Notificar al Ciudadano
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Leaflet 1.9.4 JS Oficial -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <!-- Script Modular del Centro de Mando Municipal -->
    <script src="{{ asset('js/dashboard.js') }}" defer></script>
</body>
</html>
