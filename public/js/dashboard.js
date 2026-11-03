/**
 * ==========================================================================
 * CENTRO DE MANDO URBANO C2 - ÁLAMO TEMAPACHE (GOBIERNO MUNICIPAL 2026-2029)
 * LÓGICA MODULAR: LEAFLET GIS, TEMAS, DESPACHO Y SINCRONIZACIÓN EN TIEMPO REAL
 * ==========================================================================
 */

(function () {
    'use strict';

    // ----------------------------------------------------------------------
    // 1. CONSTANTES Y ESTADO GLOBAL DE LA APLICACIÓN
    // ----------------------------------------------------------------------
    // Coordenadas geográficas oficiales de Álamo Temapache, Veracruz
    const ALAMO_CENTER = [20.9114, -97.6744];
    const DEFAULT_ZOOM = 15;

    let map = null;
    let markersLayer = null;
    let allReports = [];
    let knownIds = new Set();
    let currentCategoryFilter = 'all';

    // Metadatos visuales e institucionales por Dirección Municipal
    const categoryMeta = {
        bache: {
            icon: 'fa-road',
            label: 'Obras Públicas (Bacheo y Pavimento)',
            shortLabel: 'Obras Públicas',
            color: '#f59e0b'
        },
        drenaje: {
            icon: 'fa-water',
            label: 'Drenaje y Alcantarillado (CAEV)',
            shortLabel: 'CAEV / Drenaje',
            color: '#38bdf8'
        },
        alumbrado: {
            icon: 'fa-lightbulb',
            label: 'Servicios Municipales (Alumbrado)',
            shortLabel: 'Alumbrado Público',
            color: '#facc15'
        },
        agua: {
            icon: 'fa-faucet-drip',
            label: 'CMAS / Fugas de Agua Potable',
            shortLabel: 'Agua Potable',
            color: '#06b6d4'
        },
        basura: {
            icon: 'fa-trash-can',
            label: 'Limpia Pública y Recolección',
            shortLabel: 'Limpia Pública',
            color: '#10b981'
        },
        accidente: {
            icon: 'fa-car-burst',
            label: 'Protección Civil y Tránsito Vial',
            shortLabel: 'Protección Civil',
            color: '#f43f5e'
        }
    };

    // ----------------------------------------------------------------------
    // 2. GESTIÓN DEL MODO NOCHE / MODO CLARO CON PERSISTENCIA
    // ----------------------------------------------------------------------
    function initTheme() {
        const savedTheme = localStorage.getItem('alamo_gov_theme') || 'dark';
        applyTheme(savedTheme);

        const toggleBtn = document.getElementById('theme-toggle-btn');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', toggleTheme);
        }
    }

    function applyTheme(theme) {
        const root = document.documentElement;
        const toggleBtn = document.getElementById('theme-toggle-btn');

        if (theme === 'light') {
            root.setAttribute('data-theme', 'light');
            if (toggleBtn) {
                toggleBtn.innerHTML = '<i class="fa-solid fa-moon"></i>';
                toggleBtn.setAttribute('title', 'Cambiar a Modo Noche');
                toggleBtn.setAttribute('aria-label', 'Cambiar a Modo Noche');
            }
        } else {
            root.removeAttribute('data-theme');
            if (toggleBtn) {
                toggleBtn.innerHTML = '<i class="fa-solid fa-sun"></i>';
                toggleBtn.setAttribute('title', 'Cambiar a Modo Claro');
                toggleBtn.setAttribute('aria-label', 'Cambiar a Modo Claro');
            }
        }

        localStorage.setItem('alamo_gov_theme', theme);
    }

    function toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        applyTheme(newTheme);
    }

    // ----------------------------------------------------------------------
    // 3. INICIALIZACIÓN DE LEAFLET GIS CON OPENSTREETMAP OFICIAL
    // ----------------------------------------------------------------------
    function initMap() {
        const mapContainer = document.getElementById('map');
        if (!mapContainer) return;

        // Crear mapa centrado en Álamo Temapache
        map = L.map('map', {
            zoomControl: true,
            scrollWheelZoom: true
        }).setView(ALAMO_CENTER, DEFAULT_ZOOM);

        // Tile layer oficial de OpenStreetMap (100% gratuito, sin API Key)
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> | H. Ayuntamiento de Álamo Temapache 2026-2029'
        }).addTo(map);

        // Capa contenedora de marcadores
        markersLayer = L.layerGroup().addTo(map);
    }

    function resetMapCenter() {
        if (!map) return;
        map.flyTo(ALAMO_CENTER, DEFAULT_ZOOM, { duration: 1.2 });
    }

    function focusOnMap(lat, lng) {
        if (!map) return;
        map.flyTo([lat, lng], 18, { duration: 1.2 });
        const mapEl = document.getElementById('map');
        if (mapEl) {
            mapEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    // ----------------------------------------------------------------------
    // 4. FILTRADO Y RENDERIZADO DEL DASHBOARD (PINES + TABLA)
    // ----------------------------------------------------------------------
    function getFilteredReports() {
        const statusEl = document.getElementById('status-filter');
        const searchEl = document.getElementById('search-input');

        const statusFilter = statusEl ? statusEl.value : 'all';
        const searchQuery = searchEl ? searchEl.value.trim().toLowerCase() : '';

        return allReports.filter(report => {
            const matchCategory = currentCategoryFilter === 'all' || report.category === currentCategoryFilter;
            const matchStatus = statusFilter === 'all' || report.status === statusFilter;
            
            const folio = (report.folio || '').toLowerCase();
            const title = (report.title || '').toLowerCase();
            const colonia = (report.colonia || '').toLowerCase();
            const ref = (report.address_reference || '').toLowerCase();
            const dept = (report.department || '').toLowerCase();

            const matchSearch = !searchQuery ||
                folio.includes(searchQuery) ||
                title.includes(searchQuery) ||
                colonia.includes(searchQuery) ||
                ref.includes(searchQuery) ||
                dept.includes(searchQuery);

            return matchCategory && matchStatus && matchSearch;
        });
    }

    function renderDashboard() {
        const filtered = getFilteredReports();
        renderMapMarkers(filtered);
        renderTable(filtered);
    }

    function renderMapMarkers(reports) {
        if (!markersLayer) return;
        markersLayer.clearLayers();

        reports.forEach(report => {
            if (!report.latitude || !report.longitude) return;

            const isPending = report.status === 'pendiente';
            const isResolved = report.status === 'resuelto';
            const isInProcess = report.status === 'en_proceso';

            const statusColor = isResolved ? '#10B981' : (isInProcess ? '#F59E0B' : '#EF4444');
            const cat = categoryMeta[report.category] || { icon: 'fa-circle-exclamation', shortLabel: report.category, color: '#F97316' };
            const isCriticalOrHigh = report.priority === 'critica' || report.priority === 'alta';
            const pulseClass = (isPending && isCriticalOrHigh) ? 'marker-pulse' : '';

            const customIcon = L.divIcon({
                className: 'custom-div-icon',
                html: `
                    <div class="${pulseClass}" style="color: ${statusColor}; width: 34px; height: 34px; border-radius: 50%; background: ${statusColor}; border: 2.5px solid #FFFFFF; box-shadow: 0 4px 14px rgba(0,0,0,0.4); display:flex; align-items:center; justify-content:center;">
                        <i class="fa-solid ${cat.icon}" style="color: #FFFFFF; font-size: 13px; position:relative; z-index:2;"></i>
                    </div>
                `,
                iconSize: [34, 34],
                iconAnchor: [17, 17],
                popupAnchor: [0, -18]
            });

            const marker = L.marker([report.latitude, report.longitude], { icon: customIcon });

            const evidenceImg = report.evidence_initial
                ? `<img src="${report.evidence_initial}" alt="Evidencia" style="width:100%; height:105px; object-fit:cover; border-radius:8px; margin: 8px 0 10px 0; border: 1px solid rgba(123, 17, 35, 0.2);">`
                : '';

            const popupHtml = `
                <div style="min-width: 220px; font-family: inherit;">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:4px;">
                        <span style="font-size:11px; font-weight:800; color:#EBC886; font-family:monospace; background:#7B1123; padding:2px 6px; border-radius:4px;">${report.folio}</span>
                        <span style="font-size:10px; font-weight:700; text-transform:uppercase; color:#8B1528;">${cat.shortLabel}</span>
                    </div>
                    <div style="font-weight:700; font-size:13px; line-height:1.3; margin: 4px 0;">${escapeHtml(report.title)}</div>
                    <div style="font-size:11px; opacity:0.85; margin-bottom:4px;">📍 ${escapeHtml(report.colonia)}${report.address_reference ? ' (' + escapeHtml(report.address_reference) + ')' : ''}</div>
                    ${evidenceImg}
                    <button onclick="window.openReportModal(${report.id})" style="width:100%; background: #7B1123; color:#FFFFFF; border: 1px solid #EBC886; padding: 7px 10px; border-radius: 8px; font-size: 11px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 5px; box-shadow: 0 2px 8px rgba(123, 17, 35, 0.3);">
                        <i class="fa-solid fa-clipboard-check"></i> Inspeccionar y Atender
                    </button>
                </div>
            `;

            marker.bindPopup(popupHtml);
            markersLayer.addLayer(marker);
        });
    }

    function renderTable(reports) {
        const tbody = document.getElementById('reports-tbody');
        if (!tbody) return;

        if (reports.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-10" style="color: var(--text-muted);">
                        <i class="fa-solid fa-folder-open text-3xl mb-2 block" style="opacity: 0.5;"></i>
                        No se encontraron reportes con los filtros seleccionados en Álamo Temapache.
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = reports.map(report => {
            const cat = categoryMeta[report.category] || { icon: 'fa-circle-info', shortLabel: report.category };

            const statusBadgeMap = {
                pendiente:  `<span class="badge-status pendiente"><i class="fa-solid fa-circle-dot"></i> Pendiente</span>`,
                en_proceso: `<span class="badge-status en_proceso"><i class="fa-solid fa-person-digging"></i> En Proceso</span>`,
                resuelto:   `<span class="badge-status resuelto"><i class="fa-solid fa-circle-check"></i> Resuelto</span>`,
                rechazado:  `<span class="badge-status rechazado"><i class="fa-solid fa-ban"></i> Rechazado</span>`
            };
            const statusBadge = statusBadgeMap[report.status] || `<span class="badge-status">${escapeHtml(report.status)}</span>`;

            const priorityBadgeMap = {
                critica: `<span class="badge-priority critica"><i class="fa-solid fa-fire"></i> Crítica</span>`,
                alta:    `<span class="badge-priority alta"><i class="fa-solid fa-angles-up"></i> Alta</span>`,
                media:   `<span class="badge-priority media"><i class="fa-solid fa-angle-up"></i> Media</span>`,
                baja:    `<span class="badge-priority baja"><i class="fa-solid fa-angle-down"></i> Baja</span>`
            };
            const priorityBadge = priorityBadgeMap[report.priority] || `<span class="badge-priority">${escapeHtml(report.priority)}</span>`;

            const createdTime = report.created_at
                ? new Date(report.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                : '--:--';

            const photoInitial = report.evidence_initial
                ? `<img src="${report.evidence_initial}" onclick="window.openReportModal(${report.id})" class="w-12 h-12 rounded-lg object-cover cursor-pointer hover:scale-105 transition" style="border: 1px solid var(--border-color);" title="Evidencia Inicial (Ciudadano)">`
                : `<div class="w-12 h-12 rounded-lg flex items-center justify-center text-xs" style="background: var(--bg-surface); border: 1px solid var(--border-color); color: var(--text-muted);"><i class="fa-solid fa-camera-slash"></i></div>`;

            const photoResolution = report.evidence_resolution
                ? `<img src="${report.evidence_resolution}" onclick="window.openReportModal(${report.id})" class="w-12 h-12 rounded-lg object-cover cursor-pointer hover:scale-105 transition" style="border: 2px solid var(--status-resolved);" title="Evidencia de Cierre (Municipio)">`
                : '';

            return `
                <tr>
                    <td>
                        <span class="folio-tag">${escapeHtml(report.folio)}</span>
                        <span style="font-size: 0.68rem; color: var(--text-muted); display: block; margin-top: 2px;">
                            <i class="fa-regular fa-clock"></i> ${createdTime}
                        </span>
                    </td>
                    <td>
                        <div class="flex items-center gap-1.5">
                            ${photoInitial}
                            ${photoResolution}
                        </div>
                    </td>
                    <td>
                        <div style="font-weight: 700; color: var(--text-primary); font-size: 0.82rem; display: flex; align-items: center; gap: 0.4rem;">
                            <i class="fa-solid ${cat.icon}" style="color: var(--accent-gold);"></i>
                            <span>${escapeHtml(report.title)}</span>
                        </div>
                        <span style="font-size: 0.7rem; color: var(--text-muted); display: block; margin-top: 2px;">
                            ${escapeHtml(report.department || cat.shortLabel)}
                        </span>
                    </td>
                    <td>
                        <strong style="color: var(--text-primary); display: block;">${escapeHtml(report.colonia)}</strong>
                        <span style="font-size: 0.7rem; color: var(--text-muted);">
                            ${escapeHtml(report.address_reference || 'Ubicación GPS')}
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="upvotes-badge">
                            <i class="fa-solid fa-thumbs-up"></i> +${report.upvotes || 0}
                        </span>
                    </td>
                    <td>${priorityBadge}</td>
                    <td>${statusBadge}</td>
                    <td class="text-right" style="white-space: nowrap;">
                        <button onclick="window.focusOnMap(${report.latitude}, ${report.longitude})" class="btn-table-action btn-table-locate mr-1" title="Ubicar en el mapa">
                            <i class="fa-solid fa-location-crosshairs"></i>
                        </button>
                        <button onclick="window.openReportModal(${report.id})" class="btn-table-action btn-table-attend">
                            <i class="fa-solid fa-clipboard-check mr-1"></i> Atender
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function filterByCategory(cat) {
        currentCategoryFilter = cat;

        document.querySelectorAll('.cat-btn').forEach(btn => {
            btn.classList.remove('active');
            const badge = btn.querySelector('.cat-badge-active');
            if (badge) badge.remove();
        });

        const activeBtn = document.getElementById('btn-cat-' + cat);
        if (activeBtn) {
            activeBtn.classList.add('active');
            const badge = document.createElement('span');
            badge.className = 'cat-badge-active';
            badge.textContent = 'Activo';
            activeBtn.appendChild(badge);
        }

        renderDashboard();
    }

    function applyFilters() {
        renderDashboard();
    }

    // ----------------------------------------------------------------------
    // 5. MODAL DE AUDITORÍA Y RESOLUCIÓN ("ANTES Y DESPUÉS")
    // ----------------------------------------------------------------------
    function openReportModal(id) {
        const report = allReports.find(r => r.id === id);
        if (!report) return;

        const modal = document.getElementById('report-modal');
        if (!modal) return;

        // Poblar campos del modal
        document.getElementById('modal-report-id').value = report.id;
        document.getElementById('modal-folio').textContent = report.folio;
        document.getElementById('modal-title').textContent = report.title;
        document.getElementById('modal-department').textContent = report.department || 'Dirección Municipal General';
        document.getElementById('modal-address').textContent = `${report.colonia} — ${report.address_reference || 'Sin referencias extras'}`;
        document.getElementById('modal-citizen').textContent = `${report.citizen_name || 'Ciudadano Álamo'} (${report.citizen_phone || 'Sin teléfono'})`;
        document.getElementById('modal-description').textContent = report.description || 'Sin descripción adicional reportada.';
        document.getElementById('modal-status-select').value = report.status || 'pendiente';
        document.getElementById('modal-notes').value = report.resolution_notes || '';

        // Enlace a Google Maps
        const gmapsLink = document.getElementById('modal-gmaps');
        if (gmapsLink) {
            gmapsLink.href = `https://www.google.com/maps?q=${report.latitude},${report.longitude}`;
        }

        // Evidencia Inicial ("Antes")
        const imgInit = document.getElementById('modal-img-initial');
        if (imgInit) {
            imgInit.src = report.evidence_initial || 'https://via.placeholder.com/600x400?text=Sin+Evidencia+Inicial';
        }

        // Evidencia de Resolución ("Después")
        const imgRes = document.getElementById('modal-img-resolution');
        const noRes = document.getElementById('modal-no-resolution');
        if (report.evidence_resolution) {
            if (imgRes) {
                imgRes.src = report.evidence_resolution;
                imgRes.classList.remove('hidden');
                imgRes.style.display = 'block';
            }
            if (noRes) {
                noRes.classList.add('hidden');
                noRes.style.display = 'none';
            }
        } else {
            if (imgRes) {
                imgRes.classList.add('hidden');
                imgRes.style.display = 'none';
            }
            if (noRes) {
                noRes.classList.remove('hidden');
                noRes.style.display = 'flex';
            }
        }

        // Mostrar modal
        modal.classList.add('active');
    }

    function closeModal() {
        const modal = document.getElementById('report-modal');
        if (modal) {
            modal.classList.remove('active');
        }
        const fileInput = document.getElementById('modal-file-input');
        if (fileInput) {
            fileInput.value = '';
        }
    }

    async function submitStatusUpdate(e) {
        e.preventDefault();

        const id = document.getElementById('modal-report-id').value;
        const form = document.getElementById('update-report-form');
        const formData = new FormData(form);
        const btn = document.getElementById('btn-save-modal');
        const originalBtnHtml = btn ? btn.innerHTML : '';

        if (btn) {
            btn.disabled = true;
            btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin mr-1"></i> Guardando...`;
        }

        const csrfTokenEl = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfTokenEl ? csrfTokenEl.getAttribute('content') : '';

        const isLight = document.documentElement.getAttribute('data-theme') === 'light';
        const swalBg = isLight ? '#FFFFFF' : '#1F0B10';
        const swalText = isLight ? '#26070C' : '#FDFBF7';

        try {
            const response = await fetch(`/panel-municipal/reportes/${id}/actualizar`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                closeModal();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Reporte Actualizado!',
                        text: data.message || 'Se actualizó el reporte municipal satisfactoriamente.',
                        background: swalBg,
                        color: swalText,
                        confirmButtonColor: '#7B1123',
                        timer: 2500,
                        showConfirmButton: false
                    });
                }
                pollServer(); // Refrescar datos inmediatamente
            } else {
                throw new Error(data.message || 'Error en el servidor');
            }
        } catch (err) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al actualizar',
                    text: 'Verifica los datos y que la evidencia sea una imagen válida.',
                    background: swalBg,
                    color: swalText,
                    confirmButtonColor: '#7B1123'
                });
            } else {
                alert('Error al actualizar el reporte');
            }
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalBtnHtml;
            }
        }
    }

    // ----------------------------------------------------------------------
    // 6. MOTOR DE SINCRONIZACIÓN EN TIEMPO REAL (POLLING CADA 4 SEGUNDOS)
    // ----------------------------------------------------------------------
    async function pollServer() {
        try {
            const res = await fetch('/panel-municipal/api/poll');
            if (!res.ok) return;

            const data = await res.json();

            // 1. Actualizar Reloj y KPIs
            const clockEl = document.getElementById('sync-clock');
            if (clockEl && data.server_time) clockEl.textContent = data.server_time;

            if (data.stats) {
                updateKpi('kpi-total', data.stats.total);
                updateKpi('kpi-pendientes', data.stats.pendientes);
                updateKpi('kpi-proceso', data.stats.en_proceso);
                updateKpi('kpi-resueltos', data.stats.resueltos);
                updateKpi('kpi-criticos', data.stats.criticos);
                updateKpi('kpi-votos', '+' + (data.stats.votos_ciudadanos || 0));
            }

            // 2. Detectar nuevos reportes ingresados por ciudadanos
            if (Array.isArray(data.reports)) {
                data.reports.forEach(report => {
                    if (!knownIds.has(report.id)) {
                        knownIds.add(report.id);

                        const isLight = document.documentElement.getAttribute('data-theme') === 'light';
                        const swalBg = isLight ? '#FFFFFF' : '#1F0B10';
                        const swalText = isLight ? '#26070C' : '#FDFBF7';

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'warning',
                                title: `🚨 ¡Nuevo Reporte en ${escapeHtml(report.colonia)}!`,
                                text: `${report.folio}: ${escapeHtml(report.title)}`,
                                background: swalBg,
                                color: swalText,
                                showConfirmButton: true,
                                confirmButtonText: 'Ver en Mapa',
                                confirmButtonColor: '#7B1123',
                                timer: 8000
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    focusOnMap(report.latitude, report.longitude);
                                }
                            });
                        }
                    }
                });

                allReports = data.reports;
                renderDashboard();
            }
        } catch (error) {
            console.warn('Error en la sincronización en vivo del Centro de Mando:', error);
        }
    }

    function updateKpi(id, value) {
        const el = document.getElementById(id);
        if (el && value !== undefined) {
            el.textContent = value;
        }
    }

    // Utilidad de escape seguro para evitar XSS en inyecciones HTML dinámicas
    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    // ----------------------------------------------------------------------
    // 7. ARRANQUE E INTEGRACIÓN DEL DOM
    // ----------------------------------------------------------------------
    document.addEventListener('DOMContentLoaded', () => {
        // Inicializar tema guardado
        initTheme();

        // Leer datos iniciales inyectados en data-reports del contenedor principal
        const appContainer = document.getElementById('dashboard-app');
        if (appContainer && appContainer.dataset.reports) {
            try {
                allReports = JSON.parse(appContainer.dataset.reports);
                knownIds = new Set(allReports.map(r => r.id));
            } catch (e) {
                console.error('Error parseando data-reports inicial:', e);
                allReports = [];
            }
        }

        // Inicializar Mapa Cartográfico Leaflet
        initMap();

        // Renderizado inicial
        renderDashboard();

        // Configurar listeners de modal (cerrar con backdrop o Escape)
        const modal = document.getElementById('report-modal');
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeModal();
                }
            });
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeModal();
            }
        });

        // Configurar envío del formulario de resolución
        const form = document.getElementById('update-report-form');
        if (form) {
            form.addEventListener('submit', submitStatusUpdate);
        }

        // Iniciar Polling automático cada 4 segundos (4000ms)
        setInterval(pollServer, 4000);
    });

    // Exponer funciones necesarias al scope global para handlers en línea si son requeridos
    window.toggleTheme = toggleTheme;
    window.resetMapCenter = resetMapCenter;
    window.focusOnMap = focusOnMap;
    window.filterByCategory = filterByCategory;
    window.applyFilters = applyFilters;
    window.openReportModal = openReportModal;
    window.closeModal = closeModal;
    window.submitStatusUpdate = submitStatusUpdate;

})();

