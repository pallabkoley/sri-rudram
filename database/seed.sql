USE `divyaaastha`;

-- ═══════════════════════════════════════════════════════
-- ADMIN USER  (password: Admin@123)
-- ═══════════════════════════════════════════════════════
INSERT INTO `users` (`name`, `email`, `phone`, `password_hash`, `role`, `status`) VALUES
('Admin', 'admin@divyaaastha.in', '9000000000',
 '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1HlCS4bZJ18JuywdBhsQjZ8Z1y0S9NK',
 'admin', 1);

-- ═══════════════════════════════════════════════════════
-- TEMPLES
-- ═══════════════════════════════════════════════════════
INSERT INTO `temples` (name, slug, type, city, state, image_url, short_desc, is_featured, is_dark_featured, sort_order) VALUES
('Mata Saptashrungi Devi','mata-saptashrungi-devi','Shakti Peeth','Nashik','Maharashtra',
 'https://images.unsplash.com/photo-1524492412937-b28074a5d7da?auto=format&fit=crop&w=700&q=80',
 'One of the three and a half Shakti Peethas, home to the self-manifested Devi.',1,1,1),

('Brihadeeswarar Temple','brihadeeswarar-temple','UNESCO Heritage','Thanjavur','Tamil Nadu',
 'https://images.unsplash.com/photo-1548013146-72479768bada?auto=format&fit=crop&w=700&q=80',
 'A Chola masterpiece of Dravidian architecture, dedicated to Lord Shiva.',1,0,2),

('Trimbakeshwar Jyotirlinga','trimbakeshwar-jyotirlinga','Jyotirlinga','Nashik','Maharashtra',
 'https://images.unsplash.com/photo-1609920658906-8223bd289001?auto=format&fit=crop&w=700&q=80',
 'Source of the Godavari and home to a unique three-faced Shivling.',1,1,3),

('Tungnath Temple','tungnath-temple','Panch Kedar','Rudraprayag','Uttarakhand',
 'https://images.unsplash.com/photo-1627915599455-6e0b3d1a5f0f?auto=format&fit=crop&w=700&q=80',
 'The highest Shiva temple in the world, wrapped in Himalayan silence.',1,1,4),

('Kashi Vishwanath','kashi-vishwanath','Jyotirlinga','Varanasi','Uttar Pradesh',
 'https://images.unsplash.com/photo-1599661046289-e31897846e41?auto=format&fit=crop&w=700&q=80',
 'The eternal city of Shiva, where Moksha is granted to every devotee.',1,0,5),

('Jagannath Temple','jagannath-temple','Char Dham','Puri','Odisha',
 'https://images.unsplash.com/photo-1582510003544-4d00b7f74220?auto=format&fit=crop&w=700&q=80',
 'Home of Lord Jagannath, famous for the grand Rath Yatra.',1,0,6),

('Mahakaleshwar','mahakaleshwar','Jyotirlinga','Ujjain','Madhya Pradesh',
 'https://images.unsplash.com/photo-1604608672516-f1b9b1d1c0b9?auto=format&fit=crop&w=700&q=80',
 'The only Jyotirlinga facing south, famous for its Bhasma Aarti.',1,0,7),

('Somnath Temple','somnath-temple','Jyotirlinga','Prabhas Patan','Gujarat',
 'https://images.unsplash.com/photo-1548013146-72479768bada?auto=format&fit=crop&w=700&q=80',
 'The first among the twelve Jyotirlingas, standing by the Arabian Sea.',1,1,8),

('Kedarnath Temple','kedarnath-temple','Char Dham','Rudraprayag','Uttarakhand',
 'https://images.unsplash.com/photo-1627915599455-6e0b3d1a5f0f?auto=format&fit=crop&w=700&q=80',
 'One of the twelve Jyotirlingas, amid the Garhwal Himalayas.',1,0,9);

-- ═══════════════════════════════════════════════════════
-- PUJAS
-- ═══════════════════════════════════════════════════════
INSERT INTO `pujas` (temple_id, name, slug, short_desc, price, badge, badge_style, image_url, is_popular, sort_order) VALUES
(5,'Ganesh Puja','ganesh-puja','Remove obstacles and begin new journeys with a complete Vedic Ganesh Puja.',1999,'Most Popular','gold',
 'https://images.unsplash.com/photo-1610701596007-11502861dcfa?auto=format&fit=crop&w=700&q=80',1,1),

(7,'Lakshmi Puja','lakshmi-puja','Invite abundance, prosperity and financial growth into your household.',1499,'Recommended','plum',
 'https://images.unsplash.com/photo-1609766857041-ed402ea8069a?auto=format&fit=crop&w=700&q=80',1,2),

(1,'Durga Puja','durga-puja','Invoke the divine protection of Maa Durga with a powerful Shakti Puja.',2499,'Festival Special','terra',
 'https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?auto=format&fit=crop&w=700&q=80',1,3),

(5,'Satyanarayan Puja','satyanarayan-puja','Traditional Satyanarayan Vrat Katha and family harmony rituals.',1999,'Recommended','plum',
 'https://images.unsplash.com/photo-1524492412937-b28074a5d7da?auto=format&fit=crop&w=700&q=80',1,4),

(3,'Navgraha Puja','navgraha-puja','Pacify all nine planets and reduce malefic effects in your kundali.',2499,NULL,NULL,
 'https://images.unsplash.com/photo-1604608672516-f1b9b1d1c0b9?auto=format&fit=crop&w=700&q=80',1,5),

(7,'Maha Mrityunjaya Puja','maha-mrityunjaya-puja','Powerful Vedic protection for health, longevity and spiritual wellbeing.',2999,'Most Popular','gold',
 'https://images.unsplash.com/photo-1595246140625-573b715d11dc?auto=format&fit=crop&w=700&q=80',1,6),

(8,'Rudrabhishek','rudrabhishek','Abhishek of Shiva Lingam with powerful mantras for peace and prosperity.',1899,'Recommended','plum',
 'https://images.unsplash.com/photo-1583089892943-e02e5b017b6a?auto=format&fit=crop&w=700&q=80',1,7),

(3,'Shani Puja','shani-puja','Reduce Shani dosha and seek blessings for stability and success.',2199,'Festival Special','terra',
 'https://images.unsplash.com/photo-1601050690597-df0568f70950?auto=format&fit=crop&w=700&q=80',1,8);

-- ═══════════════════════════════════════════════════════
-- PRODUCT CATEGORIES
-- ═══════════════════════════════════════════════════════
INSERT INTO `product_categories` (name, slug, sort_order) VALUES
('Bracelets','bracelets',1),
('Rudraksha','rudraksha',2),
('Malas','malas',3),
('Crystals','crystals',4),
('Puja Tools','puja-tools',5);

-- ═══════════════════════════════════════════════════════
-- PRODUCTS
-- ═══════════════════════════════════════════════════════
INSERT INTO `products` (category_id, name, slug, short_desc, price, mrp, badge, badge_style, image_url, stock, is_featured, sort_order) VALUES
(1,'Energised Evil Eye Bracelet','energised-evil-eye-bracelet','Protects against nazar and negative energy, energised with Vedic mantra.',799,1099,'Energised','plum',
 'https://images.unsplash.com/photo-1611591437281-460bfbe1220a?auto=format&fit=crop&w=700&q=80',50,1,1),

(1,'Energised Money Magnet Bracelet','energised-money-magnet-bracelet','Attracts abundance and prosperity with pyrite and citrine crystals.',999,1399,'Bestseller','terra',
 'https://images.unsplash.com/photo-1602173574767-37ac01994b2a?auto=format&fit=crop&w=700&q=80',40,1,2),

(1,'Energised Red Pyrite Bracelet','energised-red-pyrite-bracelet','Boosts confidence, willpower and action — ideal for entrepreneurs.',899,1199,'Energised','plum',
 'https://images.unsplash.com/photo-1610375461246-83df859d849d?auto=format&fit=crop&w=700&q=80',35,1,3),

(2,'Energised 5 Mukhi Rudraksha Bracelet','energised-5-mukhi-rudraksha-bracelet','Calms the mind and supports overall well-being — lab-certified beads.',1299,1799,'Certified','sage',
 'https://images.unsplash.com/photo-1583089892943-e02e5b017b6a?auto=format&fit=crop&w=700&q=80',30,1,4),

(3,'Energised Crystal Mala','energised-crystal-mala','108 clear quartz beads for clarity, focus and amplified intention.',1499,1999,'Healing','plum',
 'https://images.unsplash.com/photo-1611080626919-7cf5a9dbab5b?auto=format&fit=crop&w=700&q=80',25,1,5),

(3,'Sacred Rudraksha Mala','sacred-rudraksha-mala','108 beads of 5 Mukhi Rudraksha, energised with Rudrabhishek.',899,1199,'Bestseller','terra',
 'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?auto=format&fit=crop&w=700&q=80',45,1,6),

(4,'Healing Crystal Bracelet','healing-crystal-bracelet','Amethyst and rose quartz blend for emotional balance and calm.',1199,1599,'Healing','plum',
 'https://images.unsplash.com/photo-1610701596007-11502861dcfa?auto=format&fit=crop&w=700&q=80',28,1,7),

(5,'Consecrated Puja Tool','consecrated-puja-tool','Handcrafted brass tool set, sanctified at a Vedic temple before dispatch.',1499,1999,'Consecrated','plum',
 'https://images.unsplash.com/photo-1609766857041-ed402ea8069a?auto=format&fit=crop&w=700&q=80',20,1,8);

-- ═══════════════════════════════════════════════════════
-- ARTICLE CATEGORIES
-- ═══════════════════════════════════════════════════════
INSERT INTO `article_categories` (name, slug, sort_order) VALUES
('Rudraksha','rudraksha',1),
('Mantra','mantra',2),
('Ritual','ritual',3),
('Festival','festival',4),
('Temple','temple',5),
('Gyan','gyan',6);

-- ═══════════════════════════════════════════════════════
-- ARTICLES
-- ═══════════════════════════════════════════════════════
INSERT INTO `articles` (category_id, title, slug, excerpt, image_url, read_minutes, is_trending, is_featured, published_at) VALUES
(1,'What is Rudraksha? Origin, Types & Benefits','what-is-rudraksha',
 'From the roots of Indian spirituality to modern wellness, discover the complete story of the sacred Rudraksha.',
 'https://images.unsplash.com/photo-1583089892943-e02e5b017b6a?auto=format&fit=crop&w=700&q=80',8,1,0,'2026-02-12 10:00:00'),

(2,'Benefits of Chanting Mantras Daily','benefits-of-chanting-mantras',
 'How consistent japa can change your mind, breath and awareness over time.',
 'https://images.unsplash.com/photo-1601050690597-df0568f70950?auto=format&fit=crop&w=700&q=80',6,0,0,'2026-02-09 10:00:00'),

(3,'How to Perform Lakshmi Puja at Home','how-to-perform-lakshmi-puja',
 'Simple, authentic steps to perform Lakshmi mantra and puja at home.',
 'https://images.unsplash.com/photo-1609766857041-ed402ea8069a?auto=format&fit=crop&w=700&q=80',10,0,0,'2026-02-05 10:00:00'),

(2,'Meaning of the Maha Mrityunjaya Mantra','meaning-of-maha-mrityunjaya-mantra',
 'Work towards understanding the greatest healing mantra of Lord Shiva.',
 'https://images.unsplash.com/photo-1604608672516-f1b9b1d1c0b9?auto=format&fit=crop&w=700&q=80',7,0,0,'2026-02-01 10:00:00'),

(4,'Importance of Ekadashi Vrat','importance-of-ekadashi-vrat',
 'Why the 11th lunar day is considered one of the most auspicious for fasting.',
 'https://images.unsplash.com/photo-1595246140625-573b715d11dc?auto=format&fit=crop&w=700&q=80',5,0,0,'2026-01-28 10:00:00'),

(4,'Complete Guide to Navratri Puja','complete-guide-to-navratri-puja',
 'Nine days, nine forms and the significance behind Navratri.',
 'https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?auto=format&fit=crop&w=700&q=80',12,1,0,'2026-01-24 10:00:00'),

(5,'12 Jyotirlingas of Lord Shiva','12-jyotirlingas-of-lord-shiva',
 'Locations, legends and spiritual significance of each Jyotirlinga.',
 'https://images.unsplash.com/photo-1548013146-72479768bada?auto=format&fit=crop&w=700&q=80',9,1,0,'2026-01-19 10:00:00'),

(6,'Panchmukhi Hanuman: Symbolism & Worship','panchmukhi-hanuman-symbolism-worship',
 'What each of the five faces represents and how to worship this idol at home.',
 'https://images.unsplash.com/photo-1610701596007-11502861dcfa?auto=format&fit=crop&w=700&q=80',6,0,0,'2026-01-14 10:00:00'),

(4,'Jagannath Rath Yatra 2026 — Significance, Rituals & Mantras','jagannath-rath-yatra-2026',
 'Everything you need to know about the world''s largest chariot festival, and how to join the online puja.',
 'https://images.unsplash.com/photo-1582510003544-4d00b7f74220?auto=format&fit=crop&w=1000&q=80',8,1,1,'2026-02-20 10:00:00');

-- ═══════════════════════════════════════════════════════
-- GYAN CATEGORIES
-- ═══════════════════════════════════════════════════════
INSERT INTO `gyan_categories` (name, slug, description, sort_order) VALUES
('Mantra','mantra','Explore powerful sacred mantras.',1),
('Stotra','stotra','Discover devotional hymns and prayers.',2),
('Chalisa','chalisa','Read powerful Chalisa collections.',3),
('Aarti','aarti','Experience daily devotional Aartis.',4),
('Katha','katha','Explore sacred stories and traditions.',5),
('Festival','festival','Learn about Hindu festivals and celebrations.',6),
('Temple','temple','Discover sacred temples across India.',7),
('Ritual','ritual','Explore traditional Hindu rituals and practices.',8);

-- ═══════════════════════════════════════════════════════
-- FESTIVALS
-- ═══════════════════════════════════════════════════════
INSERT INTO `festivals` (name, slug, festival_date, short_desc, image_url) VALUES
('Maha Shivratri','maha-shivratri','2026-02-15','The great night of Shiva — jagran, Rudrabhishek and night-long devotion.',
 'https://images.unsplash.com/photo-1583089892943-e02e5b017b6a?auto=format&fit=crop&w=700&q=80'),

('Raksha Bandhan','raksha-bandhan','2026-08-28','A celebration of sibling love with sacred rakhi and heartfelt blessings.',
 'https://images.unsplash.com/photo-1609766857041-ed402ea8069a?auto=format&fit=crop&w=700&q=80'),

('Janmashtami','janmashtami','2026-09-04','Celebrate the birth of Lord Krishna with fasting, puja and bhajans.',
 'https://images.unsplash.com/photo-1610701596007-11502861dcfa?auto=format&fit=crop&w=700&q=80'),

('Navratri','navratri','2026-10-11','Nine nights of devotion to Maa Durga with vrat and kanya puja.',
 'https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?auto=format&fit=crop&w=700&q=80'),

('Dussehra','dussehra','2026-10-20','Victory of Shri Ram over Ravana — the triumph of Dharma over evil.',
 'https://images.unsplash.com/photo-1595246140625-573b715d11dc?auto=format&fit=crop&w=700&q=80'),

('Diwali','diwali','2026-11-08','The festival of lights — Lakshmi Puja, diyas and joyful festivities.',
 'https://images.unsplash.com/photo-1604608672516-f1b9b1d1c0b9?auto=format&fit=crop&w=700&q=80');

-- ═══════════════════════════════════════════════════════
-- TESTIMONIALS
-- ═══════════════════════════════════════════════════════
INSERT INTO `testimonials` (name, city, initials, rating, message, sort_order) VALUES
('Priya Sharma','Mumbai','PS',5,'The puja booking experience was smooth and authentic. The prasad arrived beautifully packed and felt very special.',1),
('Rohit Kulkarni','Bangalore, Karnataka','RK',5,'I booked Rudrabhishek at Trimbakeshwar and the entire process was handled very professionally. The updates were excellent.',2),
('Meera Patel','Ahmedabad, Gujarat','MP',5,'The Rudraksha mala I ordered is genuinely energised and the finish is excellent. Prasad delivery was also quick.',3);

-- ═══════════════════════════════════════════════════════
-- FAQS
-- ═══════════════════════════════════════════════════════
INSERT INTO `faqs` (question, answer, sort_order) VALUES
('What is Rudraksha?','Rudraksha is a sacred seed traditionally associated with Lord Shiva. Each bead carries a specific number of mukhis (facets) which influence its properties. Our Rudraksha beads are lab-certified, energetically cleansed and sourced from Nepal and Java.',1),
('How do I book a puja online?','Choose a puja, select your preferred temple and an auspicious date. Enter your name, gotra and sankalp details, complete the secure payment and you are done.',2),
('Is prasad delivery available across India?','Yes. Prasad is delivered to over 19,000 pin codes across India. It is sealed in food-grade packaging and dispatched within 3–5 days of the puja.',3),
('Who performs the puja?','Pujas are performed by verified Vedic pandits who have studied in traditional Sanskrit pathshalas and have a minimum of 10 years of ritual experience.',4),
('How can I track my order?','Once your order is dispatched, you will receive a tracking link via SMS and email. You can also track it from My Orders in your account dashboard.',5),
('Are the products authentic?','Absolutely. Every product is either sourced directly from temples, certified laboratories, or traditional artisan families.',6),
('What payment methods are supported?','We accept UPI, credit and debit cards, net banking, popular wallets and EMI on select orders.',7),
('How can I cancel my puja booking?','Puja bookings can be cancelled free of charge up to 24 hours before the scheduled muhurat.',8);

-- ═══════════════════════════════════════════════════════
-- COUPONS
-- ═══════════════════════════════════════════════════════
INSERT INTO `coupons` (code, type, value, min_order, max_discount, usage_limit, valid_from, valid_until) VALUES
('DIVYA10','percent',10,999,500,1000,'2026-01-01','2026-12-31'),
('WELCOME50','flat',50,499,NULL,500,'2026-01-01','2026-12-31');

-- ═══════════════════════════════════════════════════════
-- SETTINGS
-- ═══════════════════════════════════════════════════════
INSERT INTO `settings` (key_name, value) VALUES
('announce_text','Free Prasad delivery on Pujas above ₹999 · Use code DIVYA10 for 10% off'),
('contact_email','seva@divyaaastha.in'),
('contact_phone','+91 90000 00000'),
('contact_address','Bharat · Serving worldwide'),
('rating','4.8'),
('devotee_count','10 Lakh+'),
('free_shipping_min','999'),
('site_logo',''),
('social_instagram','#'),
('social_facebook','#'),
('social_youtube','#'),
('social_whatsapp','919000000000');