-- Dathop - Web Quan Ly Ky Thuat
-- Database schema (MySQL 5.7+/8.0, InnoDB, utf8mb4)

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('rd','sale','manager') NOT NULL DEFAULT 'sale',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  category VARCHAR(100) DEFAULT NULL,
  unit VARCHAR(50) DEFAULT NULL,
  description TEXT,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Thu vien quy trinh xu ly / cong thuc ket hop san pham
CREATE TABLE IF NOT EXISTS procedures (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  category ENUM(
    'khi_doc',
    'gan',
    'duong_ruot',
    'mau_nuoc',
    'uong_gieo',
    'ao_lang',
    'day_ao_nhot_bat',
    'khac'
  ) NOT NULL DEFAULT 'khac',
  summary VARCHAR(500) DEFAULT NULL,
  steps MEDIUMTEXT NOT NULL,
  created_by INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_procedures_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS procedure_products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  procedure_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  dosage VARCHAR(255) DEFAULT NULL,
  note VARCHAR(500) DEFAULT NULL,
  CONSTRAINT fk_pp_procedure FOREIGN KEY (procedure_id) REFERENCES procedures(id) ON DELETE CASCADE,
  CONSTRAINT fk_pp_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS procedure_images (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  procedure_id INT UNSIGNED NOT NULL,
  file_path VARCHAR(500) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pi_procedure FOREIGN KEY (procedure_id) REFERENCES procedures(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Chan doan / ho tro ky thuat tai ao cho khach hang (sale nhap, RD tu van)
CREATE TABLE IF NOT EXISTS diagnosis_requests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_name VARCHAR(200) NOT NULL,
  agent_name VARCHAR(200) DEFAULT NULL,
  location VARCHAR(255) DEFAULT NULL,
  pond_area DECIMAL(10,2) DEFAULT NULL,
  pond_stage VARCHAR(100) DEFAULT NULL,
  problem_category ENUM(
    'khi_doc',
    'gan',
    'duong_ruot',
    'mau_nuoc',
    'uong_gieo',
    'ao_lang',
    'day_ao_nhot_bat',
    'khac'
  ) NOT NULL DEFAULT 'khac',
  description TEXT,
  status ENUM('moi','dang_xu_ly','da_tu_van','hoan_thanh') NOT NULL DEFAULT 'moi',
  created_by INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_dr_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Chi tieu moi truong linh hoat (pH, kiem, NH3/NH4, NO2, oxy, do man, mat do...)
CREATE TABLE IF NOT EXISTS diagnosis_indicators (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  request_id INT UNSIGNED NOT NULL,
  indicator_name VARCHAR(100) NOT NULL,
  indicator_value VARCHAR(100) NOT NULL,
  unit VARCHAR(50) DEFAULT NULL,
  CONSTRAINT fk_di_request FOREIGN KEY (request_id) REFERENCES diagnosis_requests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS diagnosis_images (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  request_id INT UNSIGNED NOT NULL,
  file_path VARCHAR(500) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_dimg_request FOREIGN KEY (request_id) REFERENCES diagnosis_requests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- RD gan quy trinh phu hop tu thu vien vao 1 ca chan doan
CREATE TABLE IF NOT EXISTS diagnosis_recommendations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  request_id INT UNSIGNED NOT NULL,
  procedure_id INT UNSIGNED DEFAULT NULL,
  note TEXT,
  created_by INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_drec_request FOREIGN KEY (request_id) REFERENCES diagnosis_requests(id) ON DELETE CASCADE,
  CONSTRAINT fk_drec_procedure FOREIGN KEY (procedure_id) REFERENCES procedures(id) ON DELETE SET NULL,
  CONSTRAINT fk_drec_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Trao doi qua lai giua sale va RD tren 1 ca chan doan
CREATE TABLE IF NOT EXISTS diagnosis_comments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  request_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED DEFAULT NULL,
  comment TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_dc_request FOREIGN KEY (request_id) REFERENCES diagnosis_requests(id) ON DELETE CASCADE,
  CONSTRAINT fk_dc_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tai khoan mac dinh: admin@dathop.com.vn / doi_mat_khau_ngay (bcrypt hash duoi day la placeholder)
-- Hay chay includes/create_admin.php sau khi cau hinh config.php de tao tai khoan dau tien an toan hon.
