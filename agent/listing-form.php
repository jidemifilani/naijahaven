<?php
require __DIR__ . '/_top.php';
$aid = $_SESSION['user_id'];
$id = (int)($_GET['id'] ?? 0);
$l = ['title'=>'','listing_type'=>'rent','city'=>'Lagos','area'=>'','address'=>'','bedrooms'=>'','bathrooms'=>'','toilets'=>'',
      'size_sqm'=>'','furnished'=>0,'serviced'=>0,'title_doc'=>'','price_year'=>'','price_night'=>'','price_total'=>'',
      'description'=>'','image_url'=>''];
$existingGallery = [];
if ($id) {
    $q = $pdo->prepare('SELECT * FROM listings WHERE id=? AND agent_id=?');
    $q->execute([$id, $aid]);
    $l = $q->fetch();
    if (!$l) { header('Location: listings.php'); exit; }
    $gq = $pdo->prepare('SELECT * FROM listing_images WHERE listing_id=? ORDER BY sort_order, id');
    $gq->execute([$id]);
    $existingGallery = $gq->fetchAll();
}
$wasApproved = $id && $l['status'] === 'approved';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach (['title','listing_type','city','area','address','title_doc','description','image_url'] as $f) $l[$f] = trim($_POST[$f] ?? '');
    foreach (['bedrooms','bathrooms','toilets','size_sqm'] as $f) $l[$f] = $_POST[$f] !== '' ? (int)$_POST[$f] : null;
    foreach (['price_year','price_night','price_total'] as $f) $l[$f] = $_POST[$f] !== '' ? (float)$_POST[$f] : null;
    $l['furnished'] = isset($_POST['furnished']) ? 1 : 0;
    $l['serviced'] = isset($_POST['serviced']) ? 1 : 0;
    if (!in_array($l['listing_type'], ['rent','shortlet','land'])) $l['listing_type'] = 'rent';

    [$coverUpload, $coverErr] = handle_listing_image_upload($_FILES['cover_photo'] ?? []);
    if ($coverErr) $err = $coverErr;
    elseif ($coverUpload) $l['image_url'] = $coverUpload;

    if (!$err) {
        if ($l['title'] === '' || $l['area'] === '' || $l['city'] === '') $err = 'Title, city and area are required.';
        elseif ($l['listing_type'] === 'rent' && !$l['price_year']) $err = 'Rent listings need an annual rent (₦/year).';
        elseif ($l['listing_type'] === 'shortlet' && !$l['price_night']) $err = 'Shortlets need a nightly rate (₦/night).';
        elseif ($l['listing_type'] === 'land' && (!$l['price_total'] || !$l['size_sqm'])) $err = 'Land needs a total price and size (sqm).';
    }
    if (!$err) {
        $vals = [$l['title'],$l['listing_type'],$l['city'],$l['area'],$l['address'],$l['bedrooms'],$l['bathrooms'],$l['toilets'],
                 $l['size_sqm'],$l['furnished'],$l['serviced'],$l['title_doc'],$l['price_year'],$l['price_night'],$l['price_total'],
                 $l['description'],$l['image_url']];
        if ($id) {
            // edits go back to pending for re-approval
            $pdo->prepare("UPDATE listings SET title=?,listing_type=?,city=?,area=?,address=?,bedrooms=?,bathrooms=?,toilets=?,size_sqm=?,furnished=?,serviced=?,title_doc=?,price_year=?,price_night=?,price_total=?,description=?,image_url=?,status='pending' WHERE id=? AND agent_id=?")
                ->execute([...$vals, $id, $aid]);
            $listingId = $id;
            if ($wasApproved) notify_user($aid, 'Your listing "' . $l['title'] . '" was moved back to pending for re-approval after your edit.', 'agent/listings.php');
        } else {
            $pdo->prepare('INSERT INTO listings (title,listing_type,city,area,address,bedrooms,bathrooms,toilets,size_sqm,furnished,serviced,title_doc,price_year,price_night,price_total,description,image_url,agent_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
                ->execute([...$vals, $aid]);
            $listingId = $pdo->lastInsertId();
        }

        if (!empty($_POST['delete_images'])) {
            $del = $pdo->prepare('DELETE FROM listing_images WHERE id=? AND listing_id=?');
            foreach ($_POST['delete_images'] as $imgId) $del->execute([(int)$imgId, $listingId]);
        }
        if (!empty($_FILES['gallery_photos']['name'][0])) {
            $ins = $pdo->prepare('INSERT INTO listing_images (listing_id, image_url, sort_order) VALUES (?,?,?)');
            foreach ($_FILES['gallery_photos']['name'] as $i => $name) {
                if ($name === '') continue;
                $file = ['name' => $name, 'type' => $_FILES['gallery_photos']['type'][$i],
                         'tmp_name' => $_FILES['gallery_photos']['tmp_name'][$i],
                         'error' => $_FILES['gallery_photos']['error'][$i], 'size' => $_FILES['gallery_photos']['size'][$i]];
                [$gUrl, ] = handle_listing_image_upload($file);
                if ($gUrl) $ins->execute([$listingId, $gUrl, $i]);
            }
        }

        $admins = $pdo->query("SELECT id FROM users WHERE role='admin'")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($admins as $adminId) notify_user($adminId, ($id ? 'Listing edited, needs re-approval: ' : 'New listing awaiting approval: ') . '"' . $l['title'] . '"', 'admin/listings.php?f=pending');

        $_SESSION['flash'] = 'Listing saved — it will appear publicly once approved by admin.';
        header('Location: listings.php'); exit;
    }
}
?>
<h2 style="margin-bottom:20px"><?= $id ? 'Edit listing' : 'Add a listing' ?></h2>
<?php if ($err): ?><div class="error-box"><?= e($err) ?></div><?php endif; ?>
<form method="post" class="panel glass" enctype="multipart/form-data">
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
  <div class="form-group"><label>Listing title</label><input name="title" required placeholder="e.g. Luxury 4-Bed Duplex, Lekki Phase 1" value="<?= e($l['title']) ?>"></div>
  <div class="form-row">
    <div class="form-group"><label>Category</label>
      <select name="listing_type" id="ltype">
        <?php foreach (['rent'=>'For rent (annual)','shortlet'=>'Shortlet (nightly)','land'=>'Land (outright)'] as $v => $lab): ?>
          <option value="<?= $v ?>" <?= $l['listing_type'] === $v ? 'selected' : '' ?>><?= $lab ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group"><label>City</label>
      <select name="city"><?php foreach ($NG_CITIES as $c): ?><option <?= $l['city'] === $c ? 'selected' : '' ?>><?= $c ?></option><?php endforeach; ?></select>
    </div>
  </div>
  <div class="form-row">
    <div class="form-group"><label>Area / neighbourhood</label><input name="area" required placeholder="e.g. Lekki Phase 1, Gwarinpa" value="<?= e($l['area']) ?>"></div>
    <div class="form-group"><label>Street / axis (optional)</label><input name="address" value="<?= e($l['address']) ?>"></div>
  </div>
  <div class="form-row">
    <div class="form-group"><label>Bedrooms</label><input type="number" name="bedrooms" min="0" max="20" value="<?= e($l['bedrooms']) ?>"></div>
    <div class="form-group"><label>Bathrooms</label><input type="number" name="bathrooms" min="0" max="20" value="<?= e($l['bathrooms']) ?>"></div>
  </div>
  <div class="form-row">
    <div class="form-group"><label>Toilets</label><input type="number" name="toilets" min="0" max="20" value="<?= e($l['toilets']) ?>"></div>
    <div class="form-group"><label>Size (sqm) — required for land</label><input type="number" name="size_sqm" min="0" value="<?= e($l['size_sqm']) ?>"></div>
  </div>
  <div class="form-row">
    <div class="form-group"><label>Annual rent (₦/year)</label><input type="number" step="0.01" name="price_year" value="<?= e($l['price_year']) ?>"></div>
    <div class="form-group"><label>Nightly rate (₦/night)</label><input type="number" step="0.01" name="price_night" value="<?= e($l['price_night']) ?>"></div>
  </div>
  <div class="form-row">
    <div class="form-group"><label>Total price (₦) — land</label><input type="number" step="0.01" name="price_total" value="<?= e($l['price_total']) ?>"></div>
    <div class="form-group"><label>Title document (land)</label><input name="title_doc" placeholder="C of O / Governor's Consent / Registered Survey" value="<?= e($l['title_doc']) ?>"></div>
  </div>
  <div class="form-row">
    <div class="form-group" style="display:flex;align-items:center;gap:10px">
      <label style="margin:0;display:flex;gap:10px;align-items:center;color:var(--text)"><input type="checkbox" name="furnished" style="width:auto" <?= $l['furnished'] ? 'checked' : '' ?>> Furnished</label>
    </div>
    <div class="form-group" style="display:flex;align-items:center;gap:10px">
      <label style="margin:0;display:flex;gap:10px;align-items:center;color:var(--text)"><input type="checkbox" name="serviced" style="width:auto" <?= $l['serviced'] ? 'checked' : '' ?>> Serviced</label>
    </div>
  </div>
  <div class="form-row">
    <div class="form-group"><label>Cover photo — upload (JPG/PNG/WEBP, max 5MB)</label><input type="file" name="cover_photo" accept="image/jpeg,image/png,image/webp"></div>
    <div class="form-group"><label>...or cover photo URL</label><input name="image_url" placeholder="https://…" value="<?= e($l['image_url']) ?>"></div>
  </div>
  <?php if ($l['image_url']): ?><img src="<?= e($l['image_url']) ?>" alt="Current cover" style="width:120px;height:80px;object-fit:cover;border-radius:10px;margin-bottom:16px"><?php endif; ?>
  <div class="form-group"><label>Additional photos (gallery — up to several, optional)</label><input type="file" name="gallery_photos[]" accept="image/jpeg,image/png,image/webp" multiple></div>
  <?php if ($existingGallery): ?>
    <div class="gallery-thumbs" style="margin-bottom:18px">
      <?php foreach ($existingGallery as $g): ?>
        <label style="display:flex;flex-direction:column;align-items:center;gap:4px;font-size:.7rem;color:var(--muted)">
          <img src="<?= e($g['image_url']) ?>" alt="Gallery photo">
          <span><input type="checkbox" name="delete_images[]" value="<?= $g['id'] ?>" style="width:auto"> Remove</span>
        </label>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <div class="form-group"><label>Description</label><textarea name="description" rows="4"><?= e($l['description']) ?></textarea></div>
  <button class="btn btn-gold"><?= $id ? 'Save changes' : 'Submit for approval' ?></button>
  <a class="btn btn-ghost" href="listings.php">Cancel</a>
</form>
<?php require __DIR__ . '/_bottom.php'; ?>
