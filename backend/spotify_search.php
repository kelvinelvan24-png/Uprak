<?php
/*
 * spotify_search.php
 * Endpoint untuk mencari lagu via Spotify Search API.
 *
 * Method: GET
 * Params: ?q=<keyword>
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/spotify_config.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

// ─── Token Caching & Authorization ──────────────────────────
function getSpotifyAccessToken() {
    $tokenFile = __DIR__ . '/spotify_token.json';
    
    // Check cache
    if (file_exists($tokenFile)) {
        $cache = json_decode(file_get_contents($tokenFile), true);
        if ($cache && isset($cache['access_token']) && isset($cache['expires_at']) && $cache['expires_at'] > time()) {
            return $cache['access_token'];
        }
    }

    $clientId = SPOTIFY_CLIENT_ID;
    $clientSecret = SPOTIFY_CLIENT_SECRET;

    // Jika credential masih default / kosong, signal fallback ke mock data
    if ($clientId === 'your_spotify_client_id_here' || empty($clientId) || $clientSecret === 'your_spotify_client_secret_here' || empty($clientSecret)) {
        return null;
    }

    // Request token baru
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://accounts.spotify.com/api/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode($clientId . ':' . $clientSecret),
        'Content-Type: application/x-www-form-urlencoded'
    ]);

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status === 200) {
        $data = json_decode($response, true);
        if ($data && isset($data['access_token'])) {
            $data['expires_at'] = time() + ($data['expires_in'] - 60); // buffer 60 detik
            file_put_contents($tokenFile, json_encode($data));
            return $data['access_token'];
        }
    }

    return null;
}

$accessToken = getSpotifyAccessToken();

// ─── Mode Mock / Fallback jika tidak ada credentials ──────────
if (!$accessToken) {
    $mockTracks = [
        [
            'spotifyId' => 'perfect',
            'title' => 'Perfect',
            'artist' => 'Ed Sheeran',
            'coverUrl' => '',
            'spotifyUrl' => 'https://open.spotify.com/track/1bhOSXw91E7l4dC6r1zP3Z',
            'meaning' => 'Lagu ini menceritakan tentang cinta sejati, kekaguman mendalam, dan komitmen masa depan bersama seseorang yang dianggap sempurna.'
        ],
        [
            'spotifyId' => 'photograph',
            'title' => 'Photograph',
            'artist' => 'Ed Sheeran',
            'coverUrl' => '',
            'spotifyUrl' => 'https://open.spotify.com/track/1HNkq79nGp0w8im0g8uG6P',
            'meaning' => 'Bagaimana kenangan manis dan cinta dapat disimpan secara abadi melalui sebuah foto, membantu kita melewati masa-masa sulit saat terpisah jarak.'
        ],
        [
            'spotifyId' => 'yellow',
            'title' => 'Yellow',
            'artist' => 'Coldplay',
            'coverUrl' => '',
            'spotifyUrl' => 'https://open.spotify.com/track/3ee8Jmje8o58uM651uUK3g',
            'meaning' => 'Melambangkan pengabdian dan cinta yang tulus. Warna kuning mengekspresikan keindahan dan kehangatan yang dibawa seseorang ke dalam hidup kita.'
        ],
        [
            'spotifyId' => 'until-i-found-you',
            'title' => 'Until I Found You',
            'artist' => 'Stephen Sanchez',
            'coverUrl' => '',
            'spotifyUrl' => 'https://open.spotify.com/track/0T5iZzCiU56zo58s9V0snP',
            'meaning' => 'Mengisahkan tentang menemukan cinta sejati setelah melewati masa-masa kesepian, dan berjanji untuk tidak akan pernah melepaskan orang tersebut.'
        ],
        [
            'spotifyId' => 'night-changes',
            'title' => 'Night Changes',
            'artist' => 'One Direction',
            'coverUrl' => '',
            'spotifyUrl' => 'https://open.spotify.com/track/5t8Z2r5d862yqf4X9y0Z1J',
            'meaning' => 'Waktu berlalu cepat dan hal-hal di sekitar kita berubah, namun cinta dan kebersamaan akan tetap kokoh dan tidak berubah.'
        ]
    ];

    $filtered = [];
    foreach ($mockTracks as $track) {
        if ($q === '' || stripos($track['title'], $q) !== false || stripos($track['artist'], $q) !== false) {
            $filtered[] = $track;
        }
    }

    echo json_encode([
        'success' => true,
        'mode'    => 'mock',
        'message' => 'Silakan isi SPOTIFY_CLIENT_ID & CLIENT_SECRET di backend/spotify_config.php untuk mengaktifkan pencarian real.',
        'tracks'  => $filtered
    ]);
    exit;
}

// ─── Query ke Real Spotify API ──────────────────────────────
if ($q === '') {
    echo json_encode(['success' => true, 'tracks' => []]);
    exit;
}

$searchUrl = 'https://api.spotify.com/v1/search?q=' . urlencode($q) . '&type=track&limit=6';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $searchUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $accessToken
]);

$response = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($status !== 200) {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal mencari lagu ke Spotify API.',
        'error'   => json_decode($response)
    ]);
    exit;
}

$data = json_decode($response, true);
$tracks = [];

if (isset($data['tracks']['items'])) {
    foreach ($data['tracks']['items'] as $item) {
        // Ambil cover image berukuran sedang (indeks 1) atau terkecil (indeks 2)
        $cover = '';
        if (isset($item['album']['images']) && count($item['album']['images']) > 0) {
            $cover = $item['album']['images'][0]['url']; // index 0 biasanya 640x640 px
            if (isset($item['album']['images'][1])) {
                $cover = $item['album']['images'][1]['url']; // index 1 biasanya 300x300 px
            }
        }

        // Cari arti lagu generic/placeholder untuk track baru
        $meaning = 'Lagu "' . $item['name'] . '" oleh ' . $item['artists'][0]['name'] . ' melambangkan pesan dan perasaan mendalam dari pengirim untukmu.';

        $tracks[] = [
            'spotifyId'  => $item['id'],
            'title'      => $item['name'],
            'artist'     => $item['artists'][0]['name'],
            'coverUrl'   => $cover,
            'spotifyUrl' => $item['external_urls']['spotify'],
            'meaning'    => $meaning
        ];
    }
}

echo json_encode([
    'success' => true,
    'mode'    => 'live',
    'tracks'  => $tracks
]);
?>
