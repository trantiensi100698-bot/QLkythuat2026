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

-- =========================================================
-- 1. Du an R&D
-- =========================================================
CREATE TABLE IF NOT EXISTS rd_experiments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  category ENUM('khi_doc', 'xu_ly_nuoc_truoc_tha', 'khac') NOT NULL DEFAULT 'khac',
  objective TEXT,
  start_date DATE DEFAULT NULL,
  end_date DATE DEFAULT NULL,
  status ENUM('dang_thuc_hien', 'hoan_thanh', 'tam_dung') NOT NULL DEFAULT 'dang_thuc_hien',
  findings_pros TEXT,
  findings_cons TEXT,
  cost_analysis TEXT,
  created_by INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_rde_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rd_experiment_products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  experiment_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  dosage VARCHAR(255) DEFAULT NULL,
  cost DECIMAL(12,2) DEFAULT NULL,
  note VARCHAR(500) DEFAULT NULL,
  CONSTRAINT fk_rdep_experiment FOREIGN KEY (experiment_id) REFERENCES rd_experiments(id) ON DELETE CASCADE,
  CONSTRAINT fk_rdep_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Chi tieu do dac truoc/sau khi dung san pham
CREATE TABLE IF NOT EXISTS rd_measurements (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  experiment_id INT UNSIGNED NOT NULL,
  stage ENUM('truoc', 'sau') NOT NULL DEFAULT 'truoc',
  measured_at DATE DEFAULT NULL,
  indicator_name VARCHAR(100) NOT NULL,
  indicator_value VARCHAR(100) NOT NULL,
  unit VARCHAR(50) DEFAULT NULL,
  CONSTRAINT fk_rdm_experiment FOREIGN KEY (experiment_id) REFERENCES rd_experiments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rd_experiment_images (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  experiment_id INT UNSIGNED NOT NULL,
  file_path VARCHAR(500) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_rdi_experiment FOREIGN KEY (experiment_id) REFERENCES rd_experiments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- File bao cao Word/PPT/Excel de chia se voi khach hang, nhan vien thi truong
CREATE TABLE IF NOT EXISTS rd_experiment_files (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  experiment_id INT UNSIGNED NOT NULL,
  file_path VARCHAR(500) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_rdf_experiment FOREIGN KEY (experiment_id) REFERENCES rd_experiments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- 2. Nhat ky farm nuoi Biogency
-- =========================================================
CREATE TABLE IF NOT EXISTS farm_ponds (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  area DECIMAL(10,2) DEFAULT NULL,
  note VARCHAR(500) DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS farm_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pond_id INT UNSIGNED NOT NULL,
  log_date DATE NOT NULL,
  feed_amount VARCHAR(100) DEFAULT NULL,
  note TEXT,
  created_by INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_fl_pond FOREIGN KEY (pond_id) REFERENCES farm_ponds(id) ON DELETE CASCADE,
  CONSTRAINT fk_fl_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS farm_log_indicators (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  log_id INT UNSIGNED NOT NULL,
  indicator_name VARCHAR(100) NOT NULL,
  indicator_value VARCHAR(100) NOT NULL,
  unit VARCHAR(50) DEFAULT NULL,
  CONSTRAINT fk_fli_log FOREIGN KEY (log_id) REFERENCES farm_logs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS farm_log_products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  log_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  dosage VARCHAR(255) DEFAULT NULL,
  CONSTRAINT fk_flp_log FOREIGN KEY (log_id) REFERENCES farm_logs(id) ON DELETE CASCADE,
  CONSTRAINT fk_flp_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS farm_log_images (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  log_id INT UNSIGNED NOT NULL,
  file_path VARCHAR(500) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_fli2_log FOREIGN KEY (log_id) REFERENCES farm_logs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- 3. Ho tro thi truong (thuyet trinh/demo, tham ao dinh ky, chuyen giao cong nghe)
-- =========================================================
CREATE TABLE IF NOT EXISTS market_visits (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  visit_type ENUM('thuyet_trinh_demo', 'tham_ao_dinh_ky', 'chuyen_giao_cong_nghe') NOT NULL DEFAULT 'tham_ao_dinh_ky',
  agent_name VARCHAR(200) DEFAULT NULL,
  customer_name VARCHAR(200) DEFAULT NULL,
  location VARCHAR(255) DEFAULT NULL,
  visit_date DATE NOT NULL,
  participants VARCHAR(255) DEFAULT NULL,
  content TEXT,
  customer_feedback TEXT,
  created_by INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_mv_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS market_visit_samples (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  visit_id INT UNSIGNED NOT NULL,
  sample_type VARCHAR(100) NOT NULL,
  result_description VARCHAR(500) DEFAULT NULL,
  CONSTRAINT fk_mvs_visit FOREIGN KEY (visit_id) REFERENCES market_visits(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS market_visit_images (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  visit_id INT UNSIGNED NOT NULL,
  file_path VARCHAR(500) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_mvi_visit FOREIGN KEY (visit_id) REFERENCES market_visits(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- File bao cao chuyen di (Word/PPT/Excel) gui Ms Tu Anh
CREATE TABLE IF NOT EXISTS market_visit_files (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  visit_id INT UNSIGNED NOT NULL,
  file_path VARCHAR(500) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_mvf_visit FOREIGN KEY (visit_id) REFERENCES market_visits(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tai khoan mac dinh: admin@dathop.com.vn / doi_mat_khau_ngay (bcrypt hash duoi day la placeholder)
-- Hay chay includes/create_admin.php sau khi cau hinh config.php de tao tai khoan dau tien an toan hon.
