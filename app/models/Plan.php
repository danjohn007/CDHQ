<?php
require_once CORE_PATH . '/Model.php';

/** Planes de anualidad. Tabla `subscription_plans` de cdhq_mobile. */
class Plan extends Model {
    protected string $table = 'subscription_plans';

    /** Planes con su uso real, para saber si conviene tocarlos */
    public function listWithUsage(): array {
        return $this->query(
            "SELECT sp.*,
                    (SELECT COUNT(*) FROM subscriptions s WHERE s.plan_id = sp.id)   AS suscripciones,
                    (SELECT COUNT(*) FROM payment_orders po WHERE po.plan_id = sp.id
                                                              AND po.status = 'PAGADA') AS ordenes_pagadas,
                    (SELECT COUNT(*) FROM payment_orders po WHERE po.plan_id = sp.id
                                                              AND po.status = 'PENDIENTE') AS ordenes_pendientes
             FROM subscription_plans sp
             ORDER BY sp.active DESC, sp.price"
        );
    }

    public function listActive(): array {
        return $this->query(
            "SELECT * FROM subscription_plans WHERE active = 1 ORDER BY price"
        );
    }
}
