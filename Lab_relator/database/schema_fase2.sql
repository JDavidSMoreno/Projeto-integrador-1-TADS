-- ============================================================
-- Sistema Relator de Problemas em Laboratorio
-- Backend Fase 2 - autenticacao real, CSRF, rate limit e recovery
-- MySQL 8.x / MariaDB compativel
-- ============================================================

CREATE DATABASE IF NOT EXISTS lab_relator
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE lab_relator;

CREATE TABLE IF NOT EXISTS usuarios (
  id INT NOT NULL AUTO_INCREMENT,
  nome VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL,
  senha VARCHAR(255) NOT NULL,
  perfil ENUM('gestor', 'professor', 'tecnico') NOT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuarios_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
  id INT NOT NULL AUTO_INCREMENT,
  email VARCHAR(150) NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  user_agent VARCHAR(255) NULL,
  success TINYINT(1) NOT NULL DEFAULT 0,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_login_attempts_email_time (email, attempted_at),
  KEY idx_login_attempts_ip_time (ip_address, attempted_at),
  KEY idx_login_attempts_success_time (success, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_resets (
  id INT NOT NULL AUTO_INCREMENT,
  usuario_id INT NOT NULL,
  email VARCHAR(150) NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  ip_address VARCHAR(45) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_password_resets_token_hash (token_hash),
  KEY idx_password_resets_usuario_open (usuario_id, used_at, expires_at),
  KEY idx_password_resets_email (email),
  CONSTRAINT fk_password_resets_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Senhas dos usuarios seed:
-- gestor@unieinstein.edu.br    / Gestor@123
-- professor@unieinstein.edu.br / Professor@123
-- tecnico@unieinstein.edu.br   / Tecnico@123
INSERT INTO usuarios (nome, email, senha, perfil, ativo) VALUES
('Gestor do Sistema', 'gestor@unieinstein.edu.br', '$2y$12$8cyIKgWBYJLRxd.8tm8GnueLy0nVucstBLectoCg3MCuQP32OIGqe', 'gestor', 1),
('Professor Demo', 'professor@unieinstein.edu.br', '$2y$12$OTZbGC9mGPTF4wLihHt6.uzSKdkjHzd6N4dudBWjuqCc.WmRCcW9C', 'professor', 1),
('Tecnico Demo', 'tecnico@unieinstein.edu.br', '$2y$12$Rkt2qHkISoeMnXig4Hx8VurKTy1xMrlDXIoVLYHiXDsdKWbbJ1AaG', 'tecnico', 1)
ON DUPLICATE KEY UPDATE
  nome = VALUES(nome),
  senha = VALUES(senha),
  perfil = VALUES(perfil),
  ativo = VALUES(ativo);
