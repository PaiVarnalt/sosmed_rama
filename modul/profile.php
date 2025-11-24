<?php
session_start();
include "../lib/koneksi.php"; // pastikan $pdo adalah PDO connection

// redirect kalau belum login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = (int) $_SESSION['user_id'];

// buat folder uploads jika belum ada
$uploadDir = dirname(__FILE__, 2) . '/uploads/'; // ../uploads relatif ke modul
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// --- Ensure columns exist (attempt automatic migration) ---
$needed = [
    'bio' => "TEXT NULL",
    'phone' => "VARCHAR(20) NULL",
    'last_seen' => "DATETIME NULL",
    'telegram_username' => "VARCHAR(50) NULL",
    'photo' => "VARCHAR(255) NULL"
];

try {
    foreach ($needed as $col => $def) {
        $sth = $pdo->prepare("SHOW COLUMNS FROM `users` LIKE ?");
        $sth->execute([$col]);
        if ($sth->rowCount() === 0) {
            // try to add column
            $pdo->exec("ALTER TABLE `users` ADD COLUMN `$col` $def");
        }
    }
} catch (PDOException $e) {
    // Jika tidak boleh ALTER, kita lanjutkan (tapi user akan diberi tahu pada update)
    $_SESSION['flash_error'] = "Warning: unable to auto-migrate DB columns. Jika muncul error saat simpan profil, jalankan ALTER TABLE manual.";
}

// --- Update last_seen (simple) ---
try {
    $u = $pdo->prepare("UPDATE users SET last_seen = NOW() WHERE id = ?");
    $u->execute([$user_id]);
} catch (Exception $e) {
    // ignore
}

// --- Handle photo upload (via normal POST from AJAX/form) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!empty($_POST['action']) && $_POST['action'] === 'update_photo' || isset($_FILES['photo']))) {
    if (!empty($_FILES['photo']['name'])) {
        $file = $_FILES['photo']['name'];
        $tmp  = $_FILES['photo']['tmp_name'];
        $size = $_FILES['photo']['size'];
        $ext  = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];

        if (!in_array($ext, $allowed)) {
            $_SESSION['flash_error'] = "Format file tidak diperbolehkan. Gunakan JPG/PNG/GIF/WEBP.";
        } elseif ($size > 3 * 1024 * 1024) {
            $_SESSION['flash_error'] = "Ukuran file maksimal 3MB.";
        } else {
            $newName = "profile_" . $user_id . "." . $ext;
            $dest = $uploadDir . $newName;
            if (move_uploaded_file($tmp, $dest)) {
                try {
                    $stmt = $pdo->prepare("UPDATE users SET photo = ? WHERE id = ?");
                    $stmt->execute([$newName, $user_id]);
                    $_SESSION['flash_success'] = "Foto profil berhasil diperbarui.";
                } catch (PDOException $e) {
                    $_SESSION['flash_error'] = "Foto ter-upload tetapi gagal menyimpan ke DB: " . $e->getMessage();
                }
            } else {
                $_SESSION['flash_error'] = "Gagal memindahkan file upload.";
            }
        }
    } else {
        $_SESSION['flash_error'] = "Tidak ada file yang diunggah.";
    }

    // jika request AJAX, kita akan exit dengan status singkat
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        echo json_encode(['ok' => isset($_SESSION['flash_success']), 'msg' => $_SESSION['flash_success'] ?? $_SESSION['flash_error'] ?? '']);
        exit();
    } else {
        header("Location: profile.php");
        exit();
    }
}

// --- Handle profile update (username, telegram_username, bio, phone but phone treated private) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action']) && $_POST['action'] === 'update_profile') {
    $name = trim($_POST['username'] ?? '');
    $tuser = trim($_POST['telegram_username'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($name === '') {
        $_SESSION['flash_error'] = "Nama tidak boleh kosong.";
    } else {
        try {
            // attempt update, columns are ensured above but if ALTER failed earlier and column missing this may error
            $stmt = $pdo->prepare("UPDATE users SET username = ?, telegram_username = ?, bio = ?, phone = ? WHERE id = ?");
            $stmt->execute([$name, $tuser ?: null, $bio ?: null, $phone ?: null, $user_id]);
            $_SESSION['flash_success'] = "Profil berhasil diperbarui.";
        } catch (PDOException $e) {
            $_SESSION['flash_error'] = "Gagal menyimpan profil: " . $e->getMessage();
        }
    }
    header("Location: profile.php");
    exit();
}

// ambil data user terbaru
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// helper for image src
function profile_src($photo) {
    if (!$photo) return "../uploads/default.png";
    return "../uploads/" . $photo;
}

$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error   = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Profile — Telegram Desktop Style</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
:root{ --tg-blue:#2a8af7; --bg:#f5f7fb; --card:#fff; --muted:#6b7280; }
[data-theme="dark"]{ --bg:#071026; --card:#0b1220; --muted:#9aa6bf; }
body{ background: linear-gradient(180deg,var(--bg),#eef4ff 60%); min-height:100vh; font-family:system-ui, -apple-system, "Segoe UI", Roboto, Arial; margin:0; padding:0; }
.topbar{ height:68px; background:var(--tg-blue); color:#fff; display:flex; align-items:center; padding:0 18px; box-shadow:0 2px 8px rgba(10,30,80,0.08); }
.topbar .back{ width:46px; height:46px; border-radius:8px; display:flex; align-items:center; justify-content:center; background: rgba(255,255,255,0.12); margin-right:12px; color:white; text-decoration:none; }
.container-card{ max-width:1100px; margin:26px auto; padding:0 12px; }
.profile-card{ background:var(--card); border-radius:12px; padding:22px; display:flex; gap:28px; align-items:flex-start; box-shadow:0 6px 24px rgba(13,38,76,0.06); }
.avatar-wrap{ width:240px; text-align:center; }
.avatar-container{ position:relative; display:inline-block; }
.avatar{ width:170px; height:170px; border-radius:50%; object-fit:cover; display:block; z-index:2; position:relative; border:4px solid transparent; box-shadow:0 12px 30px rgba(20,40,90,0.12); }
.avatar-ring{ position:absolute; left:50%; top:50%; transform:translate(-50%,-50%); width:188px; height:188px; border-radius:50%; z-index:1; background: conic-gradient(#20a8ff,#2a8af7,#4aa3ff); filter: blur(0.6px); }
.avatar-overlay{ position:absolute; right:2px; bottom:2px; width:44px; height:44px; border-radius:50%; display:flex; align-items:center; justify-content:center; background:var(--tg-blue); color:white; border:3px solid var(--card); cursor:pointer; z-index:3; box-shadow:0 8px 18px rgba(38,108,255,0.18); }
.profile-info{ flex:1; }
.profile-name{ font-size:22px; font-weight:700; color:#0f1724; }
.profile-username{ color:var(--muted); margin-top:6px; }
.bio{ margin-top:12px; color:var(--muted); line-height:1.45; }
.meta-list{ margin-top:18px; display:flex; gap:12px; flex-wrap:wrap; }
.meta-item{ background: rgba(42,138,247,0.08); padding:8px 12px; border-radius:8px; color:var(--tg-blue); font-weight:600; }

.right-panel{ width:360px; }
.card-panel{ background: linear-gradient(180deg, rgba(255,255,255,0.6), rgba(255,255,255,0.35)); border-radius:12px; padding:16px; box-shadow: inset 0 1px 0 rgba(255,255,255,0.6); }
.form-control:focus{ box-shadow:none; border-color:var(--tg-blue); }
.btn-telegram{ background:var(--tg-blue); border:none; color:#fff; }

.sidebar{ width:72px; background:transparent; display:flex; flex-direction:column; gap:12px; padding:12px; align-items:center; }
.side-btn{ width:48px; height:48px; border-radius:10px; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.04); cursor:pointer; }
.side-btn.active{ background: rgba(42,138,247,0.12); box-shadow:0 6px 14px rgba(38,108,255,0.08); }

@media(max-width:900px){ .profile-card{ flex-direction:column; align-items:center; text-align:center; } .right-panel{ width:100%; } .avatar-wrap{ order:-1; } .sidebar{ display:none; } }

/* flash */
.flash{ padding:10px 14px; border-radius:8px; margin-bottom:14px; }
.flash-success{ background:#e6f6ff; color:#0256a8; border-left:4px solid #2a8af7; }
.flash-error{ background:#fff0f0; color:#7a1b1b; border-left:4px solid #ff6b6b; }
</style>
</head>
<body>

<div class="topbar">
  <a class="back" href="../index.php" title="Kembali">←</a>
  <div>
    <div style="font-weight:700; font-size:18px">Profile</div>
    <small style="opacity:.9">Telegram-like desktop profile</small>
  </div>

  <div style="margin-left:auto; display:flex; align-items:center; gap:12px;">
    <label style="color:white; font-weight:600; margin-right:8px;">Dark</label>
    <input id="themeToggle" type="checkbox" aria-label="toggle theme">
  </div>
</div>

<div class="container-card">
  <?php if($flash_success): ?>
    <div class="flash flash-success"><?= htmlspecialchars($flash_success) ?></div>
  <?php endif; ?>
  <?php if($flash_error): ?>
    <div class="flash flash-error"><?= htmlspecialchars($flash_error) ?></div>
  <?php endif; ?>

  <div style="display:flex; gap:12px;">

    <div class="profile-card">
      <!-- Avatar Left -->
      <div class="avatar-wrap">
        <div class="avatar-container">
          <div class="avatar-ring"></div>
          <img id="avatarImg" src="<?= htmlspecialchars(profile_src($user['photo'] ?? null)) ?>" alt="avatar" class="avatar">
          <label class="avatar-overlay" for="photoInput" title="Change photo">✏</label>
        </div>

        <div style="margin-top:12px; font-weight:700; color:var(--muted);">Change profile photo</div>

        <!-- Hidden upload form -->
        <form id="photoForm" method="POST" enctype="multipart/form-data" style="display:none;">
          <input type="hidden" name="action" value="update_photo">
          <input id="photoInput" type="file" name="photo" accept="image/*">
        </form>

        <div style="margin-top:12px; font-size:13px; color:var(--muted);">
          <small>Preview uploaded immediately. File ≤ 3MB.</small>
        </div>

        <!-- optional link to your local mockup image (developer note) -->
        <div style="margin-top:12px;">
          <a href="/mnt/data/75cd3425-bada-4bd6-bd15-850e89abbe5d.png" target="_blank" style="font-size:12px;">Open mockup reference</a>
        </div>
      </div>

      <!-- Info Middle -->
      <div class="profile-info">
        <div class="profile-name"><?= htmlspecialchars($user['username'] ?? '') ?></div>
        <div class="profile-username">@<?= htmlspecialchars($user['telegram_username'] ?? ($user['username'] ?? '')) ?></div>

        <div class="bio"><?= nl2br(htmlspecialchars($user['bio'] ?? 'Belum ada bio. Klik edit untuk mengubah.')) ?></div>

        <div class="meta-list">
          <div class="meta-item">Last seen: <?= htmlspecialchars($user['last_seen'] ? date('d M Y H:i', strtotime($user['last_seen'])) : '—') ?></div>
          <div class="meta-item">Phone: (private)</div>
          <div class="meta-item">ID: <?= htmlspecialchars($user['id'] ?? $user_id) ?></div>
        </div>
      </div>

      <!-- Right Panel (Edit) -->
      <div class="right-panel">
        <div class="card-panel">
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <strong style="font-size:15px;">Edit Profile</strong>
            <div style="display:flex; gap:8px;">
              <button id="qrBtn" class="btn btn-outline-secondary btn-sm" title="Show QR">QR</button>
              <button id="openModalBtn" class="btn btn-outline-primary btn-sm">Edit</button>
            </div>
          </div>

          <hr style="margin:10px 0 14px 0; opacity:.06">

          <div style="font-size:13px; color:var(--muted); margin-bottom:8px;"><strong>Quick actions</strong></div>
          <div style="display:flex; gap:8px;">
            <a href="change_password.php" class="btn btn-outline-secondary btn-sm">Change password</a>
            <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal (Bootstrap-lite) -->
<div class="modal" id="editModal" tabindex="-1" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.4); align-items:center; justify-content:center; z-index:9999;">
  <div style="background:var(--card); width:720px; border-radius:12px; padding:18px; box-shadow:0 20px 60px rgba(5,20,60,0.4);">
    <div style="display:flex; justify-content:space-between; align-items:center;">
      <h5 style="margin:0">Edit Profile</h5>
      <button id="closeModal" class="btn btn-sm btn-outline-secondary">Close</button>
    </div>
    <hr>
    <form id="editForm" method="POST">
      <input type="hidden" name="action" value="update_profile">
      <div class="mb-3">
        <label class="form-label">Name</label>
        <input class="form-control" name="username" required value="<?= htmlspecialchars($user['username'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Telegram Username (handle)</label>
        <input class="form-control" name="telegram_username" placeholder="tanpa @, contoh: rama" value="<?= htmlspecialchars($user['telegram_username'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Bio / About</label>
        <textarea class="form-control" name="bio" rows="3"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
      </div>
      <div class="mb-3">
        <label class="form-label">Phone (private)</label>
        <input class="form-control" name="phone" placeholder="+62..." value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
        <div style="font-size:12px; color:var(--muted); margin-top:6px;">Nomor disimpan tapi <strong>tidak ditampilkan</strong> publik.</div>
      </div>

      <div style="display:flex; gap:8px; justify-content:flex-end;">
        <button type="button" id="cancelBtn" class="btn btn-outline-secondary">Batal</button>
        <button type="submit" class="btn btn-telegram">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- QR Modal -->
<div class="modal" id="qrModal" tabindex="-1" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); align-items:center; justify-content:center; z-index:9999;">
  <div style="background:var(--card); width:320px; border-radius:12px; padding:18px; text-align:center;">
    <h6>Profile QR</h6>
    <div id="qrImageWrap" style="margin:12px 0;">
      <!-- QR will be injected here -->
      <img id="qrImg" src="" alt="QR" style="width:200px; height:200px;">
    </div>
    <button id="closeQr" class="btn btn-outline-secondary">Close</button>
  </div>
</div>

<script>
// Theme
const themeToggle = document.getElementById('themeToggle');
const currentTheme = localStorage.getItem('theme') || 'light';
if (currentTheme === 'dark') {
  document.documentElement.setAttribute('data-theme','dark');
  themeToggle.checked = true;
}
themeToggle.addEventListener('change', function(){
  if (this.checked) { document.documentElement.setAttribute('data-theme','dark'); localStorage.setItem('theme','dark'); }
  else { document.documentElement.removeAttribute('data-theme'); localStorage.setItem('theme','light'); }
});

// Photo input upload (AJAX)
const photoInput = document.getElementById('photoInput');
const photoForm = document.getElementById('photoForm');
const avatarImg = document.getElementById('avatarImg');

photoInput.addEventListener('change', function(){
  if (this.files && this.files[0]) {
    const file = this.files[0];
    // preview
    const fr = new FileReader();
    fr.onload = function(e){ avatarImg.src = e.target.result; }
    fr.readAsDataURL(file);

    // prepare FormData
    const fd = new FormData();
    fd.append('action', 'update_photo');
    fd.append('photo', file);

    fetch('profile.php', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(r => r.json())
    .then(j => {
      if (j.ok) {
        // reload to show server-stored filename and flash message
        window.location.reload();
      } else {
        alert(j.msg || 'Upload error');
        window.location.reload();
      }
    }).catch(e => { alert('Upload gagal'); window.location.reload(); });
  }
});

// Modal logic
const editModal = document.getElementById('editModal');
const openModalBtn = document.getElementById('openModalBtn');
const closeModal = document.getElementById('closeModal');
const cancelBtn = document.getElementById('cancelBtn');

openModalBtn.addEventListener('click', ()=> { editModal.style.display = 'flex'; });
closeModal.addEventListener('click', ()=> { editModal.style.display = 'none'; });
cancelBtn.addEventListener('click', ()=> { editModal.style.display = 'none'; });

// QR Modal
const qrBtn = document.getElementById('qrBtn');
const qrModal = document.getElementById('qrModal');
const qrImg = document.getElementById('qrImg');
const closeQr = document.getElementById('closeQr');

qrBtn.addEventListener('click', () => {
  // create a small profile link (you may want to change to real public profile URL)
  const handle = "<?= rawurlencode($user['telegram_username'] ?? ($user['username'] ?? 'user')) ?>";
  const profileUrl = window.location.origin + '/u/' + handle; // example
  // use Google Chart API for quick QR (or replace with your own generator)
  const qrUrl = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=" + encodeURIComponent(profileUrl);
  qrImg.src = qrUrl;
  qrModal.style.display = 'flex';
});
closeQr.addEventListener('click', ()=> { qrModal.style.display = 'none'; });

// close modal when clicking outside
window.addEventListener('click', (e) => {
  if (e.target === editModal) editModal.style.display = 'none';
  if (e.target === qrModal) qrModal.style.display = 'none';
});
</script>
</body>
</html>
