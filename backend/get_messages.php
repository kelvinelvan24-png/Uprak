<?php
/*
 * get_messages.php
 * Mengambil semua pesan dari DB (JOIN dengan tabel songs).
 * Bisa difilter berdasarkan nama penerima.
 *
 * Method  : GET
 * Params  : ?search=<nama> (opsional)
 * Response: JSON { success, count, messages[] }
 *
 * Struktur tabel:
 *   messages : id, user_id, song_id, recipient_name, sender_name,
 *              message, images, slug, created_at
 *   songs    : id, spotify_id, title, artist, cover_url, meaning,
 *              spotify_url, created_at
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/koneksi.php';

$search = isset($_GET['search']) ? trim(strtolower($_GET['search'])) : '';

if ($search !== '') {
    $stmt = mysqli_prepare($conn,
        "SELECT
            m.id,
            m.recipient_name,
            m.sender_name,
            m.message,
            m.images,
            m.slug,
            m.created_at,
            s.spotify_id  AS song_key,
            s.title       AS song_title,
            s.artist      AS song_artist,
            s.cover_url   AS song_cover,
            s.meaning     AS song_meaning,
            s.spotify_url AS song_spotify_url
         FROM messages m
         LEFT JOIN songs s ON m.song_id = s.id
         WHERE LOWER(m.recipient_name) LIKE ?
         ORDER BY m.created_at DESC"
    );
    $like = '%' . $search . '%';
    mysqli_stmt_bind_param($stmt, 's', $like);
} else {
    $stmt = mysqli_prepare($conn,
        "SELECT
            m.id,
            m.recipient_name,
            m.sender_name,
            m.message,
            m.images,
            m.slug,
            m.created_at,
            s.spotify_id  AS song_key,
            s.title       AS song_title,
            s.artist      AS song_artist,
            s.cover_url   AS song_cover,
            s.meaning     AS song_meaning,
            s.spotify_url AS song_spotify_url
         FROM messages m
         LEFT JOIN songs s ON m.song_id = s.id
         ORDER BY m.created_at DESC"
    );
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$messages = [];
while ($row = mysqli_fetch_assoc($result)) {
    $messages[] = [
        'id'           => (int)$row['id'],
        'receiver'     => $row['recipient_name'],
        'senderName'   => $row['sender_name'],
        'songKey'      => $row['song_key'],          // spotify_id dipakai sebagai key
        'songTitle'    => $row['song_title'],
        'songArtist'   => $row['song_artist'],
        'songCover'    => $row['song_cover'],
        'songMeaning'  => $row['song_meaning'],
        'songSpotifyUrl' => $row['song_spotify_url'],
        'message'      => $row['message'],
        'images'       => $row['images'],            // path/URL gambar atau null
        'slug'         => $row['slug'],
        'timestamp'    => strtotime($row['created_at']) * 1000  // ke ms untuk JS
    ];
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

echo json_encode([
    'success'  => true,
    'count'    => count($messages),
    'messages' => $messages
]);
?>
