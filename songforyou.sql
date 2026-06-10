-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 09 Jun 2026 pada 06.13
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `songforyou`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `song_id` int(11) NOT NULL,
  `recipient_name` varchar(100) NOT NULL,
  `sender_name` varchar(100) DEFAULT NULL,
  `message` text NOT NULL,
  `images` varchar(255) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `messages`
--

INSERT INTO `messages` (`id`, `user_id`, `song_id`, `recipient_name`, `sender_name`, `message`, `images`, `slug`, `created_at`) VALUES
(6, 0, 6, 'kevin', NULL, 'Hai', '/SFY/uploads/img_6a276ea4b08ec0.76768158.jpg', 'kevin-e214121e', '2026-06-09 01:38:44'),
(7, 0, 6, 'na', NULL, 'Hello', '', 'na-bdb8119b', '2026-06-09 01:48:45'),
(8, 0, 7, 'fahri', NULL, 'King', '', 'fahri-652a1ed2', '2026-06-09 02:17:30'),
(9, 0, 8, 'ali', NULL, 'Lope you', '/SFY/uploads/img_6a27787b6e7085.74883679.jpg', 'ali-9698e31a', '2026-06-09 02:20:43'),
(10, 0, 18, 'adra', NULL, 'kangen monting', '', 'adra-d3a009db', '2026-06-09 02:41:04');

-- --------------------------------------------------------

--
-- Struktur dari tabel `songs`
--

CREATE TABLE `songs` (
  `id` int(11) NOT NULL,
  `spotify_id` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `artist` varchar(255) NOT NULL,
  `cover_url` text DEFAULT NULL,
  `meaning` text DEFAULT NULL,
  `spotify_url` varchar(255) NOT NULL,
  `preview_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `songs`
--

INSERT INTO `songs` (`id`, `spotify_id`, `title`, `artist`, `cover_url`, `meaning`, `spotify_url`, `preview_url`, `created_at`) VALUES
(6, '6VggIDYOnf9C8fJVcMpxAu', 'Kangen', 'Dewa 19', 'https://i.scdn.co/image/ab67616d00001e0270ea19fd1f517d40b9c01c0d', 'tentang perasaan rindu yang mendalam akibat terpisah oleh jarak dan waktu. Lagu ini menceritakan tentang seseorang yang sangat ingin pulang untuk bertemu kekasihnya dan meminta pasangannya untuk bersabar serta tidak larut dalam kesedihan.', 'https://open.spotify.com/track/6VggIDYOnf9C8fJVcMpxAu', NULL, '2026-06-09 01:38:14'),
(7, '35o9a4iAfLl5jRmqMX9c1D', 'Shape of My Heart', 'Backstreet Boys', 'https://i.scdn.co/image/ab67616d00001e02cf871e6326e334bfa92cff20', 'tentang penyesalan seorang pria yang menyadari kesalahannya di masa lalu. Ia meminta kesempatan kedua kepada kekasihnya, sambil berjanji untuk menjadi pria yang lebih baik dan lebih terbuka perasaannya.', 'https://open.spotify.com/track/35o9a4iAfLl5jRmqMX9c1D', NULL, '2026-06-09 02:16:44'),
(8, '0V82wcNlunw76nvvmPL9tk', 'I Lay My Love on You - Remix', 'Westlife', 'https://i.scdn.co/image/ab67616d00001e0215f19e3b1fbfc5a2f164fb6a', 'tentang seseorang yang menemukan cinta sejatinya. Lagu ini menggambarkan perasaan kagum yang mendalam, di mana kehadiran sang kekasih diibaratkan seperti malaikat yang membuat hidupnya terasa baru dan penuh harapan.', 'https://open.spotify.com/track/0V82wcNlunw76nvvmPL9tk', NULL, '2026-06-09 02:19:58'),
(9, '3AJwUDP919kvQ9QcozQPxg', 'Yellow', 'Coldplay', 'https://i.scdn.co/image/ab67616d00001e029164bafe9aaa168d93f4816a', 'Melambangkan pengabdian dan cinta yang tulus. Warna kuning mengekspresikan keindahan dan kehangatan yang dibawa seseorang ke dalam hidup kita.', 'https://open.spotify.com/track/3AJwUDP919kvQ9QcozQPxg', NULL, '2026-06-09 02:32:43'),
(10, '5O2P9iiztwhomNh8xkR9lJ', 'Night Changes', 'One Direction', 'https://i.scdn.co/image/ab67616d00001e0234a29f220057810cce98e1b4', 'Waktu berlalu cepat dan hal-hal di sekitar kita berubah, namun cinta dan kebersamaan akan tetap kokoh dan tidak berubah.', 'https://open.spotify.com/track/5O2P9iiztwhomNh8xkR9lJ', NULL, '2026-06-09 02:33:21'),
(14, '4848TVqpmgFkCFk1Acyc3G', 'Photograph', 'Ed Sheeran', 'https://i.scdn.co/image/ab67616d00001e02b05e609859f01835f3fd86e1', 'Bagaimana kenangan manis dan cinta dapat disimpan secara abadi melalui sebuah foto, membantu kita melewati masa-masa sulit saat terpisah jarak.', 'https://open.spotify.com/track/4848TVqpmgFkCFk1Acyc3G', NULL, '2026-06-09 02:38:34'),
(15, '0tgVpDi06FyKpA1z0VMD4v', 'Perfect', 'Ed Sheeran', 'https://i.scdn.co/image/ab67616d00001e02ba5db46f4b838ef6027e6f96', 'Lagu ini menceritakan tentang cinta sejati, kekaguman mendalam, dan komitmen masa depan bersama seseorang yang dianggap sempurna.', 'https://open.spotify.com/track/0tgVpDi06FyKpA1z0VMD4v', NULL, '2026-06-09 02:39:04'),
(16, '4Dvkj6JhhA12EX05fT7y2e', 'As It Was', 'Harry Styles', 'https://i.scdn.co/image/ab67616d00001e0282ce362511fb3d9dda6578ee', 'Tentang kesepian, perubahan hidup yang tak terhindarkan, dan nostalgia masa lalu yang tak bisa kembali seperti semula.', 'https://open.spotify.com/track/4Dvkj6JhhA12EX05fT7y2e', NULL, '2026-06-09 02:39:24'),
(17, '0NLm9bQG7ikL5k9x9TtYT7', 'Here With Me', 'd4vd', 'https://i.scdn.co/image/ab67616d00001e02e5ff1941799cd30cb2aa072b', 'Menceritakan tentang keinginan untuk menua bersama orang yang dicintai dan selalu berada di sampingnya dalam setiap keadaan.', 'https://open.spotify.com/track/0NLm9bQG7ikL5k9x9TtYT7', NULL, '2026-06-09 02:39:50'),
(18, '2mlNgAeIBnL78ZriXgrRHz', 'Glimpse of Us', 'Joji', 'https://i.scdn.co/image/ab67616d00001e02cdd1a8a427b3f81f4f4f4f7d', 'Kerinduan mendalam terhadap masa lalu bersama mantan kekasih saat sedang menjalani hubungan baru dengan orang lain.', 'https://open.spotify.com/track/2mlNgAeIBnL78ZriXgrRHz', NULL, '2026-06-09 02:40:19');

-- --------------------------------------------------------

--
-- Struktur dari tabel `views`
--

CREATE TABLE `views` (
  `id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD UNIQUE KEY `slug_2` (`slug`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `songs`
--
ALTER TABLE `songs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `spotify_id` (`spotify_id`),
  ADD UNIQUE KEY `spotify_id_2` (`spotify_id`);

--
-- Indeks untuk tabel `views`
--
ALTER TABLE `views`
  ADD PRIMARY KEY (`id`),
  ADD KEY `message_id` (`message_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `songs`
--
ALTER TABLE `songs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT untuk tabel `views`
--
ALTER TABLE `views`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `views`
--
ALTER TABLE `views`
  ADD CONSTRAINT `views_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
