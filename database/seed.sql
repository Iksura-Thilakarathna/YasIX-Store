-- YasIX Store Seed Data
-- Development Credentials Documentation:
-- Admin User: admin@yasix.com / AdminPass123!
-- Customer User: customer@yasix.com / CustomerPass123!

USE `yasix_store`;

-- Disable Foreign Key checks temporarily for clean re-seeding
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `notifications`;
TRUNCATE TABLE `delivery_rules`;
TRUNCATE TABLE `warranty_claims`;
TRUNCATE TABLE `warranties`;
TRUNCATE TABLE `payments`;
TRUNCATE TABLE `order_items`;
TRUNCATE TABLE `orders`;
TRUNCATE TABLE `cart_items`;
TRUNCATE TABLE `carts`;
TRUNCATE TABLE `addresses`;
TRUNCATE TABLE `product_reviews`;
TRUNCATE TABLE `product_images`;
TRUNCATE TABLE `products`;
TRUNCATE TABLE `categories`;
TRUNCATE TABLE `users`;
TRUNCATE TABLE `roles`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Insert Roles
INSERT INTO `roles` (`id`, `name`, `description`) VALUES
(1, 'ADMIN', 'System Administrator with full access'),
(2, 'CUSTOMER', 'Registered Store Customer');

-- 2. Insert Users
INSERT INTO `users` (`id`, `role_id`, `name`, `email`, `password_hash`, `phone`, `status`) VALUES
(1, 1, 'YasIX System Admin', 'admin@yasix.com', '$2y$10$FNl/fc9RYWjDlwcNYQTJueI4ma0LHKvgpgP7P/jrOEDMu9A8nbIWC', '+94770000000', 'ACTIVE'),
(2, 2, 'Cyber Customer', 'customer@yasix.com', '$2y$10$WhfqTdX6m1YljCqpuB.TfetW3p9nwA8l.V3//JjiU3U0smZWgzyc2', '+94771112233', 'ACTIVE');

-- 3. Insert Categories
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `icon_class`) VALUES
(1, 'Cyber GPUs', 'cyber-gpus', 'High-performance graphics cards for rendering and gaming.', 'fa-solid fa-microchip'),
(2, 'Neural Heuristics', 'neural-heuristics', 'Brainwave sync headsets and bio-feedback acoustics.', 'fa-solid fa-headset'),
(3, 'RGB Kits', 'rgb-kits', 'Customizable RGB peripherals, mechanical keyboards, and precision mice.', 'fa-solid fa-keyboard'),
(4, 'Quantum Gear', 'quantum-gear', 'Quantum processors, liquid nitrogen workstations, and ultra-high refresh displays.', 'fa-solid fa-cpu');

-- 4. Insert Products
INSERT INTO `products` (`id`, `category_id`, `name`, `slug`, `description`, `brand`, `price`, `stock_quantity`, `weight`, `package_length`, `package_width`, `package_height`, `warranty_months`, `condition`, `status`, `featured`) VALUES
(1, 4, 'YasIX Neural Rig RTX 5090', 'yasix-neural-rig-rtx-5090', 'Flagship handcrafted titanium workstation rig with integrated liquid nitrogen loop and 32GB GDDR7 graphics.', 'YasIX', 3499.00, 5, 15.50, 55.00, 30.00, 60.00, 36, 'NEW', 'AVAILABLE', 1),
(2, 2, 'YasIX Synapse Neural Headset', 'yasix-synapse-neural-headset', 'Spatial 7.1 audio with direct neural brainwave acoustic feedback sync and zero-latency wireless connectivity.', 'YasIX', 399.00, 25, 0.45, 20.00, 18.00, 10.00, 24, 'NEW', 'AVAILABLE', 1),
(3, 1, 'RTX 5090 Cyber-Quantum GPU', 'rtx-5090-cyber-quantum-gpu', '32GB GDDR7 VRAM, DLSS 4.0 AI frame generator, dual-chamber vapor cooling array.', 'NVIDIA', 2299.00, 12, 2.80, 35.00, 15.00, 8.00, 36, 'NEW', 'AVAILABLE', 1),
(4, 3, 'AeroBlade Cyber Keyboard', 'aeroblade-cyber-keyboard', 'Opto-magnetic switches with customizable per-key OLED hologram HUD and anodized aluminum chassis.', 'AeroBlade', 249.00, 30, 1.20, 45.00, 16.00, 4.00, 24, 'NEW', 'AVAILABLE', 1),
(5, 4, 'HoloView 360Hz Curved HUD', 'holoview-360hz-curved-hud', '49-inch OLED Quantum Dot display, 0.03ms GTG response time, 1800R curve radius.', 'HoloView', 1199.00, 8, 11.00, 110.00, 35.00, 50.00, 36, 'NEW', 'AVAILABLE', 1),
(6, 3, 'Synapse Ultra Cyber Mouse', 'synapse-ultra-cyber-mouse', '36,000 DPI carbon fiber shell with zero-drag mag-lev gliders and 8000Hz polling rate.', 'YasIX', 149.00, 50, 0.08, 12.00, 7.00, 4.00, 12, 'NEW', 'AVAILABLE', 1),
(7, 4, 'Quantum Core i11 15900KS', 'quantum-core-i11-15900ks', '32 Cores, 64 Threads, 6.2GHz Unlocked Quantum Cache architecture.', 'Intel', 899.00, 15, 0.35, 10.00, 10.00, 5.00, 36, 'NEW', 'AVAILABLE', 1),
(8, 4, 'YasIX Apex Mech-Rig 2026 Edition', 'yasix-apex-mech-rig-2026-edition', 'Custom handcrafted titanium workstation rig with dual RTX 5090 GPUs for severe matrix workloads.', 'YasIX', 2999.00, 11, 18.00, 60.00, 32.00, 65.00, 48, 'NEW', 'AVAILABLE', 1),
(9, 1, 'ASUS ROG Cyber Matrix RTX 5080', 'asus-rog-cyber-matrix-rtx-5080', '24GB GDDR7 VRAM, ARGB infinity mirror cooling shroud, overclocked factory tune.', 'ASUS Cyber', 1699.00, 0, 2.50, 34.00, 14.00, 7.00, 36, 'NEW', 'OUT_OF_STOCK', 0),
(10, 2, 'Razer Neon Synapse Acoustic Pods', 'razer-neon-synapse-acoustic-pods', 'Active noise cancelling wireless neural earbuds with customizable Chroma RGB lighting case.', 'Razer Neon', 199.00, 18, 0.15, 10.00, 10.00, 4.00, 12, 'REFURBISHED', 'AVAILABLE', 0),
(11, 4, 'YasIX Custom-Mod Matrix Liquid Loop', 'yasix-custom-mod-matrix-liquid-loop', 'Hand-bent acrylic tubing liquid cooling kit with UV reactive coolant and digital flow sensor.', 'YasIX', 650.00, 4, 4.20, 40.00, 25.00, 15.00, 24, 'CUSTOM_MOD', 'AVAILABLE', 0),
(12, 3, 'AeroBlade Wireless RGB Mechanical Pad', 'aeroblade-wireless-rgb-mechanical-pad', 'Hot-swappable tactile macro pad with wireless Bluetooth 5.3 connectivity and dial encoder.', 'AeroBlade', 120.00, 10, 0.50, 20.00, 15.00, 3.00, 12, 'REFURBISHED', 'AVAILABLE', 0),
(13, 4, 'Intel Quantum Liquid Cooler 360', 'intel-quantum-liquid-cooler-360', '360mm triple ARGB fan AIO liquid cooler with real-time CPU temperature LCD screen.', 'Intel', 299.00, 20, 2.10, 40.00, 14.00, 12.00, 36, 'NEW', 'AVAILABLE', 0),
(14, 1, 'NVIDIA Tensor Core AI Accelerator', 'nvidia-tensor-core-ai-accelerator', 'Dedicated PCIe 5.0 neural inference card for local LLM training and AI model rendering.', 'NVIDIA', 3999.00, 2, 3.10, 36.00, 16.00, 9.00, 36, 'CUSTOM_MOD', 'AVAILABLE', 0);

-- 5. Insert Product Images
INSERT INTO `product_images` (`product_id`, `image_path`, `is_primary`, `sort_order`) VALUES
(1, '/assets/images/products/neural_rig_1.png', 1, 1),
(2, '/assets/images/products/synapse_headset_1.png', 1, 1),
(3, '/assets/images/products/rtx5090_gpu_1.png', 1, 1),
(4, '/assets/images/products/aeroblade_keyboard_1.png', 1, 1),
(5, '/assets/images/products/holoview_hud_1.png', 1, 1),
(6, '/assets/images/products/synapse_mouse_1.png', 1, 1),
(7, '/assets/images/products/quantum_core_i11_1.png', 1, 1),
(8, '/assets/images/products/apex_mech_rig_1.png', 1, 1);

-- 6. Insert Product Reviews
INSERT INTO `product_reviews` (`id`, `product_id`, `user_id`, `rating`, `review_text`, `is_verified`) VALUES
(1, 1, 2, 5, 'The RTX 5090 Cyber-Quantum rig blew away my expectations. 240FPS in 8K ultra settings with zero thermal throttling!', 1),
(2, 2, 2, 5, 'Neural Headset feedback sync is unreal. Audio immersion feels like direct mind-link connection. Instant recommendation!', 1),
(3, 5, 2, 5, 'Quantum drone shipping arrived at my door in less than 4 hours. YasIX Store is officially the ultimate hardware marketplace.', 1);

-- 7. Insert Delivery Rules
INSERT INTO `delivery_rules` (`id`, `region_name`, `min_weight_kg`, `max_weight_kg`, `base_fee`, `included_weight_kg`, `additional_kg_fee`, `volumetric_divisor`, `handling_fee`, `is_active`) VALUES
(1, 'Western Province', 0.00, 50.00, 350.00, 1.00, 50.00, 5000, 0.00, 1),
(2, 'Outstation Central', 0.00, 50.00, 450.00, 1.00, 75.00, 5000, 0.00, 1),
(3, 'Express Drone Delivery', 0.00, 50.00, 950.00, 1.00, 100.00, 5000, 0.00, 1);
