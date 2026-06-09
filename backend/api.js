/* Endpoint:
*   GET  /backend/get_messages.php[?search=nama]
*   POST /backend/submit_message.php
*   GET  /backend/get_songs.php
*   DELETE /backend/delete_message.php?id=<int>
*/

const SFY_API = (() => {
    // ── BASE_URL: sesuai lokasi project di htdocs ──────────────────
    // Path: http://localhost/SFY/backend
    const BASE_URL = '/SFY/backend';

    /**
     * Ambil semua pesan dari DB, JOIN dengan songs.
     * @param {string} [search=''] - Filter nama penerima (opsional)
     * @returns {Promise<Array>} Array message objects
     */
    async function getMessages(search = '') {
        const url = search
            ? `${BASE_URL}/get_messages.php?search=${encodeURIComponent(search)}`
            : `${BASE_URL}/get_messages.php`;

        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const json = await res.json();
        if (!json.success) throw new Error(json.message || 'Gagal mengambil pesan.');
        return json.messages;
        // Setiap item: { id, receiver, senderName, songKey, songTitle, songArtist,
        //               songCover, songMeaning, songSpotifyUrl, message, images, slug, timestamp }
    }

    /**
     * Kirim pesan baru ke DB.
     * @param {Object} data
     * @param {string}      data.receiver   - Nama penerima
     * @param {string}      data.songKey    - spotify_id dari tabel songs
     * @param {string}      data.message    - Isi pesan
     * @param {string|null} data.senderName - Nama pengirim (opsional, null = anonim)
     * @param {string|null} data.images     - Path/URL gambar atau base64 (opsional)
     * @param {Object|null} data.songDetails - Informasi lagu dari Spotify (opsional)
     * @returns {Promise<{id: number, slug: string, images: string, timestamp: number}>}
     */
    async function submitMessage({ receiver, songKey, message, senderName = null, images = null, songDetails = null }) {
        const res = await fetch(`${BASE_URL}/submit_message.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ receiver, songKey, message, senderName, images, songDetails })
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message || 'Gagal mengirim pesan.');
        return { id: json.id, slug: json.slug, images: json.images, timestamp: json.timestamp };
    }

    /**
     * Hapus pesan berdasarkan ID.
     * @param {number} id
     */
    async function deleteMessage(id) {
        const res = await fetch(`${BASE_URL}/delete_message.php?id=${id}`, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json' }
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message || 'Gagal menghapus pesan.');
    }

    /**
     * Ambil daftar lagu dari DB (untuk populate <select> form).
     * @returns {Promise<Array>} Array song objects: { id, spotifyId, title, artist, coverUrl, meaning, spotifyUrl }
     */
    async function getSongs() {
        const res = await fetch(`${BASE_URL}/get_songs.php`, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const json = await res.json();
        if (!json.success) throw new Error(json.message || 'Gagal mengambil daftar lagu.');
        return json.songs;
    }

    /**
     * Cari lagu via Spotify Search API (dengan mock fallback di backend).
     * @param {string} query
     * @returns {Promise<Array>}
     */
    async function searchSpotifyTracks(query) {
        const res = await fetch(`${BASE_URL}/spotify_search.php?q=${encodeURIComponent(query)}`, {
            headers: { 'Accept': 'application/json' }
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const json = await res.json();
        if (!json.success) throw new Error(json.message || 'Gagal mencari lagu.');
        return json.tracks;
    }

    return { getMessages, submitMessage, deleteMessage, getSongs, searchSpotifyTracks };
})();
