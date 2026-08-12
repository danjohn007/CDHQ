<?php
/**
 * Avisos dirigidos a las personas que usan la aplicación móvil.
 *
 * Escribe en `notifications`, que guarda UNA FILA POR DESTINATARIO. No existe
 * una tabla de «aviso general»: un aviso a un segmento se convierte aquí en
 * tantas filas como personas tenga ese segmento. Eso es lo que permite que
 * cada quien marque el suyo como leído sin afectar a los demás.
 *
 * Como el envío es un solo INSERT de varias filas, todas comparten el mismo
 * `created_at`. Ese instante, junto con el título, es lo que usamos para
 * reagrupar las filas y mostrar el historial como envíos y no como miles de
 * registros sueltos.
 */
class Notification extends Model {

    protected string $table = 'notifications';

    /** Marca los avisos escritos desde el portal, para distinguirlos de los automáticos */
    public const TIPO_AVISO = 'AVISO_GENERAL';

    /** Días de anticipación con los que se considera que una credencial está por vencer */
    private const DIAS_POR_VENCER = 30;

    /**
     * Segmentos disponibles: etiqueta y condición SQL sobre `users u`.
     *
     * Todos exigen `u.active = 1`: a una cuenta desactivada no se le manda nada.
     */
    public const SEGMENTOS = [
        'TODOS'                   => 'Todas las personas usuarias de la app',
        'USUARIOS'                => 'Sólo personas usuarias',
        'ASESORES'                => 'Sólo asesoras y asesores',
        'ADMINISTRADORES'         => 'Sólo personal administrador',
        'AFILIADOS_ACTIVOS'       => 'Personas afiliadas activas',
        'CREDENCIALES_POR_VENCER' => 'Credenciales que vencen en los próximos ' . self::DIAS_POR_VENCER . ' días',
    ];

    /**
     * FROM + WHERE de cada segmento.
     *
     * Se devuelve el fragmento y no una lista de ids para que contar y enviar
     * usen exactamente la misma definición: si divergieran, el portal diría
     * «se envió a 120» cuando en realidad llegó a otras personas.
     */
    private function origenSegmento(string $segmento): string {
        return match ($segmento) {
            'USUARIOS' =>
                'FROM users u WHERE u.active = 1 AND u.app_role = "USUARIO"',
            'ASESORES' =>
                'FROM users u WHERE u.active = 1 AND u.app_role = "ASESOR"',
            'ADMINISTRADORES' =>
                'FROM users u WHERE u.active = 1 AND u.app_role = "ADMINISTRADOR"',
            'AFILIADOS_ACTIVOS' =>
                'FROM users u
                 JOIN affiliates a ON a.user_id = u.id
                 WHERE u.active = 1 AND a.state = "ACTIVO"',
            'CREDENCIALES_POR_VENCER' =>
                'FROM users u
                 JOIN affiliates a ON a.user_id = u.id
                 JOIN credentials c ON c.affiliate_id = a.id
                 WHERE u.active = 1 AND c.status = "VIGENTE"
                   AND c.expires_at >= NOW()
                   AND c.expires_at <= DATE_ADD(NOW(), INTERVAL ' . self::DIAS_POR_VENCER . ' DAY)',
            default =>
                'FROM users u WHERE u.active = 1',
        };
    }

    /**
     * Estático a propósito: comprobar un segmento es mirar una lista fija, no
     * consultar la base. Como método de instancia obligaba a construir el
     * modelo —y con él a abrir la conexión— sólo para validar un formulario.
     */
    public static function esSegmentoValido(string $segmento): bool {
        return array_key_exists($segmento, self::SEGMENTOS);
    }

    /**
     * Cuántas personas recibirían el aviso.
     *
     * DISTINCT porque alguien con dos credenciales por vencer aparecería dos
     * veces en el JOIN y recibiría el aviso duplicado.
     */
    public function contarDestinatarios(string $segmento): int {
        return (int) $this->queryColumn(
            'SELECT COUNT(DISTINCT u.id) ' . $this->origenSegmento($segmento)
        );
    }

    /** Cuenta de cada segmento, para mostrarla junto a la opción */
    public function conteoPorSegmento(): array {
        $conteos = [];
        foreach (array_keys(self::SEGMENTOS) as $segmento) {
            $conteos[$segmento] = $this->contarDestinatarios($segmento);
        }
        return $conteos;
    }

    /** Primeras personas del segmento, para la vista previa antes de enviar */
    public function muestraDestinatarios(string $segmento, int $limite = 8): array {
        return $this->query(
            'SELECT DISTINCT u.id, u.name, u.email ' . $this->origenSegmento($segmento)
            . " ORDER BY u.name LIMIT {$limite}"
        );
    }

    /**
     * Crea el aviso para cada integrante del segmento.
     *
     * Se resuelve dentro de MySQL con `INSERT … SELECT`: así el conteo previo y
     * lo que realmente se inserta salen de la misma consulta, y no hay que
     * traer miles de identificadores a PHP para volverlos a mandar.
     *
     * @return int Personas a las que se creó el aviso.
     */
    public function enviarASegmento(string $segmento, string $titulo, string $mensaje, ?string $enlace): int {
        return $this->exec(
            'INSERT INTO notifications (user_id, type, title, message, action_url, created_at)
             SELECT DISTINCT u.id, :tipo, :titulo, :mensaje, :enlace, NOW() '
            . $this->origenSegmento($segmento),
            [
                'tipo'    => self::TIPO_AVISO,
                'titulo'  => $titulo,
                'mensaje' => $mensaje,
                'enlace'  => $enlace,
            ]
        );
    }

    /** Aviso a una sola persona */
    public function enviarAUsuario(int $userId, string $titulo, string $mensaje, ?string $enlace): int {
        return $this->exec(
            'INSERT INTO notifications (user_id, type, title, message, action_url, created_at)
             SELECT u.id, :tipo, :titulo, :mensaje, :enlace, NOW()
             FROM users u WHERE u.id = :uid AND u.active = 1',
            [
                'tipo'    => self::TIPO_AVISO,
                'titulo'  => $titulo,
                'mensaje' => $mensaje,
                'enlace'  => $enlace,
                'uid'     => $userId,
            ]
        );
    }

    /** ¿La cuenta existe y está activa? */
    public function usuarioActivo(int $userId): ?array {
        return $this->queryOne(
            'SELECT id, name, email FROM users WHERE id = :id AND active = 1',
            ['id' => $userId]
        );
    }

    /** Busca destinatarios por nombre o correo, para el envío individual */
    public function buscarUsuarios(string $texto, int $limite = 10): array {
        return $this->query(
            'SELECT id, name, email, app_role FROM users
             WHERE active = 1 AND (name LIKE :q OR email LIKE :q)
             ORDER BY name LIMIT ' . $limite,
            ['q' => '%' . $texto . '%']
        );
    }

    /**
     * Historial de envíos.
     *
     * Se agrupa por título e instante porque las filas de un mismo envío
     * comparten `created_at` al haberse insertado en una sola sentencia.
     */
    public function historial(int $limite = 40): array {
        return $this->query(
            'SELECT title, message, action_url, created_at,
                    COUNT(*)             AS destinatarios,
                    SUM(read_at IS NOT NULL) AS leidos
             FROM notifications
             WHERE type = :tipo
             GROUP BY title, message, action_url, created_at
             ORDER BY created_at DESC
             LIMIT ' . $limite,
            ['tipo' => self::TIPO_AVISO]
        );
    }
}
