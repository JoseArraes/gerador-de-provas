-- ============================================================
-- Sistema de Provas - Fase 4: Banco de Dados
-- Rode este arquivo inteiro no phpMyAdmin (aba SQL) ou via:
--   mysql -u root -p < schema.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS sistema_provas
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE sistema_provas;

-- --------------------------------------------------------------
-- alunos
-- --------------------------------------------------------------
CREATE TABLE IF NOT EXISTS alunos (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  nome       VARCHAR(150) NOT NULL,
  turma      VARCHAR(50),
  matricula  VARCHAR(50),
  criado_em  DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_matricula (matricula)
) ENGINE=InnoDB;

-- --------------------------------------------------------------
-- provas
-- quiz_id = o "quizId" que o gerador grava no gabarito.json / prova.html,
-- é o que liga um gabarito às respostas que chegam depois.
-- --------------------------------------------------------------
CREATE TABLE IF NOT EXISTS provas (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  quiz_id    VARCHAR(100) UNIQUE,
  titulo     VARCHAR(200) NOT NULL,
  disciplina VARCHAR(150),
  professor  VARCHAR(150),
  turma      VARCHAR(50),
  tempo      VARCHAR(50),
  data       DATE NULL,
  criado_em  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- --------------------------------------------------------------
-- questoes
-- Mantido no formato de 4 alternativas (a,b,c,d) como no seu desenho
-- original. "correta" e as respostas guardam a LETRA (A/B/C/D).
-- --------------------------------------------------------------
CREATE TABLE IF NOT EXISTS questoes (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  prova_id   INT NOT NULL,
  ordem      INT NOT NULL,
  enunciado  TEXT NOT NULL,
  a          TEXT,
  b          TEXT,
  c          TEXT,
  d          TEXT,
  correta    CHAR(1) NOT NULL,
  peso       DECIMAL(4,2) NOT NULL DEFAULT 1,
  FOREIGN KEY (prova_id) REFERENCES provas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------------
-- respostas (uma linha por questão respondida por um aluno)
-- --------------------------------------------------------------
CREATE TABLE IF NOT EXISTS respostas (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  aluno_id    INT NOT NULL,
  prova_id    INT NOT NULL,
  questao_id  INT NOT NULL,
  resposta    CHAR(1) NULL,           -- NULL = não respondeu
  FOREIGN KEY (aluno_id)   REFERENCES alunos(id)   ON DELETE CASCADE,
  FOREIGN KEY (prova_id)   REFERENCES provas(id)   ON DELETE CASCADE,
  FOREIGN KEY (questao_id) REFERENCES questoes(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_resposta (aluno_id, questao_id)
) ENGINE=InnoDB;

-- --------------------------------------------------------------
-- resultados (resumo por aluno + prova)
-- --------------------------------------------------------------
CREATE TABLE IF NOT EXISTS resultados (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  aluno_id      INT NOT NULL,
  prova_id      INT NOT NULL,
  acertos       INT NOT NULL,
  total         INT NOT NULL,
  nota          DECIMAL(4,2) NOT NULL,
  tempo_gasto   VARCHAR(50),
  corrigido_em  DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
  FOREIGN KEY (prova_id) REFERENCES provas(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_aluno_prova (aluno_id, prova_id)
) ENGINE=InnoDB;
