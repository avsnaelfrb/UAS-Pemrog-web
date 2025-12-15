-- Buat Database
CREATE DATABASE IF NOT EXISTS elibrary_db;
USE elibrary_db;

-- Tabel Users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nim VARCHAR(50) NOT NULL,
    role ENUM('ADMIN', 'USER') DEFAULT 'USER',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Genres
CREATE TABLE IF NOT EXISTS genres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL
);

-- Tabel Books (MODIFIKASI UNTUK BLOB)
-- Kolom 'cover' dan 'file_path' diubah menjadi LONGBLOB untuk menyimpan data biner
CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    description TEXT,
    
    cover LONGBLOB,           -- Menyimpan data gambar binary
    file_path LONGBLOB,       -- Menyimpan data PDF binary (Meskipun namanya file_path, isinya nanti file asli)
    
    year INT,
    type ENUM('BOOK', 'JOURNAL', 'ARTICLE') DEFAULT 'BOOK',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Penghubung Book - Genres
CREATE TABLE IF NOT EXISTS book_genres (
    book_id INT,
    genre_id INT,
    PRIMARY KEY (book_id, genre_id),
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    FOREIGN KEY (genre_id) REFERENCES genres(id) ON DELETE CASCADE
);

-- Seed Data
INSERT IGNORE INTO genres (name) VALUES 
('Fiksi'), ('Teknologi'), ('Sains'), ('Sejarah'), ('Bisnis'), ('Desain');

ALTER TABLE books MODIFY file_path LONGBLOB;
ALTER TABLE books MODIFY cover LONGBLOB;

-- ... (Existing code) ...

-- Tabel History Baca (BARU)
CREATE TABLE IF NOT EXISTS history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    UNIQUE KEY unique_history (user_id, book_id) -- Mencegah duplikasi, cukup update timestamp
);

-- 1. Update kolom Role pada users untuk mendukung PENERBIT
ALTER TABLE users MODIFY COLUMN role ENUM('ADMIN', 'USER', 'PENERBIT') DEFAULT 'USER';

-- 2. Tambah kolom status request penerbit
ALTER TABLE users ADD COLUMN request_penerbit ENUM('0', '1') DEFAULT '0'; 
-- '0' = Tidak request, '1' = Sedang request

-- 3. Update tabel books untuk status publikasi dan pemilik buku
ALTER TABLE books ADD COLUMN status ENUM('APPROVED', 'PENDING', 'REJECTED') DEFAULT 'APPROVED';
ALTER TABLE books ADD COLUMN uploaded_by INT DEFAULT NULL;

-- 4. Set data lama (jika ada) agar statusnya APPROVED dan uploaded_by NULL (milik sistem/admin)
UPDATE books SET status = 'APPROVED' WHERE status IS NULL;