<?php
require_once __DIR__ . '/includes/auth.php';

$adminPageTitle = 'Dashboard';
$adminPageSub   = "Welcome back, here's what's happening today";

// ============================================================
// 1. CORE STATS (Real DB Data)
// ============================================================
$stats = [
    'orders'           => (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
    'bookings'         => (int) $pdo->query('SELECT COUNT(*) FROM puja_bookings')->fetchColumn(),
    'products'         => (int) $pdo->query('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn(),
    'users'            => (int) $pdo->query('SELECT COUNT(*) FROM users WHERE role = "devotee"')->fetchColumn(),
    'revenue'          => (float) $pdo->query('SELECT COALESCE(SUM(total), 0) FROM orders WHERE status IN ("paid","packed","shipped","delivered")')->fetchColumn(),
    'pending_orders'   => (int) $pdo->query('SELECT COUNT(*) FROM orders WHERE status = "pending"')->fetchColumn(),
    'temples'          => (int) $pdo->query('SELECT COUNT(*) FROM temples WHERE is_active = 1')->fetchColumn(),
    'pujas'            => (int) $pdo->query('SELECT COUNT(*) FROM pujas WHERE is_active = 1')->fetchColumn(),
    'bhets'            => (int) $pdo->query('SELECT COUNT(*) FROM bhets WHERE is_active = 1')->fetchColumn(),
    'articles'         => (int) $pdo->query('SELECT COUNT(*) FROM articles WHERE is_active = 1')->fetchColumn(),
    'festivals'        => (int) $pdo->query('SELECT COUNT(*) FROM festivals WHERE is_active = 1')->fetchColumn(),
    'pending_bookings' => (int) $pdo->query('SELECT COUNT(*) FROM puja_bookings WHERE status = "pending"')->fetchColumn(),
];

// ============================================================
// 2. PUJA BOOKING STATUS
// ============================================================
$bsRaw = $pdo->query('SELECT status, COUNT(*) AS c FROM puja_bookings GROUP BY status')
             ->fetchAll(PDO::FETCH_KEY_PAIR);
$bookingStatusData = [
    'pending'   => (int)($bsRaw['pending']   ?? 0),
    'confirmed' => (int)($bsRaw['confirmed'] ?? 0),
    'completed' => (int)($bsRaw['completed'] ?? 0),
    'cancelled' => (int)($bsRaw['cancelled'] ?? 0),
];

// ============================================================
// 3. REVENUE TREND
// ============================================================
$revenueRows = $pdo->query("
    SELECT DATE(created_at) AS d, SUM(total) AS s
    FROM orders
    WHERE status IN ('paid','packed','shipped','delivered')
      AND created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
    GROUP BY DATE(created_at)
")->fetchAll(PDO::FETCH_KEY_PAIR);

$revenueLabels = [];
$revenueValues = [];
for ($i = 29; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $revenueLabels[] = date('d M', strtotime($d));
    $revenueValues[] = (float)($revenueRows[$d] ?? 0);
}

// ============================================================
// 4. PUJA BOOKING TREND
// ============================================================
$btRows = $pdo->query("
    SELECT DATE(created_at) AS d, COUNT(*) AS c
    FROM puja_bookings
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
    GROUP BY DATE(created_at)
")->fetchAll(PDO::FETCH_KEY_PAIR);

$bookingTrendLabels = [];
$bookingTrendValues = [];
for ($i = 29; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $bookingTrendLabels[] = date('d M', strtotime($d));
    $bookingTrendValues[] = (int)($btRows[$d] ?? 0);
}

// ============================================================
// 5. TOP PUJAS
// ============================================================
$topPujas = $pdo->query("
    SELECT p.name, COUNT(b.id) AS c
    FROM puja_bookings b
    JOIN pujas p ON p.id = b.puja_id
    GROUP BY b.puja_id
    ORDER BY c DESC
    LIMIT 5
")->fetchAll();

// ============================================================
// 6. TOP TEMPLES
// ============================================================
$topTemples = $pdo->query("
    SELECT t.name, COUNT(b.id) AS c
    FROM puja_bookings b
    JOIN temples t ON t.id = b.temple_id
    WHERE b.temple_id IS NOT NULL
    GROUP BY b.temple_id
    ORDER BY c DESC
    LIMIT 5
")->fetchAll();

// ============================================================
// 7. RECENT ACTIVITY
// ============================================================
$recentOrders = $pdo->query(
    'SELECT o.id, o.order_number, o.total, o.status, o.created_at, u.name AS user_name
     FROM orders o LEFT JOIN users u ON u.id = o.user_id
     ORDER BY o.created_at DESC LIMIT 5'
)->fetchAll();

$recentBookings = $pdo->query(
    'SELECT b.id, b.booking_ref, b.amount, b.status, b.booking_date, b.devotee_name, p.name AS puja_name
     FROM puja_bookings b LEFT JOIN pujas p ON p.id = b.puja_id
     ORDER BY b.created_at DESC LIMIT 5'
)->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<!-- =========================================================
     DASHBOARD STYLES (Compact)
     ========================================================= -->
<style>
:root {
    --da-plum: #5c2e2e;
    --da-plum-dark: #3f1f1f;
    --da-maroon: #8b3a3a;
    --da-terracotta: #c26b4a;
    --da-gold: #b8860b;
    --da-cream: #faf6f1;
    --da-warm: #f3ede5;
    --da-text: #2c1e1e;
    --da-muted: #7a6a63;
    --da-border: #e8ddd4;
    --da-shadow: 0 4px 16px rgba(92, 46, 46, 0.06);
    --da-shadow-hover: 0 8px 24px rgba(92, 46, 46, 0.10);
    --da-radius: 16px;
    --da-radius-sm: 10px;
}

/* ---------- Page header ---------- */
.da-dash-header {
    display: flex; flex-wrap: wrap; justify-content: space-between;
    align-items: flex-start; gap: 14px; margin-bottom: 20px;
}
.da-dash-title {
    font-size: 1.5rem; font-weight: 700; color: var(--da-plum-dark);
    margin: 0 0 2px 0; letter-spacing: -0.02em;
}
.da-dash-sub { font-size: 0.85rem; color: var(--da-muted); margin: 0; }
.da-dash-actions { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }

/* ---------- Buttons ---------- */
.da-btn {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 0.8rem; font-weight: 600;
    padding: 7px 14px; border-radius: 9px;
    text-decoration: none; cursor: pointer; border: 1px solid transparent;
    transition: all 0.2s ease; line-height: 1.2;
}
.da-btn-primary { background: var(--da-plum); border-color: var(--da-plum); color: #fff; }
.da-btn-primary:hover { background: var(--da-plum-dark); border-color: var(--da-plum-dark); color: #fff; text-decoration: none; }
.da-btn-outline { background: transparent; border-color: var(--da-border); color: var(--da-text); }
.da-btn-outline:hover { border-color: var(--da-terracotta); color: var(--da-terracotta); text-decoration: none; }

/* ---------- Premium Card ---------- */
.da-card {
    background: #fff; border: 1px solid var(--da-border);
    border-radius: var(--da-radius); box-shadow: var(--da-shadow);
    transition: box-shadow .2s ease, transform .2s ease;
    overflow: hidden;
}
.da-card:hover { box-shadow: var(--da-shadow-hover); }

/* Card header (compact) */
.da-card-head {
    padding: 14px 18px 0 18px;
    display: flex; flex-wrap: wrap; justify-content: space-between;
    align-items: flex-start; gap: 10px;
}
.da-card-head h3 {
    font-size: 0.98rem; font-weight: 650; color: var(--da-plum-dark);
    margin: 0 0 1px 0;
}
.da-card-head p { font-size: 0.76rem; color: var(--da-muted); margin: 0; }
.da-card-body { padding: 12px 18px 16px 18px; }

/* ---------- Stat Cards (compact) ---------- */
.da-stat {
    padding: 16px 18px; display: flex; flex-direction: column;
    gap: 4px; min-height: 110px;
}
.da-stat-top { display: flex; justify-content: space-between; align-items: center; }
.da-stat-label {
    font-size: 0.68rem; font-weight: 600; text-transform: uppercase;
    letter-spacing: 0.08em; color: var(--da-muted);
}
.da-stat-icon {
    display: inline-flex; align-items: center; justify-content: center;
    width: 34px; height: 34px; border-radius: 50%;
    background: rgba(92, 46, 46, 0.08); color: var(--da-plum);
    font-size: 0.95rem;
}
.da-stat-icon.gold { background: rgba(184,134,11,.10); color: var(--da-gold); }
.da-stat-icon.plum { background: rgba(139,58,58,.10); color: var(--da-maroon); }
.da-stat-icon.sage { background: rgba(107,142,107,.10); color: #5a7a5a; }
.da-stat-value {
    font-size: 1.65rem; font-weight: 700; color: var(--da-plum-dark);
    line-height: 1.15; letter-spacing: -0.02em;
}
.da-stat-foot { font-size: 0.76rem; color: var(--da-muted); }

/* ---------- Chart Containers (compact & balanced) ---------- */
.da-chart-lg { position: relative; height: 240px; width: 100%; }  /* Revenue */
.da-chart-md { position: relative; height: 200px; width: 100%; }  /* Booking Trend */
.da-chart-dn { position: relative; height: 160px; width: 100%; }  /* Doughnut */
.da-chart-hz { position: relative; min-height: 120px; width: 100%; }  /* Horizontal bars – JS adjusts */

/* ---------- Doughnut legend ---------- */
.da-legend {
    display: flex; flex-wrap: wrap; gap: 10px 14px;
    justify-content: center; margin-top: 10px;
}
.da-legend-item {
    display: flex; align-items: center; gap: 5px;
    font-size: .76rem; color: var(--da-muted);
}
.da-legend-item b { color: var(--da-text); margin-left: 2px; }
.da-legend-dot { width: 9px; height: 9px; border-radius: 50%; display: inline-block; }
.da-dot-pending   { background: #e6a817; }
.da-dot-confirmed { background: #2e7d32; }
.da-dot-completed { background: #1565c0; }
.da-dot-cancelled { background: #c62828; }

/* ---------- Content Overview List ---------- */
.da-overview { list-style: none; padding: 0; margin: 0; }
.da-overview li {
    display: flex; justify-content: space-between; align-items: center;
    font-size: .85rem; color: var(--da-text);
    padding: 8px 0; border-bottom: 1px solid var(--da-warm);
}
.da-overview li:last-child { border-bottom: none; }
.da-overview li b { font-size: .98rem; color: var(--da-plum-dark); }

/* ---------- Quick Actions (compact) ---------- */
.da-qa-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(145px, 1fr));
    gap: 10px;
}
.da-qa {
    display: flex; flex-direction: column; gap: 3px;
    padding: 12px 12px; border: 1px solid var(--da-border);
    border-radius: var(--da-radius-sm); background: var(--da-cream);
    text-decoration: none; color: var(--da-text);
    transition: all .2s ease;
}
.da-qa:hover {
    border-color: var(--da-terracotta); background: #fff;
    box-shadow: 0 3px 10px rgba(194,107,74,.10);
    transform: translateY(-1px); text-decoration: none; color: var(--da-text);
}
.da-qa i { font-size: 1.05rem; color: var(--da-terracotta); margin-bottom: 1px; }
.da-qa span { font-weight: 600; font-size: .82rem; }
.da-qa small { font-size: .7rem; color: var(--da-muted); line-height: 1.25; }

/* ---------- Empty State ---------- */
.da-empty { text-align: center; padding: 26px 16px; }
.da-empty .da-om { font-size: 1.6rem; color: var(--da-muted); margin-bottom: 8px; opacity: .55; }
.da-empty h3 { font-size: .9rem; font-weight: 600; color: var(--da-text); margin: 0 0 4px 0; }
.da-empty p { font-size: .78rem; color: var(--da-muted); margin: 0; }

/* ---------- Table tweaks ---------- */
.da-truncate {
    max-width: 200px; white-space: nowrap;
    overflow: hidden; text-overflow: ellipsis;
}

/* ---------- Grid: prevent unwanted stretching ---------- */
.da-row-tight > [class*="col-"] { display: flex; }
.da-row-tight > [class*="col-"] > .da-card { width: 100%; }

/* ---------- Responsive ---------- */
@media (max-width: 991.98px) {
    .da-dash-header { flex-direction: column; align-items: flex-start; }
    .da-chart-lg { height: 220px; }
    .da-chart-md { height: 190px; }
}
@media (max-width: 767.98px) {
    .da-dash-title { font-size: 1.3rem; }
    .da-stat { padding: 14px; min-height: 100px; }
    .da-stat-value { font-size: 1.4rem; }
    .da-chart-lg { height: 210px; }
    .da-chart-md { height: 180px; }
    .da-chart-dn { height: 150px; }
    .da-qa-grid { grid-template-columns: 1fr 1fr; }
    .da-card-body { padding: 10px 14px 14px 14px; }
    .da-card-head { padding: 12px 14px 0 14px; }
    .da-truncate { max-width: 110px; }
}
@media (max-width: 575.98px) {
    .da-qa-grid { grid-template-columns: 1fr; }
    .da-dash-actions { flex-direction: column; width: 100%; }
    .da-dash-actions .da-btn { width: 100%; justify-content: center; }
}
</style>

<!-- =========================================================
     HEADER
     ========================================================= -->
<div class="da-dash-header">
    <div>
        <h1 class="da-dash-title">Dashboard</h1>
        <p class="da-dash-sub">Welcome back, here's what's happening today</p>
    </div>
    <div class="da-dash-actions">
        <a href="<?= url('/') ?>" target="_blank" class="da-btn da-btn-outline">
            <i class="bi bi-box-arrow-up-right"></i> View Site
        </a>
        <a href="<?= url('admin/modules/pujas/create.php') ?>" class="da-btn da-btn-primary">
            <i class="bi bi-plus-circle"></i> Add Puja
        </a>
        <a href="<?= url('admin/modules/temples/create.php') ?>" class="da-btn da-btn-primary">
            <i class="bi bi-plus-circle"></i> Add Temple
        </a>
    </div>
</div>

<!-- =========================================================
     STAT CARDS
     ========================================================= -->
<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-6">
        <div class="da-card da-stat">
            <div class="da-stat-top">
                <span class="da-stat-label">Revenue</span>
                <span class="da-stat-icon"><i class="bi bi-currency-rupee"></i></span>
            </div>
            <div class="da-stat-value"><?= inr($stats['revenue']) ?></div>
            <div class="da-stat-foot">From paid &amp; delivered orders</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="da-card da-stat">
            <div class="da-stat-top">
                <span class="da-stat-label">Total Orders</span>
                <span class="da-stat-icon gold"><i class="bi bi-box-seam"></i></span>
            </div>
            <div class="da-stat-value"><?= $stats['orders'] ?></div>
            <div class="da-stat-foot"><?= $stats['pending_orders'] ?> pending</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="da-card da-stat">
            <div class="da-stat-top">
                <span class="da-stat-label">Puja Bookings</span>
                <span class="da-stat-icon plum"><i class="bi bi-calendar-check"></i></span>
            </div>
            <div class="da-stat-value"><?= $stats['bookings'] ?></div>
            <div class="da-stat-foot"><?= $stats['pending_bookings'] ?> pending</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="da-card da-stat">
            <div class="da-stat-top">
                <span class="da-stat-label">Devotees</span>
                <span class="da-stat-icon sage"><i class="bi bi-people"></i></span>
            </div>
            <div class="da-stat-value"><?= $stats['users'] ?></div>
            <div class="da-stat-foot"><?= $stats['temples'] ?> temples · <?= $stats['pujas'] ?> pujas</div>
        </div>
    </div>
</div>

<!-- =========================================================
     REVENUE OVERVIEW
     ========================================================= -->
<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="da-card">
            <div class="da-card-head">
                <div>
                    <h3>Revenue Overview</h3>
                    <p>Revenue from paid &amp; delivered orders — last 30 days.</p>
                </div>
            </div>
            <div class="da-card-body">
                <?php if (array_sum($revenueValues) == 0): ?>
                    <div class="da-empty">
                        <div class="da-om"><i class="bi bi-graph-up"></i></div>
                        <h3>Not enough revenue data yet</h3>
                        <p>Revenue analytics will appear here as more orders are recorded.</p>
                    </div>
                <?php else: ?>
                    <div class="da-chart-lg"><canvas id="daRevenueChart"></canvas></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================
     BOOKING STATUS + BOOKING TREND
     ========================================================= -->
<div class="row g-3 mb-3 da-row-tight">
    <div class="col-lg-6">
        <div class="da-card">
            <div class="da-card-head">
                <div>
                    <h3>Puja Booking Status</h3>
                    <p>Distribution of all ritual bookings.</p>
                </div>
            </div>
            <div class="da-card-body">
                <?php if ($stats['bookings'] == 0): ?>
                    <div class="da-empty">
                        <div class="da-om"><i class="bi bi-pie-chart"></i></div>
                        <h3>No bookings yet</h3>
                        <p>New ritual bookings will appear here.</p>
                    </div>
                <?php else: ?>
                    <div class="da-chart-dn"><canvas id="daStatusChart"></canvas></div>
                    <div class="da-legend">
                        <div class="da-legend-item"><span class="da-legend-dot da-dot-pending"></span> Pending <b><?= $bookingStatusData['pending'] ?></b></div>
                        <div class="da-legend-item"><span class="da-legend-dot da-dot-confirmed"></span> Confirmed <b><?= $bookingStatusData['confirmed'] ?></b></div>
                        <div class="da-legend-item"><span class="da-legend-dot da-dot-completed"></span> Completed <b><?= $bookingStatusData['completed'] ?></b></div>
                        <div class="da-legend-item"><span class="da-legend-dot da-dot-cancelled"></span> Cancelled <b><?= $bookingStatusData['cancelled'] ?></b></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="da-card">
            <div class="da-card-head">
                <div>
                    <h3>Puja Booking Trend</h3>
                    <p>Daily bookings over the last 30 days.</p>
                </div>
            </div>
            <div class="da-card-body">
                <?php if (array_sum($bookingTrendValues) == 0): ?>
                    <div class="da-empty">
                        <div class="da-om"><i class="bi bi-graph-up"></i></div>
                        <h3>No booking trend data yet</h3>
                        <p>Booking trends will appear as devotees book pujas.</p>
                    </div>
                <?php else: ?>
                    <div class="da-chart-md"><canvas id="daBookingTrendChart"></canvas></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================
     TOP PUJAS + TOP TEMPLES
     ========================================================= -->
<div class="row g-3 mb-3 da-row-tight">
    <div class="col-lg-6">
        <div class="da-card">
            <div class="da-card-head">
                <div>
                    <h3>Most Booked Pujas</h3>
                    <p>Top 5 pujas by booking volume.</p>
                </div>
            </div>
            <div class="da-card-body">
                <?php if (empty($topPujas)): ?>
                    <div class="da-empty">
                        <div class="da-om"><i class="bi bi-flower1"></i></div>
                        <h3>No puja bookings yet</h3>
                        <p>Popular pujas will be ranked here.</p>
                    </div>
                <?php else: ?>
                    <div class="da-chart-hz" id="daTopPujasWrap"><canvas id="daTopPujasChart"></canvas></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="da-card">
            <div class="da-card-head">
                <div>
                    <h3>Most Booked Temples</h3>
                    <p>Top 5 temples by booking volume.</p>
                </div>
            </div>
            <div class="da-card-body">
                <?php if (empty($topTemples)): ?>
                    <div class="da-empty">
                        <div class="da-om"><i class="bi bi-building"></i></div>
                        <h3>No temple bookings yet</h3>
                        <p>Popular temples will be ranked here.</p>
                    </div>
                <?php else: ?>
                    <div class="da-chart-hz" id="daTopTemplesWrap"><canvas id="daTopTemplesChart"></canvas></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================
     RECENT ACTIVITY
     ========================================================= -->
<div class="row g-3 mb-3 da-row-tight">
    <div class="col-lg-7">
        <div class="da-card">
            <div class="da-card-head">
                <div>
                    <h3>Recent Orders</h3>
                    <p>Latest product purchases</p>
                </div>
                <a href="<?= url('admin/modules/orders/index.php') ?>" class="da-btn da-btn-outline">
                    View All <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <?php if (empty($recentOrders)): ?>
                <div class="da-empty">
                    <div class="da-om"><i class="bi bi-box"></i></div>
                    <h3>No orders yet</h3>
                    <p>Orders will appear here once devotees start purchasing.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Devotee</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $o): ?>
                                <tr>
                                    <td><b><?= e($o['order_number']) ?></b></td>
                                    <td><?= e($o['user_name'] ?? 'Guest') ?></td>
                                    <td><b><?= inr($o['total']) ?></b></td>
                                    <td><span class="status-badge status-<?= e($o['status']) ?>"><?= e($o['status']) ?></span></td>
                                    <td><?= date('d M', strtotime($o['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="da-card">
            <div class="da-card-head">
                <div>
                    <h3>Recent Puja Bookings</h3>
                    <p>Latest ritual bookings</p>
                </div>
                <a href="<?= url('admin/modules/bookings/index.php') ?>" class="da-btn da-btn-outline">
                    View All <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <?php if (empty($recentBookings)): ?>
                <div class="da-empty">
                    <div class="da-om"><i class="bi bi-flower1"></i></div>
                    <h3>No bookings yet</h3>
                    <p>Puja bookings will appear here.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Ref</th>
                                <th>Puja</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentBookings as $b): ?>
                                <tr>
                                    <td><b><?= e($b['booking_ref']) ?></b></td>
                                    <td class="da-truncate" title="<?= e($b['puja_name'] ?? '—') ?>">
                                        <?= e($b['puja_name'] ?? '—') ?>
                                    </td>
                                    <td><b><?= inr($b['amount']) ?></b></td>
                                    <td><span class="status-badge status-<?= e($b['status']) ?>"><?= e($b['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- =========================================================
     CONTENT OVERVIEW + QUICK ACTIONS
     ========================================================= -->
<div class="row g-3 mb-3 da-row-tight">
    <div class="col-lg-5">
        <div class="da-card">
            <div class="da-card-head">
                <div>
                    <h3>Content Overview</h3>
                    <p>Active content across the platform.</p>
                </div>
            </div>
            <div class="da-card-body">
                <ul class="da-overview">
                    <li><span>Temples</span> <b><?= $stats['temples'] ?></b></li>
                    <li><span>Active Pujas</span> <b><?= $stats['pujas'] ?></b></li>
                    <li><span>Bhets</span> <b><?= $stats['bhets'] ?></b></li>
                    <li><span>Articles</span> <b><?= $stats['articles'] ?></b></li>
                    <li><span>Festivals</span> <b><?= $stats['festivals'] ?></b></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="da-card">
            <div class="da-card-head">
                <div>
                    <h3>Quick Actions</h3>
                    <p>Common admin tasks</p>
                </div>
            </div>
            <div class="da-card-body">
                <div class="da-qa-grid">
                    <a href="<?= url('admin/modules/temples/create.php') ?>" class="da-qa">
                        <i class="bi bi-building-add"></i>
                        <span>Add Temple</span>
                        <small>Add a new sacred destination</small>
                    </a>
                    <a href="<?= url('admin/modules/pujas/create.php') ?>" class="da-qa">
                        <i class="bi bi-flower1"></i>
                        <span>Add Puja</span>
                        <small>Create a new puja offering</small>
                    </a>
                    <a href="<?= url('admin/modules/bhets/create.php') ?>" class="da-qa">
                        <i class="bi bi-gift"></i>
                        <span>Add Bhet</span>
                        <small>Create a new bhet seva</small>
                    </a>
                    <a href="<?= url('admin/modules/articles/create.php') ?>" class="da-qa">
                        <i class="bi bi-journal-plus"></i>
                        <span>Add Article</span>
                        <small>Publish new spiritual content</small>
                    </a>
                    <a href="<?= url('admin/modules/festivals/create.php') ?>" class="da-qa">
                        <i class="bi bi-calendar-event"></i>
                        <span>Add Festival</span>
                        <small>Manage upcoming festivals</small>
                    </a>
                    <a href="<?= url('admin/modules/products/create.php') ?>" class="da-qa">
                        <i class="bi bi-box-seam"></i>
                        <span>Add Product</span>
                        <small>Add a new product listing</small>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================
     CHART.JS + INLINE CHARTS
     ========================================================= -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    'use strict';

    var PLUM = '#5c2e2e';
    var PLUM_DARK = '#3f1f1f';
    var TERRACOTTA = '#c26b4a';
    var MUTED = '#7a6a63';
    var BORDER = '#e8ddd4';
    var STATUS_COLORS = ['#e6a817', '#2e7d32', '#1565c0', '#c62828'];

    // ============================================================
    // 1. Revenue (line)
    // ============================================================
    var revCanvas = document.getElementById('daRevenueChart');
    if (revCanvas) {
        new Chart(revCanvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: <?= json_encode($revenueLabels) ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?= json_encode($revenueValues) ?>,
                    borderColor: PLUM,
                    backgroundColor: 'rgba(92,46,46,0.08)',
                    borderWidth: 2,
                    pointBackgroundColor: PLUM,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    fill: true,
                    tension: 0.35
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: PLUM_DARK, titleColor: '#fff',
                        bodyColor: '#fff', cornerRadius: 8, padding: 10,
                        callbacks: {
                            label: function (c) {
                                return '₹' + Number(c.parsed.y).toLocaleString('en-IN');
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: MUTED, font: { size: 10 }, maxTicksLimit: 10 }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: BORDER, drawBorder: false },
                        ticks: {
                            color: MUTED, font: { size: 10 }, maxTicksLimit: 5,
                            callback: function (v) { return '₹' + Number(v).toLocaleString('en-IN'); }
                        }
                    }
                },
                interaction: { intersect: false, mode: 'index' }
            }
        });
    }

    // ============================================================
    // 2. Booking Status (doughnut)
    // ============================================================
    var stCanvas = document.getElementById('daStatusChart');
    if (stCanvas) {
        new Chart(stCanvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Confirmed', 'Completed', 'Cancelled'],
                datasets: [{
                    data: [
                        <?= (int)$bookingStatusData['pending'] ?>,
                        <?= (int)$bookingStatusData['confirmed'] ?>,
                        <?= (int)$bookingStatusData['completed'] ?>,
                        <?= (int)$bookingStatusData['cancelled'] ?>
                    ],
                    backgroundColor: STATUS_COLORS,
                    borderWidth: 2, borderColor: '#fff', hoverOffset: 4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '62%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: PLUM_DARK, titleColor: '#fff',
                        bodyColor: '#fff', cornerRadius: 8, padding: 10,
                        callbacks: {
                            label: function (c) {
                                var total = c.dataset.data.reduce(function (a, b) { return a + b; }, 0);
                                var pct = total > 0 ? ((c.parsed / total) * 100).toFixed(1) : 0;
                                return c.label + ': ' + c.parsed + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    // ============================================================
    // 3. Booking Trend (line)
    // ============================================================
    var btCanvas = document.getElementById('daBookingTrendChart');
    if (btCanvas) {
        new Chart(btCanvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: <?= json_encode($bookingTrendLabels) ?>,
                datasets: [{
                    label: 'Bookings',
                    data: <?= json_encode($bookingTrendValues) ?>,
                    borderColor: TERRACOTTA,
                    backgroundColor: 'rgba(194,107,74,0.08)',
                    borderWidth: 2,
                    pointBackgroundColor: TERRACOTTA,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 2,
                    pointHoverRadius: 5,
                    fill: true,
                    tension: 0.35
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: PLUM_DARK, titleColor: '#fff',
                        bodyColor: '#fff', cornerRadius: 8, padding: 10
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: MUTED, font: { size: 10 }, maxTicksLimit: 10 }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: BORDER, drawBorder: false },
                        ticks: { color: MUTED, font: { size: 10 }, precision: 0, maxTicksLimit: 5 }
                    }
                },
                interaction: { intersect: false, mode: 'index' }
            }
        });
    }

    // ============================================================
    // 4. Top Pujas (adaptive-height horizontal bar)
    // ============================================================
    var tpCanvas = document.getElementById('daTopPujasChart');
    if (tpCanvas) {
        var tpData = <?= json_encode($topPujas) ?>;
        // Adaptive height: ~44px per row + 40px padding, min 130px, max 240px
        var tpH = Math.min(240, Math.max(130, tpData.length * 44 + 40));
        document.getElementById('daTopPujasWrap').style.height = tpH + 'px';

        new Chart(tpCanvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: tpData.map(function (x) {
                    return x.name.length > 32 ? x.name.substring(0, 30) + '…' : x.name;
                }),
                datasets: [{
                    label: 'Bookings',
                    data: tpData.map(function (x) { return parseInt(x.c, 10); }),
                    backgroundColor: PLUM,
                    borderRadius: 5,
                    barPercentage: 0.55,
                    categoryPercentage: 0.75
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: PLUM_DARK, titleColor: '#fff',
                        bodyColor: '#fff', cornerRadius: 8, padding: 10,
                        callbacks: {
                            title: function (c) { return tpData[c[0].dataIndex].name; }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { color: BORDER, drawBorder: false },
                        ticks: { color: MUTED, font: { size: 10 }, precision: 0, maxTicksLimit: 5 }
                    },
                    y: {
                        grid: { display: false },
                        ticks: { color: '#2c1e1e', font: { size: 10 } }
                    }
                }
            }
        });
    }

    // ============================================================
    // 5. Top Temples (adaptive-height horizontal bar)
    // ============================================================
    var ttCanvas = document.getElementById('daTopTemplesChart');
    if (ttCanvas) {
        var ttData = <?= json_encode($topTemples) ?>;
        var ttH = Math.min(240, Math.max(130, ttData.length * 44 + 40));
        document.getElementById('daTopTemplesWrap').style.height = ttH + 'px';

        new Chart(ttCanvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: ttData.map(function (x) {
                    return x.name.length > 32 ? x.name.substring(0, 30) + '…' : x.name;
                }),
                datasets: [{
                    label: 'Bookings',
                    data: ttData.map(function (x) { return parseInt(x.c, 10); }),
                    backgroundColor: TERRACOTTA,
                    borderRadius: 5,
                    barPercentage: 0.55,
                    categoryPercentage: 0.75
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: PLUM_DARK, titleColor: '#fff',
                        bodyColor: '#fff', cornerRadius: 8, padding: 10,
                        callbacks: {
                            title: function (c) { return ttData[c[0].dataIndex].name; }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { color: BORDER, drawBorder: false },
                        ticks: { color: MUTED, font: { size: 10 }, precision: 0, maxTicksLimit: 5 }
                    },
                    y: {
                        grid: { display: false },
                        ticks: { color: '#2c1e1e', font: { size: 10 } }
                    }
                }
            }
        });
    }
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>