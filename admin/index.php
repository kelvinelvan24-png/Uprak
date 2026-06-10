<?php
/*
 * /SFY/admin/index.php
 * ─────────────────────────────────────────
 * Admin Dashboard SongForYou
 * - Mengelola daftar lagu & makna lagu
 * - Mencari & menambahkan lagu baru dari Spotify
 * - Moderasi/menghapus pesan dari user
 */

require_once __DIR__ . '/../backend/koneksi.php';

$message = '';
$error = '';

// ─── 1. Handling Actions ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'update_meaning') {
        $songId = (int)$_POST['song_id'];
        $meaning = trim($_POST['meaning']);
        
        $stmt = mysqli_prepare($conn, "UPDATE songs SET meaning = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'si', $meaning, $songId);
        if (mysqli_stmt_execute($stmt)) {
            $message = 'Makna lagu berhasil diperbarui!';
        } else {
            $error = 'Gagal memperbarui makna lagu: ' . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    } 
    
    elseif ($action === 'add_song') {
        $spotifyId = trim($_POST['spotify_id']);
        $title = trim($_POST['title']);
        $artist = trim($_POST['artist']);
        $coverUrl = trim($_POST['cover_url']);
        $spotifyUrl = trim($_POST['spotify_url']);
        
        $previewUrl = !empty($_POST['preview_url']) ? trim($_POST['preview_url']) : null;
        if ($previewUrl === 'custom' && !empty($_POST['preview_url_custom'])) {
            $previewUrl = trim($_POST['preview_url_custom']);
        }
        
        $meaning = trim($_POST['meaning']);

        // Cek apakah spotify_id sudah ada
        $checkStmt = mysqli_prepare($conn, "SELECT id FROM songs WHERE spotify_id = ? LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, 's', $spotifyId);
        mysqli_stmt_execute($checkStmt);
        mysqli_stmt_store_result($checkStmt);
        $exists = mysqli_stmt_num_rows($checkStmt) > 0;
        mysqli_stmt_close($checkStmt);

        if ($exists) {
            // Update makna saja
            $updateStmt = mysqli_prepare($conn, "UPDATE songs SET meaning = ?, preview_url = IFNULL(preview_url, ?) WHERE spotify_id = ?");
            mysqli_stmt_bind_param($updateStmt, 'sss', $meaning, $previewUrl, $spotifyId);
            if (mysqli_stmt_execute($updateStmt)) {
                $message = 'Lagu sudah ada di DB. Makna lagu berhasil diperbarui!';
            } else {
                $error = 'Gagal memperbarui lagu: ' . mysqli_error($conn);
            }
            mysqli_stmt_close($updateStmt);
        } else {
            // Insert lagu baru
            $insertStmt = mysqli_prepare($conn, 
                "INSERT INTO songs (spotify_id, title, artist, cover_url, meaning, spotify_url, preview_url) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($insertStmt, 'sssssss', $spotifyId, $title, $artist, $coverUrl, $meaning, $spotifyUrl, $previewUrl);
            if (mysqli_stmt_execute($insertStmt)) {
                $message = 'Lagu baru berhasil ditambahkan ke database!';
            } else {
                $error = 'Gagal menambahkan lagu: ' . mysqli_error($conn);
            }
            mysqli_stmt_close($insertStmt);
        }
    } 
    
    elseif ($action === 'delete_song') {
        $songId = (int)$_POST['song_id'];
        $stmt = mysqli_prepare($conn, "DELETE FROM songs WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $songId);
        if (mysqli_stmt_execute($stmt)) {
            $message = 'Lagu berhasil dihapus dari database!';
        } else {
            $error = 'Gagal menghapus lagu: ' . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    } 
    
    elseif ($action === 'delete_message') {
        $msgId = (int)$_POST['message_id'];
        
        // Cari path gambar untuk dihapus jika ada
        $imgStmt = mysqli_prepare($conn, "SELECT images FROM messages WHERE id = ?");
        mysqli_stmt_bind_param($imgStmt, 'i', $msgId);
        mysqli_stmt_execute($imgStmt);
        $resImg = mysqli_stmt_get_result($imgStmt);
        if ($rowImg = mysqli_fetch_assoc($resImg)) {
            $imgPath = $rowImg['images'];
            if (!empty($imgPath) && strpos($imgPath, '/uploads/') !== false) {
                $realPath = dirname(__DIR__) . str_replace('/SFY', '', $imgPath);
                if (file_exists($realPath)) {
                    @unlink($realPath);
                }
            }
        }
        mysqli_stmt_close($imgStmt);

        $stmt = mysqli_prepare($conn, "DELETE FROM messages WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $msgId);
        if (mysqli_stmt_execute($stmt)) {
            $message = 'Pesan berhasil dihapus!';
        } else {
            $error = 'Gagal menghapus pesan: ' . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}

// ─── 2. Fetch Data ────────────────────────────────────────
// Ambil daftar lagu
$songsResult = mysqli_query($conn, "SELECT * FROM songs ORDER BY id DESC");
$songs = [];
while ($row = mysqli_fetch_assoc($songsResult)) {
    $songs[] = $row;
}

// Ambil daftar pesan
$messagesResult = mysqli_query($conn, 
    "SELECT m.*, s.title AS song_title, s.artist AS song_artist 
     FROM messages m 
     LEFT JOIN songs s ON m.song_id = s.id 
     ORDER BY m.id DESC"
);
$messagesList = [];
while ($row = mysqli_fetch_assoc($messagesResult)) {
    $messagesList[] = $row;
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — SongForYou</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #09090b;
            --card-bg: #18181b;
            --border-color: #27272a;
            --text-primary: #f4f4f5;
            --text-secondary: #a1a1aa;
            --accent-color: #3b82f6;
            --accent-hover: #2563eb;
            --danger-color: #ef4444;
            --danger-hover: #dc2626;
            --spotify-green: #1db954;
            --spotify-hover: #1ed760;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-primary);
            line-height: 1.5;
            padding: 40px 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 20px;
        }

        h1 {
            font-family: 'Syne', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #fff 0%, var(--text-secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            background: var(--card-bg);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            padding: 10px 18px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-back:hover {
            background: var(--border-color);
        }

        /* Tabs */
        .tabs {
            display: flex;
            gap: 12px;
            margin-bottom: 30px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 12px;
        }

        .tab-btn {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            padding: 8px 16px;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .tab-btn:hover {
            color: var(--text-primary);
            background: rgba(255,255,255,0.05);
        }

        .tab-btn.active {
            color: var(--text-primary);
            background: var(--accent-color);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Notification */
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .alert-success {
            background-color: rgba(29, 185, 84, 0.1);
            color: var(--spotify-hover);
            border: 1px solid rgba(29, 185, 84, 0.2);
        }

        .alert-error {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        /* Song List Grid */
        .song-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .song-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
        }

        .song-meta-box {
            display: flex;
            gap: 16px;
            margin-bottom: 16px;
        }

        .album-cover {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            object-fit: cover;
            background-color: #333;
        }

        .album-cover-placeholder {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            background: linear-gradient(135deg, #333, #555);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .song-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .song-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .song-artist {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .badge {
            display: inline-block;
            align-self: flex-start;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 8px;
        }

        .badge-ready {
            background: rgba(29, 185, 84, 0.1);
            color: var(--spotify-hover);
        }

        .badge-pending {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }

        .song-meaning-preview {
            background: rgba(0,0,0,0.2);
            border: 1px solid rgba(255,255,255,0.03);
            padding: 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-bottom: 20px;
            min-height: 60px;
            max-height: 120px;
            overflow-y: auto;
        }

        .card-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-primary {
            background: var(--accent-color);
            color: #fff;
        }

        .btn-primary:hover {
            background: var(--accent-hover);
        }

        .btn-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .btn-danger:hover {
            background: var(--danger-color);
            color: #fff;
        }

        /* Search Section */
        .search-container {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 30px;
        }

        .search-group {
            display: flex;
            gap: 12px;
            max-width: 600px;
            margin-top: 10px;
        }

        .form-control {
            flex: 1;
            background: #09090b;
            border: 1px solid var(--border-color);
            padding: 12px 16px;
            border-radius: 8px;
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            outline: none;
            transition: all 0.2s;
        }

        .form-control:focus {
            border-color: var(--accent-color);
        }

        .search-results {
            margin-top: 24px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }

        .result-card {
            background: rgba(255,255,255,0.02);
            border: 1px solid var(--border-color);
            padding: 16px;
            border-radius: 12px;
            display: flex;
            gap: 16px;
            align-items: center;
            transition: all 0.2s;
        }

        .result-card:hover {
            background: rgba(255,255,255,0.04);
        }

        .btn-add {
            background: var(--spotify-green);
            color: #fff;
            padding: 8px 12px;
            font-size: 0.8rem;
            border-radius: 6px;
        }

        .btn-add:hover {
            background: var(--spotify-hover);
        }

        /* Modal */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay {
            position: absolute;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(4px);
        }

        .modal-container {
            position: relative;
            z-index: 10;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            width: calc(100% - 40px);
            max-width: 500px;
            padding: 30px;
        }

        .modal-header {
            margin-bottom: 20px;
        }

        .modal-title {
            font-family: 'Syne', sans-serif;
            font-size: 1.3rem;
            font-weight: 700;
        }

        .modal-body {
            margin-bottom: 20px;
        }

        .textarea-meaning {
            width: 100%;
            height: 150px;
            background: #09090b;
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: 12px;
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-size: 0.85rem;
            outline: none;
            resize: none;
            margin-top: 8px;
        }

        .textarea-meaning:focus {
            border-color: var(--accent-color);
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        /* Messages moderation table */
        .messages-table-container {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.9rem;
        }

        th, td {
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background: rgba(255,255,255,0.02);
            color: var(--text-secondary);
            font-weight: 600;
        }

        td {
            color: var(--text-primary);
        }

        .td-message {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .td-photo img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 6px;
        }

        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div>
                <h1>Dashboard Admin</h1>
                <p style="color: var(--text-secondary); font-size: 0.9rem;">Kelola Lagu, Makna, dan Moderasi Pesan</p>
            </div>
            <a href="../" class="btn-back">← Lihat Website Utama</a>
        </header>

        <!-- Notification Alerts -->
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Tabs Navigation -->
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('songs-tab')">Daftar Lagu (<?= count($songs) ?>)</button>
            <button class="tab-btn" onclick="switchTab('spotify-tab')">Cari Lagu Spotify</button>
            <button class="tab-btn" onclick="switchTab('manual-tab')">Tambah Lagu Manual</button>
            <button class="tab-btn" onclick="switchTab('messages-tab')">Moderasi Pesan (<?= count($messagesList) ?>)</button>
        </div>

        <!-- Tab 1: Songs List -->
        <div id="songs-tab" class="tab-content active">
            <div class="song-grid">
                <?php if (empty($songs)): ?>
                    <div class="empty-state" style="grid-column: 1/-1;">Belum ada lagu di database. Tambahkan lagu lewat pencarian Spotify.</div>
                <?php else: ?>
                    <?php foreach ($songs as $s): ?>
                        <?php $isReady = !empty($s['meaning']); ?>
                        <div class="song-card">
                            <div>
                                <div class="song-meta-box">
                                    <?php if ($s['cover_url']): ?>
                                        <img src="<?= htmlspecialchars($s['cover_url']) ?>" class="album-cover" alt="Cover">
                                    <?php else: ?>
                                        <div class="album-cover-placeholder">🎵</div>
                                    <?php endif; ?>
                                    <div class="song-info">
                                        <div class="song-title"><?= htmlspecialchars($s['title']) ?></div>
                                        <div class="song-artist"><?= htmlspecialchars($s['artist']) ?></div>
                                        <div>
                                            <span class="badge <?= $isReady ? 'badge-ready' : 'badge-pending' ?>">
                                                <?= $isReady ? 'Aktif (Ada Makna)' : 'Draft (Makna Kosong)' ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="song-meaning-preview">
                                    <strong>Makna:</strong><br>
                                    <?= htmlspecialchars($s['meaning'] ?: 'Belum diisi. Isi makna agar lagu muncul di dropdown pengirim.') ?>
                                </div>
                            </div>
                            
                            <div class="card-actions">
                                <button class="btn btn-primary" onclick="openEditModal(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes($s['title'])) ?>', '<?= htmlspecialchars(addslashes($s['meaning'])) ?>')">Edit Makna</button>
                                <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus lagu ini dari database?');">
                                    <input type="hidden" name="action" value="delete_song">
                                    <input type="hidden" name="song_id" value="<?= $s['id'] ?>">
                                    <button type="submit" class="btn btn-danger">Hapus</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab 2: Spotify Search -->
        <div id="spotify-tab" class="tab-content">
            <div class="search-container">
                <h2>Cari Lagu Baru via Spotify API</h2>
                <p style="color: var(--text-secondary); font-size: 0.85rem; margin-top: 4px;">Cari lagu, klik tambah, dan langsung berikan makna. Lagu akan otomatis masuk ke database dan bisa dipilih user.</p>
                <div class="search-group">
                    <input type="text" id="spotify-search-input" class="form-control" placeholder="Ketik judul lagu atau nama penyanyi...">
                    <button class="btn btn-primary" onclick="performSpotifySearch()">Cari</button>
                </div>
                
                <div id="spotify-loading" style="display: none; margin-top: 20px; color: var(--text-secondary);">Mencari... ⏳</div>
                <div id="spotify-results" class="search-results"></div>
            </div>
        </div>

        <!-- Tab 4: Tambah Lagu Manual -->
        <div id="manual-tab" class="tab-content">
            <div class="search-container" style="max-width: 600px; margin: 0 auto;">
                <h2>Tambah Lagu Secara Manual</h2>
                <p style="color: var(--text-secondary); font-size: 0.85rem; margin-top: 4px; margin-bottom: 20px;">
                    Gunakan form ini jika Anda tidak memiliki koneksi premium Spotify API. Anda dapat mendaftarkan lagu apa saja secara manual ke database.
                </p>
                <form method="POST">
                    <input type="hidden" name="action" value="add_song">
                    <input type="hidden" name="spotify_id" value="manual-<?= bin2hex(random_bytes(4)) ?>">
                    <input type="hidden" name="spotify_url" value="https://open.spotify.com">

                    <div style="margin-bottom: 16px;">
                        <label style="font-size: 0.85rem; color: var(--text-secondary); display: block; margin-bottom: 6px;">Judul Lagu (Wajib):</label>
                        <input type="text" name="title" class="form-control" style="width: 100%;" required placeholder="Contoh: Kangen, Sianida, dll.">
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label style="font-size: 0.85rem; color: var(--text-secondary); display: block; margin-bottom: 6px;">Nama Penyanyi / Band (Wajib):</label>
                        <input type="text" name="artist" class="form-control" style="width: 100%;" required placeholder="Contoh: Dewa 19, Taylor Swift, dll.">
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label style="font-size: 0.85rem; color: var(--text-secondary); display: block; margin-bottom: 6px;">Pilih Nada Pengiring (Audio MP3) — Wajib untuk bersuara:</label>
                        <select name="preview_url" id="manual-preview-select" class="form-control" style="width: 100%; height: auto;" onchange="toggleCustomPreviewInput(this.value)">
                            <option value="https://www.fesliyanstudios.com/play-mp3/4387">Nada Romantis 1 (Piano & Biola - Lembut)</option>
                            <option value="https://www.fesliyanstudios.com/play-mp3/4386">Nada Romantis 2 (Gitar Akustik & Piano - Hangat)</option>
                            <option value="https://www.fesliyanstudios.com/play-mp3/343">Nada Lembut 3 (Piano Klasik - Teduh)</option>
                            <option value="https://www.fesliyanstudios.com/play-mp3/2347">Nada Haru 4 (Piano Melow - Sedih)</option>
                            <option value="https://www.fesliyanstudios.com/play-mp3/4382">Nada Tenang 5 (Piano Damai - Santai)</option>
                            <option value="custom">URL MP3 Kustom (Masukkan Link Sendiri)</option>
                        </select>

                        <!-- Custom URL input + validation -->
                        <div id="manual-custom-url-section" style="display: none; margin-top: 12px;">
                            <div style="background: rgba(245,158,11,0.08); border: 1px solid rgba(245,158,11,0.25); border-radius: 8px; padding: 12px; margin-bottom: 10px; font-size: 0.82rem; line-height: 1.6; color: #f59e0b;">
                                ⚠️ <strong>Penting:</strong> URL harus berupa <strong>link langsung ke file .mp3</strong>, bukan link halaman website.<br>
                                ❌ Salah: <code style="background:rgba(0,0,0,0.2); padding: 2px 6px; border-radius: 3px;">https://situs.com/halaman-lagu/</code><br>
                                ✅ Benar: <code style="background:rgba(0,0,0,0.2); padding: 2px 6px; border-radius: 3px;">https://cdn.situs.com/audio/lagu.mp3</code>
                            </div>
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <input type="url" id="manual-preview-custom-input" name="preview_url_custom" class="form-control" style="flex: 1;" placeholder="https://...url-langsung-file.mp3">
                                <button type="button" onclick="testCustomAudio()" style="background: var(--spotify-green); color: #fff; border: none; padding: 11px 16px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer; white-space: nowrap;">▶ Test Audio</button>
                            </div>
                            <div id="audio-test-result" style="margin-top: 8px; font-size: 0.82rem; display: none;"></div>
                            <audio id="test-audio-player" style="display: none;"></audio>
                        </div>
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label style="font-size: 0.85rem; color: var(--text-secondary); display: block; margin-bottom: 6px;">URL Gambar Cover Album (Opsional):</label>
                        <input type="url" name="cover_url" class="form-control" style="width: 100%;" placeholder="https://link-gambar-cover-album.jpg (biarkan kosong untuk default)">
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="font-size: 0.85rem; color: var(--text-secondary); display: block; margin-bottom: 6px;">Masukkan Makna Lagu (Wajib):</label>
                        <textarea name="meaning" class="textarea-meaning" style="height: 120px; width: 100%;" required placeholder="Tuliskan penjelasan makna lagu ini..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px;">Daftarkan Lagu ke Database</button>
                </form>
            </div>
        </div>

        <!-- Tab 3: Messages Moderation -->
        <div id="messages-tab" class="tab-content">
            <div class="messages-table-container">
                <?php if (empty($messagesList)): ?>
                    <div class="empty-state">Belum ada pesan yang dikirim oleh user.</div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Penerima (To)</th>
                                <th>Lagu Pengiring</th>
                                <th>Pesan</th>
                                <th>Foto</th>
                                <th>Slug Link</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($messagesList as $m): ?>
                                <tr>
                                    <td style="font-size: 0.8rem; color: var(--text-secondary);"><?= date('d M Y H:i', strtotime($m['created_at'])) ?></td>
                                    <td><strong><?= htmlspecialchars($m['recipient_name']) ?></strong></td>
                                    <td>
                                        <?= htmlspecialchars($m['song_title'] ?: 'Unknown') ?><br>
                                        <span style="font-size: 0.75rem; color: var(--text-secondary);"><?= htmlspecialchars($m['song_artist'] ?: 'Unknown') ?></span>
                                    </td>
                                    <td class="td-message" title="<?= htmlspecialchars($m['message']) ?>"><?= htmlspecialchars($m['message']) ?></td>
                                    <td class="td-photo">
                                        <?php if ($m['images']): ?>
                                            <img src="<?= htmlspecialchars($m['images']) ?>" alt="Photo">
                                        <?php else: ?>
                                            <span style="color: var(--text-secondary);">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="../?s=<?= htmlspecialchars($m['slug']) ?>" target="_blank" style="color: var(--accent-color); font-size: 0.8rem; font-weight: 500;">
                                            .../?s=<?= htmlspecialchars($m['slug']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pesan ini secara permanen?');">
                                            <input type="hidden" name="action" value="delete_message">
                                            <input type="hidden" name="message_id" value="<?= $m['id'] ?>">
                                            <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 0.8rem;">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Edit Meaning Modal -->
    <div id="edit-modal" class="modal">
        <div class="modal-overlay" onclick="closeEditModal()"></div>
        <div class="modal-container">
            <div class="modal-header">
                <h3 class="modal-title" id="modal-song-title">Edit Makna Lagu</h3>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_meaning">
                    <input type="hidden" name="song_id" id="edit-song-id">
                    <label style="font-size: 0.85rem; color: var(--text-secondary);">Makna Lagu / Penjelasan Latar Belakang:</label>
                    <textarea name="meaning" id="edit-song-meaning" class="textarea-meaning" required placeholder="Tulis makna lagu di sini..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" style="background: var(--border-color);" onclick="closeEditModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Track Modal (from Spotify Search) -->
    <div id="add-track-modal" class="modal">
        <div class="modal-overlay" onclick="closeAddTrackModal()"></div>
        <div class="modal-container">
            <div class="modal-header">
                <h3 class="modal-title">Tambah Lagu & Makna</h3>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_song">
                    <input type="hidden" name="spotify_id" id="add-spotify-id">
                    <input type="hidden" name="title" id="add-title">
                    <input type="hidden" name="artist" id="add-artist">
                    <input type="hidden" name="cover_url" id="add-cover-url">
                    <input type="hidden" name="spotify_url" id="add-spotify-url">
                    <input type="hidden" name="preview_url" id="add-preview-url">

                    <div style="display: flex; gap: 12px; margin-bottom: 16px; background: rgba(0,0,0,0.2); padding: 12px; border-radius: 8px;">
                        <img id="add-display-cover" src="" style="width: 50px; height: 50px; border-radius: 6px; object-fit: cover;">
                        <div>
                            <div id="add-display-title" style="font-weight: 700; font-size: 0.95rem;"></div>
                            <div id="add-display-artist" style="font-size: 0.8rem; color: var(--text-secondary);"></div>
                        </div>
                    </div>

                    <label style="font-size: 0.85rem; color: var(--text-secondary);">Masukkan Makna Lagu (Wajib agar muncul di form pengirim):</label>
                    <textarea name="meaning" class="textarea-meaning" required placeholder="Tulis makna lagu di sini..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" style="background: var(--border-color);" onclick="closeAddTrackModal()">Batal</button>
                    <button type="submit" class="btn btn-primary" style="background: var(--spotify-green);">Tambahkan Lagu</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Switch tab
        function switchTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            
            document.getElementById(tabId).classList.add('active');
            event.target.classList.add('active');
        }

        // Edit Modal
        function openEditModal(id, title, meaning) {
            document.getElementById('edit-song-id').value = id;
            document.getElementById('modal-song-title').textContent = 'Edit Makna: ' + title;
            document.getElementById('edit-song-meaning').value = meaning;
            document.getElementById('edit-modal').style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('edit-modal').style.display = 'none';
        }

        // Toggle input kustom preview URL pada form manual
        function toggleCustomPreviewInput(val) {
            const section = document.getElementById('manual-custom-url-section');
            const customInput = document.getElementById('manual-preview-custom-input');
            if (val === 'custom') {
                section.style.display = 'block';
                customInput.required = true;
            } else {
                section.style.display = 'none';
                customInput.required = false;
                // Stop any playing test audio
                const testAudio = document.getElementById('test-audio-player');
                if (testAudio) { testAudio.pause(); testAudio.src = ''; }
                const result = document.getElementById('audio-test-result');
                if (result) result.style.display = 'none';
            }
        }

        // Test apakah URL audio bisa diputar langsung
        function testCustomAudio() {
            const urlInput = document.getElementById('manual-preview-custom-input');
            const resultDiv = document.getElementById('audio-test-result');
            const testAudio = document.getElementById('test-audio-player');
            const url = urlInput.value.trim();

            if (!url) {
                resultDiv.innerHTML = '<span style="color:#ef4444;">⚠️ Masukkan URL terlebih dahulu.</span>';
                resultDiv.style.display = 'block';
                return;
            }

            resultDiv.innerHTML = '<span style="color:#a1a1aa;">⏳ Mencoba memutar audio...</span>';
            resultDiv.style.display = 'block';

            testAudio.src = url;
            testAudio.load();

            const timeout = setTimeout(() => {
                testAudio.pause();
                resultDiv.innerHTML = `<span style="color:#ef4444;">❌ <strong>Gagal:</strong> Audio tidak bisa dimuat. Pastikan URL adalah link langsung ke file MP3, bukan halaman website.</span>`;
            }, 5000);

            testAudio.oncanplaythrough = () => {
                clearTimeout(timeout);
                testAudio.play().then(() => {
                    resultDiv.innerHTML = '<span style="color:#1ed760;">✅ <strong>Berhasil!</strong> Audio dapat diputar. URL ini valid.</span>';
                    setTimeout(() => testAudio.pause(), 3000); // Putar 3 detik lalu stop
                }).catch(e => {
                    clearTimeout(timeout);
                    resultDiv.innerHTML = `<span style="color:#ef4444;">❌ Browser memblokir audio: ${e.message}. Coba URL .mp3 yang lain.</span>`;
                });
            };

            testAudio.onerror = () => {
                clearTimeout(timeout);
                resultDiv.innerHTML = `<span style="color:#ef4444;">❌ <strong>URL tidak valid atau diblokir CORS.</strong> Gunakan link .mp3 langsung dari server yang mengizinkan akses publik.</span>`;
            };
        }

        // Spotify Search
        async function performSpotifySearch() {
            const query = document.getElementById('spotify-search-input').value.trim();
            if (!query) return;

            const loading = document.getElementById('spotify-loading');
            const results = document.getElementById('spotify-results');

            loading.style.display = 'block';
            results.innerHTML = '';

            try {
                // Panggil spotify_search.php
                const res = await fetch('../backend/spotify_search.php?q=' + encodeURIComponent(query));
                const json = await res.json();

                loading.style.display = 'none';

                if (json.success) {
                    if (json.tracks && json.tracks.length > 0) {
                        json.tracks.forEach(track => {
                            const card = document.createElement('div');
                            card.className = 'result-card';
                            card.innerHTML = `
                                <img src="${track.coverUrl || 'https://via.placeholder.com/60'}" style="width:60px; height:60px; border-radius:6px; object-fit:cover;">
                                <div style="flex:1;">
                                    <div style="font-weight:700; font-size:0.95rem; max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${track.title}</div>
                                    <div style="font-size:0.8rem; color:var(--text-secondary);">${track.artist}</div>
                                </div>
                                <button class="btn btn-add" onclick='openAddTrackModal(${JSON.stringify(track).replace(/'/g, "&apos;")})'>+ Tambah</button>
                            `;
                            results.appendChild(card);
                        });
                    } else {
                        results.innerHTML = '<div style="color:var(--text-secondary); grid-column:1/-1;">Tidak ada lagu yang ditemukan. Coba keyword lain.</div>';
                    }
                } else {
                    let errMsg = json.message || 'Gagal mencari lagu.';
                    // Tampilkan pesan informatif jika error dari Spotify API
                    if (json.error || errMsg.includes('Spotify')) {
                        errMsg += '<br><br><span style="font-size:0.85rem;color:var(--text-secondary);display:block;padding:12px;background:rgba(255,255,255,0.03);border-radius:6px;border:1px solid var(--border-color);line-height:1.5;">💡 <strong>Info Premium API Spotify:</strong> Akun Spotify Developer Anda membatasi akses API (Error 403: Premium required).<br>Silakan gunakan menu tab <strong>"Tambah Lagu Manual"</strong> untuk menambahkan lagu seperti Taylor Swift atau Dewa 19 secara instan tanpa hambatan API!</span>';
                    }
                    results.innerHTML = '<div style="color:var(--danger-color); grid-column:1/-1; line-height:1.6; text-align:left;">' + errMsg + '</div>';
                }
            } catch (err) {
                loading.style.display = 'none';
                results.innerHTML = '<div style="color:var(--danger-color); grid-column:1/-1;">Gagal melakukan pencarian: ' + err.message + '</div>';
            }
        }

        // Add Track Modal (from Spotify Search)
        function openAddTrackModal(track) {
            document.getElementById('add-spotify-id').value = track.spotifyId;
            document.getElementById('add-title').value = track.title;
            document.getElementById('add-artist').value = track.artist;
            document.getElementById('add-cover-url').value = track.coverUrl;
            document.getElementById('add-spotify-url').value = track.spotifyUrl;
            document.getElementById('add-preview-url').value = track.previewUrl || '';

            document.getElementById('add-display-cover').src = track.coverUrl || 'https://via.placeholder.com/50';
            document.getElementById('add-display-title').textContent = track.title;
            document.getElementById('add-display-artist').textContent = track.artist;

            document.getElementById('add-track-modal').style.display = 'flex';
        }

        function closeAddTrackModal() {
            document.getElementById('add-track-modal').style.display = 'none';
        }
    </script>
</body>
</html>
