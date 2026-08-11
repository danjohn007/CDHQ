<?php
require_once CORE_PATH . '/Controller.php';
require_once MODELS_PATH . '/Notification.php';

/**
 * Avisos generales hacia la aplicación móvil.
 *
 * Un aviso se guarda como una fila por destinatario en `notifications`, que es
 * la tabla que la app tiene prevista para esto.
 *
 * El envío ocurre en dos pasos —redactar y confirmar— porque un aviso no se
 * puede deshacer: en cuanto se inserta, queda para todas las personas del
 * segmento. La pantalla de confirmación muestra a cuántas se va a enviar y
 * algunos nombres concretos, que es lo que permite detectar a tiempo un
 * segmento equivocado.
 */
class NotificationsController extends Controller {

    public function index(): void {
        Auth::requireAdmin();

        $model = new Notification();

        // Buscador del envío individual. Se resuelve con una recarga normal en
        // lugar de autocompletado: son pocas búsquedas y así funciona igual sin
        // JavaScript.
        $buscar      = trim((string) ($_GET['buscar'] ?? ''));
        $encontrados = $buscar !== '' ? $model->buscarUsuarios($buscar) : [];

        $this->view('notifications/index', [
            'title'       => 'Avisos a la app',
            'segmentos'   => Notification::SEGMENTOS,
            'conteos'     => $model->conteoPorSegmento(),
            'historial'   => $model->historial(),
            'borrador'    => $this->borradorDeSesion(),
            'buscar'      => $buscar,
            'encontrados' => $encontrados,
        ]);
    }

    /** Paso 1: revisar a quién se le va a enviar antes de escribir nada en la base */
    public function preview(): void {
        Auth::requireAdmin();
        $this->verifyCsrf();

        $datos = $this->leerEntrada();
        if ($error = $this->validar($datos)) {
            $_SESSION['aviso_borrador'] = $datos;
            $this->flash('error', $error);
            $this->redirect('notifications');
        }

        $model = new Notification();

        if ($datos['destino'] === 'USUARIO') {
            $usuario = $model->usuarioActivo($datos['user_id']);
            if (!$usuario) {
                $_SESSION['aviso_borrador'] = $datos;
                $this->flash('error', 'La persona destinataria no existe o su cuenta está desactivada.');
                $this->redirect('notifications');
            }
            $destinatarios = 1;
            $muestra       = [$usuario];
            $descripcion   = $usuario['name'] . ' (' . $usuario['email'] . ')';
        } else {
            $destinatarios = $model->contarDestinatarios($datos['segmento']);
            $muestra       = $model->muestraDestinatarios($datos['segmento']);
            $descripcion   = Notification::SEGMENTOS[$datos['segmento']];

            if ($destinatarios === 0) {
                $_SESSION['aviso_borrador'] = $datos;
                $this->flash('error', 'Ese segmento no tiene destinatarios ahora mismo. '
                                    . 'Revisa la selección antes de enviar.');
                $this->redirect('notifications');
            }
        }

        unset($_SESSION['aviso_borrador']);
        $this->view('notifications/preview', [
            'title'         => 'Confirmar envío',
            'datos'         => $datos,
            'destinatarios' => $destinatarios,
            'muestra'       => $muestra,
            'descripcion'   => $descripcion,
        ]);
    }

    /** Paso 2: enviar de verdad */
    public function send(): void {
        Auth::requireAdmin();
        $this->verifyCsrf();

        $datos = $this->leerEntrada();
        if ($error = $this->validar($datos)) {
            $this->flash('error', $error);
            $this->redirect('notifications');
        }

        $model = new Notification();

        if ($datos['destino'] === 'USUARIO') {
            $usuario = $model->usuarioActivo($datos['user_id']);
            if (!$usuario) {
                $this->flash('error', 'La persona destinataria no existe o su cuenta está desactivada.');
                $this->redirect('notifications');
            }
            $enviados = $model->enviarAUsuario(
                $datos['user_id'], $datos['title'], $datos['message'], $datos['action_url']
            );
            $detalle = sprintf('«%s» a %s', $datos['title'], $usuario['email']);
        } else {
            $enviados = $model->enviarASegmento(
                $datos['segmento'], $datos['title'], $datos['message'], $datos['action_url']
            );
            $detalle = sprintf('«%s» al segmento %s · %d destinatarios',
                $datos['title'], $datos['segmento'], $enviados);
        }

        Auth::logActivity('ENVIAR_AVISO', 'notifications', 0, $detalle);

        if ($enviados === 0) {
            $this->flash('error', 'No se envió el aviso: el segmento se quedó sin destinatarios.');
        } else {
            $this->flash('success', $enviados === 1
                ? 'Aviso enviado a 1 persona.'
                : 'Aviso enviado a ' . $enviados . ' personas.');
        }
        $this->redirect('notifications');
    }

    // ─── Apoyo ───────────────────────────────────────────────────────────────

    /** Recupera lo que se estaba redactando cuando la validación falló */
    private function borradorDeSesion(): array {
        $borrador = $_SESSION['aviso_borrador'] ?? [];
        unset($_SESSION['aviso_borrador']);
        return $borrador;
    }

    private function leerEntrada(): array {
        $destino = strtoupper(trim((string) ($_POST['destino'] ?? 'SEGMENTO')));

        return [
            'destino'    => $destino === 'USUARIO' ? 'USUARIO' : 'SEGMENTO',
            'segmento'   => strtoupper(trim((string) ($_POST['segmento'] ?? ''))),
            'user_id'    => (int) ($_POST['user_id'] ?? 0),
            'title'      => trim((string) ($_POST['title']   ?? '')),
            'message'    => trim((string) ($_POST['message'] ?? '')),
            'action_url' => trim((string) ($_POST['action_url'] ?? '')) ?: null,
        ];
    }

    private function validar(array $d): ?string {
        $titulo  = mb_strlen($d['title']);
        $mensaje = mb_strlen($d['message']);

        if ($titulo  < 5  || $titulo  > 180)  return 'El título debe tener entre 5 y 180 caracteres.';
        if ($mensaje < 10 || $mensaje > 2000) return 'El mensaje debe tener entre 10 y 2,000 caracteres.';

        if ($d['destino'] === 'USUARIO') {
            if ($d['user_id'] < 1) {
                return 'Indica a qué persona se le enviará el aviso.';
            }
        } elseif (!Notification::esSegmentoValido($d['segmento'])) {
            return 'Elige un segmento de destinatarios válido.';
        }

        if ($d['action_url'] !== null) {
            $url = $d['action_url'];
            if (!filter_var($url, FILTER_VALIDATE_URL)
                || !in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
                return 'El enlace debe iniciar con http:// o https://.';
            }
            if (mb_strlen($url) > 500) {
                return 'El enlace no puede superar los 500 caracteres.';
            }
        }

        return null;
    }
}
