-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Waktu pembuatan: 17 Des 2025 pada 13.44
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
-- Database: `elibrary_db`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `books`
--

CREATE TABLE `books` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `cover` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `link` text DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `type` enum('BOOK','JOURNAL','ARTICLE') DEFAULT 'BOOK',
  `status` enum('APPROVED','PENDING','REJECTED') DEFAULT 'APPROVED',
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `books`
--

INSERT INTO `books` (`id`, `title`, `author`, `description`, `cover`, `file_path`, `link`, `year`, `type`, `status`, `uploaded_by`, `created_at`) VALUES
(35, 'Bumi Manusia', 'Pramoedya Ananta Toer', 'Roman tetralogi Buru yang mengambil latar belakang masa kebangkitan nasional awal abad ke-20.', NULL, 'bumi_manusia.pdf', NULL, 1980, 'BOOK', 'APPROVED', 2, '2025-12-15 14:16:44'),
(36, 'Atomic Habits', 'James Clear', 'Cara mudah dan terbukti untuk membentuk kebiasaan baik dan menghilangkan kebiasaan buruk.', NULL, 'atomic_habits.pdf', NULL, 2018, 'BOOK', 'APPROVED', 2, '2025-12-15 14:16:44'),
(37, 'Sapiens: Riwayat Singkat Umat Manusia', 'Yuval Noah Harari', 'Menjelajahi cara manusia biologis (Homo sapiens) berevolusi dan menaklukkan dunia.', NULL, 'sapiens.pdf', NULL, 2011, 'BOOK', 'APPROVED', 2, '2025-12-15 14:16:44'),
(38, 'Laskar Pelangi', 'Andrea Hirata', 'Kisah inspiratif tentang anak-anak di Belitung yang berjuang untuk sekolah.', NULL, 'laskar_pelangi.pdf', NULL, 2005, 'BOOK', 'APPROVED', 2, '2025-12-15 14:16:44'),
(39, 'Psikologi Uang', 'Morgan Housel', 'Pelajaran abadi tentang kekayaan, ketamakan, dan kebahagiaan.', NULL, 'psychology_of_money.pdf', NULL, 2020, 'BOOK', 'APPROVED', 2, '2025-12-15 14:16:44'),
(40, 'Laut Bercerita', 'Leila S. Chudori', 'Novel sejarah yang mengangkat tema aktivisme mahasiswa di era reformasi 1998.', NULL, 'laut_bercerita.pdf', NULL, 2017, 'BOOK', 'APPROVED', 2, '2025-12-15 14:16:44'),
(41, 'Rich Dad Poor Dad', 'Robert T. Kiyosaki', 'Buku klasik tentang pengelolaan keuangan pribadi dan investasi.', NULL, 'rich_dad.pdf', NULL, 1997, 'BOOK', 'APPROVED', 2, '2025-12-15 14:16:44'),
(42, 'Dunia Sophie', 'Jostein Gaarder', 'Novel tentang sejarah filsafat yang dikemas dalam cerita misteri.', NULL, 'dunia_sophie.pdf', NULL, 1991, 'BOOK', 'APPROVED', 2, '2025-12-15 14:16:44'),
(43, 'Pemrograman Web dengan PHP dan MySQL', 'Budi Raharjo', 'Panduan lengkap belajar membuat website dinamis untuk pemula.', NULL, 'pemrograman_web.pdf', NULL, 2022, 'BOOK', 'APPROVED', 2, '2025-12-15 14:16:44'),
(44, 'Pulang', 'Leila S. Chudori', 'Novel tentang perjalanan pulang seorang eksil politik ke tanah airnya.', NULL, 'pulang.pdf', NULL, 2012, 'BOOK', 'APPROVED', 8, '2025-12-15 14:16:44'),
(45, 'Hujan', 'Tere Liye', 'Novel tentang persahabatan, cinta, dan perpisahan di dunia masa depan.', NULL, 'hujan.pdf', NULL, 2016, 'BOOK', 'APPROVED', 8, '2025-12-15 14:16:44'),
(46, 'Cantik Itu Luka', 'Eka Kurniawan', 'Kisah magis dan tragis tentang kecantikan dan luka sejarah di Halimunda.', NULL, 'cantik_itu_luka.pdf', NULL, 2002, 'BOOK', 'APPROVED', 8, '2025-12-15 14:16:44'),
(47, 'Gadis Kretek', 'Ratih Kumala', 'Menelusuri sejarah industri kretek di Indonesia lewat kisah cinta.', NULL, 'gadis_kretek.pdf', NULL, 2012, 'BOOK', 'APPROVED', 8, '2025-12-15 14:16:44'),
(48, 'Madilog', 'Tan Malaka', 'Karya filsafat materialisme, dialektika, dan logika dari bapak republik.', NULL, 'madilog.pdf', NULL, 1943, 'BOOK', 'APPROVED', 8, '2025-12-15 14:16:44'),
(49, 'Python Dasar untuk Data Science', 'Jubilee Enterprise', 'Panduan awal menggunakan Python untuk pengolahan data.', NULL, 'python_ds.pdf', NULL, 2023, 'BOOK', 'PENDING', 8, '2025-12-15 14:16:44'),
(50, 'UI/UX Design for Beginners', 'Michael Malewicz', 'Prinsip dasar desain antarmuka pengguna yang efektif.', NULL, 'ui_ux_design.pdf', NULL, 2021, 'BOOK', 'PENDING', 8, '2025-12-15 14:16:44'),
(51, 'Sejarah Modern Indonesia', 'M.C. Ricklefs', 'Sejarah komprehensif Indonesia sejak kedatangan Islam hingga masa kini.', NULL, 'sejarah_modern.pdf', NULL, 2008, 'BOOK', 'PENDING', 8, '2025-12-15 14:16:44'),
(52, 'The Power of Habit', 'Charles Duhigg', 'Mengapa kita melakukan apa yang kita lakukan dalam hidup dan bisnis.', NULL, 'power_of_habit.pdf', NULL, 2012, 'BOOK', 'PENDING', 8, '2025-12-15 14:16:44'),
(53, 'Sebuah Seni untuk Bersikap Bodo Amat', 'Mark Manson', 'Pendekatan yang waras demi menjalani hidup yang baik.', NULL, 'subtle_art.pdf', NULL, 2016, 'BOOK', 'PENDING', 8, '2025-12-15 14:16:44'),
(54, 'Edensor', 'Andrea Hirata', 'Buku ketiga dari tetralogi Laskar Pelangi, petualangan di Eropa.', NULL, 'edensor.pdf', NULL, 2007, 'BOOK', 'APPROVED', 9, '2025-12-15 14:16:44'),
(55, 'Negeri 5 Menara', 'A. Fuadi', 'Kisah 6 santri dari 6 daerah berbeda menuntut ilmu di Gontor.', NULL, 'negeri_5_menara.pdf', NULL, 2009, 'BOOK', 'APPROVED', 9, '2025-12-15 14:16:44'),
(56, 'Rantau 1 Muara', 'A. Fuadi', 'Perjalanan merantau mencari ilmu dan cinta hingga ke Amerika.', NULL, 'rantau_1_muara.pdf', NULL, 2013, 'BOOK', 'APPROVED', 9, '2025-12-15 14:16:44'),
(57, 'Ayah', 'Andrea Hirata', 'Kisah cinta Sabari kepada Lena yang begitu tulus dan mengharukan.', NULL, 'ayah.pdf', NULL, 2015, 'BOOK', 'APPROVED', 9, '2025-12-15 14:16:44'),
(58, 'Sang Pemimpi', 'Andrea Hirata', 'Buku kedua Laskar Pelangi tentang masa SMA Ikal dan Arai.', NULL, 'sang_pemimpi.pdf', NULL, 2006, 'BOOK', 'APPROVED', 9, '2025-12-15 14:16:44'),
(59, 'Bumi', 'Tere Liye', 'Petualangan Raib di dunia paralel klan Bulan.', NULL, 'bumi.pdf', NULL, 2014, 'BOOK', 'APPROVED', 9, '2025-12-15 14:16:44'),
(60, 'Bulan', 'Tere Liye', 'Lanjutan petualangan Raib, Seli, dan Ali di klan Matahari.', NULL, 'bulan.pdf', NULL, 2015, 'BOOK', 'PENDING', 9, '2025-12-15 14:16:44'),
(61, 'Matahari', 'Tere Liye', 'Petualangan ketiga di klan Bintang.', NULL, 'matahari.pdf', NULL, 2016, 'BOOK', 'PENDING', 9, '2025-12-15 14:16:44'),
(62, 'Bintang', 'Tere Liye', 'Petualangan keempat melawan sekretaris dewan kota.', NULL, 'bintang.pdf', NULL, 2017, 'BOOK', 'PENDING', 9, '2025-12-15 14:16:44'),
(63, 'Komet', 'Tere Liye', 'Mencari pulau dengan tumbuhan aneh di klan Komet.', NULL, 'komet.pdf', NULL, 2018, 'BOOK', 'PENDING', 9, '2025-12-15 14:16:44'),
(64, 'Berita Sumatera Terbaru', 'Kominfo', 'Duka akibat banjir bandang dan tanah longsor di Sumatera kian dalam. Lebih dari seribu nyawa melayang, meninggalkan keluarga yang berduka dan ratusan orang yang hingga kini masih belum ditemukan. Badan Nasional Penanggulangan Bencana (BNPB) mencatat, korban meninggal dunia telah menembus angka 1.006 jiwa di tiga provinsi, yakni Aceh, Sumatera Utara, dan Sumatera Barat, pada Sabtu (13/12/2025).', '1765818455_436.jpeg', NULL, 'https://nasional.kompas.com/read/2025/12/14/06561711/duka-tak-terbilang-lebih-dari-1000-nyawa-melayang-dalam-banjir-sumatera', 2025, 'ARTICLE', 'APPROVED', 2, '2025-12-15 17:07:35'),
(65, 'test article 2', 'ytta', 'ytta', '1765820035_793.jpeg', NULL, 'https://github.com/avsnaelfrb', 2022, 'ARTICLE', 'PENDING', 2, '2025-12-15 17:33:55');

-- --------------------------------------------------------

--
-- Struktur dari tabel `book_genres`
--

CREATE TABLE `book_genres` (
  `book_id` int(11) NOT NULL,
  `genre_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `book_genres`
--

INSERT INTO `book_genres` (`book_id`, `genre_id`) VALUES
(35, 4),
(36, 7),
(37, 4),
(38, 1),
(39, 5),
(40, 4),
(41, 5),
(42, 8),
(43, 2),
(44, 4),
(45, 1),
(46, 1),
(47, 4),
(48, 8),
(49, 2),
(50, 6),
(51, 4),
(52, 7),
(53, 7),
(54, 1),
(55, 1),
(56, 1),
(57, 1),
(58, 1),
(59, 1),
(60, 1),
(61, 1),
(62, 1),
(63, 1),
(64, 3),
(64, 9),
(65, 6);

-- --------------------------------------------------------

--
-- Struktur dari tabel `genres`
--

CREATE TABLE `genres` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `genres`
--

INSERT INTO `genres` (`id`, `name`) VALUES
(58, 'Berita'),
(9, 'Biografi'),
(5, 'Bisnis'),
(6, 'Desain'),
(1, 'Fiksi'),
(8, 'Filosofi'),
(7, 'Psikologi'),
(3, 'Sains'),
(4, 'Sejarah'),
(2, 'Teknologi');

-- --------------------------------------------------------

--
-- Struktur dari tabel `history`
--

CREATE TABLE `history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `history`
--

INSERT INTO `history` (`id`, `user_id`, `book_id`, `read_at`) VALUES
(4, 1, 36, '2025-12-15 16:18:49'),
(6, 3, 65, '2025-12-15 17:41:40');

-- --------------------------------------------------------

--
-- Struktur dari tabel `saved_books`
--

CREATE TABLE `saved_books` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `saved_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `saved_books`
--

INSERT INTO `saved_books` (`id`, `user_id`, `book_id`, `saved_at`) VALUES
(4, 2, 64, '2025-12-15 17:37:56'),
(5, 1, 64, '2025-12-15 17:45:34');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nim` varchar(50) NOT NULL,
  `role` enum('ADMIN','USER','PENERBIT') DEFAULT 'USER',
  `request_penerbit` enum('0','1') DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `nim`, `role`, `request_penerbit`, `created_at`) VALUES
(1, 'elfaro', 'user@example.com', '$2y$10$tHRGzdAeuRX5C1YVPLHW/.14VyzSOnmmOLEcYvIHQUgixzTsOzdJe', '101010101010', 'USER', '0', '2025-12-15 09:30:49'),
(2, 'abdad', 'penerbit@example.com', '$2y$10$1UsosOOuopjtqQKvK/ZlzusgVFrwN3u0CXUOmbYxG7/eZ6X9PiTCW', '121212121212', 'PENERBIT', '0', '2025-12-15 09:31:15'),
(3, 'agung', 'admin@example.com', '$2y$10$HJ2mugjIaFsOJEBmzlkvRuBb2CfZzYya59WIDhHfA9jWOS0rHRfSi', '131313131313', 'ADMIN', '0', '2025-12-15 09:31:30'),
(5, 'Budi Santoso', 'budi@user.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '190001', 'USER', '0', '2025-12-15 13:33:04'),
(6, 'Siti Aminah', 'siti@user.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '190002', 'USER', '0', '2025-12-15 13:33:04'),
(7, 'Rizky Ramadhan', 'rizky@user.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '190003', 'USER', '1', '2025-12-15 13:33:04'),
(8, 'Gramedia Pustaka', 'editor@gramedia.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'PUB001', 'PENERBIT', '0', '2025-12-15 13:33:04'),
(9, 'Mizan Publishing', 'admin@mizan.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'PUB002', 'PENERBIT', '0', '2025-12-15 13:33:04');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `book_genres`
--
ALTER TABLE `book_genres`
  ADD PRIMARY KEY (`book_id`,`genre_id`),
  ADD KEY `genre_id` (`genre_id`);

--
-- Indeks untuk tabel `genres`
--
ALTER TABLE `genres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indeks untuk tabel `history`
--
ALTER TABLE `history`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_history` (`user_id`,`book_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indeks untuk tabel `saved_books`
--
ALTER TABLE `saved_books`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_saved` (`user_id`,`book_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT untuk tabel `genres`
--
ALTER TABLE `genres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT untuk tabel `history`
--
ALTER TABLE `history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `saved_books`
--
ALTER TABLE `saved_books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `book_genres`
--
ALTER TABLE `book_genres`
  ADD CONSTRAINT `book_genres_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `book_genres_ibfk_2` FOREIGN KEY (`genre_id`) REFERENCES `genres` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `history`
--
ALTER TABLE `history`
  ADD CONSTRAINT `history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `history_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `saved_books`
--
ALTER TABLE `saved_books`
  ADD CONSTRAINT `saved_books_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `saved_books_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE;
COMMIT;
