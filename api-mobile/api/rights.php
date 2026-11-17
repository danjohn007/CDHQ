<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
requireMethod('GET');
$query = database()->query(
    'SELECT id, name, slug, summary, content, legal_basis, source_url, updated_at
     FROM human_rights WHERE published = 1 ORDER BY name ASC'
);
jsonResponse(['ok' => true, 'rights' => $query->fetchAll()]);
