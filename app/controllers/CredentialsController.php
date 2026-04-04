<?php
require_once CORE_PATH . '/Controller.php';
require_once MODELS_PATH . '/Affiliate.php';
require_once MODELS_PATH . '/Subscription.php';

class CredentialsController extends Controller {

    public function show(string $id): void {
        Auth::requireAuth();
        $model    = new Affiliate();
        $affiliate = $model->findWithDetails((int)$id);
        if (!$affiliate) {
            $this->flash('error', 'Afiliado no encontrado.');
            $this->redirect('affiliates');
        }

        // AFILIADO role can only see their own credential
        $current = Auth::currentUser();
        if ($current['role'] === ROLE_AFILIADO) {
            $own = $model->queryOne("SELECT id FROM affiliates WHERE user_id = ?", [$current['id']]);
            if (!$own || $own['id'] != $id) {
                $this->flash('error', 'Acceso denegado.');
                $this->redirect('dashboard');
            }
        }

        $subModel  = new Subscription();
        $activeSub = $subModel->getActiveSubscription((int)$id);

        // Build QR code URL using Google Charts API
        $validationUrl = BASE_URL . '/affiliates/' . $id;
        $qrUrl = 'https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl='
                 . urlencode($validationUrl) . '&choe=UTF-8';

        $this->view('credentials/show', [
            'title'     => 'Credencial Digital',
            'affiliate' => $affiliate,
            'activeSub' => $activeSub,
            'qrUrl'     => $qrUrl,
        ]);
    }
}
