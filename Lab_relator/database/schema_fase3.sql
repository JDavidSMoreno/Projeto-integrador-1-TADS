-- ============================================================
-- Sistema Relator de Problemas em Laboratorio
-- Backend Fase 3 - CRUDs de cadastro e tabelas de dominio
-- MySQL 8.x / MariaDB compativel
-- ============================================================

CREATE DATABASE IF NOT EXISTS lab_relator
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE lab_relator;

CREATE TABLE IF NOT EXISTS laboratorios (
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

CREATE TABLE IF NOT EXISTS equipamentos (
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

CREATE TABLE IF NOT EXISTS tipos_problema (
  id INT NOT NULL AUTO_INCREMENT,
  nome VARCHAR(100) NOT NULL,
  descricao TEXT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_tipos_problema_nome (nome),
  KEY idx_tipos_problema_ativo_nome (ativo, nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tipos_problema (nome, descricao, ativo) VALUES
('Hardware', 'Problemas fisicos em computadores, monitores, cabos ou componentes.', 1),
('Software', 'Falhas em programas, aplicativos ou ferramentas usadas no laboratorio.', 1),
('Rede/Conectividade', 'Problemas de internet, rede local, Wi-Fi ou acesso a sistemas.', 1),
('Periferico', 'Problemas em mouse, teclado, impressora, webcam, projetor ou similares.', 1),
('Sistema Operacional', 'Falhas de inicializacao, login, atualizacao ou configuracao do sistema.', 1),
('Outro', 'Situacoes que nao se encaixam nos tipos principais.', 1)
ON DUPLICATE KEY UPDATE
  descricao = VALUES(descricao),
  ativo = VALUES(ativo);
