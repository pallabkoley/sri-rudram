<?php
// includes/handlers/search-handler.php
if (($_REQUEST['action'] ?? '') !== 'search') return;

$q = trim($_REQUEST['q'] ?? '');
if (strlen($q) < 2) json_out(['ok' => true, 'results' => []]);

$like    = '%' . $q . '%';
$results = [];

// Pujas
$stmt = $pdo->prepare("SELECT name, slug FROM pujas WHERE is_active=1 AND name LIKE ? LIMIT 5");
$stmt->execute([$like]);
foreach ($stmt->fetchAll() as $r) {
    $results[] = ['title'=>$r['name'], 'sub'=>'Puja Service', 'url'=>url('puja.php?slug='.$r['slug']), 'tag'=>'Puja', 'icon'=>'🕉'];
}

// Temples
$stmt = $pdo->prepare("SELECT name, slug, city FROM temples WHERE is_active=1 AND (name LIKE ? OR city LIKE ?) LIMIT 5");
$stmt->execute([$like, $like]);
foreach ($stmt->fetchAll() as $r) {
    $results[] = ['title'=>$r['name'], 'sub'=>'Temple · '.$r['city'], 'url'=>url('temple.php?slug='.$r['slug']), 'tag'=>'Temple', 'icon'=>'🛕'];
}

// Products
$stmt = $pdo->prepare("SELECT name, slug, price FROM products WHERE is_active=1 AND name LIKE ? LIMIT 5");
$stmt->execute([$like]);
foreach ($stmt->fetchAll() as $r) {
    $results[] = ['title'=>$r['name'], 'sub'=>'Store · '.inr($r['price']), 'url'=>url('product.php?slug='.$r['slug']), 'tag'=>'Product', 'icon'=>'📿'];
}

// Articles
$stmt = $pdo->prepare("SELECT title, slug, read_minutes FROM articles WHERE is_active=1 AND title LIKE ? LIMIT 5");
$stmt->execute([$like]);
foreach ($stmt->fetchAll() as $r) {
    $results[] = ['title'=>$r['title'], 'sub'=>'Article · '.($r['read_minutes']??5).' min read', 'url'=>url('article.php?slug='.$r['slug']), 'tag'=>'Article', 'icon'=>'📄'];
}

json_out(['ok'=>true, 'query'=>$q, 'results'=>array_slice($results, 0, 12)]);