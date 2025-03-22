-- First, disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Clear existing products
TRUNCATE TABLE products;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Insert new products with Indian flavors and prices
INSERT INTO products (name, description, price, image_url) VALUES
('Malai Kulfi', 'Traditional Indian ice cream made with thickened milk, cardamom, and pistachios', 89.00, 'images/products/malai-kulfi.jpg'),
('Mango Lassi Ice Cream', 'Creamy mango ice cream inspired by the classic Indian drink', 99.00, 'images/products/mango-lassi.jpg'),
('Paan Ice Cream', 'Unique blend of betel leaves, rose petals, and gulkand in creamy ice cream', 109.00, 'images/products/paan.jpg'),
('Kesar Pista', 'Premium ice cream flavored with saffron and garnished with pistachios', 129.00, 'images/products/kesar-pista.jpg'),
('Rose Falooda', 'Rose-flavored ice cream with vermicelli, basil seeds, and nuts', 119.00, 'images/products/rose-falooda.jpg'),
('Tender Coconut', 'Fresh coconut ice cream with real coconut pieces', 99.00, 'images/products/tender-coconut.jpg'),
('Gulab Jamun', 'Ice cream inspired by the classic Indian dessert', 119.00, 'images/products/gulab-jamun.jpg'),
('Chikoo Ice Cream', 'Sapota fruit ice cream with a natural caramel-like taste', 89.00, 'images/products/chikoo.jpg'),
('Anjeer Honey', 'Fig and honey ice cream with roasted almonds', 139.00, 'images/products/anjeer-honey.jpg'),
('Pista Badam', 'Rich ice cream loaded with pistachios and almonds', 129.00, 'images/products/pista-badam.jpg'),
('Filter Coffee', 'South Indian filter coffee flavored ice cream', 109.00, 'images/products/filter-coffee.jpg'),
('Pan Masala Special', 'Premium paan ice cream with silver warq and dry fruits', 149.00, 'images/products/pan-masala.jpg'),
('Gajar Ka Halwa', 'Ice cream version of the beloved carrot dessert', 119.00, 'images/products/gajar-halwa.jpg'),
('Rasgulla Sundae', 'Bengali rasgulla pieces in creamy vanilla ice cream', 129.00, 'images/products/rasgulla.jpg'),
('Jackfruit Delight', 'Tropical jackfruit ice cream with caramelized bits', 109.00, 'images/products/jackfruit.jpg');

-- Update product images
UPDATE products SET image_url = 'images/products/vanilla-bean.jpg' WHERE name LIKE '%Vanilla%';
UPDATE products SET image_url = 'images/products/chocolate-fudge.jpg' WHERE name LIKE '%Chocolate%';
UPDATE products SET image_url = 'images/products/strawberry-delight.jpg' WHERE name LIKE '%Strawberry%';
UPDATE products SET image_url = 'images/products/mango-tango.jpg' WHERE name LIKE '%Mango%';
UPDATE products SET image_url = 'images/products/butterscotch-bliss.jpg' WHERE name LIKE '%Butterscotch%';
UPDATE products SET image_url = 'images/products/pistachio-dream.jpg' WHERE name LIKE '%Pistachio%';
UPDATE products SET image_url = 'images/products/coffee-caramel.jpg' WHERE name LIKE '%Coffee%';
UPDATE products SET image_url = 'images/products/mint-chip.jpg' WHERE name LIKE '%Mint%';
UPDATE products SET image_url = 'images/products/cookies-cream.jpg' WHERE name LIKE '%Cookie%';
UPDATE products SET image_url = 'images/products/blueberry-cheesecake.jpg' WHERE name LIKE '%Blueberry%';

-- Add image column if not exists
ALTER TABLE products ADD COLUMN IF NOT EXISTS image_url VARCHAR(255);