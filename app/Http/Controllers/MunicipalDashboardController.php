<?php

namespace App\Http\Controllers;

use App\Models\IncidentReport;
use Illuminate\Http\Request;
use Carbon\Carbon;

class MunicipalDashboardController extends Controller
{
    /**
     * Renderiza el Centro de Mando Municipal (Gobierno de Álamo Temapache)
     */
    public function index()
    {
        $reports = IncidentReport::orderBy('created_at', 'desc')->get();
        $reports->transform(function ($report) {
            $report->evidence_initial = $this->formatImageUrl($report->evidence_initial);
            $report->evidence_resolution = $this->formatImageUrl($report->evidence_resolution);
            return $report;
        });
        $stats = $this->calculateStats();

        return view('municipal.dashboard', compact('reports', 'stats'));
    }

    /**
     * Endpoint JSON para actualización automática en tiempo real (Polling cada 4 seg)
     */
    public function poll()
    {
        $reports = IncidentReport::orderBy('created_at', 'desc')->get();
        $reports->transform(function ($report) {
            $report->evidence_initial = $this->formatImageUrl($report->evidence_initial);
            $report->evidence_resolution = $this->formatImageUrl($report->evidence_resolution);
            return $report;
        });
        $stats = $this->calculateStats();

        return response()->json([
            'reports' => $reports,
            'stats' => $stats,
            'server_time' => now()->format('H:i:s'),
        ]);
    }

    /**
     * Sirve imágenes desde la carpeta compartida externa o localmente como fallback
     */
    public function serveSharedImage($filename)
    {
        // Limpiar el nombre del archivo para evitar Directory Traversal
        $cleanFilename = basename($filename);

        // Rutas posibles de búsqueda (priorizando la carpeta compartida en Hostinger)
        $paths = [
            base_path('../images/' . $cleanFilename),              // Producción Hostinger (public_html/images)
            base_path('../images/' . $filename),
            public_path('images/' . $cleanFilename),               // Entorno local
            public_path('uploads/evidences/' . $cleanFilename),
        ];

        foreach ($paths as $path) {
            if (file_exists($path) && is_file($path)) {
                return response()->file($path, [
                    'Cache-Control' => 'public, max-age=86400',
                    'Access-Control-Allow-Origin' => '*'
                ]);
            }
        }

        abort(404, 'Imagen no encontrada.');
    }

    /**
     * Normaliza las URLs de imágenes antes de enviarlas al frontend
     */
    private function formatImageUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        // Si ya es una URL externa completa (Unsplash, etc.)
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        // Extraer solo el nombre de archivo y servirlo por la ruta puente
        $filename = basename($path);
        return url('/shared-images/' . $filename);
    }

    /**
     * Actualiza el estatus del reporte y permite subir la Foto de Evidencia ("Después")
     */
    public function updateStatus(Request $request, $id)
    {
        $report = IncidentReport::findOrFail($id);

        $request->validate([
            'status' => 'required|in:pendiente,en_proceso,resuelto,rechazado',
            'resolution_notes' => 'nullable|string|max:1000',
            'evidence_resolution' => 'nullable|image|max:8192', // Hasta 8MB
        ]);

        $report->status = $request->status;
        $report->resolution_notes = $request->resolution_notes;

        if ($request->status === 'resuelto' && !$report->resolved_at) {
            $report->resolved_at = Carbon::now();
        }

        // Guardado de evidencia municipal en carpeta compartida o fallback local
        if ($request->hasFile('evidence_resolution')) {
            $file = $request->file('evidence_resolution');
            $filename = 'res_' . $report->folio . '_' . time() . '.' . $file->getClientOriginalExtension();

            $sharedDir = base_path('../images');
            $destination = (is_dir($sharedDir) && is_writable($sharedDir)) ? $sharedDir : public_path('images');

            if (!file_exists($destination)) {
                @mkdir($destination, 0755, true);
            }

            $file->move($destination, $filename);
            $report->evidence_resolution = $filename;
        }

        $report->save();

        // Normalizar URLs antes de enviar en la respuesta JSON
        $report->evidence_initial = $this->formatImageUrl($report->evidence_initial);
        $report->evidence_resolution = $this->formatImageUrl($report->evidence_resolution);

        return response()->json([
            'success' => true,
            'message' => "Folio {$report->folio} actualizado correctamente.",
            'report' => $report,
        ]);
    }

    /**
     * Calcula los indicadores ejecutivos (KPIs)
     */
    private function calculateStats(): array
    {
        $total = IncidentReport::count();
        $pendientes = IncidentReport::where('status', 'pendiente')->count();
        $enProceso = IncidentReport::where('status', 'en_proceso')->count();
        $resueltos = IncidentReport::where('status', 'resuelto')->count();
        $criticos = IncidentReport::where('priority', 'critica')->where('status', '!=', 'resuelto')->count();
        $votosCiudadanos = IncidentReport::sum('upvotes');

        return [
            'total' => $total,
            'pendientes' => $pendientes,
            'en_proceso' => $enProceso,
            'resueltos' => $resueltos,
            'criticos' => $criticos,
            'votos_ciudadanos' => $votosCiudadanos,
            'efectividad' => $total > 0 ? round(($resueltos / $total) * 100) : 0,
        ];
    }
}