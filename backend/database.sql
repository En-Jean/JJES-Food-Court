-- =====================================================
-- JJES Food Court - Complete Database Setup
-- =====================================================

-- Drop database if exists (WARNING: This deletes all data!)
-- DROP DATABASE IF EXISTS jjes_foodcourt;

-- Create database
CREATE DATABASE IF NOT EXISTS jjes_foodcourt;
USE jjes_foodcourt;

-- =====================================================
-- TABLE: users
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    contact VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABLE: menu_items
-- =====================================================
CREATE TABLE IF NOT EXISTS menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category VARCHAR(50) NOT NULL,
    image_url VARCHAR(255),
    is_available TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABLE: orders
-- =====================================================
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    customer_name VARCHAR(100) NOT NULL,
    contact VARCHAR(20) NOT NULL,
    order_type ENUM('dine-in', 'delivery') NOT NULL,
    table_number VARCHAR(20),
    address TEXT,
    total_price DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    payment_reference VARCHAR(100),
    status ENUM('pending', 'preparing', 'ready', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABLE: order_details
-- =====================================================
CREATE TABLE IF NOT EXISTS order_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    menu_item_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABLE: payments
-- =====================================================
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    payment_details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- INSERT: Menu Items (22 items)
-- =====================================================
INSERT INTO menu_items (name, description, price, category, image_url, is_available) VALUES
('Chickenjoy (1pc)', 'Crispy, juicy fried chicken served with gravy. A classic crowd favorite!', 45.00, 'Chicken', 'images/menu/chicken-joy.jpg', 1),
('Grilled Chicken Sandwich', 'Tender grilled chicken fillet in a toasted bun with fresh veggies', 55.00, 'Chicken', 'images/menu/Grilled-Chicken-Sandwich.jpg', 1),
('Classic Burger', 'Juicy beef patty with lettuce, tomato, onion, and special sauce', 35.00, 'Burgers', 'images/menu/burger.jpg', 1),
('Double Cheeseburger', 'Two beef patties stacked with double cheese and smoky BBQ sauce', 99.00, 'Burgers', 'images/menu/cheese-burger.jpg', 1),
('Spaghetti', 'Sweet-style spaghetti topped with ground meat and special tomato sauce', 59.00, 'Pasta', 'images/menu/spaghetti.jpg', 1),
('Carbonara', 'Creamy white sauce pasta with bacon bits and a sprinkle of parmesan', 59.00, 'Pasta', 'images/menu/carbonara.jpg', 1),
('French Fries (Large)', 'Golden, crispy fries seasoned to perfection. Best paired with any meal!', 55.00, 'Sides', 'images/menu/french-fries.jpg', 1),
('Onion Rings', 'Crunchy battered onion rings, golden fried and served with dipping sauce', 49.00, 'Sides', 'images/menu/onion-rings.jpg', 1),
('Iced Coffee', 'Chilled brewed coffee with milk and sugar. Refreshing any time of day.', 49.00, 'Drinks', 'images/menu/iced-coffee.jpg', 1),
('Mango Shake', 'Fresh mango shake', 49.00, 'Drinks', 'images/menu/mango-shake.jpg', 1),
('Lemonade', 'Freshly squeezed lemon juice with a hint of mint and sweetness', 39.00, 'Drinks', 'images/menu/lemonade.jpg', 1),
('Mango Float', 'Creamy layers of graham crackers, fresh mangoes, and condensed milk', 89.00, 'Desserts', 'images/menu/mango-float.jpg', 1),
('Siomai', 'Steamed pork and shrimp dumplings served with soy sauce and calamansi', 45.00, 'Sides', 'images/menu/siomai.jpg', 1),
('Halo-Halo', 'Classic Filipino shaved ice dessert with mixed fruits, beans, and leche flan', 79.00, 'Desserts', 'images/menu/halo-halo.jpg', 1),
('Hamburger Steak', 'Juicy beef patty smothered in savory mushroom gravy with rice', 85.00, 'Main Dishes', 'images/menu/hamburger-steak.jpg', 1),
('Soda Float', 'Refreshing soda with a scoop of vanilla ice cream on top', 55.00, 'Drinks', 'images/menu/soda-float.jpg', 1),
('Leche Flan', 'Rich and creamy caramel custard made with egg yolk and condensed milk', 69.00, 'Desserts', 'images/menu/leche-flan.jpg', 1),
('Veggie Wrap', 'Fresh vegetables and hummus wrapped in a soft tortilla', 95.00, 'Salads', 'images/menu/veggie-wrap.jpg', 1),
('Sundae', 'Creamy vanilla ice cream topped with your choice of chocolate or strawberry sauce', 59.00, 'Desserts', 'images/menu/sundae.jpg', 1),
('Chicken Salad', 'Grilled chicken breast over mixed greens with fresh vegetables and dressing', 79.00, 'Salads', 'images/menu/chicken-salad.jpg', 1),
('Iced Tea', 'Refreshing brewed tea served cold with lemon slice', 35.00, 'Drinks', 'images/menu/iced-tea.jpg', 1),
('Teriyaki Chicken', 'Grilled chicken glazed with sweet and savory teriyaki sauce with rice', 135.00, 'Main Dishes', 'images/menu/teriyaki-chicken.jpg', 1);

-- =====================================================
-- INSERT: Sample User (Optional - for testing)
-- Password: password123 (hashed with password_hash)
-- =====================================================
INSERT INTO users (name, email, password, contact) VALUES
('Test User', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '09123456789');

-- =====================================================
-- VERIFICATION QUERIES (Optional - to check data)
-- =====================================================
-- SELECT COUNT(*) as total_menu_items FROM menu_items;
-- SELECT COUNT(*) as total_users FROM users;
-- SELECT * FROM menu_items WHERE is_available = 1 ORDER BY category, name;