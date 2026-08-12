<?php
require_once CORE_PATH . '/Controller.php';
require_once MODELS_PATH . '/Plan.php';

/**
 * Planes y precios de la anualidad.
 *
 * El precio y la duración se congelan en `payment_orders` al crear cada orden,
 * así que un cambio aquí sólo afecta a órdenes futuras: nunca reescribe lo ya
 * cobrado.
 */
class PlansController extends Controller {

    public function index(): void {
        Auth::requireAdmin();
        $model = new Plan();
        $this->view('plans/index', [
            'title' => 'Planes y precios',
            'plans' => $model->listWithUsage(),
        ]);
    }

    public function store(): void {
        Auth::requireAdmin();
        $this->verifyCsrf();

        $data = $this->readInput();
        if ($error = $this->validate($data)) {
            $this->flash('error', $error);
            $this->redirect('plans');
        }

        $id = (new Plan())->create([
            'name'          => $data['name'],
            'description'   => $data['description'],
            'price'         => $data['price'],
            'duration_days' => $data['duration_days'],
            'active'        => $data['active'],
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        Auth::logActivity('CREAR_PLAN', 'subscription_plans', $id,
            sprintf('%s · $%s MXN · %d días', $data['name'], $data['price'], $data['duration_days']));
        $this->flash('success', 'Plan creado.');
        $this->redirect('plans');
    }

    public function update(string $id): void {
        Auth::requireAdmin();
        $this->verifyCsrf();

        $model = new Plan();
        $plan  = $model->find((int) $id);
        if (!$plan) {
            $this->flash('error', 'Plan no encontrado.');
            $this->redirect('plans');
        }

        $data = $this->readInput();
        if ($error = $this->validate($data)) {
            $this->flash('error', $error);
            $this->redirect('plans');
        }

        $model->update((int) $id, [
            'name'          => $data['name'],
            'description'   => $data['description'],
            'price'         => $data['price'],
            'duration_days' => $data['duration_days'],
            'active'        => $data['active'],
        ]);

        // El historial de precios queda en la bitácora: es lo que permite
        // explicar después por qué una orden vieja tiene otro importe.
        $cambios = [];
        if ((float) $plan['price'] !== (float) $data['price']) {
            $cambios[] = sprintf('precio $%s → $%s', $plan['price'], $data['price']);
        }
        if ((int) $plan['duration_days'] !== (int) $data['duration_days']) {
            $cambios[] = sprintf('duración %d → %d días', $plan['duration_days'], $data['duration_days']);
        }
        if ((int) $plan['active'] !== (int) $data['active']) {
            $cambios[] = $data['active'] ? 'activado' : 'desactivado';
        }

        Auth::logActivity('EDITAR_PLAN', 'subscription_plans', (int) $id,
            $plan['name'] . ($cambios ? ' · ' . implode(', ', $cambios) : ' · sin cambios de importe'));
        $this->flash('success', 'Plan actualizado. Aplica sólo a órdenes futuras.');
        $this->redirect('plans');
    }

    public function toggle(string $id): void {
        Auth::requireAdmin();
        $this->verifyCsrf();

        $model = new Plan();
        $plan  = $model->find((int) $id);
        if (!$plan) {
            $this->flash('error', 'Plan no encontrado.');
            $this->redirect('plans');
        }

        $model->update((int) $id, ['active' => $plan['active'] ? 0 : 1]);
        Auth::logActivity('CAMBIAR_ESTADO_PLAN', 'subscription_plans', (int) $id,
            $plan['name'] . ($plan['active'] ? ' desactivado' : ' activado'));
        $this->flash('success', $plan['active'] ? 'Plan desactivado.' : 'Plan activado.');
        $this->redirect('plans');
    }

    private function readInput(): array {
        return [
            'name'          => trim($_POST['name'] ?? ''),
            'description'   => trim($_POST['description'] ?? ''),
            'price'         => (float) ($_POST['price'] ?? 0),
            'duration_days' => (int) ($_POST['duration_days'] ?? 0),
            'active'        => !empty($_POST['active']) ? 1 : 0,
        ];
    }

    private function validate(array $d): ?string {
        if ($d['name'] === '')                        return 'El nombre del plan es obligatorio.';
        if ($d['price'] < 0)                          return 'El precio no puede ser negativo.';
        if ($d['duration_days'] < 1)                  return 'La duración debe ser de al menos 1 día.';
        if ($d['duration_days'] > 3650)               return 'La duración no puede superar los 3650 días.';
        return null;
    }
}
