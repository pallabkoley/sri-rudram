<?php
// includes/handlers/puja-book-handler.php
if (($_POST['action'] ?? '') !== 'puja_book') return;

if (!csrf_verify($_POST['_token'] ?? null)) {
    flash_set('error', 'Session expired. Please try again.');
    redirect('pujas.php');
}

if (!is_logged_in()) {
    flash_set('info', 'Please login to book a puja.');
    redirect('login.php');
}

$pujaId     = (int) ($_POST['puja_id'] ?? 0);
$name       = trim($_POST['devotee_name'] ?? '');
$gotra      = trim($_POST['gotra'] ?? '');
$date       = $_POST['booking_date'] ?? '';
$sankalp    = trim($_POST['sankalp'] ?? '');

if (!$pujaId || !$name || !$date) {
    flash_set('error', 'Please fill in all required fields.');
    redirect('puja.php?slug=' . ($_POST['slug'] ?? ''));
}

$stmt = $pdo->prepare('SELECT id, price, temple_id FROM pujas WHERE id = ? AND is_active = 1 LIMIT 1');
$stmt->execute([$pujaId]);
$puja = $stmt->fetch();

if (!$puja) {
    flash_set('error', 'Puja not found.');
    redirect('pujas.php');
}

$ref = 'DA-PJ-' . strtoupper(bin2hex(random_bytes(4)));

$stmt = $pdo->prepare(
    'INSERT INTO puja_bookings
     (booking_ref, user_id, puja_id, temple_id, booking_date, devotee_name, gotra, sankalp, amount, status)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "pending")'
);
$stmt->execute([
    $ref,
    $_SESSION['user_id'],
    $pujaId,
    $puja['temple_id'],
    $date,
    $name,
    $gotra ?: null,
    $sankalp ?: null,
    $puja['price'],
]);

flash_set('success', 'Booking confirmed! Reference: ' . $ref);
redirect('account-bookings.php');