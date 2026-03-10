-- Test categories (10)
INSERT INTO categories (name, parent_id, icon_path) VALUES
('Електроніка', NULL, ''),
('Смартфони', 1, ''),
('Ноутбуки', 1, ''),
('Дім і сад', NULL, ''),
('Меблі', 4, ''),
('Послуги', NULL, ''),
('Ремонт', 6, ''),
('Транспорт', NULL, ''),
('Велосипеди', 8, ''),
('Дитячі товари', NULL, '');

-- Users
INSERT INTO users (email, role, is_blocked, verified) VALUES
('user1@example.com', 'user', false, true),
('user2@example.com', 'user', false, true),
('user3@example.com', 'user', false, false),
('admin_seed@example.com', 'admin', false, true);

-- Listings (sample 30)
INSERT INTO listings (title, body, author_id, category_id, price, currency, lat, lng, status, rejection_reason, photo_paths)
SELECT
  'Test listing #' || gs,
  'Generated seed listing ' || gs,
  1 + (gs % 3),
  1 + (gs % 10),
  100 + gs * 15,
  'UAH',
  50.40 + (gs % 9) * 0.01,
  30.40 + (gs % 9) * 0.01,
  (ARRAY['draft','pending_verification','active','rejected','archived'])[1 + (gs % 5)],
  CASE WHEN gs % 5 = 3 THEN 'Неповні дані в оголошенні' ELSE '' END,
  '[]'
FROM generate_series(1, 30) gs;
