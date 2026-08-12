<?php
require_once CORE_PATH . '/Controller.php';
require_once CORE_PATH . '/ContentImage.php';
require_once MODELS_PATH . '/ContentPost.php';

/**
 * Noticias y eventos que la aplicación móvil muestra a las personas usuarias.
 *
 * Este módulo escribe en `content_posts`, la misma tabla que consume la app:
 * al publicar aquí, la entrada aparece en la pestaña «Novedades»; si además se
 * marca como destacada, entra al carrusel «Novedades destacadas» del inicio.
 *
 * Reglas que se respetan porque la app depende de ellas:
 *   · Sólo se ve lo que tiene `status = PUBLICADA` y `published_at` ya cumplido.
 *     Una fecha futura equivale a programar la publicación.
 *   · Un EVENTO exige fecha de inicio; una NOTICIA no arrastra datos de evento.
 *   · Nada se borra: se archiva con `deleted_at`, igual que hace la app.
 *
 * La validación repite la de `api-mobile/api/admin-content.php` a propósito:
 * son dos puertas de escritura sobre la misma tabla y deben aceptar exactamente
 * lo mismo, o la app acabaría mostrando registros que ella no habría permitido.
 */
class ContentController extends Controller {

    private const POR_PAGINA = 15;

    // ─── Listado ─────────────────────────────────────────────────────────────

    public function index(): void {
        Auth::requireAdmin();

        $filtros = [
            'type'     => strtoupper(trim((string) ($_GET['type']   ?? ''))),
            'status'   => strtoupper(trim((string) ($_GET['status'] ?? ''))),
            'featured' => !empty($_GET['featured']),
            'q'        => trim((string) ($_GET['q'] ?? '')),
        ];

        $model     = new ContentPost();
        $resultado = $model->paginate($filtros, (int) ($_GET['page'] ?? 1), self::POR_PAGINA);

        $this->view('content/index', [
            'title'       => 'Noticias y eventos',
            'posts'       => $resultado['rows'],
            'total'       => $resultado['total'],
            'pages'       => $resultado['pages'],
            'page'        => $resultado['page'],
            'filtros'     => $filtros,
            'contadores'  => $model->contadores(),
        ]);
    }

    // ─── Alta y edición ──────────────────────────────────────────────────────

    public function create(): void {
        Auth::requireAdmin();
        $this->view('content/create', [
            'title' => 'Nueva publicación',
            'post'  => null,
        ]);
    }

    public function store(): void {
        Auth::requireAdmin();
        $this->verifyCsrf();

        $datos = $this->leerEntrada();
        if ($error = $this->validar($datos)) {
            $this->flash('error', $error);
            $this->redirect('content/create');
        }

        $model = new ContentPost();
        $id    = $model->guardar($datos, null, $this->adminId());

        // La imagen se procesa después del alta porque el nombre del archivo se
        // deriva del registro; si falla, la publicación ya quedó guardada y sólo
        // se avisa del problema de imagen.
        $avisoImagen = $this->procesarImagen($model, $id);

        Auth::logActivity('CREAR_CONTENIDO', 'content_posts', $id,
            sprintf('%s · %s · %s', $datos['type'], $datos['title'], $datos['status']));

        $this->flash($avisoImagen ? 'error' : 'success',
            $avisoImagen ?: $this->mensajePublicacion($datos, 'Publicación creada.'));
        $this->redirect('content/' . $id . '/edit');
    }

    public function edit(string $id): void {
        Auth::requireAdmin();

        $model = new ContentPost();
        $post  = $model->findOne((int) $id);
        if (!$post) {
            $this->flash('error', 'La publicación no existe o fue archivada.');
            $this->redirect('content');
        }

        $this->view('content/edit', [
            'title'      => 'Editar publicación',
            'post'       => $post,
            'image_url'  => ContentImage::url($post['image_token']),
            'destacadas' => $model->destacadasVisibles(),
        ]);
    }

    public function update(string $id): void {
        Auth::requireAdmin();
        $this->verifyCsrf();

        $model = new ContentPost();
        $post  = $model->findOne((int) $id);
        if (!$post) {
            $this->flash('error', 'La publicación no existe o fue archivada.');
            $this->redirect('content');
        }

        $datos = $this->leerEntrada();
        if ($error = $this->validar($datos)) {
            $this->flash('error', $error);
            $this->redirect('content/' . (int) $id . '/edit');
        }

        $model->guardar($datos, (int) $id, $this->adminId());
        $avisoImagen = $this->procesarImagen($model, (int) $id);

        Auth::logActivity('EDITAR_CONTENIDO', 'content_posts', (int) $id,
            sprintf('%s · %s · %s', $datos['type'], $datos['title'], $datos['status']));

        $this->flash($avisoImagen ? 'error' : 'success',
            $avisoImagen ?: $this->mensajePublicacion($datos, 'Cambios guardados.'));
        $this->redirect('content/' . (int) $id . '/edit');
    }

    // ─── Acciones rápidas desde el listado ───────────────────────────────────

    /** Publica o regresa a borrador */
    public function changeStatus(string $id): void {
        Auth::requireAdmin();
        $this->verifyCsrf();

        $model = new ContentPost();
        $post  = $model->findOne((int) $id);
        if (!$post) {
            $this->flash('error', 'La publicación no existe o fue archivada.');
            $this->redirect('content');
        }

        $publicar = ($post['status'] !== 'PUBLICADA');
        if ($publicar) {
            // Al publicar sin fecha se toma la de este momento: sin
            // `published_at` la app no muestra la entrada aunque esté publicada.
            $publishedAt = $post['published_at'] ?: date('Y-m-d H:i:s');
            $model->cambiarEstado((int) $id, 'PUBLICADA', $publishedAt, $this->adminId());
            $mensaje = strtotime($publishedAt) > time()
                ? 'Publicación programada para el ' . fechaHora($publishedAt) . '.'
                : 'Publicación visible en la app.';
        } else {
            $model->cambiarEstado((int) $id, 'BORRADOR', $post['published_at'], $this->adminId());
            $mensaje = 'Publicación retirada de la app.';
        }

        Auth::logActivity($publicar ? 'PUBLICAR_CONTENIDO' : 'DESPUBLICAR_CONTENIDO',
            'content_posts', (int) $id, $post['title']);
        $this->flash('success', $mensaje);
        $this->redirect('content');
    }

    /** Marca o desmarca la publicación como destacada */
    public function toggleFeatured(string $id): void {
        Auth::requireAdmin();
        $this->verifyCsrf();

        $model = new ContentPost();
        $post  = $model->findOne((int) $id);
        if (!$post) {
            $this->flash('error', 'La publicación no existe o fue archivada.');
            $this->redirect('content');
        }

        $destacar = !$post['featured'];
        $model->cambiarDestacada((int) $id, $destacar, $this->adminId());

        Auth::logActivity($destacar ? 'DESTACAR_CONTENIDO' : 'QUITAR_DESTACADO',
            'content_posts', (int) $id, $post['title']);

        $mensaje = $destacar ? 'Publicación destacada.' : 'Se quitó de destacadas.';
        // El carrusel de la app sólo alcanza a mostrar 5: avisamos en vez de
        // impedirlo, porque cuál sacrificar es decisión de quien administra.
        if ($destacar && $model->destacadasVisibles() > ContentPost::MAX_DESTACADAS) {
            $mensaje .= ' Aviso: hay más de ' . ContentPost::MAX_DESTACADAS
                      . ' destacadas visibles y el carrusel de la app sólo muestra las '
                      . ContentPost::MAX_DESTACADAS . ' más recientes.';
        }
        $this->flash('success', $mensaje);
        $this->redirect('content');
    }

    /** Quita la imagen de portada */
    public function removeImage(string $id): void {
        Auth::requireAdmin();
        $this->verifyCsrf();

        $model = new ContentPost();
        $post  = $model->findOne((int) $id);
        if (!$post) {
            $this->flash('error', 'La publicación no existe o fue archivada.');
            $this->redirect('content');
        }

        $model->quitarImagen((int) $id, $this->adminId());
        ContentImage::delete($post['image_filename']);

        Auth::logActivity('QUITAR_IMAGEN_CONTENIDO', 'content_posts', (int) $id, $post['title']);
        $this->flash('success', 'Imagen eliminada.');
        $this->redirect('content/' . (int) $id . '/edit');
    }

    /** Archiva: desaparece de la app pero se conserva en la base */
    public function archive(string $id): void {
        Auth::requireAdmin();
        $this->verifyCsrf();

        $model = new ContentPost();
        $post  = $model->findOne((int) $id);
        if (!$post) {
            $this->flash('error', 'La publicación no existe o fue archivada.');
            $this->redirect('content');
        }

        $model->archivar((int) $id, $this->adminId());
        Auth::logActivity('ARCHIVAR_CONTENIDO', 'content_posts', (int) $id, $post['title']);
        $this->flash('success', 'Publicación archivada. Ya no aparece en la app.');
        $this->redirect('content');
    }

    // ─── Apoyo ───────────────────────────────────────────────────────────────

    private function adminId(): int {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    /**
     * Guarda la imagen recibida, si viene alguna.
     *
     * @return string Mensaje de error, o cadena vacía si todo salió bien.
     */
    private function procesarImagen(ContentPost $model, int $id): string {
        $archivo = $_FILES['image'] ?? null;
        if (!is_array($archivo) || (int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return '';
        }

        $anterior = $model->findOne($id)['image_filename'] ?? null;
        $token    = ContentImage::store($archivo);
        if ($token === null) {
            return 'Se guardó el texto, pero la imagen no: ' . ContentImage::lastError();
        }

        $model->asignarImagen($id, $token, $this->adminId());
        // El archivo viejo se borra sólo después de que el nuevo quedó grabado
        // en la base: si algo falla antes, la publicación conserva su portada.
        if ($anterior !== $token . '.jpg') {
            ContentImage::delete($anterior);
        }
        return '';
    }

    /** Aclara en el mensaje si la entrada ya se ve en la app o todavía no */
    private function mensajePublicacion(array $d, string $base): string {
        if ($d['status'] !== 'PUBLICADA') {
            return $base . ' Queda como borrador: todavía no se ve en la app.';
        }
        if ($d['published_at'] && strtotime($d['published_at']) > time()) {
            return $base . ' Programada para el ' . fechaHora($d['published_at'])
                 . ': aparecerá en la app en esa fecha.';
        }
        return $base . ' Ya es visible en la app.';
    }

    private function leerEntrada(): array {
        $tipo   = strtoupper(trim((string) ($_POST['type'] ?? '')));
        $estado = strtoupper(trim((string) ($_POST['status'] ?? 'BORRADOR')));

        // Publicar sin fecha equivale a publicar ahora. Sin este ajuste la
        // entrada quedaría PUBLICADA pero con `published_at` vacío, y la app
        // exige `published_at <= NOW()`: no la mostraría nunca.
        $publicadaEn = $this->leerFecha($_POST['published_at'] ?? '');
        if ($estado === 'PUBLICADA' && $publicadaEn === null) {
            $publicadaEn = date('Y-m-d H:i:s');
        }

        return [
            'type'             => $tipo,
            'title'            => trim((string) ($_POST['title']   ?? '')),
            'summary'          => trim((string) ($_POST['summary'] ?? '')),
            'body'             => trim((string) ($_POST['body']    ?? '')),
            'status'           => $estado,
            'featured'         => !empty($_POST['featured']),
            'published_at'     => $publicadaEn,
            'event_start'      => $this->leerFecha($_POST['event_start'] ?? ''),
            'event_end'        => $this->leerFecha($_POST['event_end']   ?? ''),
            'event_location'   => trim((string) ($_POST['event_location'] ?? '')) ?: null,
            'event_address'    => trim((string) ($_POST['event_address']  ?? '')) ?: null,
            'latitude'         => $this->leerCoordenada($_POST['latitude']  ?? ''),
            'longitude'        => $this->leerCoordenada($_POST['longitude'] ?? ''),
            'registration_url' => trim((string) ($_POST['registration_url'] ?? '')) ?: null,
        ];
    }

    /** `datetime-local` llega como "2026-08-11T18:30"; MySQL espera un espacio */
    private function leerFecha(string $valor): ?string {
        $valor = trim($valor);
        if ($valor === '') {
            return null;
        }
        $marca = strtotime($valor);
        return $marca === false ? null : date('Y-m-d H:i:s', $marca);
    }

    private function leerCoordenada(string $valor) {
        $valor = trim($valor);
        return $valor === '' ? null : filter_var($valor, FILTER_VALIDATE_FLOAT);
    }

    /**
     * Mismos límites que aplica la API móvil, para que las dos puertas de
     * escritura sobre `content_posts` acepten exactamente lo mismo.
     */
    private function validar(array $d): ?string {
        if (!in_array($d['type'], ['NOTICIA', 'EVENTO'], true)) {
            return 'Elige si es una noticia o un evento.';
        }
        if (!in_array($d['status'], ['BORRADOR', 'PUBLICADA'], true)) {
            return 'El estado de publicación no es válido.';
        }

        $titulo  = mb_strlen($d['title']);
        $resumen = mb_strlen($d['summary']);
        $cuerpo  = mb_strlen($d['body']);
        if ($titulo  < 5  || $titulo  > 180)    return 'El título debe tener entre 5 y 180 caracteres.';
        if ($resumen < 10 || $resumen > 350)    return 'El resumen debe tener entre 10 y 350 caracteres.';
        if ($cuerpo  < 20 || $cuerpo  > 100000) return 'El contenido debe tener entre 20 y 100,000 caracteres.';

        if ($d['type'] === 'EVENTO') {
            if (!$d['event_start']) {
                return 'Un evento necesita fecha y hora de inicio.';
            }
            if ($d['event_end'] && $d['event_end'] < $d['event_start']) {
                return 'La fecha final del evento no puede ser anterior al inicio.';
            }
            if ($d['latitude'] === false || ($d['latitude'] !== null && ($d['latitude'] < -90 || $d['latitude'] > 90))) {
                return 'La latitud debe ser un número entre -90 y 90.';
            }
            if ($d['longitude'] === false || ($d['longitude'] !== null && ($d['longitude'] < -180 || $d['longitude'] > 180))) {
                return 'La longitud debe ser un número entre -180 y 180.';
            }
            if ($d['registration_url'] !== null) {
                $url = $d['registration_url'];
                if (!filter_var($url, FILTER_VALIDATE_URL)
                    || !in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
                    return 'El enlace de registro debe iniciar con http:// o https://.';
                }
            }
        }

        return null;
    }
}
