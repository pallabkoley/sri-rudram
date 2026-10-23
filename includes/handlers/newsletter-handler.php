<?php
// includes/handlers/newsletter-handler.php
if (($_POST['action'] ?? '') !== 'newsletter_subscribe') return;

if (!csrf_verify($_POST['_token'] ?? null)) {
    json_out(['ok' => false, 'error' => 'Session expired'], 419);
}

$email = trim($_POST['email'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_out(['ok' => false, 'error' => 'Please enter a valid email'], 400);
}

$stmt = $pdo->prepare('SELECT id FROM subscribers WHERE email=? LIMIT 1');
$stmt->execute([$email]);
if ($stmt->fetchColumn()) {
    json_out(['ok' => true, 'message' => 'You are already subscribed 🙏']);
}

$token = bin2hex(random_bytes(16));
$pdo->prepare('INSERT INTO subscribers (email, token) VALUES (?, ?)')->execute([$email, $token]);

json_out(['ok' => true, 'message' => 'Thank you for subscribing 🙏']);