<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0e85a5">
    <meta name="robots" content="noindex, nofollow">
    <title><?= htmlspecialchars($title ?? 'Portal CDHQ') ?> – <?= htmlspecialchars($app_name ?? 'CDHQ') ?></title>
    <link rel="icon" href="<?= SITE_URL ?>/CDHQ%20VERTICAL.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: {
            brand: { 50:'#f0fbfd',100:'#dbf3fa',200:'#b6e7f2',300:'#82d5e8',400:'#3fb5d3',500:'#0e85a5',600:'#0c7290',700:'#0e5c75',800:'#124b60',900:'#143f51' },
            leaf:  { 50:'#eefbee',100:'#d7f6d7',200:'#aeeaae',300:'#75d975',400:'#33c433',500:'#00ac00',600:'#009300',700:'#047604',800:'#085d08',900:'#094c09' }
        } } } }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        .sidebar-link {
            display: flex; align-items: center; gap: 0.75rem;
            padding: 0.6rem 1rem; border-radius: 0.6rem;
            font-size: 0.875rem; font-weight: 500; width: 100%;
            color: rgba(255,255,255,0.75);
            transition: color 150ms, background-color 150ms;
        }
        .sidebar-link:hover { background-color: rgba(255,255,255,0.12); color: #fff; }
        .sidebar-link.active { background-color: rgba(255,255,255,0.18); color: #fff; font-weight: 600; }
        .sidebar-group { padding: 0.9rem 1rem 0.35rem; font-size: 0.68rem; font-weight: 700;
                         text-transform: uppercase; letter-spacing: 0.08em; color: rgba(255,255,255,0.45); }
        .badge { display:inline-flex; align-items:center; padding:0.15rem 0.55rem; border-radius:9999px;
                 font-size:0.7rem; font-weight:700; letter-spacing:0.02em; }
        table.data-table th { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em;
                              color: #64748b; font-weight: 600; }
        table.data-table td, table.data-table th { padding: 0.7rem 0.75rem; text-align: left; }
        table.data-table tbody tr:hover { background-color: #f0fbfd; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen">
