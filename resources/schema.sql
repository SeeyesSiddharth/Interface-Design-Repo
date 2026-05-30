-- ============================================================
-- MySQL 8.0+ import for games.js seed data (flattened req fields)
-- Source: games.js (seedGames, seedReviews)
-- ============================================================

START TRANSACTION;

-- 1) Drop existing tables (safe order due to FKs)
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS game_tags;
DROP TABLE IF EXISTS game_platforms;
DROP TABLE IF EXISTS games;
DROP TABLE IF EXISTS users;

-- 2) Core games table (requirements flattened)
CREATE TABLE games (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  slug          VARCHAR(128) NOT NULL UNIQUE,
  title         VARCHAR(255) NOT NULL,
  studio        VARCHAR(255) NOT NULL,
  genre         VARCHAR(128) NOT NULL,
  release_year  SMALLINT NOT NULL,

  rating        DECIMAL(2,1) NOT NULL,
  likes         INT NOT NULL DEFAULT 0,
  cover_theme   VARCHAR(64) NOT NULL,
  summary       TEXT NOT NULL,
  notes         TEXT,

  -- Minimum requirements (flattened)
  min_cpu       VARCHAR(255) NOT NULL,
  min_gpu       VARCHAR(255) NOT NULL,
  min_ram       SMALLINT NOT NULL,   -- GB
  min_storage   SMALLINT NOT NULL,   -- GB
  min_os        VARCHAR(255) NOT NULL,

  -- Recommended requirements (flattened)
  rec_cpu       VARCHAR(255) NOT NULL,
  rec_gpu       VARCHAR(255) NOT NULL,
  rec_ram       SMALLINT NOT NULL,   -- GB
  rec_storage   SMALLINT NOT NULL,   -- GB
  rec_os        VARCHAR(255) NOT NULL,

  CHECK (rating >= 0 AND rating <= 5),
  CHECK (likes >= 0),
  CHECK (min_ram >= 0 AND rec_ram >= 0),
  CHECK (min_storage >= 0 AND rec_storage >= 0)
) ENGINE=InnoDB;

-- 3) Normalised platforms (from seedGames.platform arrays) [1](https://onedrive.live.com?cid=A547EA7417BFDB0F&id=A547EA7417BFDB0F!se5ad05b0679d4e0a8bb1cf621e0025f8)
CREATE TABLE game_platforms (
  game_id   INT NOT NULL,
  platform  VARCHAR(64) NOT NULL,
  PRIMARY KEY (game_id, platform),
  CONSTRAINT fk_gp_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4) Normalised tags (from seedGames.tags arrays) [1](https://onedrive.live.com?cid=A547EA7417BFDB0F&id=A547EA7417BFDB0F!se5ad05b0679d4e0a8bb1cf621e0025f8)
CREATE TABLE game_tags (
  game_id  INT NOT NULL,
  tag      VARCHAR(64) NOT NULL,
  PRIMARY KEY (game_id, tag),
  CONSTRAINT fk_gt_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5) Reviews (seedReviews) [1](https://onedrive.live.com?cid=A547EA7417BFDB0F&id=A547EA7417BFDB0F!se5ad05b0679d4e0a8bb1cf621e0025f8)
CREATE TABLE reviews (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  game_id  INT NOT NULL,
  author   VARCHAR(128) NOT NULL,
  score    TINYINT NOT NULL,
  comment  TEXT NOT NULL,
  CHECK (score >= 0 AND score <= 5),
  CONSTRAINT fk_reviews_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6) Users
CREATE TABLE users (
  id 		INT AUTO_INCREMENT PRIMARY KEY,
    
  name 		VARCHAR(50) NOT NULL,
  email 	VARCHAR(100) NOT NULL UNIQUE,
  password 	VARCHAR(255) NOT NULL,
    
  role 		ENUM('admin', 'member') NOT NULL DEFAULT 'member',
    
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Helpful indexes
CREATE INDEX idx_games_release_year ON games(release_year);
CREATE INDEX idx_games_genre        ON games(genre);
CREATE INDEX idx_reviews_game_id    ON reviews(game_id);

-- ============================================================
-- 6) Insert games (flattened min_*/rec_* columns)
-- ============================================================

INSERT INTO games (
  id, slug, title, studio, genre, release_year,
  rating, likes, cover_theme, summary, notes,
  min_cpu, min_gpu, min_ram, min_storage, min_os,
  rec_cpu, rec_gpu, rec_ram, rec_storage, rec_os
) VALUES
(
  1, 'starfall-frontier', 'Starfall Frontier', 'Northgate Interactive', 'Open World RPG', 2025,
  4.7, 132, 'cover-space',
  'A wide-open science fiction RPG with settlement building, ship upgrades, and demanding lighting effects.',
  'SSD strongly recommended for city streaming and fast travel.',
  'Intel i5-8400 / Ryzen 5 2600', 'GTX 1660 / RX 590', 12, 85, 'Windows 10 64-bit',
  'Intel i7-12700 / Ryzen 7 5800X', 'RTX 3070 / RX 6800', 16, 85, 'Windows 11 64-bit'
),
(
  2, 'neon-rally-zero', 'Neon Rally Zero', 'Pulse Arcade', 'Racing', 2024,
  4.4, 88, 'cover-neon',
  'An arcade racing game built around night circuits, destructible signage, and split-screen modes.',
  'Runs well on mid-range laptops with shadows set to medium.',
  'Intel i3-9100 / Ryzen 3 3100', 'GTX 1050 Ti / RX 570', 8, 32, 'Windows 10 64-bit',
  'Intel i5-11600K / Ryzen 5 5600', 'RTX 2060 / RX 6600', 16, 32, 'Windows 11 64-bit'
),
(
  3, 'mythic-harbor', 'Mythic Harbor', 'Lantern Loom', 'Cozy Simulation', 2026,
  4.8, 154, 'cover-harbor',
  'A relaxed town builder about restoring a magical port, managing resources, and trading with friends.',
  'Great candidate for the accessibility page because the UI has scalable text and color options.',
  'Intel i3-6100 / Ryzen 3 1200', 'Intel Iris Xe / GTX 950', 6, 18, 'Windows 10 / macOS 13',
  'Intel i5-10400 / Ryzen 5 3600', 'GTX 1650 / RX 5500', 8, 18, 'Windows 11 / macOS 14'
),
(
  4, 'iron-siege-tactics', 'Iron Siege Tactics', 'Grey Banner', 'Strategy', 2023,
  4.2, 73, 'cover-iron',
  'A turn-based tactics game with destructible cover, morale systems, and a detailed battle replay viewer.',
  'CPU load rises on large maps with many active units.',
  'Intel i5-6500 / Ryzen 3 1300X', 'GTX 960 / RX 470', 8, 24, 'Windows 10 64-bit',
  'Intel i5-10600 / Ryzen 5 3600', 'GTX 1660 Super / RX 5600 XT', 16, 24, 'Windows 11 64-bit'
),
(
  5, 'echoes-of-vanta', 'Echoes of Vanta', 'Orbital Finch', 'Horror Adventure', 2025,
  4.5, 119, 'cover-vanta',
  'A cinematic horror adventure using heavy volumetric fog, spatial audio, and branching investigation scenes.',
  'The ultra preset is GPU-heavy because of fog, reflections, and high-resolution shadows.',
  'Intel i5-9400F / Ryzen 5 2600', 'GTX 1660 Super / RX 5600 XT', 12, 46, 'Windows 10 64-bit',
  'Intel i7-12700K / Ryzen 7 7700', 'RTX 4070 / RX 7800 XT', 32, 46, 'Windows 11 64-bit'
),
(
  6, 'bytebound', 'Bytebound', 'Tiny Circuit', 'Puzzle Platformer', 2024,
  4.1, 65, 'cover-byte',
  'A puzzle platformer where players rewrite rules in the level using simple logic blocks.',
  'Good example for explaining low-spec optimization and browser play.',
  'Dual-core 2.4 GHz', 'Integrated graphics', 4, 4, 'Modern desktop OS',
  'Intel i3-8100 / Ryzen 3 2200G', 'GTX 950 / Iris Xe', 8, 4, 'Windows 10 / macOS 13 / Linux'
),
(
  7, 'verdant-skies-online', 'Verdant Skies Online', 'Meadowforge', 'MMO', 2026,
  4.3, 101, 'cover-verdant',
  'A social MMO focused on exploration, crafting economies, guild housing, and seasonal events.',
  'Network stability matters more than peak frame rate during raids.',
  'Intel i5-7500 / Ryzen 5 1600', 'GTX 1060 / RX 580', 12, 70, 'Windows 10 64-bit',
  'Intel i7-11700 / Ryzen 7 5700X', 'RTX 3060 / RX 6700 XT', 16, 70, 'Windows 11 64-bit'
),
(
  8, 'apex-kitchen-clash', 'Apex Kitchen Clash', 'Sauce Labs Studio', 'Party Game', 2023,
  4.0, 49, 'cover-kitchen',
  'A chaotic party game where teams cook, sabotage kitchens, and manage absurd delivery routes.',
  'Excellent entry for accessibility and couch multiplayer filtering.',
  'Intel i3-7100 / Ryzen 3 1200', 'GTX 750 Ti / RX 460', 4, 10, 'Windows 10 64-bit',
  'Intel i5-9400 / Ryzen 5 2600', 'GTX 1050 Ti / RX 570', 8, 10, 'Windows 11 64-bit'
),
(
  9, 'riftwalkers', 'Riftwalkers', 'Blue Ember', 'Action Roguelite', 2024,
  4.6, 140, 'cover-rift',
  'A fast roguelite with procedural arenas, deep builds, and short runs designed for replayability.',
  'Performance is strongest with effects quality reduced during boss phases.',
  'Intel i5-6600 / Ryzen 3 2300X', 'GTX 970 / RX 570', 8, 16, 'Windows 10 / Linux',
  'Intel i5-12400 / Ryzen 5 5600X', 'RTX 2060 / RX 6600', 16, 16, 'Windows 11 / Linux'
),
(
  10, 'solar-forge-manager', 'Solar Forge Manager', 'Copperline Systems', 'Management Sim', 2025,
  4.2, 81, 'cover-solar',
  'A factory management sim about designing orbital production lines and avoiding resource bottlenecks.',
  'Late-game factories are CPU and memory intensive.',
  'Intel i5-8500 / Ryzen 5 2600', 'GTX 1050 / RX 560', 8, 20, 'Windows 10 / macOS 13',
  'Intel i7-13700 / Ryzen 7 7700X', 'RTX 3060 / RX 6700', 32, 20, 'Windows 11 / macOS 14'
),
(
  11, 'court-of-ash', 'Court of Ash', 'Violet Path', 'Narrative RPG', 2022,
  4.5, 96, 'cover-ash',
  'A branching political RPG with dialogue checks, party relationships, and a dense codex.',
  'A strong detail-page example because the content is mostly narrative rather than benchmark based.',
  'Intel i3-6100 / Ryzen 3 1200', 'GTX 950 / RX 460', 6, 28, 'Windows 10 / macOS 12',
  'Intel i5-9600 / Ryzen 5 2600', 'GTX 1650 / RX 5500', 12, 28, 'Windows 11 / macOS 14'
),
(
  12, 'deep-current', 'Deep Current', 'Abyssal Works', 'Survival', 2026,
  4.7, 127, 'cover-current',
  'An underwater survival game with base construction, scanning, storms, and dynamic water simulation.',
  'Physics, water quality, and draw distance are the main tuning controls.',
  'Intel i5-10400 / Ryzen 5 3600', 'RTX 2060 / RX 6600', 16, 58, 'Windows 10 64-bit',
  'Intel i7-13700K / Ryzen 7 7800X3D', 'RTX 4080 / RX 7900 XTX', 32, 58, 'Windows 11 64-bit'
);

-- ============================================================
-- 7) Insert platforms (one row per platform per game) [1](https://onedrive.live.com?cid=A547EA7417BFDB0F&id=A547EA7417BFDB0F!se5ad05b0679d4e0a8bb1cf621e0025f8)
-- ============================================================

INSERT INTO game_platforms (game_id, platform) VALUES
(1,'Windows'),(1,'Steam Deck'),(1,'Cloud'),
(2,'Windows'),(2,'Linux'),
(3,'Windows'),(3,'macOS'),(3,'Steam Deck'),
(4,'Windows'),
(5,'Windows'),(5,'Cloud'),
(6,'Windows'),(6,'macOS'),(6,'Linux'),(6,'Browser'),
(7,'Windows'),
(8,'Windows'),(8,'Steam Deck'),
(9,'Windows'),(9,'Linux'),
(10,'Windows'),(10,'macOS'),
(11,'Windows'),(11,'macOS'),(11,'Cloud'),
(12,'Windows');

-- ============================================================
-- 8) Insert tags (one row per tag per game) [1](https://onedrive.live.com?cid=A547EA7417BFDB0F&id=A547EA7417BFDB0F!se5ad05b0679d4e0a8bb1cf621e0025f8)
-- ============================================================

INSERT INTO game_tags (game_id, tag) VALUES
(1,'Ray tracing'),(1,'Controller friendly'),(1,'Large install'),
(2,'Split-screen'),(2,'Low latency'),(2,'HDR'),
(3,'Co-op'),(3,'Low spec'),(3,'Creative'),
(4,'Turn-based'),(4,'Replay tools'),(4,'Keyboard first'),
(5,'Spatial audio'),(5,'Story rich'),(5,'High VRAM'),
(6,'Browser demo'),(6,'Education'),(6,'Low spec'),
(7,'Online'),(7,'Guilds'),(7,'Persistent world'),
(8,'Local co-op'),(8,'Family friendly'),(8,'Controller friendly'),
(9,'Procedural'),(9,'Fast load'),(9,'High FPS'),
(10,'Automation'),(10,'Planning'),(10,'CPU heavy'),
(11,'Story rich'),(11,'Cloud save'),(11,'Choices matter'),
(12,'Survival'),(12,'Physics'),(12,'Atmospheric');

-- ============================================================
-- 9) Insert reviews [1](https://onedrive.live.com?cid=A547EA7417BFDB0F&id=A547EA7417BFDB0F!se5ad05b0679d4e0a8bb1cf621e0025f8)
-- ============================================================

INSERT INTO reviews (id, game_id, author, score, comment) VALUES
(1, 1, 'Aelor', 5, 'The compare table makes it clear this needs a modern GPU for high settings.'),
(2, 3, 'Mira', 5, 'Runs smoothly on a student laptop and has clear accessibility options.'),
(3, 5, 'Kai', 4, 'Fantastic atmosphere, but the recommended requirements should be explained carefully.'),
(4, 10, 'Siddh', 4, 'Useful example for showing CPU-heavy games in the requirements guide.');

COMMIT;