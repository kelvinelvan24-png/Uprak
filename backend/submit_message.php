<?php
/*
 * submit_message.php
 * Menyimpan pesan baru ke database.
 *
 * Method  : POST
 * Body    : JSON {
 *               receiver      : string,   // recipient_name
 *               senderName    : string?,  // sender_name (boleh kosong/anonim)
 *               songKey       : string,   // spotify_id dari tabel songs
 *               message       : string,
 *               images        : string?   // path file gambar, opsional
 *           }
 * Response: JSON { success, id, slug }
 *
 * Alur:
 *  1. Cari song_id berdasarkan spotify_id (songKey)
 *  2. Generate slug unik
 *  3. INSERT ke tabel messages
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Gunakan POST.']);
    exit;
}

require_once __DIR__ . '/koneksi.php';

$body = file_get_contents('php://input');
$data = json_decode($body, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Request body harus berformat JSON.']);
    exit;
}

// ─── Ambil & validasi field ──────────────────────────────
$receiver    = isset($data['receiver'])    ? trim($data['receiver'])             : '';
$senderName  = isset($data['senderName'])  ? trim($data['senderName'])           : null;
$songKey     = isset($data['songKey'])     ? trim($data['songKey'])              : '';
$message     = isset($data['message'])     ? trim($data['message'])              : '';
$images      = isset($data['images'])      ? trim($data['images'])               : '';
$songDetails = isset($data['songDetails']) ? $data['songDetails']                : null;

if (empty($receiver)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Nama penerima tidak boleh kosong.']);
    exit;
}
if (strlen($receiver) > 100) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Nama penerima maks 100 karakter.']);
    exit;
}
if (empty($songKey)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Lagu harus dipilih.']);
    exit;
}
if (empty($message)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Pesan tidak boleh kosong.']);
    exit;
}
if (strlen($message) > 5000) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Pesan terlalu panjang (maks 5000 karakter).']);
    exit;
}

// ─── Cari song_id dari spotify_id ────────────────────────
$stmtSong = mysqli_prepare($conn, "SELECT id FROM songs WHERE spotify_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmtSong, 's', $songKey);
mysqli_stmt_execute($stmtSong);
$resSong = mysqli_stmt_get_result($stmtSong);
$songRow = mysqli_fetch_assoc($resSong);
mysqli_stmt_close($stmtSong);

$songId = 0;
if ($songRow) {
    $songId = (int)$songRow['id'];
} else if ($songDetails) {
    // Jika lagu belum ada di DB tapi ada info detailnya, insert baru
    $s_title   = isset($songDetails['title']) ? trim($songDetails['title']) : 'Unknown';
    $s_artist  = isset($songDetails['artist']) ? trim($songDetails['artist']) : 'Unknown';
    $s_cover   = isset($songDetails['coverUrl']) ? trim($songDetails['coverUrl']) : '';
    $s_meaning = isset($songDetails['meaning']) ? trim($songDetails['meaning']) : 'Lagu ini melambangkan pesan dan perasaan mendalam yang ingin disampaikan.';
    $s_spotify_url = isset($songDetails['spotifyUrl']) ? trim($songDetails['spotifyUrl']) : '';

    $stmtInsertSong = mysqli_prepare($conn,
        "INSERT INTO songs (spotify_id, title, artist, cover_url, meaning, spotify_url)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param($stmtInsertSong, 'ssssss',
        $songKey,
        $s_title,
        $s_artist,
        $s_cover,
        $s_meaning,
        $s_spotify_url
    );
    
    if (mysqli_stmt_execute($stmtInsertSong)) {
        $songId = mysqli_insert_id($conn);
    }
    mysqli_stmt_close($stmtInsertSong);
}

if ($songId === 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => "Lagu dengan key '$songKey' tidak ditemukan di database dan tidak ada detail lagu."]);
    exit;
}

// ─── Generate slug unik ───────────────────────────────────
// Format: recipient-<random 8 hex chars>  contoh: zahra-a3f9c12b
function generateSlug($name) {
    $base = preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($name)));
    $base = trim($base, '-');
    return $base . '-' . bin2hex(random_bytes(4));
}

// Pastikan slug benar-benar unik
$slug = '';
$attempts = 0;
do {
    $slug = generateSlug($receiver);
    $stmtSlug = mysqli_prepare($conn, "SELECT id FROM messages WHERE slug = ? LIMIT 1");
    mysqli_stmt_bind_param($stmtSlug, 's', $slug);
    mysqli_stmt_execute($stmtSlug);
    mysqli_stmt_store_result($stmtSlug);
    $exists = mysqli_stmt_num_rows($stmtSlug) > 0;
    mysqli_stmt_close($stmtSlug);
    $attempts++;
} while ($exists && $attempts < 5);

// ─── user_id default (0 = anonim) ────────────────────────
$userId = 0;

// ─── Proses Unggah Gambar (Base64) jika ada ────────────────
$imagesVal = '';
if (!empty($images)) {
    if (strpos($images, 'data:image/') === 0) {
        // Format base64: data:image/png;base64,iVBORw0KGgo...
        $parts = explode(',', $images);
        if (count($parts) === 2) {
            $header = $parts[0];
            $dataBase64 = $parts[1];
            
            // Cari ekstensi file
            $ext = 'png';
            if (preg_match('/data:image\/([a-zA-Z0-9+]+);base64/', $header, $matches)) {
                $ext = $matches[1];
                if ($ext === 'jpeg') $ext = 'jpg';
            }
            
            $decodedData = base64_decode($dataBase64);
            if ($decodedData !== false) {
                // Directory uploads di root folder (/SFY/uploads)
                $uploadDir = dirname(__DIR__) . '/uploads';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $filename = uniqid('img_', true) . '.' . $ext;
                $filePath = $uploadDir . '/' . $filename;
                
                if (file_put_contents($filePath, $decodedData) !== false) {
                    $imagesVal = '/SFY/uploads/' . $filename;
                }
            }
        }
    } else {
        // Jika sudah berbentuk path, simpan apa adanya
        $imagesVal = $images;
    }
}

// ─── INSERT ke tabel messages ────────────────────────────
$stmt = mysqli_prepare($conn,
    "INSERT INTO messages (user_id, song_id, recipient_name, sender_name, message, images, slug)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);
mysqli_stmt_bind_param($stmt, 'iisssss',
    $userId,
    $songId,
    $receiver,
    $senderName,
    $message,
    $imagesVal,
    $slug
);

if (mysqli_stmt_execute($stmt)) {
    $newId = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    http_response_code(201);
    echo json_encode([
        'success'   => true,
        'message'   => 'Pesan berhasil dikirim!',
        'id'        => $newId,
        'slug'      => $slug,
        'images'    => $imagesVal,
        'timestamp' => time() * 1000
    ]);
} else {
    $error = mysqli_error($conn);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Gagal menyimpan pesan ke database.',
        'error'   => $error
    ]);
}
?>
