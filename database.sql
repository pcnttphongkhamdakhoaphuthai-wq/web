CREATE DATABASE IF NOT EXISTS benhvien_support
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE benhvien_support;

CREATE TABLE patients (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cccd VARCHAR(12) NOT NULL,
  full_name VARCHAR(255) NOT NULL,
  phone VARCHAR(15) NOT NULL,
  email VARCHAR(255) NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT uq_patients_cccd UNIQUE (cccd),
  CONSTRAINT uq_patients_phone UNIQUE (phone),
  CONSTRAINT uq_patients_email UNIQUE (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL,
  full_name VARCHAR(255) NOT NULL,
  department VARCHAR(255) NULL,
  role VARCHAR(50) NOT NULL DEFAULT 'staff',
  is_root TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  can_manage_accounts TINYINT(1) NOT NULL DEFAULT 0,
  can_manage_records TINYINT(1) NOT NULL DEFAULT 1,
  can_manage_all_records TINYINT(1) NOT NULL DEFAULT 0,
  can_manage_clinic_content TINYINT(1) NOT NULL DEFAULT 0,
  can_manage_doctors TINYINT(1) NOT NULL DEFAULT 0,
  can_manage_patients TINYINT(1) NOT NULL DEFAULT 0,
  can_manage_chatbot TINYINT(1) NOT NULL DEFAULT 0,
  can_manage_support_chat TINYINT(1) NOT NULL DEFAULT 0,
  can_publish_announcements TINYINT(1) NOT NULL DEFAULT 0,
  can_create_backup TINYINT(1) NOT NULL DEFAULT 0,
  can_view_logs TINYINT(1) NOT NULL DEFAULT 0,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT uq_admins_username UNIQUE (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE doctors (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  title VARCHAR(255) NULL,
  department VARCHAR(255) NOT NULL,
  specialties VARCHAR(255) NULL,
  bio TEXT NULL,
  photo_path VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE site_settings (
  setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
  setting_value TEXT NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE chat_quick_replies (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  question VARCHAR(255) NOT NULL,
  answer TEXT NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE appointments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id INT UNSIGNED NOT NULL,
  doctor_id INT UNSIGNED NOT NULL,
  appointment_date DATETIME NOT NULL,
  reason TEXT NOT NULL,
  status VARCHAR(50) NOT NULL DEFAULT 'Cho kham',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_appointments_patient_id (patient_id),
  INDEX idx_appointments_doctor_id (doctor_id),
  INDEX idx_appointments_appointment_date (appointment_date),
  CONSTRAINT fk_appointments_patient
    FOREIGN KEY (patient_id) REFERENCES patients(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_appointments_doctor
    FOREIGN KEY (doctor_id) REFERENCES doctors(id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE medical_records (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id INT UNSIGNED NOT NULL,
  doctor_id INT UNSIGNED NOT NULL,
  visit_date DATE NOT NULL,
  diagnosis TEXT NOT NULL,
  prescription TEXT,
  result_file VARCHAR(255),
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_medical_records_patient_id (patient_id),
  INDEX idx_medical_records_doctor_id (doctor_id),
  INDEX idx_medical_records_visit_date (visit_date),
  CONSTRAINT fk_medical_records_patient
    FOREIGN KEY (patient_id) REFERENCES patients(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_medical_records_doctor
    FOREIGN KEY (doctor_id) REFERENCES doctors(id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE chats (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id INT UNSIGNED NOT NULL,
  sender VARCHAR(20) NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_chats_patient_id (patient_id),
  INDEX idx_chats_created_at (created_at),
  CONSTRAINT fk_chats_patient
    FOREIGN KEY (patient_id) REFERENCES patients(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE patient_announcements (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  created_by_admin_id INT UNSIGNED NOT NULL,
  push_to_chat TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_patient_announcements_created_at (created_at),
  CONSTRAINT fk_patient_announcements_admin
    FOREIGN KEY (created_by_admin_id) REFERENCES admins(id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE news_posts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  excerpt TEXT NULL,
  body TEXT NOT NULL,
  media_path VARCHAR(255) NULL,
  media_type VARCHAR(20) NULL,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  created_by_admin_id INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_news_posts_published (is_published, created_at),
  CONSTRAINT fk_news_posts_admin
    FOREIGN KEY (created_by_admin_id) REFERENCES admins(id)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE customer_resources (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  resource_url VARCHAR(500) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  created_by_admin_id INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_customer_resources_published (is_published, sort_order, id),
  CONSTRAINT fk_customer_resources_admin
    FOREIGN KEY (created_by_admin_id) REFERENCES admins(id)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO doctors (name, title, department, specialties, bio)
VALUES
  ('BS Minh', 'BS CKI', 'Noi', 'Noi tong quat, tu van benh man tinh', 'Bac si Minh phu trach kham noi tong quat va cap nhat ho so cho nguoi benh.'),
  ('BS Lan', 'ThS.BS', 'Xet nghiem', 'Tu van xet nghiem, doc ket qua can lam sang', 'Bac si Lan phu trach tiep nhan chi dinh xet nghiem va ho tro tra ket qua truc tuyen.');

INSERT INTO site_settings (setting_key, setting_value) VALUES
  ('clinic_name', 'Phòng khám đa khoa Phú Thái'),
  ('clinic_logo_path', 'assets/logo.png'),
  ('clinic_intro', 'Phòng khám cung cấp dịch vụ đặt lịch, trả kết quả và quản lý hồ sơ khám bệnh trên cùng một hệ thống.'),
  ('clinic_mission', 'Tối ưu quy trình tiếp nhận và giúp bệnh nhân theo dõi hồ sơ nhanh hơn.'),
  ('clinic_facility', 'Có khu khám, khu xét nghiệm và hệ thống lưu trữ hồ sơ điện tử phục vụ tra cứu kết quả.'),
  ('clinic_support', 'Hỗ trợ người bệnh từ đặt lịch, tiếp nhận hồ sơ đến tra cứu kết quả trực tuyến.'),
  ('support_hotline', '1900 0000'),
  ('support_email', 'congnghethongtin247@gmail.com'),
  ('clinic_address', 'Xóm Hoà Bình 2, xã Phú Bình, tỉnh Thái Nguyên, Việt Nam'),
  ('google_maps_url', ''),
  ('apple_maps_url', ''),
  ('appointments_enabled', '1'),
  ('chatbot_intro', 'Chat hỗ trợ giúp bệnh nhân xem nhanh hướng dẫn thường gặp và gửi câu hỏi cho bộ phận hỗ trợ.'),
  ('chatbot_fallback', 'Chúng tôi đã nhận được câu hỏi của bạn. Bộ phận hỗ trợ sẽ phản hồi sớm hoặc bạn có thể chọn một câu hỏi nhanh bên dưới để xem hướng dẫn ngay.'),
  ('ai_prompt_procedures', '1. Đăng ký tại quầy lễ tân.\n2. Lấy số thứ tự và đóng phí tạm ứng.\n3. Đến phòng khám chuyên khoa theo hướng dẫn.\n4. Thực hiện các chỉ định lâm sàng (nếu có).\n5. Quay lại phòng khám ban đầu để nghe kết luận.\n6. Lấy thuốc và thanh toán.'),
  ('ai_prompt_pricing', 'Siêu âm tổng quát: 150.000 VNĐ\nXét nghiệm máu cơ bản: 200.000 VNĐ\nKhám nội chung: 100.000 VNĐ\nChụp X-quang: 120.000 VNĐ\nĐiện tim đồ: 80.000 VNĐ');

INSERT INTO chat_quick_replies (question, answer, sort_order, is_active) VALUES
  ('Làm sao để xem kết quả xét nghiệm?', 'Đăng nhập tài khoản bệnh nhân, vao muc ket qua tren dashboard de xem va tai file PDF neu da co.', 1, 1),
  ('Toi muon doi so dien thoai thi lam o dau?', 'Ban vao muc quan ly tai khoan sau khi dang nhap de cap nhat ho ten, so dien thoai va mat khau.', 2, 1),
  ('Toi muon dat lich kham thi lam the nao?', 'Ban vao muc dat lich kham, chon bac si, thoi gian va ly do kham roi xac nhan de tao lich hen.', 3, 1),
  ('Khi nao co the nhan ket qua?', 'Khi nhan vien hoac bac si cap nhat ho so kham, ket qua se xuat hien ngay trong tai khoan benh nhan.', 4, 1);
