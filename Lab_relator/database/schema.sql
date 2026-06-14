-- Arquivo: database/schema.sql
-- Criado em: Projeto Integrador TADS - UniEinstein Limeira 2026
-- Depende de: MySQL 8.4, tabelas de dominio e modulo de ocorrencias

CREATE DATABASE IF NOT EXISTS lab_relator
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE lab_relator;

SET NAMES utf8mb4;

DROP TABLE IF EXISTS ocorrencia_historico;
DROP TABLE IF EXISTS ocorrencia;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS equipamentos;
DROP TABLE IF EXISTS tipos_problema;
DROP TABLE IF EXISTS laboratorios;
DROP TABLE IF EXISTS usuarios;

CREATE TABLE usuarios (
  id INT NOT NULL AUTO_INCREMENT,
  nome VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL,
  senha VARCHAR(255) NOT NULL,
  perfil ENUM('gestor', 'professor', 'tecnico') NOT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuarios_email (email),
  KEY idx_usuarios_perfil_ativo (perfil, ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_attempts (
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

CREATE TABLE password_resets (
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

CREATE TABLE laboratorios (
  id INT NOT NULL AUTO_INCREMENT,
  nome VARCHAR(100) NOT NULL,
  bloco VARCHAR(50) NULL,
  capacidade INT UNSIGNED NULL,
  descricao TEXT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_laboratorios_ativo_nome (ativo, nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE equipamentos (
  id INT NOT NULL AUTO_INCREMENT,
  laboratorio_id INT NOT NULL,
  nome VARCHAR(100) NOT NULL,
  patrimonio VARCHAR(50) NULL,
  tipo VARCHAR(50) NULL,
  status ENUM('disponivel', 'em_manutencao', 'inutilizavel') NOT NULL DEFAULT 'disponivel',
  observacao TEXT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_equipamentos_laboratorio (laboratorio_id),
  KEY idx_equipamentos_ativo_status (ativo, status),
  CONSTRAINT fk_equipamentos_laboratorio
    FOREIGN KEY (laboratorio_id) REFERENCES laboratorios (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tipos_problema (
  id INT NOT NULL AUTO_INCREMENT,
  nome VARCHAR(100) NOT NULL,
  descricao TEXT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_tipos_problema_nome (nome),
  KEY idx_tipos_problema_ativo_nome (ativo, nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ocorrencia (
  id INT NOT NULL AUTO_INCREMENT,
  id_professor INT NOT NULL,
  id_tecnico INT NULL,
  id_laboratorio INT NOT NULL,
  id_equipamento INT NULL,
  id_tipo_problema INT NOT NULL,
  descricao TEXT NOT NULL,
  status ENUM('Nao Atendida', 'Em Edicao', 'Em Atendimento', 'Encerrada') NOT NULL DEFAULT 'Nao Atendida',
  data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  data_atualizacao DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  data_encerramento DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_ocorrencia_professor (id_professor),
  KEY idx_ocorrencia_tecnico (id_tecnico),
  KEY idx_ocorrencia_laboratorio (id_laboratorio),
  KEY idx_ocorrencia_equipamento (id_equipamento),
  KEY idx_ocorrencia_tipo_problema (id_tipo_problema),
  KEY idx_ocorrencia_status_data (status, data_criacao),
  CONSTRAINT fk_ocorrencia_professor
    FOREIGN KEY (id_professor) REFERENCES usuarios (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_ocorrencia_tecnico
    FOREIGN KEY (id_tecnico) REFERENCES usuarios (id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_ocorrencia_laboratorio
    FOREIGN KEY (id_laboratorio) REFERENCES laboratorios (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_ocorrencia_equipamento
    FOREIGN KEY (id_equipamento) REFERENCES equipamentos (id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_ocorrencia_tipo_problema
    FOREIGN KEY (id_tipo_problema) REFERENCES tipos_problema (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ocorrencia_historico (
  id INT NOT NULL AUTO_INCREMENT,
  id_ocorrencia INT NOT NULL,
  id_usuario INT NOT NULL,
  status_anterior ENUM('Nao Atendida', 'Em Edicao', 'Em Atendimento', 'Encerrada') NULL,
  status_novo ENUM('Nao Atendida', 'Em Edicao', 'Em Atendimento', 'Encerrada') NOT NULL,
  observacao VARCHAR(500) NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_ocorrencia_historico_ocorrencia (id_ocorrencia, criado_em),
  KEY idx_ocorrencia_historico_usuario (id_usuario),
  CONSTRAINT fk_ocorrencia_historico_ocorrencia
    FOREIGN KEY (id_ocorrencia) REFERENCES ocorrencia (id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_ocorrencia_historico_usuario
    FOREIGN KEY (id_usuario) REFERENCES usuarios (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO usuarios (nome, email, senha, perfil, ativo) VALUES
('Administrador do Sistema', 'admin@unieinstein.edu.br', '$2y$10$2EZqWzPeLfo1wD/nDhl1/eFfZLrQzeubhfZow5.pRFyl3AUrIBZcm', 'gestor', 1);

INSERT INTO laboratorios (nome, bloco, capacidade, descricao, ativo) VALUES
('Laboratorio de Informatica 1', 'Bloco A', 30, 'Laboratorio de exemplo para testes iniciais do sistema.', 1);

INSERT INTO equipamentos (laboratorio_id, nome, patrimonio, tipo, status, observacao, ativo) VALUES
(1, 'Computador Professor', 'PAT-2026-001', 'Desktop', 'disponivel', 'Equipamento de exemplo vinculado ao laboratorio inicial.', 1);

INSERT INTO tipos_problema (nome, descricao, ativo) VALUES
('Hardware', 'Problemas fisicos em computadores, monitores, cabos ou componentes.', 1),
('Software', 'Falhas em programas, aplicativos ou ferramentas usadas no laboratorio.', 1),
('Rede/Conectividade', 'Problemas de internet, rede local, Wi-Fi ou acesso a sistemas.', 1),
('Periferico', 'Problemas em mouse, teclado, impressora, webcam, projetor ou similares.', 1),
('Sistema Operacional', 'Falhas de inicializacao, login, atualizacao ou configuracao do sistema.', 1),
('Outro', 'Situacoes que nao se encaixam nos tipos principais.', 1);
