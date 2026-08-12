<?php
require_once CORE_PATH . '/Controller.php';

/**
 * Sitio público del Consejo.
 *
 * Sirve las páginas estáticas de `app/views/landing/` (inicio, derechos,
 * servicios, nosotros, contacto, aviso de privacidad) SIN exigir sesión: es
 * lo primero que ve cualquier visitante.
 *
 * Por qué existe este controlador y no se sirven como archivos sueltos:
 * las páginas se escribieron para vivir en la RAÍZ del dominio, con el portal
 * administrativo aparte en `/sistema` (el botón «Acceso al sistema» apunta a
 * `/sistema/`, a mano, dentro del HTML). Cuando este mismo repositorio se
 * despliega solo —sin ese segundo sitio estático copiado a mano en la raíz—,
 * la ruta `/` caía en `DashboardController`, que exige administrador: la
 * landing dejaba de verse y la raíz abría el login de una vez.
 *
 * Este controlador hace que el repositorio se baste solo: sirve la landing
 * directamente y corrige al vuelo los enlaces `/sistema/…` para que apunten a
 * donde de verdad vive el portal en ESTA instalación (`BASE_URL`), sea que
 * viva en la raíz o en un subdirectorio. El contenido de las páginas no se
 * toca; sólo esos dos enlaces cambian.
 */
class LandingController extends Controller {

    private const DIRECTORIO = VIEWS_PATH . '/landing';

    public function index(): void      { $this->servirHtml('index.html'); }
    public function derechos(): void   { $this->servirHtml('derechos.html'); }
    public function servicios(): void  { $this->servirHtml('servicios.html'); }
    public function nosotros(): void   { $this->servirHtml('nosotros.html'); }
    public function contacto(): void   { $this->servirHtml('contacto.html'); }
    public function privacidad(): void { $this->servirHtml('aviso-de-privacidad.html'); }

    public function css(): void { $this->servirEstatico('cdhq.css', 'text/css; charset=utf-8'); }
    public function js(): void  { $this->servirEstatico('cdhq.js', 'application/javascript; charset=utf-8'); }

    /** Página HTML autocontenida, con los enlaces al portal ya corregidos */
    private function servirHtml(string $archivo): void {
        $ruta = self::DIRECTORIO . '/' . $archivo;
        if (!is_file($ruta)) {
            http_response_code(404);
            $this->notFound();
            return;
        }

        $html = file_get_contents($ruta);
        $html = str_replace(
            ['href="/sistema/verificar"', 'href="/sistema/"'],
            ['href="' . BASE_URL . '/verificar"', 'href="' . BASE_URL . '/login"'],
            $html
        );

        header('Content-Type: text/html; charset=utf-8');
        echo $html;
    }

    /** CSS/JS de la landing, con caché larga: cambian rara vez y de forma versionada por despliegue */
    private function servirEstatico(string $archivo, string $tipo): void {
        $ruta = self::DIRECTORIO . '/' . $archivo;
        if (!is_file($ruta)) {
            http_response_code(404);
            return;
        }
        header('Content-Type: ' . $tipo);
        header('Cache-Control: public, max-age=3600');
        readfile($ruta);
    }
}
