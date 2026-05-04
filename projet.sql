-- ============================================================
-- projet.sql — P2P Learning Platform
-- Schema version: 2.0
-- Last updated: 2026-05-03
--
-- Usage:
--   mysql -u root p2p_learning < projet.sql
--   ou Import via phpMyAdmin.
--
-- Test accounts (password: student123 for all student et admin123 for admin):
--   admin@gmail.com    -> admin --> password: admin123
--   younes@gmail.com   -> student --> password: student123
--   ahmed@gmail.com    -> student --> password: password123
--   hajar@gmail.com    -> student --> password: student123
--   youssef@gmail.com  -> student --> password: student123
--   ferdaous@gmail.com -> student --> password: student123
-- ============================================================

CREATE DATABASE IF NOT EXISTS p2p_learning
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE p2p_learning;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS custom_modules;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS ratings;
DROP TABLE IF EXISTS sessions;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS user_modules;
DROP TABLE IF EXISTS modules;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- ── 1. users ──────────────────────────────────────────────────────
CREATE TABLE users (
    id         INT UNSIGNED            NOT NULL AUTO_INCREMENT,
    name       VARCHAR(100)            NOT NULL,
    first_name VARCHAR(100)            NOT NULL DEFAULT '',
    email      VARCHAR(150)            NOT NULL,
    password   VARCHAR(255)            NOT NULL,
    role       ENUM('student','admin') NOT NULL DEFAULT 'student',
    school     VARCHAR(150)            NOT NULL DEFAULT '',
    field      VARCHAR(150)            NOT NULL DEFAULT '',
    bio        TEXT                    NULL,
    avatar     VARCHAR(200)            NULL,
    created_at TIMESTAMP               NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2. modules ────────────────────────────────────────────────────
CREATE TABLE modules (
    id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_module_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 3. user_modules ───────────────────────────────────────────────
CREATE TABLE user_modules (
    id        INT UNSIGNED              NOT NULL AUTO_INCREMENT,
    user_id   INT UNSIGNED              NOT NULL,
    module_id INT UNSIGNED              NOT NULL,
    type      ENUM('maitrise','lacune') NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_user_module (user_id, module_id),
    INDEX idx_um_user_id   (user_id),
    INDEX idx_um_module_id (module_id),
    CONSTRAINT fk_um_user   FOREIGN KEY (user_id)   REFERENCES users   (id) ON DELETE CASCADE,
    CONSTRAINT fk_um_module FOREIGN KEY (module_id) REFERENCES modules (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 4. messages ───────────────────────────────────────────────────
CREATE TABLE messages (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    sender_id   INT UNSIGNED NOT NULL,
    receiver_id INT UNSIGNED NOT NULL,
    content     TEXT         NOT NULL,
    sent_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_at     DATETIME     NULL     DEFAULT NULL,
    PRIMARY KEY (id),
    INDEX idx_receiver_unread (receiver_id, read_at),
    INDEX idx_sender          (sender_id),
    INDEX idx_sent_at         (sent_at),
    CONSTRAINT fk_msg_sender   FOREIGN KEY (sender_id)   REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_msg_receiver FOREIGN KEY (receiver_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 5. sessions ───────────────────────────────────────────────────
CREATE TABLE sessions (
    id           INT UNSIGNED                                   NOT NULL AUTO_INCREMENT,
    proposer_id  INT UNSIGNED                                   NOT NULL,
    partner_id   INT UNSIGNED                                   NOT NULL,
    module_id    INT UNSIGNED                                   NOT NULL,
    scheduled_at DATETIME                                       NULL,
    status       ENUM('pending','confirmed','done','cancelled') NOT NULL DEFAULT 'pending',
    created_at   TIMESTAMP                                      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_proposer (proposer_id),
    INDEX idx_partner  (partner_id),
    INDEX idx_status   (status),
    CONSTRAINT fk_sess_proposer FOREIGN KEY (proposer_id) REFERENCES users   (id) ON DELETE CASCADE,
    CONSTRAINT fk_sess_partner  FOREIGN KEY (partner_id)  REFERENCES users   (id) ON DELETE CASCADE,
    CONSTRAINT fk_sess_module   FOREIGN KEY (module_id)   REFERENCES modules (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 6. ratings ────────────────────────────────────────────────────
CREATE TABLE ratings (
    id                INT UNSIGNED                   NOT NULL AUTO_INCREMENT,
    session_id        INT UNSIGNED                   NOT NULL,
    rater_id          INT UNSIGNED                   NOT NULL,
    rated_id          INT UNSIGNED                   NOT NULL,
    score_clarity     TINYINT                        NOT NULL CHECK (score_clarity     BETWEEN 1 AND 5),
    score_punctuality TINYINT                        NOT NULL CHECK (score_punctuality BETWEEN 1 AND 5),
    score_engagement  TINYINT                        NOT NULL CHECK (score_engagement  BETWEEN 1 AND 5),
    role_in_session   ENUM('enseignant','apprenant') NOT NULL DEFAULT 'enseignant',
    comment           TEXT                           NULL,
    created_at        TIMESTAMP                      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_rating (session_id, rater_id),
    INDEX idx_rated_id (rated_id),
    CONSTRAINT fk_rat_session FOREIGN KEY (session_id) REFERENCES sessions (id) ON DELETE CASCADE,
    CONSTRAINT fk_rat_rater   FOREIGN KEY (rater_id)   REFERENCES users    (id) ON DELETE CASCADE,
    CONSTRAINT fk_rat_rated   FOREIGN KEY (rated_id)   REFERENCES users    (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 7. password_resets ────────────────────────────────────────────
CREATE TABLE password_resets (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED NOT NULL,
    token      VARCHAR(64)  NOT NULL,
    expires_at DATETIME     NOT NULL,
    used       TINYINT(1)   NOT NULL DEFAULT 0,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_token (token),
    KEY idx_user_id (user_id),
    CONSTRAINT fk_pr_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 8. custom_modules ─────────────────────────────────────────────
CREATE TABLE custom_modules (
    id         INT UNSIGNED                          NOT NULL AUTO_INCREMENT,
    name       VARCHAR(100)                          NOT NULL,
    created_by INT UNSIGNED                          NOT NULL,
    status     ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP                             NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_cm_status     (status),
    INDEX idx_cm_created_by (created_by),
    CONSTRAINT fk_cm_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- DONNEES DE TEST
-- Tous les comptes : mot de passe = password123
-- Hashes generes avec password_hash('password123', PASSWORD_DEFAULT)
-- ============================================================

INSERT INTO users (name, first_name, email, password, role, school, field, bio, avatar) VALUES
    ('Admin P2P', 'Admin', 'admin@gmail.com',
     '$2y$10$1vEn/F2CON2Jev5vcA84xezfi6mXS4eaxMNMhy/CfHbOVI5Rj7fs.',
     'admin', 'P2P Learning', 'Administration', NULL, NULL),

    ('Youness Khalfi', 'Youness', 'younes@gmail.com',
     '$2y$10$F4gr18powRL./NwunVqVXud5SWaplexI5WkY3SW81FFPVhxQouPFi',
     'student', 'ENSIAS', 'Genie Informatique',
     'Passionne par les algorithmes et le developpement web. Je cherche a progresser en bases de donnees et reseaux tout en partageant mes connaissances en algorithmique et POO.',
     NULL),

    ('Ahmed Benali', 'Ahmed', 'ahmed@gmail.com',
     '$2y$10$bU.lgds4gVNPXZlvS3iyZ.QoneBPrFtMv5WHExywwrpfTVpY9r56K',
     'student', 'ENSA Marrakech', 'Reseaux & Telecoms',
     'Etudiant en reseaux, je maitrise la configuration des equipements Cisco et la cybersecurite. Je veux approfondir mes bases en algorithmique et en programmation orientee objet.',
     NULL),

    ('Hajar Mansouri', 'Hajar', 'hajar@gmail.com',
     '$2y$10$x9ZoKBDtR3xBjQxH2L/aqOFeWIYllY7TQOcOyTjneK/dZp6M5okRO',
     'student', 'Universite Cadi Ayyad', 'Mathematiques Appliquees',
     'Forte en mathematiques et en gestion de projet, je recherche de l''aide en developpement web frontend et en cybersecurite. Je peux aider sur les maths et l''anglais technique.',
     NULL),

    ('Youssef Ait Omar', 'Youssef', 'youssef@gmail.com',
     '$2y$10$aZa8vmeQQK2dnOGDaFTmsey/Jigkp715VSJaF22upotJW8QJLTIKW',
     'student', 'ENSIAS', 'Genie Informatique',
     'Developpeur passionne par l''algorithmique et les mathematiques. Je cherche a ameliorer mes competences en reseaux et en gestion de bases de donnees.',
     NULL),

    ('Ferdaous Rochdi', 'Ferdaous', 'ferdaous@gmail.com',
     '$2y$10$P7UtTuBrzVnauCE1KE9Nc.3uCW022VR1aDyXbl27St5gA9RXJci3q',
     'student', 'ENSA Agadir', 'Systemes Embarques',
     'Specialisee en Linux et shell scripting, je maitrise les reseaux bas niveau. Je cherche a progresser en POO et en developpement web frontend.',
     NULL);

INSERT INTO modules (name) VALUES
    ('Algorithmique'), ('Bases de donnees'), ('Reseaux'),
    ('Programmation orientee objet'), ('Mathematiques'), ('Anglais technique'),
    ('Web Frontend'), ('Linux & Shell'), ('Cybersecurite'), ('Gestion de projet');

INSERT INTO user_modules (user_id, module_id, type) VALUES
    (2,1,'maitrise'),(2,4,'maitrise'),(2,7,'maitrise'),
    (2,2,'lacune'),  (2,3,'lacune'),  (2,5,'lacune'),  (2,8,'lacune'),
    (3,2,'maitrise'),(3,3,'maitrise'),(3,9,'maitrise'),
    (3,1,'lacune'),  (3,4,'lacune'),  (3,6,'lacune'),
    (4,5,'maitrise'),(4,6,'maitrise'),(4,10,'maitrise'),
    (4,7,'lacune'),  (4,9,'lacune'),
    (5,1,'maitrise'),(5,5,'maitrise'),
    (5,3,'lacune'),  (5,2,'lacune'),  (5,10,'lacune'),
    (6,8,'maitrise'),(6,3,'maitrise'),
    (6,4,'lacune'),  (6,7,'lacune');

INSERT INTO messages (sender_id, receiver_id, content, sent_at, read_at) VALUES
    (2, 3, 'Salut Ahmed ! On peut s''aider sur Algo et BDD. Tu es dispo ?',
     NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 2 DAY),
    (3, 2, 'Oui ! Jeudi soir pour Algorithmique ?',
     NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 2 DAY),
    (2, 3, 'Parfait pour jeudi. A 19h ?',
     NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 1 DAY),
    (3, 2, 'C''est note, a jeudi !',
     NOW() - INTERVAL 1 DAY, NULL),
    (2, 4, 'Coucou Hajar ! Echange Web Frontend et Maths ?',
     NOW() - INTERVAL 1 DAY, NULL),
    (5, 3, 'Ahmed, tu peux m''aider sur les Reseaux ? Je bloque sur les sous-reseaux.',
     NOW() - INTERVAL 4 HOUR, NULL);

INSERT INTO sessions (proposer_id, partner_id, module_id, scheduled_at, status) VALUES
    (2, 3, 1, NOW() + INTERVAL 2 DAY, 'confirmed'),
    (3, 2, 2, NOW() + INTERVAL 5 DAY, 'pending'),
    (2, 4, 7, NOW() - INTERVAL 7 DAY, 'done');

INSERT INTO ratings (session_id, rater_id, rated_id, score_clarity, score_punctuality, score_engagement, role_in_session, comment) VALUES
    (3, 4, 2, 5, 5, 5, 'apprenant',  'Youness explique vraiment bien, tres patient et pedagogue !'),
    (3, 2, 4, 4, 4, 4, 'enseignant', 'Hajar est super impliquee, la session s''est tres bien passee.');

-- Exemples de modules proposes par les etudiants
INSERT INTO custom_modules (name, created_by, status) VALUES
    ('Intelligence artificielle', 2, 'pending'),
    ('Flutter & Dart',            5, 'pending');
