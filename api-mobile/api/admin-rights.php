<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
$admin = requireRoles('ADMINISTRADOR');
$db = database();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function rightSlug(string $name): string
{
    $ascii = function_exists('iconv') ? iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) : $name;
    $slug = lowercase((string) ($ascii !== false ? $ascii : $name));
    $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', $slug), '-');
    return $slug !== '' ? substr($slug, 0, 135) : 'derecho';
}

if ($method === 'POST') {
    $input = jsonInput();
    $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
    $name = trim((string) ($input['name'] ?? ''));
    $summary = trim((string) ($input['summary'] ?? ''));
    $content = trim((string) ($input['content'] ?? ''));
    $legalBasis = trim((string) ($input['legal_basis'] ?? ''));
    $sourceUrl = trim((string) ($input['source_url'] ?? ''));
    $published = filter_var($input['published'] ?? false, FILTER_VALIDATE_BOOL) ? 1 : 0;
    if (textLength($name) < 4 || textLength($name) > 140) {
        jsonResponse(['ok' => false, 'error' => 'INVALID_NAME', 'message' => 'El nombre debe tener entre 4 y 140 caracteres.'], 422);
    }
    if (textLength($summary) < 10 || textLength($summary) > 1000) {
        jsonResponse(['ok' => false, 'error' => 'INVALID_SUMMARY', 'message' => 'Escribe un resumen de entre 10 y 1,000 caracteres.'], 422);
    }
    if ($sourceUrl !== '' && (!filter_var($sourceUrl, FILTER_VALIDATE_URL) || !in_array(parse_url($sourceUrl, PHP_URL_SCHEME), ['http', 'https'], true))) {
        jsonResponse(['ok' => false, 'error' => 'INVALID_SOURCE_URL', 'message' => 'La fuente debe ser una dirección http:// o https:// válida.'], 422);
    }

    if ($id) {
        $exists = $db->prepare('SELECT id FROM human_rights WHERE id = :id LIMIT 1');
        $exists->execute(['id' => $id]);
        if (!$exists->fetch()) jsonResponse(['ok' => false, 'error' => 'RIGHT_NOT_FOUND'], 404);
        $db->prepare(
            'UPDATE human_rights SET name = :name, summary = :summary, content = :content,
             legal_basis = :legal_basis, source_url = :source_url, published = :published, updated_by = :admin
             WHERE id = :id'
        )->execute([
            'name' => $name, 'summary' => $summary, 'content' => $content !== '' ? $content : null,
            'legal_basis' => $legalBasis !== '' ? $legalBasis : null,
            'source_url' => $sourceUrl !== '' ? $sourceUrl : null,
            'published' => $published, 'admin' => $admin['id'], 'id' => $id,
        ]);
    } else {
        $baseSlug = rightSlug($name);
        $slug = $baseSlug;
        $suffix = 2;
        $slugQuery = $db->prepare('SELECT id FROM human_rights WHERE slug = :slug LIMIT 1');
        do {
            $slugQuery->execute(['slug' => $slug]);
            if (!$slugQuery->fetch()) break;
            $slug = substr($baseSlug, 0, 150) . '-' . $suffix++;
        } while ($suffix < 1000);
        $db->prepare(
            'INSERT INTO human_rights
             (name, slug, summary, content, legal_basis, source_url, published, created_by, updated_by)
             VALUES (:name, :slug, :summary, :content, :legal_basis, :source_url, :published, :admin, :admin)'
        )->execute([
            'name' => $name, 'slug' => $slug, 'summary' => $summary,
            'content' => $content !== '' ? $content : null,
            'legal_basis' => $legalBasis !== '' ? $legalBasis : null,
            'source_url' => $sourceUrl !== '' ? $sourceUrl : null,
            'published' => $published, 'admin' => $admin['id'],
        ]);
        $id = (int) $db->lastInsertId();
    }
    $db->prepare(
        'INSERT INTO activity_log (user_id, action, entity, entity_id, details, ip)
         VALUES (:user_id, "SAVE_HUMAN_RIGHT", "human_rights", :entity_id, :details, :ip)'
    )->execute([
        'user_id' => $admin['id'], 'entity_id' => $id,
        'details' => json_encode(['published' => (bool) $published], JSON_FLAGS),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}

if (!in_array($method, ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    jsonResponse(['ok' => false, 'error' => 'METHOD_NOT_ALLOWED'], 405);
}

$rights = $db->query(
    'SELECT id, name, slug, summary, content, legal_basis, source_url, published, updated_at
     FROM human_rights ORDER BY published DESC, name ASC'
)->fetchAll();
foreach ($rights as &$right) {
    $right['id'] = (int) $right['id'];
    $right['published'] = (bool) $right['published'];
}
unset($right);
jsonResponse(['ok' => true, 'rights' => $rights]);
