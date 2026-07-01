USE localmarket;

CREATE TABLE IF NOT EXISTS users (
    id          INT          AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('buyer','seller','admin') DEFAULT 'buyer',
    profile_pic VARCHAR(255) DEFAULT NULL,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categories (
    id   INT          AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

INSERT IGNORE INTO categories (name) VALUES
    ('Electronics'),
    ('Clothing'),
    ('Furniture'),
    ('Books'),
    ('Vehicles'),
    ('Garden'),
    ('Toys'),
    ('Other');

CREATE TABLE IF NOT EXISTS listings (
    id          INT            AUTO_INCREMENT PRIMARY KEY,
    user_id     INT            NOT NULL,
    title       VARCHAR(200)   NOT NULL,
    description TEXT,
    price       DECIMAL(10,2)  NOT NULL,
    category    VARCHAR(100),
    image       VARCHAR(255)   DEFAULT NULL,
    location    VARCHAR(150),
    status      ENUM('active','sold','removed') DEFAULT 'active',
    created_at  TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS messages (
    id          INT       AUTO_INCREMENT PRIMARY KEY,
    sender_id   INT       NOT NULL,
    receiver_id INT       NOT NULL,
    listing_id  INT       DEFAULT NULL,
    message     TEXT      NOT NULL,
    is_read     TINYINT(1) DEFAULT 0,
    created_at  TIMESTAMP  DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id)   REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (listing_id)  REFERENCES listings(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS orders (
    id          INT           AUTO_INCREMENT PRIMARY KEY,
    listing_id  INT           NOT NULL,
    buyer_id    INT           NOT NULL,
    seller_id   INT           NOT NULL,
    amount      DECIMAL(10,2) NOT NULL,
    status      ENUM('pending','completed','cancelled') DEFAULT 'pending',
    created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
    FOREIGN KEY (buyer_id)   REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (seller_id)  REFERENCES users(id)    ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS reports (
    id                  INT          AUTO_INCREMENT PRIMARY KEY,
    reporter_id         INT          NOT NULL,
    reported_listing_id INT          DEFAULT NULL,
    reported_user_id    INT          DEFAULT NULL,
    reason              VARCHAR(255) NOT NULL,
    status              ENUM('open','reviewed','dismissed') DEFAULT 'open',
    created_at          TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reporter_id)         REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (reported_listing_id) REFERENCES listings(id) ON DELETE SET NULL,
    FOREIGN KEY (reported_user_id)    REFERENCES users(id)    ON DELETE SET NULL
);