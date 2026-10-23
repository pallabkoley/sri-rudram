<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

/* ==================================================================
 |  Inline helpers
 * ================================================================== */
if (!defined('ASSETS_PATH')) {
    foreach ([
        dirname(__DIR__, 3) . '/assets',
        dirname(__DIR__, 2) . '/assets',
    ] as $__p) {
        if ($__p && is_dir($__p)) { define('ASSETS_PATH', $__p); break; }
    }
}
if (!function_exists('pj_img_url')) {
    function pj_img_url(?string $s): string {
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
if (!function_exists('pj_admin_upload')) {
    function pj_admin_upload(string $field, string $sub = 'pujas', ?string $ex = null): ?string {
        if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? 4) === 4) return $ex;
        $f = $_FILES[$field];
        if ($f['error'] !== UPLOAD_ERR_OK) { flash_set('error','Upload failed.'); return $ex; }
        if ($f['size'] > 3 * 1024 * 1024)  { flash_set('error','Max 3 MB.'); return $ex; }

        $mime = null;
        if (function_exists('finfo_open')) {
            $fi = @finfo_open(FILEINFO_MIME_TYPE);
            if ($fi) { $mime = @finfo_file($fi, $f['tmp_name']); @finfo_close($fi); }
        }
        if (!$mime && function_exists('mime_content_type')) $mime = @mime_content_type($f['tmp_name']);
        if (!$mime) { $i = @getimagesize($f['tmp_name']); if ($i && !empty($i['mime'])) $mime = $i['mime']; }

        $ok = ['image/jpeg'=>'jpg','image/jpg'=>'jpg','image/pjpeg'=>'jpg',
               'image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
        if (!$mime || !isset($ok[$mime])) { flash_set('error','Only JPG/PNG/WEBP/GIF.'); return $ex; }
        if (@getimagesize($f['tmp_name']) === false) { flash_set('error','Invalid image.'); return $ex; }
        if (!defined('ASSETS_PATH')) return $ex;

        $sub = trim($sub, '/');
        $dir = ASSETS_PATH . '/img' . ($sub ? '/' . $sub : '');
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) { flash_set('error','Cannot create folder.'); return $ex; }
        if (!is_writable($dir)) { flash_set('error','Not writable.'); return $ex; }

        $ext  = $ok[$mime];
        $name = date('Y-m-d') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
        $dest = $dir . '/' . $name;
        if (!@move_uploaded_file($f['tmp_name'], $dest)) { flash_set('error','Could not save.'); return $ex; }
        @chmod($dest, 0644);
        return $sub . '/' . $name;
    }
}
if (!function_exists('pj_delete_image_file')) {
    function pj_delete_image_file(?string $p): void {
        if (!$p || !defined('ASSETS_PATH')) return;
        if (preg_match('#^https?://#i', $p)) return;
        $rel = ltrim($p, '/');
        if (strpos($rel, 'assets/img/') === 0) $rel = substr($rel, strlen('assets/img/'));
        $m = 'assets/img/';
        $pos = strpos($rel, $m);
        if ($pos !== false) $rel = substr($rel, $pos + strlen($m));
        if (strpos($rel, 'img/') === 0) $rel = substr($rel, 4);
        $rel = str_replace(['..', '\\'], ['', '/'], $rel);
        $rel = ltrim($rel, '/');
        if ($rel === '') return;
        $full = ASSETS_PATH . '/img/' . $rel;
        $real = @realpath($full); $root = @realpath(ASSETS_PATH . '/img');
        if ($real && $root && strpos($real, $root) === 0 && is_file($real)) @unlink($real);
    }
}

/* ==================================================================
 |  Load puja
 * ================================================================== */
$id = (int) get('id', 0);
if ($id <= 0) { flash_set('error','Invalid puja ID.'); redirect('admin/modules/pujas/index.php'); }

$stmt = $pdo->prepare('SELECT * FROM pujas WHERE id = ?');
$stmt->execute([$id]);
$puja = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$puja) { flash_set('error','Puja not found.'); redirect('admin/modules/pujas/index.php'); }

$adminPageTitle = 'Manage Puja';
$adminPageSub   = 'Edit ritual + packages, benefits, procedures, FAQs and related';
$errors         = [];
$oldImageUrl    = $puja['image_url'];
$temples        = $pdo->query('SELECT id, name FROM temples ORDER BY name ASC')->fetchAll();

$currentTempleId = (int) ($puja['temple_id'] ?? 0);
$currentCustom   = trim((string) ($puja['custom_temple_name'] ?? ''));

$data = [
    'name'               => $puja['name'],
    'slug'               => $puja['slug'],
    'temple_id'          => 0,
    'custom_temple_name' => '',
    'short_desc'         => (string) ($puja['short_desc'] ?? ''),
    'description'        => (string) ($puja['description'] ?? ''),
    'price'              => (float) $puja['price'],
    'duration_mins'      => (int) ($puja['duration_mins'] ?? 0),
    'badge'              => (string) ($puja['badge'] ?? ''),
    'badge_style'        => (string) ($puja['badge_style'] ?? 'gold'),
    'image_url'          => (string) ($puja['image_url'] ?? ''),
    'is_popular'         => (int) $puja['is_popular'],
    'sort_order'         => (int) $puja['sort_order'],
    'is_active'          => (int) $puja['is_active'],
];
if ($currentCustom !== '') {
    $data['temple_id']          = -1;
    $data['custom_temple_name'] = $currentCustom;
} elseif ($currentTempleId > 0) {
    $data['temple_id'] = $currentTempleId;
}

/* ==================================================================
 |  Handle POST
 * ================================================================== */
$activeTab = '#tab-packages';   // default redirect

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ---------- CSRF CHECK — NON-SILENT ---------- */
    $csrfToken = post('_csrf', '');
    if (!csrf_verify($csrfToken)) {
        error_log('[pujas.edit] CSRF FAILED. token=' . var_export($csrfToken, true)
                . ' session=' . var_export($_SESSION['_csrf'] ?? null, true));
        flash_set('error', 'Security token expired. Please refresh the page and try again.');
        redirect('admin/modules/pujas/edit.php?id=' . $id);
    }

    $action = (string) post('action', '');

    /* ══════════ SUB-CONTENT ACTIONS ══════════ */
    if ($action !== '' && $action !== 'edit_puja') {

        try {
            switch ($action) {

                /* ---------- PACKAGES ---------- */
                case 'add_package':
                    $activeTab = '#tab-packages';
                    $pkgName = trim((string) post('pkg_name', ''));
                    $pkgSlug = trim((string) post('pkg_slug', ''));
                    if ($pkgName === '') { flash_set('error','Package name is required.'); break; }
                    if ($pkgSlug === '') {
                        $pkgSlug = trim(strtolower(preg_replace('/[^a-z0-9]+/i','-', $pkgName)), '-');
                    }
                    $chk = $pdo->prepare('SELECT id FROM puja_packages WHERE puja_id = ? AND slug = ?');
                    $chk->execute([$id, $pkgSlug]);
                    if ($chk->fetch()) {
                        $pkgSlug .= '-' . substr(bin2hex(random_bytes(3)), 0, 5);
                    }

                    $pdo->prepare(
                        'INSERT INTO puja_packages
                         (puja_id, name, slug, short_desc, price, compare_price,
                          included_items, badge, is_popular, sort_order, is_active)
                         VALUES (?,?,?,?,?,?,?,?,?,?,?)'
                    )->execute([
                        $id,
                        $pkgName,
                        $pkgSlug,
                        trim((string) post('pkg_short_desc', '')) ?: null,
                        (float) post('pkg_price', 0),
                        ((float) post('pkg_compare_price', 0)) > 0 ? (float) post('pkg_compare_price', 0) : null,
                        trim((string) post('pkg_included', ''))   ?: null,
                        trim((string) post('pkg_badge', ''))      ?: null,
                        (int) post('pkg_is_popular', 0),
                        (int) post('pkg_sort_order', 0),
                        1
                    ]);
                    flash_set('success', 'Package "' . $pkgName . '" added successfully.');
                    break;

                case 'del_package':
                    $activeTab = '#tab-packages';
                    $pid = (int) post('row_id', 0);
                    if ($pid > 0) {
                        $pdo->prepare('DELETE FROM puja_packages WHERE id = ? AND puja_id = ?')->execute([$pid, $id]);
                        flash_set('success','Package deleted.');
                    }
                    break;

                case 'toggle_package':
                    $activeTab = '#tab-packages';
                    $pid = (int) post('row_id', 0);
                    if ($pid > 0) {
                        $pdo->prepare('UPDATE puja_packages SET is_active = 1 - is_active WHERE id = ? AND puja_id = ?')->execute([$pid, $id]);
                        flash_set('success','Package status updated.');
                    }
                    break;

                /* ---------- BENEFITS ---------- */
                case 'add_benefit':
                    $activeTab = '#tab-benefits';
                    $bTitle = trim((string) post('ben_title', ''));
                    if ($bTitle === '') { flash_set('error','Benefit title is required.'); break; }
                    $pdo->prepare(
                        'INSERT INTO puja_benefits
                         (puja_id, title, description, icon, sort_order, is_active)
                         VALUES (?,?,?,?,?,?)'
                    )->execute([
                        $id,
                        $bTitle,
                        trim((string) post('ben_desc', '')) ?: null,
                        trim((string) post('ben_icon', '')) ?: null,
                        (int) post('ben_sort_order', 0),
                        1
                    ]);
                    flash_set('success', 'Benefit "' . $bTitle . '" added successfully.');
                    break;

                case 'del_benefit':
                    $activeTab = '#tab-benefits';
                    $bid = (int) post('row_id', 0);
                    if ($bid > 0) {
                        $pdo->prepare('DELETE FROM puja_benefits WHERE id = ? AND puja_id = ?')->execute([$bid, $id]);
                        flash_set('success','Benefit deleted.');
                    }
                    break;

                case 'toggle_benefit':
                    $activeTab = '#tab-benefits';
                    $bid = (int) post('row_id', 0);
                    if ($bid > 0) {
                        $pdo->prepare('UPDATE puja_benefits SET is_active = 1 - is_active WHERE id = ? AND puja_id = ?')->execute([$bid, $id]);
                        flash_set('success','Benefit status updated.');
                    }
                    break;

                /* ---------- PROCEDURES ---------- */
                case 'add_procedure':
                    $activeTab = '#tab-procedures';
                    $pTitle = trim((string) post('prc_title', ''));
                    if ($pTitle === '') { flash_set('error','Step title is required.'); break; }
                    $prcImg = null;
                    if (!empty($_FILES['prc_image']['name'])) {
                        $prcImg = pj_admin_upload('prc_image', 'pujas/procedures', null);
                    }
                    $pdo->prepare(
                        'INSERT INTO puja_procedures
                         (puja_id, step_number, title, description, image_url, is_active)
                         VALUES (?,?,?,?,?,?)'
                    )->execute([
                        $id,
                        (int) post('prc_step', 1),
                        $pTitle,
                        trim((string) post('prc_desc', '')) ?: null,
                        $prcImg,
                        1
                    ]);
                    flash_set('success', 'Procedure step "' . $pTitle . '" added.');
                    break;

                case 'del_procedure':
                    $activeTab = '#tab-procedures';
                    $pid = (int) post('row_id', 0);
                    if ($pid > 0) {
                        $img = (string) $pdo->query("SELECT image_url FROM puja_procedures WHERE id = " . (int)$pid)->fetchColumn();
                        $pdo->prepare('DELETE FROM puja_procedures WHERE id = ? AND puja_id = ?')->execute([$pid, $id]);
                        if ($img !== '') pj_delete_image_file($img);
                        flash_set('success','Step deleted.');
                    }
                    break;

                case 'toggle_procedure':
                    $activeTab = '#tab-procedures';
                    $pid = (int) post('row_id', 0);
                    if ($pid > 0) {
                        $pdo->prepare('UPDATE puja_procedures SET is_active = 1 - is_active WHERE id = ? AND puja_id = ?')->execute([$pid, $id]);
                        flash_set('success','Step status updated.');
                    }
                    break;

                /* ---------- FAQS ---------- */
                case 'add_faq':
                    $activeTab = '#tab-faqs';
                    $q = trim((string) post('faq_q', ''));
                    $a = trim((string) post('faq_a', ''));
                    if ($q === '' || $a === '') { flash_set('error','Both question and answer are required.'); break; }
                    $pdo->prepare(
                        'INSERT INTO puja_faqs (puja_id, question, answer, sort_order, is_active)
                         VALUES (?,?,?,?,?)'
                    )->execute([$id, $q, $a, (int) post('faq_sort', 0), 1]);
                    flash_set('success','FAQ added successfully.');
                    break;

                case 'del_faq':
                    $activeTab = '#tab-faqs';
                    $fid = (int) post('row_id', 0);
                    if ($fid > 0) {
                        $pdo->prepare('DELETE FROM puja_faqs WHERE id = ? AND puja_id = ?')->execute([$fid, $id]);
                        flash_set('success','FAQ deleted.');
                    }
                    break;

                case 'toggle_faq':
                    $activeTab = '#tab-faqs';
                    $fid = (int) post('row_id', 0);
                    if ($fid > 0) {
                        $pdo->prepare('UPDATE puja_faqs SET is_active = 1 - is_active WHERE id = ? AND puja_id = ?')->execute([$fid, $id]);
                        flash_set('success','FAQ status updated.');
                    }
                    break;

                /* ---------- RELATED ---------- */
                case 'add_related':
                    $activeTab = '#tab-related';
                    $rId = (int) post('rel_puja_id', 0);
                    if ($rId <= 0 || $rId === $id) { flash_set('error','Invalid related puja selected.'); break; }
                    $chk = $pdo->prepare('SELECT id FROM pujas WHERE id = ? AND is_active = 1');
                    $chk->execute([$rId]);
                    if (!$chk->fetch()) { flash_set('error','Selected puja not found.'); break; }
                    $pdo->prepare('INSERT IGNORE INTO puja_related (puja_id, related_puja_id, sort_order)
                                   VALUES (?,?,?)')
                        ->execute([$id, $rId, (int) post('rel_sort', 0)]);
                    flash_set('success','Related puja added.');
                    break;

                case 'del_related':
                    $activeTab = '#tab-related';
                    $rid = (int) post('row_id', 0);
                    if ($rid > 0) {
                        $pdo->prepare('DELETE FROM puja_related WHERE related_puja_id = ? AND puja_id = ?')->execute([$rid, $id]);
                        flash_set('success','Related puja removed.');
                    }
                    break;

                default:
                    flash_set('error', 'Unknown action: ' . $action);
                    break;
            }
        } catch (Throwable $e) {
            error_log('[pujas.edit:sub] ' . $e->getMessage());
            flash_set('error', 'Action failed: ' . $e->getMessage());
        }

        redirect('admin/modules/pujas/edit.php?id=' . $id . $activeTab);
    }

    /* ══════════ MAIN PUJA EDIT ══════════ */
    foreach (['name','slug','short_desc','description','badge','badge_style','custom_temple_name'] as $k) {
        $data[$k] = trim((string) post($k, ''));
    }
    $data['temple_id']     = (int) post('temple_id', 0);
    $data['price']         = (float) post('price', 0);
    $data['duration_mins'] = (int) post('duration_mins', 0);
    $data['is_popular']    = (int) post('is_popular', 0);
    $data['sort_order']    = (int) post('sort_order', 0);
    $data['is_active']     = (int) post('is_active', 0);

    $newPath = null;
    if (!empty($_FILES['image_file']) && ($_FILES['image_file']['error'] ?? 4) !== 4) {
        try {
            $newPath = pj_admin_upload('image_file', 'pujas', $oldImageUrl);
            if ($newPath && $newPath !== $oldImageUrl) $data['image_url'] = $newPath;
        } catch (Throwable $e) { $errors['_'] = 'Upload failed: ' . $e->getMessage(); }
    }

    if (post('remove_image')) {
        if ($oldImageUrl && strpos($oldImageUrl, 'http') !== 0) pj_delete_image_file($oldImageUrl);
        $data['image_url'] = '';
    } elseif (empty($data['image_url']) && $oldImageUrl && !$newPath) {
        $data['image_url'] = $oldImageUrl;
    }

    if ($data['name'] === '') $errors['name']  = 'Puja name is required.';
    if ($data['price'] <= 0)  $errors['price'] = 'Price must be greater than 0.';
    if ($data['slug'] === '') $data['slug'] = function_exists('slugify')
        ? slugify($data['name'])
        : trim(strtolower(preg_replace('/[^a-z0-9]+/i', '-', $data['name'])), '-');

    if (!isset($errors['slug'])) {
        $c = $pdo->prepare('SELECT id FROM pujas WHERE slug = ? AND id <> ?');
        $c->execute([$data['slug'], $id]);
        if ($c->fetch()) $errors['slug'] = 'That slug is already in use.';
    }

    $finalTempleId   = null;
    $finalCustomName = null;
    if ($data['temple_id'] === -1) {
        $name = trim($data['custom_temple_name']);
        if ($name === '') {
            $errors['custom_temple_name'] = 'Please enter the custom temple name.';
        } else {
            $finalCustomName = $name;
        }
    } elseif ($data['temple_id'] > 0) {
        $t = $pdo->prepare('SELECT id FROM temples WHERE id = ?');
        $t->execute([$data['temple_id']]);
        if (!$t->fetch()) $errors['temple_id'] = 'Selected temple not found.';
        else $finalTempleId = $data['temple_id'];
    }

    if (!$errors) {
        try {
            $pdo->prepare(
                'UPDATE pujas SET
                   temple_id = ?, custom_temple_name = ?,
                   name = ?, slug = ?, short_desc = ?, description = ?,
                   price = ?, duration_mins = ?, badge = ?, badge_style = ?,
                   image_url = ?, is_popular = ?, sort_order = ?, is_active = ?
                 WHERE id = ?'
            )->execute([
                $finalTempleId,
                $finalCustomName,
                $data['name'],
                $data['slug'],
                $data['short_desc'] ?: null,
                $data['description'] ?: null,
                $data['price'],
                $data['duration_mins'] ?: null,
                $data['badge'] ?: null,
                $data['badge_style'] ?: 'gold',
                $data['image_url'] ?: null,
                $data['is_popular'],
                $data['sort_order'],
                $data['is_active'],
                $id,
            ]);

            if ($oldImageUrl && $oldImageUrl !== $data['image_url'] && strpos($oldImageUrl, 'http') !== 0) {
                pj_delete_image_file($oldImageUrl);
            }

            flash_set('success', 'Puja details updated.');
            redirect('admin/modules/pujas/edit.php?id=' . $id);
        } catch (PDOException $e) {
            error_log('[pujas.edit] ' . $e->getMessage());
            $errors['_'] = 'Could not save: ' . $e->getMessage();
        }
    }
}

/* ==================================================================
 |  Load sub-content
 * ================================================================== */
$packages = $benefits = $procedures = $faqs = $relatedRows = $otherPujas = [];
try { $packages = $pdo->query("SELECT * FROM puja_packages WHERE puja_id = $id ORDER BY is_popular DESC, sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC); } catch (Throwable $e) {}
try { $benefits = $pdo->query("SELECT * FROM puja_benefits WHERE puja_id = $id ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC); } catch (Throwable $e) {}
try { $procedures = $pdo->query("SELECT * FROM puja_procedures WHERE puja_id = $id ORDER BY step_number ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC); } catch (Throwable $e) {}
try { $faqs = $pdo->query("SELECT * FROM puja_faqs WHERE puja_id = $id ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC); } catch (Throwable $e) {}
try {
    $relatedRows = $pdo->query("SELECT pr.related_puja_id, pr.sort_order, p.name AS puja_name, p.image_url
                                FROM puja_related pr
                                INNER JOIN pujas p ON p.id = pr.related_puja_id
                                WHERE pr.puja_id = $id
                                ORDER BY pr.sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}
try { $otherPujas = $pdo->query("SELECT id, name FROM pujas WHERE is_active = 1 AND id <> $id ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC); } catch (Throwable $e) {}

$refBookings = (int) $pdo->query('SELECT COUNT(*) FROM puja_bookings WHERE puja_id = ' . (int) $id)->fetchColumn();

require dirname(__DIR__, 2) . '/includes/header.php';
$previewUrl = pj_img_url($data['image_url']);
?>

<style>
.pj-flash{display:flex;align-items:flex-start;gap:12px;padding:16px 20px;margin:0 0 22px;border-radius:12px;font-size:14px;line-height:1.5}
.pj-flash.success{background:linear-gradient(135deg,#ECFDF5,#D1FAE5);border:1px solid #A7F3D0;color:#065F46}
.pj-flash.error{background:linear-gradient(135deg,#FEF2F2,#FEE2E2);border:1px solid #FECACA;color:#991B1B}
.pj-flash.info{background:linear-gradient(135deg,#EFF6FF,#DBEAFE);border:1px solid #BFDBFE;color:#1E40AF}
.pj-flash i{font-size:20px;flex-shrink:0;margin-top:1px}

.sub-banner{display:flex;align-items:center;gap:14px;margin:44px 0 0;padding:22px 26px;
  background:linear-gradient(135deg,#4B080C,#310508);border-radius:14px 14px 0 0;
  color:#F6E8CF;border:1px solid rgba(214,168,62,.35);border-bottom:none}
.sub-banner__icon{width:48px;height:48px;border-radius:12px;flex-shrink:0;display:grid;place-items:center;
  background:linear-gradient(135deg,#D6A83E,#B58A2B);color:#310508;font-size:22px;
  box-shadow:0 4px 12px -4px rgba(0,0,0,.4)}
.sub-banner__text h2{margin:0 0 3px;font-size:19px;font-weight:700;color:#FFF3D2;font-family:Georgia,serif}
.sub-banner__text p{margin:0;font-size:13px;color:rgba(246,232,207,.72);line-height:1.5}

.sub-tabs{display:flex;gap:4px;flex-wrap:wrap;background:#F4EAF0;padding:8px;
  border-left:1px solid rgba(214,168,62,.35);border-right:1px solid rgba(214,168,62,.35)}
.sub-tabs a{display:inline-flex;align-items:center;gap:7px;padding:10px 18px;font-size:13px;font-weight:600;
  color:#6B7280;text-decoration:none;border-radius:9px;border:1px solid transparent;transition:all .15s;cursor:pointer}
.sub-tabs a:hover{background:#fff;color:#4B080C;border-color:#DCC49A}
.sub-tabs a.active{background:#fff;color:#B76146;border-color:#DCC49A;box-shadow:0 2px 6px -2px rgba(75,8,12,.15)}
.sub-tabs a .cnt{font-size:10.5px;padding:1px 7px;border-radius:99px;background:#F4EAF0;color:#5B3A55;font-weight:700}
.sub-tabs a.active .cnt{background:#B76146;color:#fff}

.sub-section{background:#fff;border:1px solid rgba(214,168,62,.35);border-top:none;
  border-radius:0 0 14px 14px;padding:28px 26px;box-shadow:0 1px 3px rgba(0,0,0,.06);margin-bottom:36px}
.sub-section h3{font-size:17px;font-weight:700;color:#351B2F;margin:0 0 6px;display:flex;align-items:center;gap:9px;font-family:Georgia,serif}
.sub-section > p.sub-desc{color:#6B7280;font-size:13px;line-height:1.6;margin:0 0 22px}
.sub-sec{border-top:1px solid #F3F4F6;padding-top:22px;margin-top:22px}
.sub-sec:first-of-type{border-top:none;padding-top:0;margin-top:0}
.sub-sec h4{font-size:13px;font-weight:700;color:#4B080C;margin:0 0 14px;text-transform:uppercase;letter-spacing:1.2px}

.sub-list{display:flex;flex-direction:column;gap:8px;margin-bottom:18px}
.sub-item{display:flex;align-items:center;gap:12px;padding:12px 14px;background:#FAF7F2;border:1px solid #EFE6D9;border-radius:10px}
.sub-item.inactive{opacity:.55;background:#F9FAFB;border-color:#E5E7EB}
.sub-item__icon{width:38px;height:38px;border-radius:9px;flex-shrink:0;display:grid;place-items:center;
  background:linear-gradient(135deg,#FBF3E1,#F3E8D2);border:1px solid rgba(214,168,62,.30);
  color:#92700C;font-size:16px;font-family:Georgia,serif;font-weight:700;overflow:hidden}
.sub-item__icon img{width:100%;height:100%;object-fit:cover}
.sub-item__main{flex:1;min-width:0}
.sub-item__title{font-weight:600;color:#1F2937;font-size:13.5px;line-height:1.3;margin:0 0 3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sub-item__desc{font-size:12px;color:#6B7280;line-height:1.5;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sub-item__meta{display:flex;gap:6px;align-items:center;flex-shrink:0}
.sub-item__price{font-family:Georgia,serif;font-weight:700;color:#B76146;font-size:15px}
.sub-item__badge{font-size:10px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;padding:3px 8px;border-radius:5px;background:#FFF7E0;color:#92700C}

.sub-empty{padding:30px 20px;text-align:center;background:#FAF7F2;border:1px dashed #DCC49A;
  border-radius:10px;color:#6B7280;font-size:13px;margin-bottom:18px}
.sub-empty i{font-size:32px;color:#D6A83E;display:block;margin-bottom:10px;line-height:1}

.sub-add{background:linear-gradient(135deg,#FBF7F0,#F7F0E5);border:1px solid #EFE6D9;border-radius:12px;padding:20px 22px}
.sub-add h5{font-size:12px;font-weight:700;letter-spacing:1.3px;text-transform:uppercase;
  color:#B76146;margin:0 0 16px;display:flex;align-items:center;gap:7px}
.sub-add .row{gap:12px}
.sub-add .f-label{font-size:11.5px;margin-bottom:4px;color:#4B080C;font-weight:700}
.sub-add .f-input, .sub-add .f-textarea{font-size:13px;padding:9px 12px;background:#fff}
.sub-add .form-actions{display:flex;gap:10px;margin-top:16px;padding-top:16px;border-top:1px solid rgba(214,168,62,.22)}
.sub-add .form-actions .form-btn{height:38px;padding:0 22px;font-size:12.5px}
.sub-check{display:inline-flex;align-items:center;gap:8px;font-size:12.5px;font-weight:600;color:#4B080C;cursor:pointer;padding:6px 0}
.sub-check input{accent-color:#B76146;width:16px;height:16px;cursor:pointer}

.sub-act{width:30px;height:30px;border-radius:7px;display:inline-flex;align-items:center;justify-content:center;
  border:1px solid transparent;cursor:pointer;background:#fff;font-size:12px;transition:all .15s}
.sub-act-del{color:#DC2626;border-color:#FECACA;background:#FEF2F2}
.sub-act-del:hover{background:#FEE2E2}
.sub-act-tog{color:#D97706;border-color:#FDE68A;background:#FFFBEB}
.sub-act-tog:hover{background:#FEF3C7}
.sub-act-tog.on{color:#059669;border-color:#A7F3D0;background:#ECFDF5}
</style>

<!-- ══════════ FLASH MESSAGES ══════════ -->
<?php
  $flashes = function_exists('flash_get') ? flash_get() : [];
  foreach ($flashes as $fl):
    $type = $fl['type'] ?? 'info';
    $icon = $type === 'success' ? 'check-circle-fill' : ($type === 'error' ? 'exclamation-triangle-fill' : 'info-circle-fill');
?>
    <div class="pj-flash <?= e($type) ?>">
      <i class="bi bi-<?= $icon ?>"></i>
      <div><?= nl2br(e($fl['msg'])) ?></div>
    </div>
<?php endforeach; ?>

<?php if (!empty($errors['_'])): ?>
  <div class="pj-flash error"><i class="bi bi-exclamation-triangle-fill"></i><div><?= e($errors['_']) ?></div></div>
<?php endif; ?>

<!-- ══════════ MAIN PUJA FORM ══════════ -->
<form method="post" enctype="multipart/form-data" novalidate autocomplete="off">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="edit_puja">
  <div class="form-page">
    <div class="form-main">

      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-info-circle"></i></div>
          <div><h4>Basic Information</h4><p>Name, slug and temple</p></div>
        </div>
        <div class="form-card-body">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="f-label">Puja Name <span class="req">*</span></label>
              <input type="text" name="name" value="<?= e($data['name']) ?>" class="f-input <?= isset($errors['name']) ? 'is-invalid' : '' ?>" required autofocus>
              <?php if (isset($errors['name'])): ?><div class="f-err"><?= e($errors['name']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-4">
              <label class="f-label">Slug</label>
              <input type="text" name="slug" value="<?= e($data['slug']) ?>" class="f-input <?= isset($errors['slug']) ? 'is-invalid' : '' ?>">
              <?php if (isset($errors['slug'])): ?><div class="f-err"><?= e($errors['slug']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-6">
              <label class="f-label">Temple</label>
              <select id="f-temple" name="temple_id" class="f-input">
                <option value="0"  <?= $data['temple_id'] === 0  ? 'selected' : '' ?>>— No specific temple —</option>
                <?php foreach ($temples as $t): ?>
                  <option value="<?= (int) $t['id'] ?>" <?= $data['temple_id'] == $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
                <?php endforeach; ?>
                <option value="-1" <?= $data['temple_id'] === -1 ? 'selected' : '' ?>>＋ Custom Temple (enter below)</option>
              </select>
            </div>

            <div class="col-md-6" id="customTempleWrap" style="<?= $data['temple_id'] === -1 ? '' : 'display:none' ?>">
              <label class="f-label">Custom Temple Name <span class="req">*</span></label>
              <input type="text" id="f-custom-temple" name="custom_temple_name" value="<?= e($data['custom_temple_name']) ?>" class="f-input <?= isset($errors['custom_temple_name']) ? 'is-invalid' : '' ?>">
              <?php if (isset($errors['custom_temple_name'])): ?><div class="f-err"><?= e($errors['custom_temple_name']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-4">
              <label class="f-label">Price (₹) <span class="req">*</span></label>
              <input type="number" step="0.01" min="0" name="price" value="<?= e($data['price']) ?>" class="f-input <?= isset($errors['price']) ? 'is-invalid' : '' ?>">
              <?php if (isset($errors['price'])): ?><div class="f-err"><?= e($errors['price']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-4">
              <label class="f-label">Duration (mins)</label>
              <input type="number" min="0" name="duration_mins" value="<?= e($data['duration_mins']) ?>" class="f-input">
            </div>
            <div class="col-md-4">
              <label class="f-label">Badge Text</label>
              <input type="text" name="badge" value="<?= e($data['badge']) ?>" class="f-input">
            </div>
            <div class="col-md-4">
              <label class="f-label">Badge Style</label>
              <select name="badge_style" class="f-input">
                <?php foreach (['gold','plum','terra','green','blue','amber'] as $s): ?>
                  <option value="<?= $s ?>" <?= $data['badge_style'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>
      </div>

      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-image"></i></div>
          <div><h4>Featured Image</h4><p>Replace or remove</p></div>
        </div>
        <div class="form-card-body">
          <div class="f-upload" id="uploadBox">
            <input type="file" id="f-file" name="image_file" accept="image/jpeg,image/png,image/webp,image/gif">
            <label for="f-file" class="f-upload-btn"><i class="bi bi-cloud-arrow-up"></i> Choose File</label>
            <div class="f-upload-info" id="fileName">No file chosen</div>
          </div>
          <div class="f-imgprev" id="imgPreview" style="margin-top:16px">
            <?php if ($previewUrl): ?>
              <img src="<?= e($previewUrl) ?>" alt="preview">
            <?php else: ?>
              <div class="ph"><i class="bi bi-image"></i>Preview appears here</div>
            <?php endif; ?>
          </div>
          <?php if ($previewUrl): ?>
            <label class="f-toggle" for="f-remove" style="margin-top:12px">
              <input type="checkbox" id="f-remove" name="remove_image" value="1">
              <span class="txt"><b>Remove current image</b><span>Deletes the file</span></span>
            </label>
          <?php endif; ?>
        </div>
      </div>

      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-text-paragraph"></i></div>
          <div><h4>Descriptions</h4><p>Short summary + full details</p></div>
        </div>
        <div class="form-card-body">
          <label class="f-label">Short Description</label>
          <input type="text" name="short_desc" value="<?= e($data['short_desc']) ?>" class="f-input" maxlength="400">
          <div style="height:16px"></div>
          <label class="f-label">Full Description</label>
          <textarea name="description" class="f-textarea" rows="6"><?= e($data['description']) ?></textarea>
        </div>
      </div>

    </div>

    <div class="form-side">
      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-sliders"></i></div>
          <div><h4>Display</h4><p>Ordering</p></div>
        </div>
        <div class="form-card-body">
          <label class="f-label">Sort Order</label>
          <input type="number" name="sort_order" value="<?= (int) $data['sort_order'] ?>" class="f-input">
        </div>
      </div>

      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-star"></i></div>
          <div><h4>Placement</h4><p>Highlight and visibility</p></div>
        </div>
        <div class="form-card-body" style="display:flex;flex-direction:column;gap:10px">
          <label class="f-toggle" for="f-pop">
            <input type="checkbox" id="f-pop" name="is_popular" value="1" <?= $data['is_popular'] ? 'checked' : '' ?>>
            <span class="txt"><b>Popular</b><span>Highlight on homepage</span></span>
          </label>
          <label class="f-toggle" for="f-act">
            <input type="checkbox" id="f-act" name="is_active" value="1" <?= $data['is_active'] ? 'checked' : '' ?>>
            <span class="txt"><b>Active</b><span>Visible for booking</span></span>
          </label>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="form-btn form-btn-save"><i class="bi bi-check2-circle"></i> Save Changes</button>
        <a href="<?= url('admin/modules/pujas/index.php') ?>" class="form-btn form-btn-cancel">Cancel</a>
        <div class="meta">
          <?php if ($refBookings > 0): ?>
            Referenced by <b><?= (int) $refBookings ?></b> booking(s)
          <?php else: ?>
            No bookings — safe to delete
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</form>

<!-- ══════════ SUB-CONTENT BANNER ══════════ -->
<div id="sub-content"></div>
<div class="sub-banner">
  <div class="sub-banner__icon"><i class="bi bi-layers-fill"></i></div>
  <div class="sub-banner__text">
    <h2>Manage Puja Sub-Content</h2>
    <p>Add packages, benefits, procedure steps, FAQs and related pujas — all shown on the public puja page.</p>
  </div>
</div>

<!-- ══════════ TABS ══════════ -->
<div class="sub-tabs">
  <a href="#tab-packages" class="active" data-tab="tab-packages">
    <i class="bi bi-box"></i> Packages <span class="cnt"><?= count($packages) ?></span>
  </a>
  <a href="#tab-benefits" data-tab="tab-benefits">
    <i class="bi bi-star"></i> Benefits <span class="cnt"><?= count($benefits) ?></span>
  </a>
  <a href="#tab-procedures" data-tab="tab-procedures">
    <i class="bi bi-list-ol"></i> Procedure <span class="cnt"><?= count($procedures) ?></span>
  </a>
  <a href="#tab-faqs" data-tab="tab-faqs">
    <i class="bi bi-question-circle"></i> FAQs <span class="cnt"><?= count($faqs) ?></span>
  </a>
  <a href="#tab-related" data-tab="tab-related">
    <i class="bi bi-link-45deg"></i> Related <span class="cnt"><?= count($relatedRows) ?></span>
  </a>
</div>

<!-- ══════════ PACKAGES ══════════ -->
<div id="tab-packages" class="sub-section">
  <h3><i class="bi bi-box" style="color:#B76146"></i> Puja Packages</h3>
  <p class="sub-desc">Different price tiers for this puja. The package marked "Popular" will be pre-selected on the frontend.</p>

  <div class="sub-sec">
    <h4>Existing Packages</h4>
    <?php if (empty($packages)): ?>
      <div class="sub-empty"><i class="bi bi-box"></i> No packages yet — add your first package below.</div>
    <?php else: ?>
      <div class="sub-list">
        <?php foreach ($packages as $pk): ?>
          <div class="sub-item <?= $pk['is_active'] ? '' : 'inactive' ?>">
            <div class="sub-item__icon"><i class="bi bi-box-seam"></i></div>
            <div class="sub-item__main">
              <div class="sub-item__title">
                <?= e($pk['name']) ?>
                <?php if ($pk['is_popular']): ?><span class="sub-item__badge" style="margin-left:6px">★ Popular</span><?php endif; ?>
              </div>
              <div class="sub-item__desc"><?= e(mb_substr($pk['short_desc'] ?? '', 0, 90)) ?></div>
            </div>
            <div class="sub-item__meta">
              <span class="sub-item__price"><?= inr($pk['price']) ?></span>
              <form method="post" style="margin:0">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle_package">
                <input type="hidden" name="row_id" value="<?= (int)$pk['id'] ?>">
                <button class="sub-act sub-act-tog <?= $pk['is_active'] ? 'on' : '' ?>"><i class="bi bi-<?= $pk['is_active'] ? 'toggle-on' : 'toggle-off' ?>"></i></button>
              </form>
              <form method="post" style="margin:0" onsubmit="return confirm('Delete this package?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="del_package">
                <input type="hidden" name="row_id" value="<?= (int)$pk['id'] ?>">
                <button class="sub-act sub-act-del"><i class="bi bi-trash"></i></button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="sub-add">
    <h5><i class="bi bi-plus-circle"></i> Add New Package</h5>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_package">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="f-label">Package Name *</label>
          <input type="text" name="pkg_name" class="f-input" required placeholder="e.g. Gold Package">
        </div>
        <div class="col-md-6">
          <label class="f-label">Slug <small style="opacity:.6">(auto if blank)</small></label>
          <input type="text" name="pkg_slug" class="f-input" placeholder="gold-package">
        </div>
        <div class="col-md-4">
          <label class="f-label">Price (₹) *</label>
          <input type="number" step="0.01" name="pkg_price" class="f-input" required min="0">
        </div>
        <div class="col-md-4">
          <label class="f-label">Compare Price (₹) <small style="opacity:.6">optional</small></label>
          <input type="number" step="0.01" name="pkg_compare_price" class="f-input" min="0">
        </div>
        <div class="col-md-4">
          <label class="f-label">Badge Text <small style="opacity:.6">optional</small></label>
          <input type="text" name="pkg_badge" class="f-input" placeholder="Best Value">
        </div>
        <div class="col-12">
          <label class="f-label">Short Description</label>
          <input type="text" name="pkg_short_desc" class="f-input" maxlength="400">
        </div>
        <div class="col-12">
          <label class="f-label">Included Items <small style="opacity:.6">(one per line)</small></label>
          <textarea name="pkg_included" class="f-textarea" rows="3" placeholder="Full puja video&#10;Premium prasad kit&#10;Personalized sankalp"></textarea>
        </div>
        <div class="col-md-4">
          <label class="f-label">Sort Order</label>
          <input type="number" name="pkg_sort_order" class="f-input" value="0">
        </div>
        <div class="col-md-8">
          <label class="sub-check" style="margin-top:22px">
            <input type="checkbox" name="pkg_is_popular" value="1">
            Mark as Popular (pre-selected on frontend)
          </label>
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="form-btn form-btn-save"><i class="bi bi-plus-lg"></i> Add Package</button>
      </div>
    </form>
  </div>
</div>

<!-- ══════════ BENEFITS ══════════ -->
<div id="tab-benefits" class="sub-section" style="display:none">
  <h3><i class="bi bi-star" style="color:#B76146"></i> Puja Benefits</h3>
  <p class="sub-desc">Spiritual benefits shown on the puja detail page as a card grid.</p>

  <div class="sub-sec">
    <h4>Existing Benefits</h4>
    <?php if (empty($benefits)): ?>
      <div class="sub-empty"><i class="bi bi-star"></i> No benefits yet — add your first below.</div>
    <?php else: ?>
      <div class="sub-list">
        <?php foreach ($benefits as $bn): ?>
          <div class="sub-item <?= $bn['is_active'] ? '' : 'inactive' ?>">
            <div class="sub-item__icon"><?= $bn['icon'] ?: '<i class="bi bi-star"></i>' ?></div>
            <div class="sub-item__main">
              <div class="sub-item__title"><?= e($bn['title']) ?></div>
              <div class="sub-item__desc"><?= e(mb_substr($bn['description'] ?? '', 0, 100)) ?></div>
            </div>
            <div class="sub-item__meta">
              <form method="post" style="margin:0">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle_benefit">
                <input type="hidden" name="row_id" value="<?= (int)$bn['id'] ?>">
                <button class="sub-act sub-act-tog <?= $bn['is_active'] ? 'on' : '' ?>"><i class="bi bi-<?= $bn['is_active'] ? 'toggle-on' : 'toggle-off' ?>"></i></button>
              </form>
              <form method="post" style="margin:0" onsubmit="return confirm('Delete this benefit?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="del_benefit">
                <input type="hidden" name="row_id" value="<?= (int)$bn['id'] ?>">
                <button class="sub-act sub-act-del"><i class="bi bi-trash"></i></button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="sub-add">
    <h5><i class="bi bi-plus-circle"></i> Add New Benefit</h5>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_benefit">
      <div class="row g-3">
        <div class="col-md-8">
          <label class="f-label">Benefit Title *</label>
          <input type="text" name="ben_title" class="f-input" required placeholder="e.g. Removes Obstacles">
        </div>
        <div class="col-md-4">
          <label class="f-label">Icon <small style="opacity:.6">emoji or empty</small></label>
          <input type="text" name="ben_icon" class="f-input" placeholder="🙏" maxlength="10">
        </div>
        <div class="col-12">
          <label class="f-label">Description</label>
          <textarea name="ben_desc" class="f-textarea" rows="2"></textarea>
        </div>
        <div class="col-md-4">
          <label class="f-label">Sort Order</label>
          <input type="number" name="ben_sort_order" class="f-input" value="0">
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="form-btn form-btn-save"><i class="bi bi-plus-lg"></i> Add Benefit</button>
      </div>
    </form>
  </div>
</div>

<!-- ══════════ PROCEDURES ══════════ -->
<div id="tab-procedures" class="sub-section" style="display:none">
  <h3><i class="bi bi-list-ol" style="color:#B76146"></i> Puja Procedure Steps</h3>
  <p class="sub-desc">Step-by-step procedure displayed as a timeline on the frontend.</p>

  <div class="sub-sec">
    <h4>Existing Steps</h4>
    <?php if (empty($procedures)): ?>
      <div class="sub-empty"><i class="bi bi-list-ol"></i> No steps yet — add the first step below.</div>
    <?php else: ?>
      <div class="sub-list">
        <?php foreach ($procedures as $pr): ?>
          <div class="sub-item <?= $pr['is_active'] ? '' : 'inactive' ?>">
            <div class="sub-item__icon"><?= (int)$pr['step_number'] ?></div>
            <div class="sub-item__main">
              <div class="sub-item__title"><?= e($pr['title']) ?></div>
              <div class="sub-item__desc"><?= e(mb_substr($pr['description'] ?? '', 0, 100)) ?></div>
            </div>
            <div class="sub-item__meta">
              <?php if (!empty($pr['image_url'])): ?><span class="sub-item__badge">IMG</span><?php endif; ?>
              <form method="post" style="margin:0">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle_procedure">
                <input type="hidden" name="row_id" value="<?= (int)$pr['id'] ?>">
                <button class="sub-act sub-act-tog <?= $pr['is_active'] ? 'on' : '' ?>"><i class="bi bi-<?= $pr['is_active'] ? 'toggle-on' : 'toggle-off' ?>"></i></button>
              </form>
              <form method="post" style="margin:0" onsubmit="return confirm('Delete this step?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="del_procedure">
                <input type="hidden" name="row_id" value="<?= (int)$pr['id'] ?>">
                <button class="sub-act sub-act-del"><i class="bi bi-trash"></i></button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="sub-add">
    <h5><i class="bi bi-plus-circle"></i> Add New Step</h5>
    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_procedure">
      <div class="row g-3">
        <div class="col-md-3">
          <label class="f-label">Step Number *</label>
          <input type="number" name="prc_step" class="f-input" required min="1" value="<?= count($procedures) + 1 ?>">
        </div>
        <div class="col-md-9">
          <label class="f-label">Step Title *</label>
          <input type="text" name="prc_title" class="f-input" required placeholder="e.g. Sankalp">
        </div>
        <div class="col-12">
          <label class="f-label">Description</label>
          <textarea name="prc_desc" class="f-textarea" rows="2"></textarea>
        </div>
        <div class="col-12">
          <label class="f-label">Optional Image</label>
          <input type="file" name="prc_image" class="f-input" accept="image/*">
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="form-btn form-btn-save"><i class="bi bi-plus-lg"></i> Add Step</button>
      </div>
    </form>
  </div>
</div>

<!-- ══════════ FAQS ══════════ -->
<div id="tab-faqs" class="sub-section" style="display:none">
  <h3><i class="bi bi-question-circle" style="color:#B76146"></i> Puja FAQs</h3>
  <p class="sub-desc">Questions shown on the puja page as an accordion.</p>

  <div class="sub-sec">
    <h4>Existing FAQs</h4>
    <?php if (empty($faqs)): ?>
      <div class="sub-empty"><i class="bi bi-question-circle"></i> No FAQs yet — add the first below.</div>
    <?php else: ?>
      <div class="sub-list">
        <?php foreach ($faqs as $fq): ?>
          <div class="sub-item <?= $fq['is_active'] ? '' : 'inactive' ?>">
            <div class="sub-item__icon"><i class="bi bi-question-lg"></i></div>
            <div class="sub-item__main">
              <div class="sub-item__title"><?= e($fq['question']) ?></div>
              <div class="sub-item__desc"><?= e(mb_substr($fq['answer'], 0, 100)) ?></div>
            </div>
            <div class="sub-item__meta">
              <form method="post" style="margin:0">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle_faq">
                <input type="hidden" name="row_id" value="<?= (int)$fq['id'] ?>">
                <button class="sub-act sub-act-tog <?= $fq['is_active'] ? 'on' : '' ?>"><i class="bi bi-<?= $fq['is_active'] ? 'toggle-on' : 'toggle-off' ?>"></i></button>
              </form>
              <form method="post" style="margin:0" onsubmit="return confirm('Delete this FAQ?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="del_faq">
                <input type="hidden" name="row_id" value="<?= (int)$fq['id'] ?>">
                <button class="sub-act sub-act-del"><i class="bi bi-trash"></i></button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="sub-add">
    <h5><i class="bi bi-plus-circle"></i> Add New FAQ</h5>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_faq">
      <div class="row g-3">
        <div class="col-12">
          <label class="f-label">Question *</label>
          <input type="text" name="faq_q" class="f-input" required maxlength="300">
        </div>
        <div class="col-12">
          <label class="f-label">Answer *</label>
          <textarea name="faq_a" class="f-textarea" rows="3" required></textarea>
        </div>
        <div class="col-md-4">
          <label class="f-label">Sort Order</label>
          <input type="number" name="faq_sort" class="f-input" value="0">
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="form-btn form-btn-save"><i class="bi bi-plus-lg"></i> Add FAQ</button>
      </div>
    </form>
  </div>
</div>

<!-- ══════════ RELATED ══════════ -->
<div id="tab-related" class="sub-section" style="display:none">
  <h3><i class="bi bi-link-45deg" style="color:#B76146"></i> Related Pujas</h3>
  <p class="sub-desc">Manually pick pujas to show in the "You May Also Book" section on the frontend.</p>

  <div class="sub-sec">
    <h4>Currently Related</h4>
    <?php if (empty($relatedRows)): ?>
      <div class="sub-empty"><i class="bi bi-link-45deg"></i> No related pujas yet.</div>
    <?php else: ?>
      <div class="sub-list">
        <?php foreach ($relatedRows as $rr): ?>
          <div class="sub-item">
            <?php $rImg = pj_img_url($rr['image_url']); ?>
            <div class="sub-item__icon" style="padding:0">
              <?php if ($rImg): ?>
                <img src="<?= e($rImg) ?>" alt="">
              <?php else: ?>
                <i class="bi bi-flower1"></i>
              <?php endif; ?>
            </div>
            <div class="sub-item__main">
              <div class="sub-item__title"><?= e($rr['puja_name']) ?></div>
              <div class="sub-item__desc">Sort: <?= (int)$rr['sort_order'] ?></div>
            </div>
            <div class="sub-item__meta">
              <form method="post" style="margin:0" onsubmit="return confirm('Remove this related puja?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="del_related">
                <input type="hidden" name="row_id" value="<?= (int)$rr['related_puja_id'] ?>">
                <button class="sub-act sub-act-del"><i class="bi bi-x-lg"></i></button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="sub-add">
    <h5><i class="bi bi-plus-circle"></i> Add Related Puja</h5>
    <?php if (empty($otherPujas)): ?>
      <div class="sub-empty">No other active pujas to relate.</div>
    <?php else: ?>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add_related">
        <div class="row g-3">
          <div class="col-md-8">
            <label class="f-label">Select Puja *</label>
            <select name="rel_puja_id" class="f-input" required>
              <option value="">— Choose a puja —</option>
              <?php foreach ($otherPujas as $op): ?>
                <option value="<?= (int)$op['id'] ?>"><?= e($op['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="f-label">Sort Order</label>
            <input type="number" name="rel_sort" class="f-input" value="0">
          </div>
        </div>
        <div class="form-actions">
          <button type="submit" class="form-btn form-btn-save"><i class="bi bi-plus-lg"></i> Add Related</button>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>

<script>
(function () {
  /* ---------- Temple custom toggle ---------- */
  var templeSelect = document.getElementById('f-temple');
  var customWrap   = document.getElementById('customTempleWrap');
  var customInput  = document.getElementById('f-custom-temple');

  function toggleCustom() {
    if (!templeSelect || !customWrap) return;
    var v = templeSelect.value;
    if (v === '-1') {
      customWrap.style.display = '';
      if (customInput) customInput.setAttribute('required', 'required');
    } else {
      customWrap.style.display = 'none';
      if (customInput) customInput.removeAttribute('required');
    }
  }
  if (templeSelect) { templeSelect.addEventListener('change', toggleCustom); toggleCustom(); }

  /* ---------- Image preview ---------- */
  var fileInput  = document.getElementById('f-file');
  var fileName   = document.getElementById('fileName');
  var imgPreview = document.getElementById('imgPreview');
  var removeBox  = document.getElementById('f-remove');
  var ph = '<div class="ph"><i class="bi bi-image"></i>Preview appears here</div>';

  function show(src) {
    if (!imgPreview) return;
    if (!src) { imgPreview.innerHTML = ph; return; }
    imgPreview.innerHTML = '<div class="ph">Loading…</div>';
    var img = new Image();
    img.onload  = function () { imgPreview.innerHTML = ''; imgPreview.appendChild(img); };
    img.onerror = function () { imgPreview.innerHTML = '<div class="ph">Invalid image</div>'; };
    img.src = src;
  }
  if (fileInput) fileInput.addEventListener('change', function () {
    var f = fileInput.files && fileInput.files[0];
    if (!f) { if (fileName) fileName.innerHTML = 'No file chosen'; return; }
    if (removeBox) removeBox.checked = false;
    if (fileName) fileName.innerHTML = f.name.replace(/[<>&"]/g, '');
    var r = new FileReader();
    r.onload = function (e) { show(e.target.result); };
    r.readAsDataURL(f);
  });
  if (removeBox) removeBox.addEventListener('change', function () {
    if (removeBox.checked) {
      if (fileInput) fileInput.value = '';
      if (fileName) fileName.innerHTML = 'No file chosen';
      show('');
    }
  });

  /* ---------- Tabs ---------- */
  var tabs = document.querySelectorAll('.sub-tabs a');
  var sections = {
    'tab-packages':   document.getElementById('tab-packages'),
    'tab-benefits':   document.getElementById('tab-benefits'),
    'tab-procedures': document.getElementById('tab-procedures'),
    'tab-faqs':       document.getElementById('tab-faqs'),
    'tab-related':    document.getElementById('tab-related')
  };

  function activateTab(tabId) {
    Object.keys(sections).forEach(function (k) {
      if (!sections[k]) return;
      sections[k].style.display = (k === tabId) ? '' : 'none';
    });
    tabs.forEach(function (t) {
      t.classList.toggle('active', t.getAttribute('data-tab') === tabId);
    });
  }

  tabs.forEach(function (t) {
    t.addEventListener('click', function (e) {
      e.preventDefault();
      var tabId = t.getAttribute('data-tab');
      if (!sections[tabId]) return;
      activateTab(tabId);
      try { history.replaceState(null, '', '#' + tabId); } catch (err) {}
    });
  });

  // Restore from URL hash (supports #sub-content too → default to packages)
  if (window.location.hash) {
    var hashId = window.location.hash.replace('#', '');
    if (sections[hashId]) activateTab(hashId);
    else if (hashId === 'sub-content') {
      activateTab('tab-packages');
      setTimeout(function () {
        var banner = document.querySelector('.sub-banner');
        if (banner) banner.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }, 100);
    }
  }
})();
</script>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>