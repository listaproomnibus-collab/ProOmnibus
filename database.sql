CREATE TABLE IF NOT EXISTS pro_omnibus_books (
  id VARCHAR(40) NOT NULL PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  subject VARCHAR(80) NOT NULL,
  publisher VARCHAR(160) NOT NULL DEFAULT '',
  class_level VARCHAR(8) NOT NULL,
  condition_label VARCHAR(20) NOT NULL,
  notes TEXT NOT NULL,
  seller_name VARCHAR(80) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  image_data MEDIUMTEXT NOT NULL,
  created_at VARCHAR(40) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'available',
  taken_at VARCHAR(40) NULL,
  INDEX idx_status_created (status, created_at),
  INDEX idx_class_subject (class_level, subject)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
