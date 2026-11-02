<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Centro de Mando Urbano C2 | H. Ayuntamiento de Álamo Temapache</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Leaflet GIS CSS & JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
        #map { height: 480px; width: 100%; border-radius: 0.75rem; z-index: 10; }
        
        /* Efecto de pulso para incidentes críticos / nuevos */
        @keyframes pulse-ring {
            0% { transform: scale(0.8); opacity: 0.9; }
            100% { transform: scale(2.4); opacity: 0; }
        }
        .marker-pulse {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .marker-pulse::before {
            content: '';
            position: absolute;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: currentColor;
            animation: pulse-ring 1.6s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
        }
        /* Scrollbar estilizado */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 3px; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col">

    <!-- BARRA SUPERIOR INSTITUCIONAL -->
    <header class="bg-slate-900/90 backdrop-blur border-b border-slate-800 sticky top-0 z-40 px-6 py-3.5">
        <div class="max-w-[1600px] mx-auto flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-orange-500 to-amber-600 flex items-center justify-center shadow-lg shadow-orange-500/20">
                    <i class="fa-solid fa-satellite-dish text-white text-xl"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2.5">
                        <span class="text-xs font-bold uppercase tracking-widest px-2.5 py-0.5 rounded-full bg-orange-500/20 text-orange-400 border border-orange-500/30">
                            Álamo Temapache, Ver.
                        </span>
                        <span class="flex items-center gap-1.5 text-xs text-emerald-400 font-medium">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                            Monitor en Vivo Activo
                        </span>
                    </div>
                    <h1 class="text-lg md:text-xl font-bold text-white tracking-tight mt-0.5">
                        Plataforma Inteligente de Atención Ciudadana y Respuesta Urbana
                    </h1>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="hidden md:flex flex-col text-right mr-2">
                    <span class="text-xs text-slate-400">Última sincronización</span>
                    <span id="sync-clock" class="text-sm font-mono font-semibold text-slate-200">{{ now()->format('H:i:s') }}</span>
                </div>
                
                <!-- Botón acceso rápido a la App Ciudadana (se activará en Fase 2) -->
                <a href="/reportar" target="_blank" class="px-4 py-2.5 rounded-xl bg-orange-600 hover:bg-orange-500 text-white font-semibold text-sm flex items-center gap-2 shadow-lg shadow-orange-600/25 transition">
                    <i class="fa-solid fa-mobile-screen-button"></i>
                    <span>Abrir App Ciudadana</span>
                </a>
            </div>
        </div>
    </header>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="max-w-[1600px] w-full mx-auto px-6 py-6 flex-1 space-y-6">

        <!-- TARJETAS DE INDICADORES EJECUTIVOS (KPIs) -->
        <section class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
                <div class="flex items-center justify-between text-slate-400 text-xs font-medium uppercase">
                    <span>Total Reportes</span>
                    <i class="fa-solid fa-layer-group text-slate-500 text-base"></i>
                </div>
                <p id="kpi-total" class="text-3xl font-extrabold text-white mt-2">{{ $stats['total'] }}</p>
                <span class="text-[11px] text-slate-400">Folios registrados</span>
            </div>

            <div class="bg-slate-900 border border-red-500/30 rounded-xl p-4">
                <div class="flex items-center justify-between text-red-400 text-xs font-medium uppercase">
                    <span>Pendientes</span>
                    <i class="fa-solid fa-bell text-red-500 text-base"></i>
                </div>
                <p id="kpi-pendientes" class="text-3xl font-extrabold text-red-400 mt-2">{{ $stats['pendientes'] }}</p>
                <span class="text-[11px] text-red-300/70">Requieren despacho</span>
            </div>

            <div class="bg-slate-900 border border-amber-500/30 rounded-xl p-4">
                <div class="flex items-center justify-between text-amber-400 text-xs font-medium uppercase">
                    <span>En Proceso</span>
                    <i class="fa-solid fa-person-digging text-amber-500 text-base"></i>
                </div>
                <p id="kpi-proceso" class="text-3xl font-extrabold text-amber-400 mt-2">{{ $stats['en_proceso'] }}</p>
                <span class="text-[11px] text-amber-300/70">Cuadrillas trabajando</span>
            </div>

            <div class="bg-slate-900 border border-emerald-500/30 rounded-xl p-4">
                <div class="flex items-center justify-between text-emerald-400 text-xs font-medium uppercase">
                    <span>Resueltos</span>
                    <i class="fa-solid fa-circle-check text-emerald-500 text-base"></i>
                </div>
                <p id="kpi-resueltos" class="text-3xl font-extrabold text-emerald-400 mt-2">{{ $stats['resueltos'] }}</p>
                <span class="text-[11px] text-emerald-300/70">Con evidencia fotográfica</span>
            </div>

            <div class="bg-slate-900 border border-purple-500/30 rounded-xl p-4">
                <div class="flex items-center justify-between text-purple-400 text-xs font-medium uppercase">
                    <span>Emergencias / Vial</span>
                    <i class="fa-solid fa-triangle-exclamation text-purple-400 text-base"></i>
                </div>
                <p id="kpi-criticos" class="text-3xl font-extrabold text-purple-400 mt-2">{{ $stats['criticos'] }}</p>
                <span class="text-[11px] text-purple-300/70">Prioridad crítica activa</span>
            </div>

            <div class="bg-slate-900 border border-sky-500/30 rounded-xl p-4">
                <div class="flex items-center justify-between text-sky-400 text-xs font-medium uppercase">
                    <span>Impacto Vecinal</span>
                    <i class="fa-solid fa-users text-sky-400 text-base"></i>
                </div>
                <p id="kpi-votos" class="text-3xl font-extrabold text-sky-400 mt-2">+{{ $stats['votos_ciudadanos'] }}</p>
                <span class="text-[11px] text-sky-300/70">Votos ciudadanos ("Me afecta")</span>
            </div>
        </section>

        <!-- BLOQUE CENTRAL: MAPA GIS DE ÁLAMO + FILTROS RÁPIDOS -->
        <section class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <!-- Mapa Cartográfico Interactivo (8 Columnas) -->
            <div class="lg:col-span-8 bg-slate-900 border border-slate-800 rounded-2xl p-4 flex flex-col">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                    <div>
                        <h2 class="text-base font-bold text-white flex items-center gap-2">
                            <i class="fa-solid fa-map-location-dot text-orange-500"></i>
                            Cartografía Geo-referenciada de Álamo Temapache
                        </h2>
                        <p class="text-xs text-slate-400">Haz clic en cualquier marcador para inspeccionar la evidencia fotográfica y despachar cuadrilla.</p>
                    </div>
                    <div class="flex items-center gap-3 text-xs">
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-red-500 inline-block"></span> Pendiente</span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-amber-500 inline-block"></span> En Proceso</span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span> Resuelto</span>
                        <button onclick="resetMapCenter()" class="ml-2 px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700">
                            <i class="fa-solid fa-crosshairs mr-1"></i> Centrar Álamo
                        </button>
                    </div>
                </div>

                <div id="map" class="border border-slate-800 shadow-inner"></div>
            </div>

            <!-- Panel Lateral: Filtros por Dirección Municipal y Prioridad (4 Columnas) -->
            <div class="lg:col-span-4 bg-slate-900 border border-slate-800 rounded-2xl p-5 flex flex-col justify-between space-y-4">
                <div>
                    <h3 class="text-base font-bold text-white flex items-center gap-2 mb-1">
                        <i class="fa-solid fa-sliders text-orange-500"></i>
                        Filtro por Dirección Municipal
                    </h3>
                    <p class="text-xs text-slate-400 mb-4">Enrutamiento inteligente según tipo de incidente</p>

                    <div class="space-y-2">
                        <button onclick="filterByCategory('all')" id="btn-cat-all" class="cat-btn w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-orange-600 text-white text-xs font-semibold transition">
                            <span class="flex items-center gap-2.5"><i class="fa-solid fa-globe w-4"></i> Todas las Direcciones Municipales</span>
                            <span class="px-2 py-0.5 rounded-full bg-black/20 text-[11px]">Ver Todo</span>
                        </button>

                        <button onclick="filterByCategory('drenaje')" id="btn-cat-drenaje" class="cat-btn w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-slate-800/80 hover:bg-slate-800 text-slate-300 text-xs font-medium transition">
                            <span class="flex items-center gap-2.5"><i class="fa-solid fa-water text-sky-400 w-4"></i> Drenaje y Alcantarillado (CAEV)</span>
                            <i class="fa-solid fa-chevron-right text-[10px] text-slate-500"></i>
                        </button>

                        <button onclick="filterByCategory('bache')" id="btn-cat-bache" class="cat-btn w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-slate-800/80 hover:bg-slate-800 text-slate-300 text-xs font-medium transition">
                            <span class="flex items-center gap-2.5"><i class="fa-solid fa-road text-amber-400 w-4"></i> Obras Públicas (Bacheo y Pavimento)</span>
                            <i class="fa-solid fa-chevron-right text-[10px] text-slate-500"></i>
                        </button>

                        <button onclick="filterByCategory('alumbrado')" id="btn-cat-alumbrado" class="cat-btn w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-slate-800/80 hover:bg-slate-800 text-slate-300 text-xs font-medium transition">
                            <span class="flex items-center gap-2.5"><i class="fa-solid fa-lightbulb text-yellow-400 w-4"></i> Servicios Públicos (Alumbrado)</span>
                            <i class="fa-solid fa-chevron-right text-[10px] text-slate-500"></i>
                        </button>

                        <button onclick="filterByCategory('agua')" id="btn-cat-agua" class="cat-btn w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-slate-800/80 hover:bg-slate-800 text-slate-300 text-xs font-medium transition">
                            <span class="flex items-center gap-2.5"><i class="fa-solid fa-faucet-drip text-cyan-400 w-4"></i> Fugas de Agua Potable</span>
                            <i class="fa-solid fa-chevron-right text-[10px] text-slate-500"></i>
                        </button>

                        <button onclick="filterByCategory('basura')" id="btn-cat-basura" class="cat-btn w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-slate-800/80 hover:bg-slate-800 text-slate-300 text-xs font-medium transition">
                            <span class="flex items-center gap-2.5"><i class="fa-solid fa-trash-can text-emerald-400 w-4"></i> Limpia Pública y Recolección</span>
                            <i class="fa-solid fa-chevron-right text-[10px] text-slate-500"></i>
                        </button>

                        <button onclick="filterByCategory('accidente')" id="btn-cat-accidente" class="cat-btn w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-slate-800/80 hover:bg-slate-800 text-slate-300 text-xs font-medium transition">
                            <span class="flex items-center gap-2.5"><i class="fa-solid fa-car-burst text-rose-500 w-4"></i> Protección Civil y Tránsito Vial</span>
                            <i class="fa-solid fa-chevron-right text-[10px] text-slate-500"></i>
                        </button>
                    </div>
                </div>

                <!-- Filtro por Estatus -->
                <div class="pt-4 border-t border-slate-800">
                    <label class="block text-xs font-semibold uppercase text-slate-400 mb-2">Filtrar por Estatus Operativo</label>
                    <select id="status-filter" onchange="applyFilters()" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-200 focus:outline-none focus:border-orange-500">
                        <option value="all">Todos los Estatus</option>
                        <option value="pendiente">🔴 Pendientes de Atención</option>
                        <option value="en_proceso">🟡 En Proceso (Cuadrilla Asignada)</option>
                        <option value="resuelto">🟢 Resueltos con Evidencia</option>
                    </select>
                </div>
            </div>
        </section>

        <!-- TABLA DE CONTROL Y DESPACHO DE TICKETS CIUDADANOS -->
        <section class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
            <div class="p-5 border-b border-slate-800 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-list-check text-orange-500"></i>
                        Bandeja de Despacho y Auditoría de Reportes Ciudadanos
                    </h2>
                    <p class="text-xs text-slate-400">Los reportes enviados desde la App Ciudadana aparecen automáticamente en esta bandeja sin recargar la página.</p>
                </div>
                <div class="relative w-full sm:w-72">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-slate-500 text-xs"></i>
                    <input type="text" id="search-input" oninput="applyFilters()" placeholder="Buscar por folio, colonia o calle..." class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-9 pr-4 py-2 text-xs text-slate-200 focus:outline-none focus:border-orange-500">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-950/60 text-[11px] font-bold uppercase text-slate-400 border-b border-slate-800">
                            <th class="py-3.5 px-4">Folio / Hora</th>
                            <th class="py-3.5 px-4">Evidencia</th>
                            <th class="py-3.5 px-4">Incidente y Dirección Asignada</th>
                            <th class="py-3.5 px-4">Colonia / Ubicación</th>
                            <th class="py-3.5 px-4 text-center">Apoyos (+1)</th>
                            <th class="py-3.5 px-4">Prioridad</th>
                            <th class="py-3.5 px-4">Estatus</th>
                            <th class="py-3.5 px-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="reports-tbody" class="divide-y divide-slate-800/80 text-xs">
                        <!-- Renderizado dinámicamente por JavaScript -->
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- MODAL DE AUDITORÍA Y RESOLUCIÓN ("ANTES Y DESPUÉS") -->
    <div id="report-modal" class="fixed inset-0 bg-black/75 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-700 rounded-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto shadow-2xl">
            
            <!-- Encabezado del Modal -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800 sticky top-0 bg-slate-900 z-10">
                <div class="flex items-center gap-3">
                    <span id="modal-folio" class="px-3 py-1 rounded-lg bg-orange-500/20 text-orange-400 font-mono font-bold text-xs border border-orange-500/30">ALM-2026-0001</span>
                    <h3 id="modal-title" class="text-base font-bold text-white">Detalle del Reporte</h3>
                </div>
                <button onclick="closeModal()" class="w-8 h-8 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Cuerpo del Modal -->
            <div class="p-6 space-y-6">
                
                <!-- Comparativa Visual: ANTES (Ciudadano) vs DESPUÉS (Ayuntamiento) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-slate-950 border border-slate-800 rounded-xl p-3">
                        <span class="text-[11px] font-bold uppercase text-red-400 flex items-center gap-1.5 mb-2">
                            <i class="fa-solid fa-camera"></i> Evidencia Inicial (Ciudadano)
                        </span>
                        <img id="modal-img-initial" src="" alt="Evidencia inicial" class="w-full h-52 object-cover rounded-lg border border-slate-800">
                    </div>

                    <div class="bg-slate-950 border border-slate-800 rounded-xl p-3 flex flex-col justify-between">
                        <span class="text-[11px] font-bold uppercase text-emerald-400 flex items-center gap-1.5 mb-2">
                            <i class="fa-solid fa-check-double"></i> Evidencia de Reparación (Municipio)
                        </span>
                        <div id="modal-after-container" class="w-full h-52 rounded-lg border border-dashed border-slate-700 flex flex-col items-center justify-center text-slate-500 overflow-hidden">
                            <img id="modal-img-resolution" src="" class="w-full h-full object-cover hidden">
                            <div id="modal-no-resolution" class="text-center p-4">
                                <i class="fa-solid fa-helmet-safety text-2xl mb-2 text-slate-600"></i>
                                <p class="text-xs">Aún sin foto de cierre de cuadrilla</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Datos de Geolocalización y Ciudadano -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 bg-slate-950 p-4 rounded-xl border border-slate-800 text-xs">
                    <div>
                        <span class="text-slate-500 block">Dirección Municipal Responsable:</span>
                        <strong id="modal-department" class="text-slate-200 mt-0.5 block"></strong>
                    </div>
                    <div>
                        <span class="text-slate-500 block">Colonia y Referencia:</span>
                        <strong id="modal-address" class="text-slate-200 mt-0.5 block"></strong>
                    </div>
                    <div>
                        <span class="text-slate-500 block">Reportado por:</span>
                        <strong id="modal-citizen" class="text-slate-200 mt-0.5 block"></strong>
                        <a id="modal-gmaps" href="#" target="_blank" class="inline-flex items-center gap-1 text-orange-400 hover:underline mt-1 font-medium">
                            <i class="fa-solid fa-location-arrow"></i> Abrir en Google Maps para Cuadrilla
                        </a>
                    </div>
                </div>

                <div>
                    <span class="text-xs font-semibold text-slate-400 block mb-1">Descripción del Ciudadano:</span>
                    <p id="modal-description" class="text-xs text-slate-300 bg-slate-950 p-3.5 rounded-xl border border-slate-800"></p>
                </div>

                <!-- Formulario de Despacho y Cierre Municipal -->
                <form id="update-report-form" onsubmit="submitStatusUpdate(event)" class="space-y-4 border-t border-slate-800 pt-5">
                    <input type="hidden" id="modal-report-id">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-300 mb-2">Cambiar Estatus Operativo</label>
                            <select id="modal-status-select" name="status" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white focus:border-orange-500 focus:outline-none">
                                <option value="pendiente">🔴 Pendiente (En espera de cuadrilla)</option>
                                <option value="en_proceso">🟡 En Proceso (Cuadrilla trabajando en sitio)</option>
                                <option value="resuelto">🟢 Resuelto (Trabajo concluido)</option>
                                <option value="rechazado">⚪ Rechazado / Reporte Falso</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-300 mb-2">Subir Foto del "Después" (Evidencia Municipal)</label>
                            <input type="file" id="modal-file-input" name="evidence_resolution" accept="image/*" class="w-full text-xs text-slate-300 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-orange-400 hover:file:bg-slate-700">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-300 mb-2">Dictamen / Nota Oficial de Atención</label>
                        <textarea id="modal-notes" name="resolution_notes" rows="2" placeholder="Ej: Se envió cuadrilla #3 de Obras Públicas, se reparó registro con concreto hidráulico..." class="w-full bg-slate-950 border border-slate-700 rounded-xl p-3 text-xs text-white focus:border-orange-500 focus:outline-none"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" onclick="closeModal()" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-300">
                            Cancelar
                        </button>
                        <button type="submit" id="btn-save-modal" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-xs font-bold text-white flex items-center gap-2 shadow-lg shadow-emerald-600/25">
                            <i class="fa-solid fa-floppy-disk"></i> Guardar y Notificar al Ciudadano
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- LÓGICA JAVASCRIPT: MAPA GIS, FILTROS Y POLLING EN TIEMPO REAL -->
    <script>
        // Coordenadas centrales de Álamo Temapache, Veracruz
        const ALAMO_CENTER = [20.9114, -97.6744];
        let allReports = @json($reports);
        let knownIds = new Set(allReports.map(r => r.id));
        let currentCategoryFilter = 'all';
        let markersLayer = null;

        // Inicializar Mapa Leaflet con diseño oscuro de CartoDB
        const map = L.map('map').setView(ALAMO_CENTER, 15);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; OpenStreetMap & H. Ayuntamiento de Álamo Temapache',
            maxZoom: 19
        }).addTo(map);

        markersLayer = L.layerGroup().addTo(map);

        function resetMapCenter() {
            map.flyTo(ALAMO_CENTER, 15, { duration: 1.2 });
        }

        // Diccionario visual de categorías
        const categoryMeta = {
            drenaje:   { icon: 'fa-water', label: 'Drenaje / Alcantarilla', color: '#38bdf8' },
            bache:     { icon: 'fa-road', label: 'Bache / Pavimento', color: '#f59e0b' },
            alumbrado: { icon: 'fa-lightbulb', label: 'Alumbrado Público', color: '#facc15' },
            agua:      { icon: 'fa-faucet-drip', label: 'Fuga de Agua', color: '#06b6d4' },
            basura:    { icon: 'fa-trash-can', label: 'Limpia Pública', color: '#10b981' },
            accidente: { icon: 'fa-car-burst', label: 'Accidente / Emergencia', color: '#f43f5e' }
        };

        // Renderizar pines en el mapa y filas en la tabla
        function renderDashboard() {
            const statusFilter = document.getElementById('status-filter').value;
            const searchQuery = document.getElementById('search-input').value.toLowerCase();

            const filtered = allReports.filter(r => {
                const matchCat = currentCategoryFilter === 'all' || r.category === currentCategoryFilter;
                const matchStatus = statusFilter === 'all' || r.status === statusFilter;
                const matchSearch = r.folio.toLowerCase().includes(searchQuery) ||
                                    r.title.toLowerCase().includes(searchQuery) ||
                                    r.colonia.toLowerCase().includes(searchQuery) ||
                                    (r.address_reference && r.address_reference.toLowerCase().includes(searchQuery));
                return matchCat && matchStatus && matchSearch;
            });

            // 1. Actualizar Marcadores del Mapa
            markersLayer.clearLayers();
            filtered.forEach(report => {
                const statusColor = report.status === 'resuelto' ? '#10b981' : (report.status === 'en_proceso' ? '#f59e0b' : '#ef4444');
                const cat = categoryMeta[report.category] || { icon: 'fa-circle-exclamation', label: report.category };
                const pulseClass = (report.status === 'pendiente' && (report.priority === 'critica' || report.priority === 'alta')) ? 'marker-pulse' : '';

                const customIcon = L.divIcon({
                    className: 'custom-div-icon',
                    html: `<div class="${pulseClass}" style="color: ${statusColor}; width: 34px; height: 34px; border-radius: 50%; background: ${statusColor}; border: 3px solid #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.45); display:flex; align-items:center; justify-content:center;">
                              <i class="fa-solid ${cat.icon}" style="color: white; font-size: 14px; position:relative; z-index:2;"></i>
                           </div>`,
                    iconSize: [34, 34],
                    iconAnchor: [17, 17]
                });

                const marker = L.marker([report.latitude, report.longitude], { icon: customIcon });
                marker.bindPopup(`
                    <div style="min-width: 210px; font-family: system-ui;">
                        <div style="font-size:11px; font-weight:700; color:#ea580c;">${report.folio} • ${cat.label}</div>
                        <div style="font-weight:700; font-size:13px; margin: 3px 0; color:#0f172a;">${report.title}</div>
                        <div style="font-size:11px; color:#475569; margin-bottom:6px;">📍 ${report.colonia} (${report.address_reference || ''})</div>
                        <img src="${report.evidence_initial}" style="width:100%; height:95px; object-fit:cover; border-radius:6px; margin-bottom:8px;">
                        <button onclick="openReportModal(${report.id})" style="width:100%; background:#0f172a; color:#fff; border:none; padding:6px; border-radius:6px; font-size:11px; font-weight:600; cursor:pointer;">
                            Inspeccionar y Atender
                        </button>
                    </div>
                `);
                markersLayer.addLayer(marker);
            });

            // 2. Actualizar Tabla de Tickets
            const tbody = document.getElementById('reports-tbody');
            if (filtered.length === 0) {
                tbody.innerHTML = `<tr><td colspan="8" class="text-center py-8 text-slate-500">No se encontraron reportes con los filtros seleccionados.</td></tr>`;
                return;
            }

            tbody.innerHTML = filtered.map(report => {
                const cat = categoryMeta[report.category] || { icon: 'fa-circle-info', label: report.category };
                
                const statusBadge = {
                    pendiente:  `<span class="px-2.5 py-1 rounded-full bg-red-500/15 text-red-400 border border-red-500/30 font-semibold">🔴 Pendiente</span>`,
                    en_proceso: `<span class="px-2.5 py-1 rounded-full bg-amber-500/15 text-amber-400 border border-amber-500/30 font-semibold">🟡 En Proceso</span>`,
                    resuelto:   `<span class="px-2.5 py-1 rounded-full bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 font-semibold">🟢 Resuelto</span>`,
                    rechazado:  `<span class="px-2.5 py-1 rounded-full bg-slate-700 text-slate-300 font-semibold">⚪ Rechazado</span>`
                }[report.status];

                const priorityBadge = {
                    critica: `<span class="text-rose-400 font-bold uppercase"><i class="fa-solid fa-fire mr-1"></i>Crítica</span>`,
                    alta:    `<span class="text-orange-400 font-semibold uppercase">Alta</span>`,
                    media:   `<span class="text-amber-300 uppercase">Media</span>`,
                    baja:    `<span class="text-slate-400 uppercase">Baja</span>`
                }[report.priority];

                return `
                    <tr class="hover:bg-slate-800/50 transition">
                        <td class="py-3.5 px-4 font-mono font-bold text-orange-400">
                            ${report.folio}
                            <span class="block text-[10px] font-sans font-normal text-slate-400 mt-0.5">${new Date(report.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                        </td>
                        <td class="py-3.5 px-4">
                            <div class="flex items-center gap-1.5">
                                <img src="${report.evidence_initial}" onclick="openReportModal(${report.id})" class="w-12 h-12 rounded-lg object-cover border border-slate-700 cursor-pointer hover:scale-105 transition" title="Foto Antes">
                                ${report.evidence_resolution ? `<img src="${report.evidence_resolution}" onclick="openReportModal(${report.id})" class="w-12 h-12 rounded-lg object-cover border-2 border-emerald-500 cursor-pointer" title="Foto Después">` : ''}
                            </div>
                        </td>
                        <td class="py-3.5 px-4">
                            <div class="font-bold text-white text-xs flex items-center gap-1.5">
                                <i class="fa-solid ${cat.icon} text-orange-400"></i> ${report.title}
                            </div>
                            <span class="text-[11px] text-slate-400 block mt-0.5">${report.department}</span>
                        </td>
                        <td class="py-3.5 px-4">
                            <strong class="text-slate-200 block">${report.colonia}</strong>
                            <span class="text-[11px] text-slate-400">${report.address_reference || 'Coordenadas GPS exactas'}</span>
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            <span class="px-2.5 py-1 rounded-lg bg-sky-500/15 text-sky-400 font-bold border border-sky-500/30">
                                <i class="fa-solid fa-thumbs-up mr-1"></i>+${report.upvotes}
                            </span>
                        </td>
                        <td class="py-3.5 px-4">${priorityBadge}</td>
                        <td class="py-3.5 px-4">${statusBadge}</td>
                        <td class="py-3.5 px-4 text-right space-x-1">
                            <button onclick="focusOnMap(${report.latitude}, ${report.longitude})" class="p-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300" title="Ubicar en el mapa">
                                <i class="fa-solid fa-location-crosshairs"></i>
                            </button>
                            <button onclick="openReportModal(${report.id})" class="px-3 py-1.5 rounded-lg bg-orange-600 hover:bg-orange-500 text-white font-semibold">
                                Atender
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function filterByCategory(cat) {
            currentCategoryFilter = cat;
            document.querySelectorAll('.cat-btn').forEach(btn => {
                btn.classList.remove('bg-orange-600', 'text-white');
                btn.classList.add('bg-slate-800/80', 'text-slate-300');
            });
            const activeBtn = document.getElementById('btn-cat-' + cat);
            if (activeBtn) {
                activeBtn.classList.remove('bg-slate-800/80', 'text-slate-300');
                activeBtn.classList.add('bg-orange-600', 'text-white');
            }
            renderDashboard();
        }

        function applyFilters() {
            renderDashboard();
        }

        function focusOnMap(lat, lng) {
            map.flyTo([lat, lng], 18, { duration: 1.2 });
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Abrir Modal de Inspección y Cierre ("Antes y Después")
        function openReportModal(id) {
            const report = allReports.find(r => r.id === id);
            if (!report) return;

            document.getElementById('modal-report-id').value = report.id;
            document.getElementById('modal-folio').textContent = report.folio;
            document.getElementById('modal-title').textContent = report.title;
            document.getElementById('modal-img-initial').src = report.evidence_initial;
            document.getElementById('modal-department').textContent = report.department;
            document.getElementById('modal-address').textContent = `${report.colonia} — ${report.address_reference || ''}`;
            document.getElementById('modal-citizen').textContent = `${report.citizen_name} (${report.citizen_phone || 'Sin teléfono'})`;
            document.getElementById('modal-description').textContent = report.description || 'Sin descripción adicional.';
            document.getElementById('modal-status-select').value = report.status;
            document.getElementById('modal-notes').value = report.resolution_notes || '';
            document.getElementById('modal-gmaps').href = `https://www.google.com/maps?q=${report.latitude},${report.longitude}`;

            const imgRes = document.getElementById('modal-img-resolution');
            const noRes = document.getElementById('modal-no-resolution');
            if (report.evidence_resolution) {
                imgRes.src = report.evidence_resolution;
                imgRes.classList.remove('hidden');
                noRes.classList.add('hidden');
            } else {
                imgRes.classList.add('hidden');
                noRes.classList.remove('hidden');
            }

            const modal = document.getElementById('report-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModal() {
            const modal = document.getElementById('report-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.getElementById('modal-file-input').value = '';
        }

        // Guardar cambios del Municipio vía AJAX
        async function submitStatusUpdate(e) {
            e.preventDefault();
            const id = document.getElementById('modal-report-id').value;
            const form = document.getElementById('update-report-form');
            const formData = new FormData(form);
            const btn = document.getElementById('btn-save-modal');

            btn.disabled = true;
            btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Guardando...`;

            try {
                const response = await fetch(`/panel-municipal/reportes/${id}/actualizar`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    closeModal();
                    Swal.fire({
                        icon: 'success',
                        title: '¡Reporte Actualizado!',
                        text: data.message,
                        background: '#0f172a',
                        color: '#fff',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    pollServer(); // Refrescar datos inmediatamente
                }
            } catch (err) {
                Swal.fire({ icon: 'error', title: 'Error al actualizar', text: 'Verifica la imagen seleccionada.' });
            } finally {
                btn.disabled = false;
                btn.innerHTML = `<i class="fa-solid fa-floppy-disk"></i> Guardar y Notificar al Ciudadano`;
            }
        }

        // MOTOR DE SINCRONIZACIÓN EN TIEMPO REAL (DETECTA NUEVOS REPORTES DE LA APP CIUDADANA)
        async function pollServer() {
            try {
                const res = await fetch('/panel-municipal/api/poll');
                const data = await res.json();

                // Actualizar reloj y KPIs
                document.getElementById('sync-clock').textContent = data.server_time;
                document.getElementById('kpi-total').textContent = data.stats.total;
                document.getElementById('kpi-pendientes').textContent = data.stats.pendientes;
                document.getElementById('kpi-proceso').textContent = data.stats.en_proceso;
                document.getElementById('kpi-resueltos').textContent = data.stats.resueltos;
                document.getElementById('kpi-criticos').textContent = data.stats.criticos;
                document.getElementById('kpi-votos').textContent = '+' + data.stats.votos_ciudadanos;

                // Detectar si entró un nuevo reporte desde el celular de un ciudadano
                data.reports.forEach(r => {
                    if (!knownIds.has(r.id)) {
                        knownIds.add(r.id);
                        
                        // Alerta visual y sonora en el Centro de Mando
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'warning',
                            title: `🚨 ¡Nuevo Reporte en ${r.colonia}!`,
                            text: `${r.folio}: ${r.title}`,
                            background: '#1e293b',
                            color: '#fff',
                            showConfirmButton: true,
                            confirmButtonText: 'Ver en Mapa',
                            confirmButtonColor: '#ea580c',
                            timer: 8000
                        }).then((result) => {
                            if (result.isConfirmed) {
                                focusOnMap(r.latitude, r.longitude);
                            }
                        });
                    }
                });

                allReports = data.reports;
                renderDashboard();
            } catch (error) {
                console.error('Error sincronizando con el servidor:', error);
            }
        }

        // Render inicial y activar Polling automático cada 4 segundos
        renderDashboard();
        setInterval(pollServer, 4000);
    </script>
</body>
</html>