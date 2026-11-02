<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncidentReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'folio',
        'category',
        'department',
        'priority',
        'title',
        'description',
        'colonia',
        'address_reference',
        'latitude',
        'longitude',
        'citizen_name',
        'citizen_phone',
        'evidence_initial',
        'evidence_resolution',
        'resolution_notes',
        'status',
        'upvotes',
        'resolved_at',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'upvotes' => 'integer',
        'resolved_at' => 'datetime',
    ];

    /**
     * Asigna automáticamente la Dirección Municipal y Prioridad según la categoría reportada
     */
    public static function resolveDepartmentAndPriority(string $category): array
    {
        return match ($category) {
            'drenaje'   => ['department' => 'Agua Potable y Drenaje (CAEV/Municipal)', 'priority' => 'alta'],
            'bache'     => ['department' => 'Dirección de Obras Públicas', 'priority' => 'media'],
            'alumbrado' => ['department' => 'Servicios Públicos / Alumbrado', 'priority' => 'media'],
            'agua'      => ['department' => 'Agua Potable y Drenaje (CAEV/Municipal)', 'priority' => 'alta'],
            'basura'    => ['department' => 'Limpia Pública y Ecología', 'priority' => 'baja'],
            'accidente' => ['department' => 'Protección Civil y Tránsito Municipal', 'priority' => 'critica'],
            default     => ['department' => 'Atención Ciudadana General', 'priority' => 'media'],
        };
    }

    /**
     * Genera el siguiente folio consecutivo oficial para Álamo Temapache
     */
    public static function generateNextFolio(): string
    {
        $lastId = self::max('id') ?? 0;
        return 'ALM-' . date('Y') . '-' . str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
    }
}