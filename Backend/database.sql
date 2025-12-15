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
    role ENUM('ADMIN', 'USER', 'PENERBIT') DEFAULT 'USER',
    request_penerbit ENUM('0', '1') DEFAULT '0',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Genres
CREATE TABLE IF NOT EXISTS genres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL
);

-- Tabel Books (MODIFIKASI UNTUK FITUR ARTIKEL)
-- Menambahkan kolom 'link' dan mengubah 'file_path' agar boleh NULL
CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    description TEXT,
    
    cover VARCHAR(255) DEFAULT NULL,
    file_path VARCHAR(255) DEFAULT NULL,  -- Boleh NULL untuk Artikel
    link TEXT DEFAULT NULL,               -- Kolom Baru untuk Link Artikel
    
    year INT,
    type ENUM('BOOK', 'JOURNAL', 'ARTICLE') DEFAULT 'BOOK',
    status ENUM('APPROVED', 'PENDING', 'REJECTED') DEFAULT 'APPROVED',
    uploaded_by INT DEFAULT NULL,
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

-- Tabel History Baca
CREATE TABLE IF NOT EXISTS history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    UNIQUE KEY unique_history (user_id, book_id)
);

-- Tabel Saved Books
CREATE TABLE IF NOT EXISTS saved_books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    UNIQUE KEY unique_saved (user_id, book_id)
);

-- Seed Data Genre
INSERT IGNORE INTO genres (name) VALUES 
('Fiksi'), ('Teknologi'), ('Sains'), ('Sejarah'), ('Bisnis'), ('Desain'), ('Biografi'), ('Filosofi'), ('Psikologi');