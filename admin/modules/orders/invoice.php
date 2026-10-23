<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$id = (int) get('id', 0);
if ($id <= 0) { flash_set('error','Invalid order ID.'); redirect('admin/modules/orders/index.php'); }

$stmt = $pdo->prepare(
    'SELECT o.*, u.name AS customer_name, u.email AS customer_email
     FROM orders o
     LEFT JOIN users u ON u.id = o.user_id
     WHERE o.id = ? LIMIT 1'
);
$stmt->execute([$id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) { flash_set('error','Order not found.'); redirect('admin/modules/orders/index.php'); }

$it = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
$it->execute([$id]);
$items = $it->fetchAll(PDO::FETCH_ASSOC);

/* Site info */
$siteName = defined('APP_NAME') ? APP_NAME : 'Divya Aastha';
$siteEmail = 'seva@divyaaastha.in';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Invoice #<?= htmlspecialchars($order['order_number']) ?></title>
<style>
*{box-sizing:border-box}
body{font-family:Inter,-apple-system,'Segoe UI',sans-serif;background:#F3F4F6;margin:0;padding:24px;color:#1F2937}
.inv{max-width:800px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;
  box-shadow:0 4px 24px rgba(0,0,0,.08)}
.inv-head{padding:30px 40px;background:#351B2F;color:#F7EBDD;
  display:flex;justify-content:space-between;align-items:flex-start;gap:20px}
.inv-head h1{margin:0;font-family:Georgia,serif;font-size:26px;letter-spacing:.5px;color:#F7EBDD}
.inv-head .sub{font-size:12px;opacity:.75;margin-top:4px;letter-spacing:1.5px;text-transform:uppercase}
.inv-head .right{text-align:right}
.inv-head .right h2{margin:0;font-size:22px;font-family:Georgia,serif;color:#F7EBDD}
.inv-head .right p{margin:4px 0 0;font-size:12px;opacity:.8}

.inv-body{padding:34px 40px}
.row{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:28px}
.row h4{margin:0 0 8px;font-size:11px;letter-spacing:1.4px;text-transform:uppercase;
  color:#6B7280;font-weight:700}
.row p{margin:0;font-size:13.5px;line-height:1.7;color:#4B5563}

table{width:100%;border-collapse:collapse;margin-bottom:24px}
th{background:#FAF6EF;text-align:left;padding:12px 14px;font-size:11px;font-weight:700;
  letter-spacing:1.2px;text-transform:uppercase;color:#6B7280}
td{padding:12px 14px;border-bottom:1px solid #F3EEE6;font-size:13.5px;color:#1F2937}
td.right,th.right{text-align:right}
td.bold{font-weight:700}

.totals{margin-left:auto;width:280px}
.totals .l{display:flex;justify-content:space-between;padding:7px 0;font-size:13.5px;color:#6B7280}
.totals .l.grand{border-top:1px solid #E5E7EB;padding-top:12px;margin-top:8px;
  font-size:16px;font-weight:700;color:#351B2F}

.inv-foot{padding:22px 40px;background:#FAF6EF;border-top:1px solid #EFE6D9;
  text-align:center;font-size:12px;color:#6B7280;line-height:1.7}
.inv-foot b{color:#351B2F}

.btn-print{position:fixed;top:20px;right:20px;padding:10px 20px;font-size:13px;
  font-weight:600;background:#B76146;color:#fff;border:1px solid #B76146;
  border-radius:10px;cursor:pointer;font-family:inherit}
.btn-print:hover{background:#9B4C34}

@media print {
  body{background:#fff;padding:0}
  .inv{box-shadow:none;border-radius:0;max-width:100%}
  .btn-print{display:none}
}
</style>
</head>
<body>

<button class="btn-print" onclick="window.print()">🖨 Print</button>

<div class="inv">

  <div class="inv-head">
    <div>
      <h1><?= htmlspecialchars($siteName) ?></h1>
      <div class="sub">Tax Invoice</div>
    </div>
    <div class="right">
      <h2>#<?= htmlspecialchars($order['order_number']) ?></h2>
      <p><?= date('d M Y', strtotime($order['created_at'])) ?></p>
      <p style="text-transform:uppercase;font-weight:700;letter-spacing:1px">
        <?= htmlspecialchars($order['status']) ?>
      </p>
    </div>
  </div>

  <div class="inv-body">

    <div class="row">
      <div>
        <h4>Billed To</h4>
        <p>
          <b><?= htmlspecialchars($order['ship_name']) ?></b><br>
          <?= htmlspecialchars($order['customer_email'] ?: '') ?><br>
          <?= htmlspecialchars($order['ship_phone'] ?: '') ?>
        </p>
      </div>
      <div>
        <h4>Ship To</h4>
        <p>
          <?= htmlspecialchars($order['ship_line1']) ?><br>
          <?php if (!empty($order['ship_line2'])): ?><?= htmlspecialchars($order['ship_line2']) ?><br><?php endif; ?>
          <?= htmlspecialchars($order['ship_city']) ?>, <?= htmlspecialchars($order['ship_state']) ?><br>
          PIN: <?= htmlspecialchars($order['ship_pincode']) ?>
        </p>
      </div>
    </div>

    <table>
      <thead>
        <tr>
          <th style="width:50%">Item</th>
          <th class="right">Price</th>
          <th class="right">Qty</th>
          <th class="right">Total</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $i): ?>
          <tr>
            <td><?= htmlspecialchars($i['product_name']) ?></td>
            <td class="right"><?= inr($i['price']) ?></td>
            <td class="right"><?= (int) $i['qty'] ?></td>
            <td class="right bold"><?= inr($i['line_total']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="totals">
      <div class="l"><span>Subtotal</span><span><?= inr($order['subtotal']) ?></span></div>
      <div class="l"><span>Shipping</span><span><?= inr($order['shipping']) ?></span></div>
      <?php if ((float) $order['discount'] > 0): ?>
        <div class="l" style="color:#059669"><span>Discount</span><span>− <?= inr($order['discount']) ?></span></div>
      <?php endif; ?>
      <div class="l grand"><span>Grand Total</span><span><?= inr($order['total']) ?></span></div>
    </div>

    <div style="clear:both;margin-top:30px;font-size:13px;color:#6B7280;line-height:1.7">
      <b style="color:#351B2F">Payment:</b>
      <?= htmlspecialchars(strtoupper($order['payment_method'] ?: 'N/A')) ?>
      <?php if (!empty($order['payment_ref'])): ?>
        (Ref: <?= htmlspecialchars($order['payment_ref']) ?>)
      <?php endif; ?>
      <?php if (!empty($order['coupon_code'])): ?>
        <br><b style="color:#351B2F">Coupon Applied:</b> <?= htmlspecialchars($order['coupon_code']) ?>
      <?php endif; ?>
    </div>

  </div>

  <div class="inv-foot">
    Thank you for shopping with <b><?= htmlspecialchars($siteName) ?></b><br>
    <?= htmlspecialchars($siteEmail) ?> · Divya Aastha
  </div>

</div>

</body>
</html>