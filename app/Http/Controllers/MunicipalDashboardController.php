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
        $stats = $this->calculateStats();

        return view('municipal.dashboard', compact('reports', 'stats'));
    }

    /**
     * Endpoint JSON para actualización automática en tiempo real (Polling cada 4 seg)
     */
    public function poll()
    {
        $reports = IncidentReport::orderBy('created_at', 'desc')->get();
        $stats = $this->calculateStats();

        return response()->json([
            'reports' => $reports,
            'stats' => $stats,
            'server_time' => now()->format('H:i:s'),
        ]);
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

        // Guardado directo en public/uploads/evidences (100% compatible con Hostinger sin problemas de symlink)
        if ($request->hasFile('evidence_resolution')) {
            $file = $request->file('evidence_resolution');
            $filename = 'res_' . $report->folio . '_' . time() . '.' . $file->getClientOriginalExtension();
            $destinationPath = public_path('uploads/evidences');

            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $file->move($destinationPath, $filename);
            $report->evidence_resolution = asset('uploads/evidences/' . $filename);
        }

        $report->save();

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