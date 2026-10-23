<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$adminPageTitle = 'Temples';
$adminPageSub   = 'Manage all temples';

/* ------------------------------------------------------------------
 |  POST-only destructive actions (delete + toggle)
 * ------------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify(post('_csrf', ''));
    $id     = (int) post('id', 0);
    $action = (string) post('action', '');

    if ($id > 0 && in_array($action, ['delete', 'toggle'], true)) {
        try {
            if ($action === 'delete') {
                $p = $pdo->prepare('SELECT COUNT(*) FROM pujas WHERE temple_id = ?');
                $p->execute([$id]);
                $pujas = (int) $p->fetchColumn();

                $b = $pdo->prepare('SELECT COUNT(*) FROM puja_bookings WHERE temple_id = ?');
                $b->execute([$id]);
                $bookings = (int) $b->fetchColumn();

                if ($pujas > 0 || $bookings > 0) {
                    $pdo->prepare('UPDATE temples SET is_active = 0 WHERE id = ?')->execute([$id]);
                    flash_set('info', "Temple has {$pujas} puja(s) and {$bookings} booking(s) — deactivated instead of deleted.");
                } else {
                    $pdo->prepare('DELETE FROM temples WHERE id = ?')->execute([$id]);
                    flash_set('success', 'Temple deleted.');
                }
            } else {
                $pdo->prepare('UPDATE temples SET is_active = 1 - is_active WHERE id = ?')
                    ->execute([$id]);
                flash_set('success', 'Status updated.');
            }
        } catch (PDOException $e) {
            error_log('[temples] ' . $e->getMessage());
            flash_set('error', 'Action failed. Please try again.');
        }
    }
    redirect('admin/modules/temples/index.php');
}

/* ------------------------------------------------------------------
 |  Helper: format time + closed-days label (safe if column empty)
 * ------------------------------------------------------------------ */
if (!function_exists('tp_time_fmt')) {
    function tp_time_fmt(?string $hhmm): string {
        if (!$hhmm) return '';
        $t = strtotime($hhmm);
        return $t ? date('g:i A', $t) : $hhmm;
    }
}
if (!function_exists('tp_closed_label')) {
    function tp_closed_label(?string $stored): string {
        if (!$stored) return 'Open daily';
        $stored = strtolower(trim($stored));
        $parts  = preg_split('/[\s,|;]+/', $stored, -1, PREG_SPLIT_NO_EMPTY);
        $map = [
            'mon'=>'Mon','monday'=>'Mon','tue'=>'Tue','tuesday'=>'Tue','tues'=>'Tue',
            'wed'=>'Wed','wednesday'=>'Wed','thu'=>'Thu','thursday'=>'Thu','thur'=>'Thu','thurs'=>'Thu',
            'fri'=>'Fri','friday'=>'Fri','sat'=>'Sat','saturday'=>'Sat','sun'=>'Sun','sunday'=>'Sun',
        ];
        $names = [];
        foreach ($parts as $p) {
            $k = $map[$p] ?? ucfirst(substr($p, 0, 3));
            if (!in_array($k, $names, true)) $names[] = $k;
        }
        if (!$names) return 'Open daily';
        if (count($names) === 1) return 'Closed: ' . $names[0];
        if (count($names) === 2) return 'Closed: ' . $names[0] . ' & ' . $names[1];
        return 'Closed: ' . implode(', ', $names);
    }
}

/* ------------------------------------------------------------------
 |  KPI stats
 * ------------------------------------------------------------------ */
$stats = [
    'total'    => (int) $pdo->query('SELECT COUNT(*) FROM temples')->fetchColumn(),
    'active'   => (int) $pdo->query('SELECT COUNT(*) FROM temples WHERE is_active = 1')->fetchColumn(),
    'featured' => (int) $pdo->query('SELECT COUNT(*) FROM temples WHERE is_featured = 1')->fetchColumn(),
    'dark'     => (int) $pdo->query('SELECT COUNT(*) FROM temples WHERE is_dark_featured = 1')->fetchColumn(),
];

/* ------------------------------------------------------------------
 |  Fetch all temples — includes new closure columns
 * ------------------------------------------------------------------ */
$temples = $pdo->query(
    'SELECT id, name, slug, type, city, state, image_url,
            closed_days, open_time, close_time, closure_note,
            is_featured, is_dark_featured, sort_order, is_active
     FROM temples
     ORDER BY sort_order ASC, id DESC'
)->fetchAll();

require dirname(__DIR__, 2) . '/includes/header.php';
?>

<style>
/* ============================================================
   TEMPLES INDEX — stat cards + tabs (DataTables handles table)
   ============================================================ */
.tp-wrap{margin:-8px 0 0}

.tp-stats{
  display:grid;grid-template-columns:repeat(4,1fr);
  gap:20px;margin-bottom:22px;
}
@media (max-width:1100px){.tp-stats{grid-template-columns:repeat(2,1fr)}}
@media (max-width:600px) {.tp-stats{grid-template-columns:1fr}}

.tp-stat{
  background:#fff;border-radius:14px;padding:22px;
  box-shadow:0 1px 3px rgba(0,0,0,.06),0 1px 2px rgba(0,0,0,.04);
  border:1px solid #E5E7EB;
  display:flex;justify-content:space-between;align-items:flex-start;gap:16px;
}
.tp-stat-label{
  font-size:11px;font-weight:700;letter-spacing:1.4px;
  text-transform:uppercase;color:#6B7280;margin-bottom:10px;
}
.tp-stat-value{font-size:28px;font-weight:700;color:#1F2937;line-height:1;margin-bottom:8px}
.tp-stat-foot{font-size:12px;color:#6B7280}
.tp-stat-icon{
  width:48px;height:48px;border-radius:10px;
  display:grid;place-items:center;font-size:22px;
  color:#fff;flex-shrink:0;
}
.tp-stat-icon.plum {background:linear-gradient(135deg,#5B3A55,#351B2F)}
.tp-stat-icon.green{background:linear-gradient(135deg,#10B981,#059669)}
.tp-stat-icon.amber{background:linear-gradient(135deg,#F59E0B,#D97706)}
.tp-stat-icon.terra{background:linear-gradient(135deg,#D07B5C,#B76146)}

.tp-card{
  background:#fff;border-radius:14px;
  box-shadow:0 1px 3px rgba(0,0,0,.06),0 1px 2px rgba(0,0,0,.04);
  border:1px solid #E5E7EB;overflow:hidden;
}

/* Tabs row */
.tp-tabs-row{
  display:flex;align-items:center;justify-content:space-between;
  gap:14px;flex-wrap:wrap;padding:16px 22px;
  border-bottom:1px solid #E5E7EB;
}
.tp-tabs{display:flex;gap:8px;flex-wrap:wrap}
.tp-tab{
  display:inline-flex;align-items:center;gap:6px;
  padding:7px 16px;font-size:13px;font-weight:600;
  border-radius:8px;border:1px solid #E5E7EB;background:#fff;color:#1F2937;
  text-decoration:none;transition:all .18s;line-height:1.4;cursor:pointer;
  font-family:inherit;
}
.tp-tab:hover{border-color:#B76146;color:#B76146}
.tp-tab.active{background:#B76146;border-color:#B76146;color:#fff}
.tp-tab .cnt{
  font-size:11px;background:rgba(255,255,255,.25);
  padding:1px 7px;border-radius:99px;
}
.tp-tab:not(.active) .cnt{background:#F3F4F6;color:#6B7280}

.tp-btn-add{
  display:inline-flex;align-items:center;gap:7px;
  height:38px;padding:0 18px;font-size:13px;font-weight:600;
  border-radius:8px;background:#B76146;color:#fff;border:1px solid #B76146;
  text-decoration:none;transition:background .18s;white-space:nowrap;
}
.tp-btn-add:hover{background:#9B4C34;color:#fff}

/* Table */
table.dataTable.tp-table{
  width:100% !important;
  border-collapse:separate !important;
  border-spacing:0;
  margin:0 !important;
}
table.dataTable.tp-table thead th{
  background:#351B2F !important;
  color:#F7EBDD !important;
  text-align:left;
  padding:14px 16px !important;
  font-size:11px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;
  white-space:nowrap;border:none !important;
}
table.dataTable.tp-table thead th:first-child{border-radius:8px 0 0 8px}
table.dataTable.tp-table thead th:last-child {border-radius:0 8px 8px 0}

table.dataTable.tp-table tbody td{
  padding:16px !important;
  border-bottom:1px solid #E5E7EB !important;
  vertical-align:middle;color:#1F2937;background:#fff;
}
table.dataTable.tp-table tbody tr:hover td{background:#FAFAFB}

.tp-thumb{
  width:52px;height:52px;border-radius:10px;
  object-fit:cover;background:#F3F4F6;border:1px solid #E5E7EB;display:block;
}
.tp-name{font-weight:600;color:#1F2937;line-height:1.3}
.tp-slug{
  font-size:11.5px;color:#6B7280;
  font-family:'SF Mono',Consolas,monospace;margin-top:3px;
}

.tp-pill{
  display:inline-flex;align-items:center;gap:5px;
  padding:5px 12px;font-size:11px;font-weight:700;
  letter-spacing:.4px;text-transform:uppercase;
  border-radius:99px;white-space:nowrap;
}
.tp-pill.green{background:#ECFDF5;color:#059669}
.tp-pill.red  {background:#FEF2F2;color:#DC2626}
.tp-pill.amber{background:#FFFBEB;color:#D97706}
.tp-pill.plum {background:#F4EAF0;color:#351B2F}
.tp-pill.blue {background:#EFF6FF;color:#2563EB}

/* ============================================================
   TIMINGS CELL — new column
   ============================================================ */
.tp-timings{
  display:flex;flex-direction:column;gap:5px;
  font-size:12px;line-height:1.45;
  min-width:180px;
}
.tp-timings__row{
  display:flex;align-items:center;gap:6px;
  color:#4B5563;
}
.tp-timings__row i{
  color:#B58A2B;font-size:13px;flex-shrink:0;
}
.tp-timings__closed{
  color:#B73A22;font-weight:600;
}
.tp-timings__closed i{color:#B73A22}
.tp-timings__note{
  color:#8A6A18;
  background:rgba(214,168,62,.12);
  border-left:2px solid #D6A83E;
  padding:3px 8px;
  border-radius:4px;
  font-size:11px;
  font-style:italic;
}
.tp-timings__empty{
  color:#9CA3AF;font-size:12px;font-style:italic;
}
.tp-timings__closed-today{
  display:inline-flex;align-items:center;gap:4px;
  margin-top:2px;
  padding:2px 8px;
  font-size:10px;
  font-weight:800;
  letter-spacing:.5px;
  text-transform:uppercase;
  background:#FEE2E2;color:#B73A22;
  border-radius:99px;
}

.tp-actions{display:flex;gap:6px;flex-wrap:nowrap;align-items:center}
.tp-act{
  display:inline-flex;align-items:center;gap:5px;
  padding:7px 12px;font-size:12px;font-weight:600;
  border-radius:7px;border:1px solid transparent;
  cursor:pointer;text-decoration:none;transition:all .15s;
  line-height:1.3;white-space:nowrap;font-family:inherit;background:#fff;
}
.tp-act-edit{background:#EFF6FF;color:#2563EB;border-color:#BFDBFE}
.tp-act-edit:hover{background:#DBEAFE;color:#1E40AF}
.tp-act-warn{background:#FFFBEB;color:#D97706;border-color:#FDE68A}
.tp-act-warn:hover{background:#FEF3C7}
.tp-act-ok  {background:#ECFDF5;color:#059669;border-color:#A7F3D0}
.tp-act-ok:hover{background:#D1FAE5}
.tp-act-del {background:#FEF2F2;color:#DC2626;border-color:#FECACA}
.tp-act-del:hover{background:#FEE2E2}
</style>

<div class="tp-wrap">

  <!-- ============================================================
       STAT CARDS
       ============================================================ -->
  <div class="tp-stats">
    <div class="tp-stat">
      <div>
        <div class="tp-stat-label">Total Temples</div>
        <div class="tp-stat-value"><?= number_format($stats['total']) ?></div>
        <div class="tp-stat-foot">Sacred destinations</div>
      </div>
      <div class="tp-stat-icon plum"><i class="bi bi-building"></i></div>
    </div>

    <div class="tp-stat">
      <div>
        <div class="tp-stat-label">Active</div>
        <div class="tp-stat-value"><?= number_format($stats['active']) ?></div>
        <div class="tp-stat-foot">Live on the site</div>
      </div>
      <div class="tp-stat-icon green"><i class="bi bi-check2-circle"></i></div>
    </div>

    <div class="tp-stat">
      <div>
        <div class="tp-stat-label">Featured</div>
        <div class="tp-stat-value"><?= number_format($stats['featured']) ?></div>
        <div class="tp-stat-foot">On homepage carousel</div>
      </div>
      <div class="tp-stat-icon amber"><i class="bi bi-star-fill"></i></div>
    </div>

    <div class="tp-stat">
      <div>
        <div class="tp-stat-label">Dark Featured</div>
        <div class="tp-stat-value"><?= number_format($stats['dark']) ?></div>
        <div class="tp-stat-foot">Dark section showcase</div>
      </div>
      <div class="tp-stat-icon terra"><i class="bi bi-moon-stars-fill"></i></div>
    </div>
  </div>

  <!-- ============================================================
       MAIN CARD
       ============================================================ -->
  <div class="tp-card">

    <!-- Tabs + Add -->
    <div class="tp-tabs-row">
      <div class="tp-tabs" id="tpTabs">
        <button type="button" class="tp-tab active" data-filter="all">
          All <span class="cnt"><?= (int) $stats['total'] ?></span>
        </button>
        <button type="button" class="tp-tab" data-filter="active">
          Active <span class="cnt"><?= (int) $stats['active'] ?></span>
        </button>
        <button type="button" class="tp-tab" data-filter="inactive">
          Inactive <span class="cnt"><?= (int) ($stats['total'] - $stats['active']) ?></span>
        </button>
        <button type="button" class="tp-tab" data-filter="featured">
          Featured <span class="cnt"><?= (int) $stats['featured'] ?></span>
        </button>
      </div>

      <a href="<?= url('admin/modules/temples/create.php') ?>" class="tp-btn-add">
        <i class="bi bi-plus-lg"></i> Add Temple
      </a>
    </div>

    <!-- DataTable -->
    <table id="templesTable" class="tp-table">
      <thead>
        <tr>
          <th style="width:70px"></th>
          <th>Temple</th>
          <th>Type</th>
          <th>Location</th>
          <th>Timings</th>
          <th>Featured</th>
          <th>Dark</th>
          <th>Order</th>
          <th>Status</th>
          <th style="width:290px">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($temples as $t): ?>
          <?php
            /* ----------- Resolve image URL ----------- */
            $storedImage = trim((string)($t['image_url'] ?? ''));
            $imageUrl    = '';

            if ($storedImage !== '') {
                if (preg_match('#^https?://#i', $storedImage)) {
                    $imageUrl = $storedImage;
                } else {
                    $storedImage = ltrim($storedImage, '/');
                    if (strpos($storedImage, 'assets/img/') === 0) {
                        $storedImage = substr($storedImage, strlen('assets/img/'));
                    }
                    $marker = 'assets/img/';
                    $pos = strpos($storedImage, $marker);
                    if ($pos !== false) {
                        $storedImage = substr($storedImage, $pos + strlen($marker));
                    }
                    $imageUrl = 'https://divyaaastha.xinkpayroll.co.in/assets/img/'
                              . ltrim($storedImage, '/');
                }
            }

            /* ----------- Timings data ----------- */
            $closedDays   = $t['closed_days']   ?? '';
            $openTime     = $t['open_time']     ?? '';
            $closeTime    = $t['close_time']    ?? '';
            $closureNote  = $t['closure_note']  ?? '';

            $hasTime      = ($openTime !== '' && $closeTime !== '');
            $closedLabel  = tp_closed_label($closedDays);
            $isOpenDaily  = ($closedDays === '' || $closedDays === null);
            $hasAnyData   = $hasTime || !$isOpenDaily || $closureNote !== '';

            /* Is the temple closed today? */
            $closedToday = false;
            if (!$isOpenDaily && $closedDays) {
                $today = strtolower(date('D'));
                $todayKey = substr($today, 0, 3);
                $days = array_map('trim', explode(',', strtolower($closedDays)));
                if (in_array($todayKey, $days, true)) $closedToday = true;
            }
          ?>

          <tr data-status="<?= $t['is_active'] ? 'active' : 'inactive' ?>"
              data-featured="<?= $t['is_featured'] ? '1' : '0' ?>">

            <td>
              <?php if ($imageUrl !== ''): ?>
                <img src="<?= e($imageUrl) ?>"
                     alt="<?= e($t['name']) ?>"
                     class="tp-thumb"
                     loading="lazy">
              <?php else: ?>
                <div class="tp-thumb" style="display:grid;place-items:center;color:#9CA3AF">
                  <i class="bi bi-image"></i>
                </div>
              <?php endif; ?>
            </td>

            <td>
              <div class="tp-name"><?= e($t['name']) ?></div>
              <div class="tp-slug"><?= e($t['slug']) ?></div>
            </td>

            <td>
              <?php if ($t['type']): ?>
                <span class="tp-pill plum"><?= e($t['type']) ?></span>
              <?php else: ?>
                <span style="color:#9CA3AF">—</span>
              <?php endif; ?>
            </td>

            <td>
              <?php
                $loc = array_filter([$t['city'], $t['state']]);
                echo $loc ? e(implode(', ', $loc)) : '<span style="color:#9CA3AF">—</span>';
              ?>
            </td>

            <!-- ═══════════ TIMINGS (NEW) ═══════════ -->
            <td data-order="<?= $hasAnyData ? 1 : 0 ?>">
              <?php if (!$hasAnyData): ?>
                <span class="tp-timings__empty">Not set</span>
              <?php else: ?>
                <div class="tp-timings">

                  <?php if ($hasTime): ?>
                    <div class="tp-timings__row">
                      <i class="bi bi-clock"></i>
                      <span><?= e(tp_time_fmt($openTime)) ?> – <?= e(tp_time_fmt($closeTime)) ?></span>
                    </div>
                  <?php endif; ?>

                  <div class="tp-timings__row<?= $isOpenDaily ? '' : ' tp-timings__closed' ?>">
                    <i class="bi bi-calendar-week"></i>
                    <span><?= e($closedLabel) ?></span>
                  </div>

                  <?php if ($closureNote !== ''): ?>
                    <div class="tp-timings__note">
                      <i class="bi bi-info-circle"></i>
                      <?= e($closureNote) ?>
                    </div>
                  <?php endif; ?>

                  <?php if ($closedToday): ?>
                    <span class="tp-timings__closed-today">
                      <i class="bi bi-x-octagon-fill"></i> Closed today
                    </span>
                  <?php endif; ?>

                </div>
              <?php endif; ?>
            </td>

            <td data-order="<?= (int) $t['is_featured'] ?>">
              <?php if ($t['is_featured']): ?>
                <span class="tp-pill amber"><i class="bi bi-star-fill"></i> Yes</span>
              <?php else: ?>
                <span style="color:#9CA3AF">—</span>
              <?php endif; ?>
            </td>

            <td data-order="<?= (int) $t['is_dark_featured'] ?>">
              <?php if ($t['is_dark_featured']): ?>
                <span class="tp-pill blue"><i class="bi bi-moon-stars-fill"></i> Yes</span>
              <?php else: ?>
                <span style="color:#9CA3AF">—</span>
              <?php endif; ?>
            </td>

            <td data-order="<?= (int) $t['sort_order'] ?>" style="color:#6B7280;font-weight:600">
              <?= (int) $t['sort_order'] ?>
            </td>

            <td data-order="<?= (int) $t['is_active'] ?>">
              <?php if ($t['is_active']): ?>
                <span class="tp-pill green"><i class="bi bi-check-circle-fill"></i> Active</span>
              <?php else: ?>
                <span class="tp-pill red"><i class="bi bi-x-circle-fill"></i> Inactive</span>
              <?php endif; ?>
            </td>

            <td>
              <div class="tp-actions">
                <a href="<?= url('admin/modules/temples/edit.php?id=' . (int) $t['id']) ?>"
                   class="tp-act tp-act-edit">
                  <i class="bi bi-pencil-square"></i> Edit
                </a>

                <form method="post" style="margin:0"
                      data-confirm="<?= $t['is_active'] ? 'Disable' : 'Enable' ?> '<?= e($t['name']) ?>'?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                  <?php if ($t['is_active']): ?>
                    <button type="submit" class="tp-act tp-act-warn">
                      <i class="bi bi-pause-circle-fill"></i> Disable
                    </button>
                  <?php else: ?>
                    <button type="submit" class="tp-act tp-act-ok">
                      <i class="bi bi-play-circle-fill"></i> Enable
                    </button>
                  <?php endif; ?>
                </form>

                <form method="post" style="margin:0"
                      data-confirm="Delete '<?= e($t['name']) ?>' permanently?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                  <button type="submit" class="tp-act tp-act-del">
                    <i class="bi bi-trash-fill"></i> Delete
                  </button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

  </div>
</div>

<script>
/* ============================================================
   DataTable init + tab filter for temples
   ============================================================ */
(function () {
  'use strict';

  function init() {
    if (!window.jQuery || !$.fn.DataTable) {
      console.error('[temples] DataTables not loaded');
      return;
    }
    if ($.fn.DataTable.isDataTable('#templesTable')) return;

    var table = $('#templesTable').DataTable({
      order: [[7, 'asc']],
      columnDefs: [
        { targets: [0, 9], orderable: false }
      ],
      pageLength: 10,
      stateSave: true,
      stateDuration: 60 * 60 * 24
    });

    /* ---------- Tab filter ---------- */
    var activeFilter = 'all';

    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
      if (settings.nTable.id !== 'templesTable') return true;
      if (activeFilter === 'all') return true;

      var row = table.row(dataIndex).node();

      switch (activeFilter) {
        case 'active':   return row.dataset.status === 'active';
        case 'inactive': return row.dataset.status === 'inactive';
        case 'featured': return row.dataset.featured === '1';
      }
      return true;
    });

    document.querySelectorAll('#tpTabs .tp-tab').forEach(function (tab) {
      tab.addEventListener('click', function () {
        document.querySelectorAll('#tpTabs .tp-tab').forEach(function (t) {
          t.classList.remove('active');
        });
        tab.classList.add('active');
        activeFilter = tab.dataset.filter;
        table.draw();
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
</script>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>