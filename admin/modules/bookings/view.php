<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$id = (int) get('id', 0);
if ($id <= 0) { flash_set('error','Invalid booking ID.'); redirect('admin/modules/bookings/index.php'); }

$stmt = $pdo->prepare(
    'SELECT b.*, p.name AS puja_name, p.slug AS puja_slug, p.image_url AS puja_image,
            t.name AS temple_name, t.city AS temple_city, t.state AS temple_state,
            u.name AS user_name, u.email AS user_email, u.phone AS user_phone
     FROM puja_bookings b
     LEFT JOIN pujas p   ON p.id = b.puja_id
     LEFT JOIN temples t ON t.id = b.temple_id
     LEFT JOIN users u   ON u.id = b.user_id
     WHERE b.id = ? LIMIT 1'
);
$stmt->execute([$id]);
$bk = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$bk) { flash_set('error','Booking not found.'); redirect('admin/modules/bookings/index.php'); }

$adminPageTitle = 'Booking ' . $bk['booking_ref'];
$adminPageSub   = 'Puja booking details';

if (!function_exists('bk_status_class')) {
    function bk_status_class(string $s): string {
        return match ($s) {
            'completed' => 'green',
            'confirmed' => 'blue',
            'cancelled' => 'red',
            default     => 'amber',
        };
    }
}
if (!function_exists('bk_img_url')) {
    function bk_img_url(?string $s): string {
        $s = trim((string) $s);
        if ($s === '') return '';
        if (preg_match('#^https?://#i', $s)) return $s;
        $s = ltrim($s, '/');
        if (strpos($s, 'assets/img/') === 0) $s = substr($s, strlen('assets/img/'));
        $m = 'assets/img/';
        $pos = strpos($s, $m);
        if ($pos !== false) $s = substr($s, $pos + strlen($m));
        return 'https://divyaaastha.xinkpayroll.co.in/assets/img/' . ltrim($s, '/');
    }
}

$validStatuses = ['pending','confirmed','completed','cancelled'];
$imgSrc = bk_img_url($bk['puja_image']);

require dirname(__DIR__, 2) . '/includes/header.php';
?>

<style>
.bv-wrap{margin:-8px 0 0}
.bv-back{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;
  font-size:12.5px;font-weight:600;border-radius:8px;background:#fff;color:#351B2F;
  border:1px solid #E5E7EB;text-decoration:none;margin-bottom:20px}
.bv-back:hover{border-color:#B76146;color:#B76146}
.bv-grid{display:grid;grid-template-columns:1.6fr 1fr;gap:24px;align-items:flex-start}
@media (max-width:1000px){.bv-grid{grid-template-columns:1fr}}
.bv-card{background:#fff;border:1px solid #E5E7EB;border-radius:14px;overflow:hidden;
  box-shadow:0 1px 3px rgba(0,0,0,.06);margin-bottom:20px}
.bv-card__head{padding:16px 22px;border-bottom:1px solid #E5E7EB;
  display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
.bv-card__head h3{margin:0;font-family:var(--font-serif,Georgia,serif);
  font-size:16px;color:#351B2F;font-weight:600}
.bv-card__body{padding:22px}
.bv-rows{list-style:none;padding:0;margin:0}
.bv-rows li{padding:12px 0;border-bottom:1px solid #F3EEE6;display:flex;
  justify-content:space-between;gap:14px;font-size:13.5px}
.bv-rows li:last-child{border-bottom:0}
.bv-rows .lbl{color:#6B7280}
.bv-rows .val{color:#351B2F;font-weight:600;text-align:right}
.bv-pill{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;font-size:11px;
  font-weight:700;letter-spacing:.4px;text-transform:uppercase;border-radius:99px}
.bv-pill.green{background:#ECFDF5;color:#059669}
.bv-pill.red{background:#FEF2F2;color:#DC2626}
.bv-pill.amber{background:#FFFBEB;color:#D97706}
.bv-pill.blue{background:#EFF6FF;color:#2563EB}
.bv-btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;
  font-size:12.5px;font-weight:600;border-radius:9px;border:1px solid transparent;
  cursor:pointer;font-family:inherit;text-decoration:none;transition:all .15s;white-space:nowrap}
.bv-btn-primary{background:#B76146;color:#fff;border-color:#B76146}
.bv-btn-primary:hover{background:#9B4C34;color:#fff}
.bv-btn-outline{background:#fff;color:#351B2F;border-color:#E5E7EB}
.bv-btn-outline:hover{border-color:#B76146;color:#B76146}
.bv-field{display:flex;flex-direction:column;gap:6px;margin-bottom:14px}
.bv-field label{font-size:12px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;color:#6B7280}
.bv-field input,.bv-field select,.bv-field textarea{
  width:100%;padding:11px 14px;border:1px solid #E5E7EB;border-radius:10px;
  font-family:inherit;font-size:14px;color:#1F2937;background:#fff;
  transition:border-color .18s,box-shadow .18s}
.bv-field input:focus,.bv-field select:focus,.bv-field textarea:focus{
  outline:none;border-color:#B76146;box-shadow:0 0 0 3px rgba(183,97,70,.12)}
.bv-hero{display:grid;grid-template-columns:120px 1fr;gap:20px;align-items:center;
  padding:22px;background:#FAF6EF;border-bottom:1px solid #EFE6D9}
@media (max-width:600px){.bv-hero{grid-template-columns:1fr;text-align:center;justify-items:center}}
.bv-hero__img{width:120px;height:120px;border-radius:14px;object-fit:cover;
  background:#F3F4F6;border:1px solid #E5E7EB}
.bv-hero__img.ph{display:grid;place-items:center;color:#9A8B7C;font-size:36px}
.bv-hero__name{font-family:var(--font-serif,Georgia,serif);font-size:22px;
  color:#351B2F;font-weight:600;margin:0 0 6px;line-height:1.25}
.bv-hero__meta{font-size:13px;color:#6B7280;line-height:1.6}
.bv-alert{padding:14px 18px;border-radius:12px;font-size:13.5px;margin-bottom:18px;line-height:1.55}
.bv-alert.success{background:#ECFDF5;color:#065F46;border:1px solid #A7F3D0}
.bv-alert.error{background:#FEF2F2;color:#991B1B;border:1px solid #FECACA}
.bv-alert.info{background:#EFF6FF;color:#1E40AF;border:1px solid #BFDBFE}
</style>

<div class="bv-wrap">

  <a href="<?= url('admin/modules/bookings/index.php') ?>" class="bv-back">
    <i class="bi bi-arrow-left"></i> Back to Bookings
  </a>

  <?php foreach (flash_get() as $f): ?>
    <div class="bv-alert <?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
  <?php endforeach; ?>

  <div class="bv-card">
    <div class="bv-hero">
      <?php if ($imgSrc): ?>
        <img src="<?= e($imgSrc) ?>" alt="" class="bv-hero__img" onerror="this.style.display='none'">
      <?php else: ?>
        <div class="bv-hero__img ph"><i class="bi bi-flower1"></i></div>
      <?php endif; ?>
      <div>
        <h2 class="bv-hero__name"><?= e($bk['puja_name'] ?: 'Puja') ?></h2>
        <div class="bv-hero__meta">
          Booking Ref: <b><?= e($bk['booking_ref']) ?></b>
          <?php if ($bk['temple_name']): ?>
            <br>🛕 <?= e($bk['temple_name']) ?><?= $bk['temple_city'] ? ', ' . e($bk['temple_city']) : '' ?>
          <?php endif; ?>
        </div>
        <div style="margin-top:10px">
          <span class="bv-pill <?= bk_status_class($bk['status']) ?>"><?= e($bk['status']) ?></span>
        </div>
      </div>
    </div>
  </div>

  <div class="bv-grid">

    <!-- LEFT -->
    <div>

      <div class="bv-card">
        <div class="bv-card__head"><h3>Booking Details</h3></div>
        <div class="bv-card__body">
          <ul class="bv-rows">
            <li><span class="lbl">Booking Ref</span><span class="val"><?= e($bk['booking_ref']) ?></span></li>
            <li><span class="lbl">Booking Date</span><span class="val"><?= date('d M Y', strtotime($bk['booking_date'])) ?></span></li>
            <?php if ($bk['muhurat_time']): ?>
              <li><span class="lbl">Muhurat Time</span><span class="val"><?= date('h:i A', strtotime($bk['muhurat_time'])) ?></span></li>
            <?php endif; ?>
            <li><span class="lbl">Amount</span><span class="val"><?= inr($bk['amount']) ?></span></li>
            <li><span class="lbl">Booked On</span><span class="val"><?= date('d M Y, h:i A', strtotime($bk['created_at'])) ?></span></li>
          </ul>
        </div>
      </div>

      <div class="bv-card">
        <div class="bv-card__head"><h3>Devotee &amp; Sankalp</h3></div>
        <div class="bv-card__body">
          <ul class="bv-rows">
            <li><span class="lbl">Devotee Name</span><span class="val"><?= e($bk['devotee_name']) ?></span></li>
            <?php if ($bk['gotra']): ?>
              <li><span class="lbl">Gotra</span><span class="val"><?= e($bk['gotra']) ?></span></li>
            <?php endif; ?>
            <?php if ($bk['user_email']): ?>
              <li><span class="lbl">Account Email</span><span class="val"><?= e($bk['user_email']) ?></span></li>
            <?php endif; ?>
            <?php if ($bk['sankalp']): ?>
              <li><span class="lbl">Sankalp / Wish</span><span class="val" style="text-align:left;font-weight:400"><?= e($bk['sankalp']) ?></span></li>
            <?php endif; ?>
          </ul>
        </div>
      </div>

      <?php if ($bk['temple_name']): ?>
        <div class="bv-card">
          <div class="bv-card__head"><h3>Temple</h3></div>
          <div class="bv-card__body" style="font-size:13.5px;color:#4B5563;line-height:1.8">
            <b style="color:#351B2F;font-size:14.5px"><?= e($bk['temple_name']) ?></b><br>
            <?php if ($bk['temple_city']): ?><?= e($bk['temple_city']) ?><?= $bk['temple_state'] ? ', ' . e($bk['temple_state']) : '' ?><?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- Video proof -->
      <?php if (!empty($bk['video_url'])): ?>
        <div class="bv-card">
          <div class="bv-card__head"><h3>Puja Video Proof</h3></div>
          <div class="bv-card__body">
            <a href="<?= e($bk['video_url']) ?>" target="_blank" rel="noopener"
               class="bv-btn bv-btn-primary">
              <i class="bi bi-play-circle"></i> Watch Video
            </a>
            <div style="margin-top:12px;font-size:12.5px;color:#6B7280;word-break:break-all">
              <?= e($bk['video_url']) ?>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- Prasad tracking -->
      <?php if (!empty($bk['prasad_tracking'])): ?>
        <div class="bv-card">
          <div class="bv-card__head"><h3>Prasad Tracking</h3></div>
          <div class="bv-card__body" style="font-size:14px;color:#351B2F;font-weight:600">
            <?= e($bk['prasad_tracking']) ?>
          </div>
        </div>
      <?php endif; ?>

    </div>

    <!-- RIGHT -->
    <div>

      <!-- Status update -->
      <div class="bv-card">
        <div class="bv-card__head"><h3>Update Status</h3></div>
        <div class="bv-card__body">
          <form method="post" action="<?= url('admin/modules/bookings/update-status.php') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $bk['id'] ?>">

            <div class="bv-field">
              <label>Booking Status</label>
              <select name="status" required>
                <?php foreach ($validStatuses as $s): ?>
                  <option value="<?= $s ?>" <?= $bk['status'] === $s ? 'selected' : '' ?>>
                    <?= ucfirst($s) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="bv-field">
              <label>Muhurat Time (optional)</label>
              <input type="time" name="muhurat_time"
                     value="<?= $bk['muhurat_time'] ? date('H:i', strtotime($bk['muhurat_time'])) : '' ?>">
            </div>

            <button type="submit" class="bv-btn bv-btn-primary" style="width:100%">
              <i class="bi bi-check2-circle"></i> Update
            </button>
          </form>
        </div>
      </div>

      <!-- Upload video -->
      <div class="bv-card">
        <div class="bv-card__head"><h3>Upload Video Proof</h3></div>
        <div class="bv-card__body">
          <form method="post" action="<?= url('admin/modules/bookings/upload-video.php') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $bk['id'] ?>">

            <div class="bv-field">
              <label>Video URL</label>
              <input type="url" name="video_url"
                     value="<?= e($bk['video_url'] ?: '') ?>"
                     placeholder="https://youtube.com/watch?v=…">
            </div>

            <div class="bv-field">
              <label style="text-transform:none;letter-spacing:0;font-weight:500;color:#6B7280;font-size:12px">
                Paste a YouTube / Vimeo / Drive link
              </label>
            </div>

            <button type="submit" class="bv-btn bv-btn-primary" style="width:100%">
              <i class="bi bi-cloud-upload"></i> Save Video Link
            </button>
          </form>
        </div>
      </div>

      <!-- Prasad tracking -->
      <div class="bv-card">
        <div class="bv-card__head"><h3>Add Prasad Tracking</h3></div>
        <div class="bv-card__body">
          <form method="post" action="<?= url('admin/modules/bookings/add-prasad-tracking.php') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $bk['id'] ?>">

            <div class="bv-field">
              <label>Tracking / AWB</label>
              <input type="text" name="prasad_tracking"
                     value="<?= e($bk['prasad_tracking'] ?: '') ?>"
                     placeholder="e.g. AWB123456789">
            </div>

            <button type="submit" class="bv-btn bv-btn-primary" style="width:100%">
              <i class="bi bi-truck"></i> Save Tracking
            </button>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>