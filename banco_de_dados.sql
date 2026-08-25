-- =====================================================================
-- Modelagem do banco e primeira migração
-- Tema: Jogo de Basquete 2K25
-- =====================================================================

CREATE DATABASE IF NOT EXISTS basquete_2k25
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE basquete_2k25;

-- ---------------------------------------------------------------------
-- Tabela: usuarios
-- Usuários do sistema (ex: quem gerencia o modo franquia)
-- ---------------------------------------------------------------------
CREATE TABLE usuarios (
    id_usuario      INT AUTO_INCREMENT PRIMARY KEY,
    nome            VARCHAR(100)        NOT NULL,
    email           VARCHAR(150)        NOT NULL UNIQUE,
    senha           VARCHAR(255)        NOT NULL,
    data_cadastro   DATETIME            DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabela: times
-- Times/franquias da NBA
-- ---------------------------------------------------------------------
CREATE TABLE times (
    id_time             INT AUTO_INCREMENT PRIMARY KEY,
    nome                VARCHAR(100)    NOT NULL,
    cidade              VARCHAR(100)    NOT NULL,
    conferencia         ENUM('Leste', 'Oeste')          NOT NULL,
    divisao             VARCHAR(50)     NOT NULL,
    overall_rating      TINYINT UNSIGNED NOT NULL DEFAULT 70,
    id_usuario          INT             NULL,
    CONSTRAINT fk_times_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabela: jogadores
-- Elenco de jogadores de cada time
-- ---------------------------------------------------------------------
CREATE TABLE jogadores (
    id_jogador          INT AUTO_INCREMENT PRIMARY KEY,
    nome                VARCHAR(100)    NOT NULL,
    posicao             ENUM('PG', 'SG', 'SF', 'PF', 'C') NOT NULL,
    altura_cm           SMALLINT UNSIGNED NOT NULL,
    peso_kg             SMALLINT UNSIGNED NOT NULL,
    overall_rating      TINYINT UNSIGNED NOT NULL DEFAULT 70,
    numero_camisa       TINYINT UNSIGNED NOT NULL,
    id_time             INT             NULL,
    CONSTRAINT fk_jogadores_time
        FOREIGN KEY (id_time) REFERENCES times(id_time)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabela: temporadas
-- Temporadas simuladas no jogo
-- ---------------------------------------------------------------------
CREATE TABLE temporadas (
    id_temporada        INT AUTO_INCREMENT PRIMARY KEY,
    ano_inicio           SMALLINT        NOT NULL,
    ano_fim               SMALLINT        NOT NULL,
    descricao            VARCHAR(100)    NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabela: jogos
-- Partidas entre dois times dentro de uma temporada
-- ---------------------------------------------------------------------
CREATE TABLE jogos (
    id_jogo              INT AUTO_INCREMENT PRIMARY KEY,
    id_temporada         INT             NOT NULL,
    id_time_casa         INT             NOT NULL,
    id_time_visitante    INT             NOT NULL,
    data_jogo            DATETIME        NOT NULL,
    placar_casa          SMALLINT UNSIGNED DEFAULT 0,
    placar_visitante     SMALLINT UNSIGNED DEFAULT 0,
    CONSTRAINT fk_jogos_temporada
        FOREIGN KEY (id_temporada) REFERENCES temporadas(id_temporada)
        ON DELETE CASCADE,
    CONSTRAINT fk_jogos_time_casa
        FOREIGN KEY (id_time_casa) REFERENCES times(id_time)
        ON DELETE CASCADE,
    CONSTRAINT fk_jogos_time_visitante
        FOREIGN KEY (id_time_visitante) REFERENCES times(id_time)
        ON DELETE CASCADE,
    CONSTRAINT chk_times_diferentes
        CHECK (id_time_casa <> id_time_visitante)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabela: estatisticas_jogador
-- Estatísticas individuais de cada jogador em cada jogo
-- ---------------------------------------------------------------------
CREATE TABLE estatisticas_jogador (
    id_estatistica       INT AUTO_INCREMENT PRIMARY KEY,
    id_jogo               INT             NOT NULL,
    id_jogador            INT             NOT NULL,
    pontos                SMALLINT UNSIGNED DEFAULT 0,
    rebotes               SMALLINT UNSIGNED DEFAULT 0,
    assistencias          SMALLINT UNSIGNED DEFAULT 0,
    roubos_de_bola        SMALLINT UNSIGNED DEFAULT 0,
    tocos                 SMALLINT UNSIGNED DEFAULT 0,
    minutos_jogados       DECIMAL(4,1)    DEFAULT 0,
    CONSTRAINT fk_estatisticas_jogo
        FOREIGN KEY (id_jogo) REFERENCES jogos(id_jogo)
        ON DELETE CASCADE,
    CONSTRAINT fk_estatisticas_jogador
        FOREIGN KEY (id_jogador) REFERENCES jogadores(id_jogador)
        ON DELETE CASCADE,
    CONSTRAINT uq_jogador_jogo UNIQUE (id_jogo, id_jogador)
) ENGINE=InnoDB;

-- =====================================================================
-- Migração 2 — vincula o app a `times` e `jogadores`
-- Aplicada automaticamente pelo api.php (função ensureSchema) na primeira
-- requisição após a atualização; mantida aqui só como documentação.
-- app_id guarda o id local (do localStorage) de cada time/jogador, para
-- que salvar/editar/excluir no app reflita sempre na mesma linha do banco.
-- =====================================================================
ALTER TABLE times
    ADD COLUMN app_id INT NULL,
    ADD UNIQUE KEY uq_times_app_id (app_id);

ALTER TABLE jogadores
    ADD COLUMN app_id INT NULL,
    ADD UNIQUE KEY uq_jogadores_app_id (app_id),
    ADD COLUMN idade TINYINT UNSIGNED NULL,
    ADD COLUMN jogos SMALLINT UNSIGNED NULL,
    ADD COLUMN pontos_media DECIMAL(4,1) NULL,
    ADD COLUMN rebotes_media DECIMAL(4,1) NULL,
    ADD COLUMN assistencias_media DECIMAL(4,1) NULL,
    ADD COLUMN roubos_media DECIMAL(4,1) NULL,
    ADD COLUMN tocos_media DECIMAL(4,1) NULL,
    ADD COLUMN aproveitamento_fg DECIMAL(4,1) NULL,
    MODIFY altura_cm SMALLINT UNSIGNED NULL,
    MODIFY peso_kg SMALLINT UNSIGNED NULL,
    MODIFY numero_camisa TINYINT UNSIGNED NULL;
