<?php
/**
 * Noticias y eventos publicados en la aplicación móvil.
 *
 * Escribe sobre `content_posts`, la MISMA tabla que lee la app. No hay
 * sincronización ni copia: al publicar aquí, la entrada aparece en la app.
 *
 * Dónde sale cada cosa en la app:
 *   · status = PUBLICADA  y  published_at <= ahora   → pestaña «Novedades»
 *   · lo anterior  +  featured = 1                   → carrusel «Novedades
 *                                                       destacadas» del inicio
 *
 * El carrusel muestra como máximo 5 entradas (límite fijado en
 * `api-mobile/api/content.php`), por eso el portal avisa cuando se superan.
 *
 * Una publicación nunca se borra de verdad: se marca `deleted_at`, tal como
 * hace la app, para no perder lo que ya se comunicó.
 */
class ContentPost extends Model {

    protected string $table = 'content_posts';

    /** Tope de destacadas que el carrusel de la app alcanza a mostrar */
    public const MAX_DESTACADAS = 5;

    /** Columnas que devolvemos a las vistas */
    private const CAMPOS = 'id, type, title, summary, body, image_filename, image_token,
                            status, featured, published_at, event_start, event_end,
                            event_location, event_address, latitude, longitude,
                            registration_url, created_at, updated_at';

    /** Caché de columnas presentes, para no consultar el esquema en cada llamada */
    private static array $columnas = [];

    /**
     * ¿La tabla tiene esta columna?
     *
     * `created_by` / `updated_by` llegaron después en algunas instalaciones. La
     * API móvil también lo comprueba antes de escribirlas, así que el portal
     * hace lo mismo: sin esto, un portal nuevo contra una base vieja revienta.
     */
    private function tieneColumna(string $columna): bool {
        if (!isset(self::$columnas[$columna])) {
            try {
                self::$columnas[$columna] = (bool) $this->queryOne(
                    "SHOW COLUMNS FROM `{$this->table}` LIKE ?", [$columna]
                );
            } catch (Throwable $e) {
                self::$columnas[$columna] = false;
            }
        }
        return self::$columnas[$columna];
    }

    // ─── Lectura ─────────────────────────────────────────────────────────────

    /** Una publicación viva (no eliminada) */
    public function findOne(int $id): ?array {
        return $this->queryOne(
            'SELECT ' . self::CAMPOS . " FROM `{$this->table}`
             WHERE id = :id AND deleted_at IS NULL LIMIT 1",
            ['id' => $id]
        );
    }

    /**
     * Listado con filtros y paginación desde SQL.
     *
     * @return array{rows: array, total: int, pages: int}
     */
    public function paginate(array $filtros, int $page = 1, int $perPage = 15): array {
        [$where, $params] = $this->construirFiltros($filtros);

        $total = (int) $this->queryColumn(
            "SELECT COUNT(*) FROM `{$this->table}` WHERE {$where}", $params
        );
        $pages   = max(1, (int) ceil($total / $perPage));
        $page    = max(1, min($page, $pages));
        $offset  = ($page - 1) * $perPage;

        // Los borradores arriba: son los que esperan una decisión.
        $rows = $this->query(
            'SELECT ' . self::CAMPOS . " FROM `{$this->table}`
             WHERE {$where}
             ORDER BY CASE WHEN status = 'BORRADOR' THEN 0 ELSE 1 END,
                      COALESCE(published_at, updated_at) DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['rows' => $rows, 'total' => $total, 'pages' => $pages, 'page' => $page];
    }

    /** WHERE + parámetros a partir de los filtros de la vista */
    private function construirFiltros(array $f): array {
        $where  = ['deleted_at IS NULL'];
        $params = [];

        if (!empty($f['type']) && in_array($f['type'], ['NOTICIA', 'EVENTO'], true)) {
            $where[] = 'type = :type';
            $params['type'] = $f['type'];
        }
        if (!empty($f['status']) && in_array($f['status'], ['BORRADOR', 'PUBLICADA'], true)) {
            $where[] = 'status = :status';
            $params['status'] = $f['status'];
        }
        if (!empty($f['featured'])) {
            $where[] = 'featured = 1';
        }
        if (!empty($f['q'])) {
            $where[] = '(title LIKE :q OR summary LIKE :q)';
            $params['q'] = '%' . $f['q'] . '%';
        }

        return [implode(' AND ', $where), $params];
    }

    /** Contadores del encabezado del listado */
    public function contadores(): array {
        $fila = $this->queryOne(
            "SELECT
                COUNT(*)                                                          AS total,
                SUM(status = 'PUBLICADA')                                         AS publicadas,
                SUM(status = 'BORRADOR')                                          AS borradores,
                SUM(status = 'PUBLICADA' AND published_at > NOW())                 AS programadas,
                SUM(type = 'EVENTO' AND event_start >= NOW() AND status = 'PUBLICADA') AS eventos_proximos
             FROM `{$this->table}` WHERE deleted_at IS NULL"
        ) ?? [];

        return [
            'total'            => (int) ($fila['total'] ?? 0),
            'publicadas'       => (int) ($fila['publicadas'] ?? 0),
            'borradores'       => (int) ($fila['borradores'] ?? 0),
            'programadas'      => (int) ($fila['programadas'] ?? 0),
            'eventos_proximos' => (int) ($fila['eventos_proximos'] ?? 0),
            'destacadas'       => $this->destacadasVisibles(),
        ];
    }

    /**
     * Destacadas que la app realmente está mostrando ahora mismo.
     *
     * Se cuentan con las mismas condiciones que usa `content.php?featured=1`,
     * no sólo `featured = 1`: una destacada en borrador no la ve nadie.
     */
    public function destacadasVisibles(): int {
        return (int) $this->queryColumn(
            "SELECT COUNT(*) FROM `{$this->table}`
             WHERE featured = 1 AND status = 'PUBLICADA' AND deleted_at IS NULL
               AND published_at IS NOT NULL AND published_at <= NOW()"
        );
    }

    // ─── Escritura ───────────────────────────────────────────────────────────

    /**
     * Crea o actualiza una publicación.
     *
     * Los campos de evento se anulan cuando el tipo es NOTICIA: así una entrada
     * que cambia de evento a noticia no arrastra la fecha y el mapa anteriores
     * a la ficha de la app.
     *
     * @return int El id de la publicación guardada.
     */
    public function guardar(array $d, ?int $id, int $adminId): int {
        $esEvento = $d['type'] === 'EVENTO';

        $valores = [
            'type'             => $d['type'],
            'title'            => $d['title'],
            'summary'          => $d['summary'],
            'body'             => $d['body'],
            'status'           => $d['status'],
            'featured'         => $d['featured'] ? 1 : 0,
            'published_at'     => $d['published_at'],
            'event_start'      => $esEvento ? $d['event_start']      : null,
            'event_end'        => $esEvento ? $d['event_end']        : null,
            'event_location'   => $esEvento ? $d['event_location']   : null,
            'event_address'    => $esEvento ? $d['event_address']    : null,
            'latitude'         => $esEvento ? $d['latitude']         : null,
            'longitude'        => $esEvento ? $d['longitude']        : null,
            'registration_url' => $esEvento ? $d['registration_url'] : null,
        ];

        if ($id) {
            $asignaciones = [];
            foreach (array_keys($valores) as $columna) {
                $asignaciones[] = "`{$columna}` = :{$columna}";
            }
            if ($this->tieneColumna('updated_by')) {
                $asignaciones[]      = '`updated_by` = :admin';
                $valores['admin']    = $adminId;
            }
            $valores['id'] = $id;
            $this->exec(
                "UPDATE `{$this->table}` SET " . implode(', ', $asignaciones)
                . ' WHERE id = :id AND deleted_at IS NULL',
                $valores
            );
            return $id;
        }

        if ($this->tieneColumna('created_by')) {
            $valores['created_by'] = $adminId;
        }
        if ($this->tieneColumna('updated_by')) {
            $valores['updated_by'] = $adminId;
        }
        $columnas     = array_keys($valores);
        $marcadores   = array_map(static fn(string $c): string => ':' . $c, $columnas);
        $this->exec(
            "INSERT INTO `{$this->table}` (`" . implode('`, `', $columnas) . '`) '
            . 'VALUES (' . implode(', ', $marcadores) . ')',
            $valores
        );
        return (int) $this->db->getConnection()->lastInsertId();
    }

    /** Cambia sólo el estado de publicación */
    public function cambiarEstado(int $id, string $estado, ?string $publishedAt, int $adminId): void {
        $valores = [
            'status'       => $estado,
            'published_at' => $publishedAt,
            'id'           => $id,
        ];
        $set = '`status` = :status, `published_at` = :published_at';
        if ($this->tieneColumna('updated_by')) {
            $set .= ', `updated_by` = :admin';
            $valores['admin'] = $adminId;
        }
        $this->exec(
            "UPDATE `{$this->table}` SET {$set} WHERE id = :id AND deleted_at IS NULL",
            $valores
        );
    }

    /** Marca o desmarca como destacada */
    public function cambiarDestacada(int $id, bool $destacada, int $adminId): void {
        $valores = ['featured' => $destacada ? 1 : 0, 'id' => $id];
        $set = '`featured` = :featured';
        if ($this->tieneColumna('updated_by')) {
            $set .= ', `updated_by` = :admin';
            $valores['admin'] = $adminId;
        }
        $this->exec(
            "UPDATE `{$this->table}` SET {$set} WHERE id = :id AND deleted_at IS NULL",
            $valores
        );
    }

    /** Asocia una imagen ya guardada en disco */
    public function asignarImagen(int $id, string $token, int $adminId): void {
        $valores = [
            'image_filename' => $token . '.jpg',
            'image_token'    => $token,
            'id'             => $id,
        ];
        $set = '`image_filename` = :image_filename, `image_token` = :image_token';
        if ($this->tieneColumna('updated_by')) {
            $set .= ', `updated_by` = :admin';
            $valores['admin'] = $adminId;
        }
        $this->exec("UPDATE `{$this->table}` SET {$set} WHERE id = :id", $valores);
    }

    /** Quita la referencia a la imagen (el archivo lo borra el controlador) */
    public function quitarImagen(int $id, int $adminId): void {
        $valores = ['id' => $id];
        $set = '`image_filename` = NULL, `image_token` = NULL';
        if ($this->tieneColumna('updated_by')) {
            $set .= ', `updated_by` = :admin';
            $valores['admin'] = $adminId;
        }
        $this->exec("UPDATE `{$this->table}` SET {$set} WHERE id = :id", $valores);
    }

    /** Archiva la publicación: desaparece de la app, se conserva en la base */
    public function archivar(int $id, int $adminId): void {
        $valores = ['id' => $id];
        $set = '`deleted_at` = NOW()';
        if ($this->tieneColumna('updated_by')) {
            $set .= ', `updated_by` = :admin';
            $valores['admin'] = $adminId;
        }
        $this->exec("UPDATE `{$this->table}` SET {$set} WHERE id = :id", $valores);
    }
}
