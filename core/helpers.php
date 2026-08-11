<?php
/**
 * Helpers de presentación compartidos por las vistas del portal.
 *
 * Los estados se representan SIEMPRE con texto además de color: nunca sólo
 * con color, para que sigan siendo legibles sin distinguir tonos.
 */

/** Escapa texto para HTML. Atajo de uso constante en las vistas. */
function e(?string $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Clases Tailwind del distintivo de un estado */
function estadoClase(string $estado): string {
    return match ($estado) {
        // Positivos / finales correctos
        'ACTIVO', 'ACTIVA', 'VIGENTE', 'PAGADA', 'ACREDITADA', 'APROBADA', 'CERRADA'
            => 'bg-leaf-50 text-leaf-700 border border-leaf-200',
        // En curso
        'EN_REVISION', 'EN_ATENCION', 'EN_VALIDACION', 'ENVIADA', 'RECIBIDA', 'CANALIZADA'
            => 'bg-brand-50 text-brand-700 border border-brand-200',
        // Requieren atención
        'PENDIENTE', 'PENDIENTE_PAGO', 'INFORMACION_REQUERIDA', 'CON_OBSERVACIONES',
        'COMPROBANTE_ENVIADO', 'PROSPECTO', 'BORRADOR'
            => 'bg-amber-50 text-amber-700 border border-amber-200',
        // Negativos
        'RECHAZADA', 'CANCELADA', 'REVOCADA', 'SUSPENDIDA', 'SUSPENDIDO'
            => 'bg-red-50 text-red-700 border border-red-200',
        // Caducados
        'VENCIDA', 'VENCIDO', 'REEMBOLSADA'
            => 'bg-slate-100 text-slate-600 border border-slate-200',
        default
            => 'bg-slate-100 text-slate-600 border border-slate-200',
    };
}

/** Distintivo de estado listo para imprimir */
function estadoBadge(?string $estado): string {
    $estado = (string) $estado;
    if ($estado === '') {
        return '<span class="badge bg-slate-100 text-slate-500 border border-slate-200">—</span>';
    }
    return '<span class="badge ' . estadoClase($estado) . '">'
         . e(str_replace('_', ' ', $estado)) . '</span>';
}

/** Distintivo de urgencia de una solicitud */
function urgenciaBadge(?string $urgencia): string {
    $clases = match ((string) $urgencia) {
        'EMERGENCIA' => 'bg-red-600 text-white',
        'ALTA'       => 'bg-amber-500 text-white',
        'MEDIA'      => 'bg-brand-100 text-brand-800',
        'BAJA'       => 'bg-slate-100 text-slate-600',
        default      => 'bg-slate-100 text-slate-600',
    };
    return '<span class="badge ' . $clases . '">' . e((string) $urgencia) . '</span>';
}

/** Fecha corta legible */
function fechaCorta(?string $fecha): string {
    if (empty($fecha) || $fecha === '0000-00-00' || $fecha === '0000-00-00 00:00:00') {
        return '—';
    }
    $ts = strtotime($fecha);
    return $ts ? date('d/m/Y', $ts) : '—';
}

/** Fecha con hora */
function fechaHora(?string $fecha): string {
    if (empty($fecha)) return '—';
    $ts = strtotime($fecha);
    return $ts ? date('d/m/Y H:i', $ts) : '—';
}

/** Importe en pesos */
function dinero($monto): string {
    return '$' . number_format((float) $monto, 2) . ' MXN';
}

/** Enlace de paginación, conservando los filtros actuales */
function paginacion(int $page, int $pages, array $params = []): string {
    if ($pages <= 1) return '';
    unset($params['page']);
    // La ruta actual ya trae el prefijo de instalación, así que se reutiliza tal cual
    $base = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '', '/');

    $link = function (int $p, string $label, bool $disabled = false) use ($params, $base): string {
        if ($disabled) {
            return '<span class="px-3 py-1.5 rounded-lg text-sm text-slate-300 border border-slate-200">' . $label . '</span>';
        }
        $qs = http_build_query($params + ['page' => $p]);
        return '<a href="' . e($base) . '?' . e($qs) . '" class="px-3 py-1.5 rounded-lg text-sm text-brand-700 border border-brand-200 hover:bg-brand-50">' . $label . '</a>';
    };

    return '<div class="flex items-center justify-center gap-2 mt-6">'
         . $link(max(1, $page - 1), '&larr; Anterior', $page <= 1)
         . '<span class="px-3 py-1.5 text-sm text-slate-500">Página ' . $page . ' de ' . $pages . '</span>'
         . $link(min($pages, $page + 1), 'Siguiente &rarr;', $page >= $pages)
         . '</div>';
}
