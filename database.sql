```sql
CREATE DATABASE IF NOT EXISTS digital_tourist
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE digital_tourist;


-- ==================================================
-- REMOVE EXISTING TABLES
-- ==================================================

DROP TABLE IF EXISTS places;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS admin;


-- ==================================================
-- CATEGORIES TABLE
-- ==================================================

CREATE TABLE categories (

    id INT AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(100) NOT NULL UNIQUE

);


-- ==================================================
-- ADMIN TABLE
-- ==================================================

CREATE TABLE admin (

    id INT AUTO_INCREMENT PRIMARY KEY,

    username VARCHAR(100) NOT NULL UNIQUE,

    password VARCHAR(255) NOT NULL

);


-- ==================================================
-- PLACES TABLE
-- ==================================================

CREATE TABLE places (

    id INT AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(200) NOT NULL,

    category_id INT NOT NULL,

    description TEXT NOT NULL,

    distance DECIMAL(6,2) NOT NULL,

    opening_hours VARCHAR(255) DEFAULT '',

    facilities TEXT,

    travel_tips TEXT,

    entrance_fee VARCHAR(255) DEFAULT '',

    latitude VARCHAR(50) DEFAULT '',

    longitude VARCHAR(50) DEFAULT '',

    image VARCHAR(500) DEFAULT '',

    status TINYINT(1) NOT NULL DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (category_id)
        REFERENCES categories(id)

);


-- ==================================================
-- INSERT CATEGORIES
-- ==================================================

INSERT INTO categories (name)
VALUES
    ('Historical'),
    ('Religious'),
    ('Nature'),
    ('Restaurant'),
    ('Public Place');


-- ==================================================
-- INSERT ADMIN
-- ==================================================

INSERT INTO admin (username, password)
VALUES
(
    'admin',
    '$2y$10$KyniHYmdlXc8IO/CD0KiOeb2z7NnVZVKYvq1SsiItbI51EpppSNca'
);

-- Default password for the seed account: admin123


-- ==================================================
-- INSERT PLACES
-- ==================================================

INSERT INTO places (
    name,
    category_id,
    description,
    distance,
    opening_hours,
    facilities,
    travel_tips,
    entrance_fee,
    status
)
VALUES

(
    'Ritigala Archaeological Site',
    3,
    'Peaceful natural spot with beautiful kubuk trees and a relaxing environment.',
    11,
    'Not specified',
    'Not specified',
    'Check current access information before visiting.',
    'Not specified',
    1
),

(
    'Labunoruwakanda Aranya Senasanaya',
    3,
    'A quiet forest meditation monastery surrounded by natural scenery.',
    11,
    'Not specified',
    'Not specified',
    'Respect the religious environment and local rules.',
    'Not specified',
    1
),

(
    'Kala Wewa',
    3,
    'A large ancient reservoir built by King Dhatusena, famous for its scenic beauty and historical importance.',
    22,
    'Not specified',
    'Not specified',
    'Take care near the water and check local conditions.',
    'Not specified',
    1
),

(
    'Avukana Buddha Statue',
    2,
    'A magnificent granite Buddha statue from the Anuradhapura period and one of Sri Lanka’s iconic monuments.',
    23,
    'Not specified',
    'Not specified',
    'Observe site rules and dress respectfully.',
    'Not specified',
    1
),

(
    'Maradankadawala Tank',
    3,
    'A peaceful local reservoir suitable for relaxing, bird watching, and enjoying nature.',
    2,
    'Not specified',
    'Not specified',
    'Take care near the water.',
    'Not specified',
    1
),

(
    'Maradankadawala Raja Maha Viharaya',
    2,
    'A local Buddhist temple with religious and cultural importance to the community.',
    1,
    'Not specified',
    'Not specified',
    'Observe temple customs and dress respectfully.',
    'Not specified',
    1
),

(
    'Mohidhin Jummah Masjidh',
    2,
    'A local mosque serving residents and travellers for daily and Friday prayers.',
    2,
    'Not specified',
    'Not specified',
    'Observe mosque etiquette and prayer arrangements.',
    'Not specified',
    1
),

(
    'Maradankawala Town',
    5,
    'The main town of the area with shops, banks, restaurants, pharmacies, and transport facilities. It is a convenient starting point for visitors.',
    1,
    'Not specified',
    'Shops, banks, restaurants, pharmacies, transport',
    'Useful starting point for visitors.',
    'Not specified',
    1
),

(
    'Alankulama Family Restaurant',
    4,
    'Family-friendly restaurant serving Sri Lankan food and refreshments.',
    2,
    'Not specified',
    'Food and refreshments',
    'Check current opening hours before visiting.',
    'Not specified',
    1
),

(
    'Jathika Namal Uyana',
    1,
    'A protected nature reserve known for its pink quartz mountain, forest trails, and biodiversity.',
    25,
    'Not specified',
    'Not specified',
    'Check current access rules and conditions before visiting.',
    'Not specified',
    1
);
```