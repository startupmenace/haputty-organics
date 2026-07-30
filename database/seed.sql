USE toothsavior;

INSERT INTO categories (name, slug) VALUES
('ALL PRODUCTS', 'all-products'),
('TEETH WHITENING KITS', 'teeth-whitening-kits'),
('SONIC TOOTHBRUSHES', 'sonic-toothbrushes'),
('WATER FLOSSERS', 'water-flossers'),
('WHITENING GELS & PENS', 'whitening-gels-pens'),
('WHITENING STRIPS', 'whitening-strips'),
('CHARCOAL CARE', 'charcoal-care');

INSERT INTO products (title, category_id, price, original_price, rating, reviews_count, description, features, details, colors, images, in_stock) VALUES
(
  'HAPUTTY ORGANICS Professional 32-LED Cold Light Teeth Whitening Kit - Complete Home Edition',
  2, 7500, 9800, 5.0, 64,
  'Achieve dentist-grade teeth whitening from home in just 16 minutes per day. Features dual-light technology (Blue LED for fast whitening + Red LED for gum care) with 32 high-powered cold light emitters.',
  '[\"32 Dual LED Light Emitters (Blue & Red light therapeutic wavelength)\",\"Includes 4x Carbamide Peroxide Non-Sensitivity Gel Pens (35% concentration)\",\"Wireless magnetic fast-charging base with IPX7 waterproof mouthpiece\",\"Visibly whiter teeth by up to 8 shades in 3 consecutive treatments\",\"Enamel-safe formula with potassium nitrate to protect sensitive teeth\"]',
  '[{\"key\":\"Light Technology\",\"val\":\"32 Cold-Light LEDs (460-480nm)\"},{\"key\":\"Gel Formula\",\"val\":\"35% Carbamide Peroxide + Phthalimidoperoxycaproic Acid\"},{\"key\":\"Treatment Time\",\"val\":\"16 Minutes Auto Timer\"},{\"key\":\"Battery Life\",\"val\":\"20 Treatments per Charge\"},{\"key\":\"Warranty\",\"val\":\"1 Year Official HAPUTTY ORGANICS Warranty\"}]',
  '[]',
  '[\"https://images.unsplash.com/photo-1620916566398-39f1143ab7be?auto=format&fit=crop&w=800&q=80\",\"https://images.unsplash.com/photo-1556228720-195a672e8a03?auto=format&fit=crop&w=800&q=80\"]',
  1
),
(
  'HAPUTTY ORGANICS SonicPro X9 40,000 VPM Electric Toothbrush Set with UV Sanitizer',
  3, 6800, 8500, 4.9, 52,
  'Removes up to 10x more surface stains and deep interdental plaque than manual brushing. Powered by sonic levitation motor reaching 40,000 micro-vibrations per minute.',
  '[\"40,000 Micro-Brushes per minute sonic levitation motor\",\"5 Cleaning Modes: Whiten, Clean, Polish, Sensitive, Massage\",\"UV Sanitizing Travel Case kills 99.9% of oral bacteria on brush heads\",\"Includes 6 DuPont soft diamond-cut replacement brush heads\",\"30 Days battery life on a single USB-C fast charge\"]',
  '[{\"key\":\"Vibration Frequency\",\"val\":\"40,000 VPM\"},{\"key\":\"Waterproof Level\",\"val\":\"IPX7 Washable\"},{\"key\":\"Timer\",\"val\":\"2-Minute Smart Quad-Pacer\"}]',
  '[]',
  '[\"https://images.unsplash.com/photo-1559591937-e58af10079d3?auto=format&fit=crop&w=800&q=80\",\"https://images.unsplash.com/photo-1556228720-195a672e8a03?auto=format&fit=crop&w=800&q=80\"]',
  1
),
(
  'HAPUTTY ORGANICS V34 Color Corrector Teeth Whitening Serum (30ml)',
  2, 2800, 3800, 4.8, 39,
  'Non-invasive purple color-correcting technology that neutralizes yellow undertones on tooth surface enamel to instantly restore natural brightness.',
  '[\"Water-soluble purple dye formula cancels yellow tones\",\"Instant bright visual effect for photos and daily boost\",\"100% safe on tooth enamel and dental work\"]',
  '[{\"key\":\"Volume\",\"val\":\"30ml Pump Bottle\"},{\"key\":\"Usage\",\"val\":\"Daily Post-Brushing Rinse or Brush\"}]',
  '[]',
  '[\"https://images.unsplash.com/photo-1608248597261-83325803d450?auto=format&fit=crop&w=800&q=80\"]',
  1
),
(
  'HAPUTTY ORGANICS Enamel-Safe 35% Whitening Gel Refill Pens (4-Pack Bundle)',
  5, 3200, 4200, 4.9, 41,
  'Precision twist-pen applicators packed with active whitening serum to target individual stubborn coffee, wine, and tea stains accurately.',
  '[\"4 Precision Brush-tip Pens included\",\"Fast 15-minute quick dry formulation\",\"Refreshing Natural Peppermint Essence\"]',
  '[{\"key\":\"Total Volume\",\"val\":\"4 x 2ml Pens\"},{\"key\":\"Applications\",\"val\":\"Up to 80 Whitening Sessions\"}]',
  '[]',
  '[\"https://images.unsplash.com/photo-1556228720-195a672e8a03?auto=format&fit=crop&w=800&q=80\"]',
  1
),
(
  'HAPUTTY ORGANICS Express Dissolving Whitening Strips (28 Strips / 14 Days)',
  6, 2600, 3500, 4.7, 28,
  'No-slip active dry grip strips that stick firmly to teeth while lifting embedded deep enamel stains without leaving gooey residue behind.',
  '[\"30-Minute Daily Express Whitening Action\",\"No-Slip Grip Seal Technology\",\"Zero Sensitivity Formula with Soothing Aloe\"]',
  '[{\"key\":\"Quantity\",\"val\":\"28 Strips (14 Upper / 14 Lower)\"}]',
  '[]',
  '[\"https://images.unsplash.com/photo-1588776814546-1ffcf47267a5?auto=format&fit=crop&w=800&q=80\"]',
  1
),
(
  'HAPUTTY ORGANICS Cordless Professional Water Flosser Hydro Irrigator (300ml)',
  4, 4900, 6500, 4.9, 57,
  'Flushes away trapped food debris and plaque along gumlines where dental floss cannot reach. Essential for braces, implants, and veneer care.',
  '[\"1400-1800 Water Pulses per minute high pressure jet\",\"4 Pressure Modes: Soft, Normal, Pulse, Custom DIY\",\"Large 300ml detachable leak-proof water reservoir\",\"Includes 4 interchangeable 360° rotating jet nozzles\"]',
  '[{\"key\":\"Tank Capacity\",\"val\":\"300ml\"},{\"key\":\"Pressure Range\",\"val\":\"30 - 120 PSI\"}]',
  '[]',
  '[\"https://images.unsplash.com/photo-1588776814546-1ffcf47267a5?auto=format&fit=crop&w=800&q=80\",\"https://images.unsplash.com/photo-1559591937-e58af10079d3?auto=format&fit=crop&w=800&q=80\"]',
  1
),
(
  'HAPUTTY ORGANICS Organic Activated Charcoal Powder & Bamboo Toothbrush Set',
  7, 1950, 2600, 4.8, 33,
  '100% natural ultra-fine activated coconut shell charcoal powder. Adsorbs surface enamel discolorations and detoxifies mouth odors naturally.',
  '[\"100% Organic Coconut Shell Charcoal\",\"Includes Ultra-Soft Biodegradable Bamboo Brush\",\"Chemical-free, Peroxide-free, Fluoride-free\"]',
  '[{\"key\":\"Weight\",\"val\":\"50g Jar\"}]',
  '[]',
  '[\"https://images.unsplash.com/photo-1608248597261-83325803d450?auto=format&fit=crop&w=800&q=80\"]',
  1
),
(
  'HAPUTTY ORGANICS Nano-Hydroxyapatite Remineralizing Whitening Toothpaste (100g)',
  2, 1600, 2200, 4.9, 22,
  'Biocompatible Nano-Hydroxyapatite (nHAp) formula that repairs micro-fissures in tooth enamel while gently lifting daily coffee stains.',
  '[\"Rebuilds and thickens tooth enamel naturally\",\"Instantly relieves hot & cold tooth sensitivity\",\"SLS Free, Paraben Free, Artificial Dye Free\"]',
  '[{\"key\":\"Net Weight\",\"val\":\"100g Tube\"}]',
  '[]',
  '[\"https://images.unsplash.com/photo-1556228720-195a672e8a03?auto=format&fit=crop&w=800&q=80\"]',
  1
);

-- Admin user (password: admin123)
INSERT INTO users (first_name, last_name, email, phone, password, is_admin) VALUES
('Admin', 'HAPUTTY ORGANICS', 'admin@haputty.co.ke', '254700000000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);
