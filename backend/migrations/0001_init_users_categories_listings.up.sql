CREATE TABLE IF NOT EXISTS users (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  role VARCHAR(32) NOT NULL DEFAULT 'user'
);

CREATE TABLE IF NOT EXISTS categories (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  parent_id BIGINT,
  icon_path TEXT,
  CONSTRAINT fk_categories_parent
    FOREIGN KEY (parent_id) REFERENCES categories(id)
    ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS listings (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  body TEXT,
  author_id BIGINT NOT NULL,
  category_id BIGINT NOT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'pending',
  CONSTRAINT fk_listings_author
    FOREIGN KEY (author_id) REFERENCES users(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_listings_category
    FOREIGN KEY (category_id) REFERENCES categories(id)
    ON DELETE RESTRICT
);
