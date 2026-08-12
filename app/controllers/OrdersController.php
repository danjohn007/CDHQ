<?php
require_once CORE_PATH . '/Controller.php';
require_once MODELS_PATH . '/PaymentOrder.php';

/**
 * Órdenes de pago de Stripe.
 *
 * Módulo de SÓLO CONSULTA a propósito: únicamente el webhook firmado de Stripe
 * puede confirmar un pago. No existe aquí ninguna acción que marque una orden
 * como pagada a mano.
 */
class OrdersController extends Controller {

    private const PER_PAGE = 30;

    public function index(): void {
        Auth::requireAdmin();

        $filters = [
            'q'      => trim($_GET['q'] ?? ''),
            'status' => $_GET['status'] ?? '',
        ];
        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * self::PER_PAGE;

        $model = new PaymentOrder();
        $total = $model->countWithFilters($filters);

        $this->view('orders/index', [
            'title'    => 'Pagos y anualidades',
            'orders'   => $model->listWithFilters($filters, self::PER_PAGE, $offset),
            'filters'  => $filters,
            'page'     => $page,
            'total'    => $total,
            'pages'    => max(1, (int) ceil($total / self::PER_PAGE)),
            'counts'   => $model->countsByStatus(),
            'expired'  => $model->countExpired(),
            'collected'=> $model->totalCollected(30),
        ]);
    }
}
