-- ============================================================================
-- SkillBridge.lk — Demo Seed Data
-- 3 freelancers per category (8 categories x 3 = 24 freelancers), each with
-- one service listing. Verified status is mixed within every category so
-- search/filter results look realistic. Run this AFTER schema.sql.
--
-- All demo accounts share the password: Passw0rd!
-- (hash below is password_hash('Passw0rd!', PASSWORD_DEFAULT) — bcrypt)
-- ============================================================================

USE gpss_database;

SET @demo_hash = '$2y$10$92k1w8x7WqvV6zZbYhF9UOe1s0m1r0F0m4Kk2m8G0m2wq3fD1P2Nu';

-- ----------------------------------------------------------------------------
-- Users (24 freelancers). role='freelancer', language_pref mixed for realism.
-- ----------------------------------------------------------------------------
INSERT INTO users (full_name, email, password_hash, role, language_pref) VALUES
-- Tutoring
('Nadeesha Perera',   'nadeesha.tutor@example.com',   @demo_hash, 'freelancer', 'english'),
('Kavindu Silva',     'kavindu.tutor@example.com',    @demo_hash, 'freelancer', 'sinhala'),
('Abirami Raj',       'abirami.tutor@example.com',    @demo_hash, 'freelancer', 'tamil'),
-- Photography
('Dilshan Fernando',  'dilshan.photo@example.com',    @demo_hash, 'freelancer', 'english'),
('Sanduni Wickrama',  'sanduni.photo@example.com',    @demo_hash, 'freelancer', 'sinhala'),
('Karthik Selvam',    'karthik.photo@example.com',    @demo_hash, 'freelancer', 'tamil'),
-- Web Design
('Ruwan Jayasuriya',  'ruwan.web@example.com',        @demo_hash, 'freelancer', 'english'),
('Ishara Bandara',    'ishara.web@example.com',       @demo_hash, 'freelancer', 'sinhala'),
('Priya Kumaran',     'priya.web@example.com',        @demo_hash, 'freelancer', 'tamil'),
-- Electrical Repair
('Chamara Gunawardena','chamara.elec@example.com',    @demo_hash, 'freelancer', 'english'),
('Nuwan Rathnayake',  'nuwan.elec@example.com',       @demo_hash, 'freelancer', 'sinhala'),
('Suresh Kandiah',    'suresh.elec@example.com',      @demo_hash, 'freelancer', 'tamil'),
-- Plumbing
('Lasantha Peiris',   'lasantha.plumb@example.com',   @demo_hash, 'freelancer', 'english'),
('Tharindu Madushan', 'tharindu.plumb@example.com',   @demo_hash, 'freelancer', 'sinhala'),
('Vignesh Ramasamy',  'vignesh.plumb@example.com',    @demo_hash, 'freelancer', 'tamil'),
-- Graphic Design
('Yasodha Wijeratne', 'yasodha.design@example.com',   @demo_hash, 'freelancer', 'english'),
('Hasitha Karunaratne','hasitha.design@example.com',  @demo_hash, 'freelancer', 'sinhala'),
('Divya Chandran',    'divya.design@example.com',     @demo_hash, 'freelancer', 'tamil'),
-- Content Writing
('Ashan Dissanayake', 'ashan.write@example.com',      @demo_hash, 'freelancer', 'english'),
('Menaka Rajapaksha', 'menaka.write@example.com',     @demo_hash, 'freelancer', 'sinhala'),
('Deepan Murugesan',  'deepan.write@example.com',     @demo_hash, 'freelancer', 'tamil'),
-- Event Services
('Chathura Amarasinghe','chathura.event@example.com', @demo_hash, 'freelancer', 'english'),
('Iresha Senanayake', 'iresha.event@example.com',     @demo_hash, 'freelancer', 'sinhala'),
('Kirushanth Pillai',  'kirushanth.event@example.com', @demo_hash, 'freelancer', 'tamil');

-- ----------------------------------------------------------------------------
-- Freelancer profiles — verified_badge mixed within every category
-- (2 verified + 1 not yet verified, rotated per category for realism)
-- ----------------------------------------------------------------------------
INSERT INTO freelancer_profiles (user_id, skill_category, bio, verified_badge, latitude, longitude)
SELECT user_id,
  CASE
    WHEN email LIKE '%.tutor@%'  THEN 'Tutoring'
    WHEN email LIKE '%.photo@%'  THEN 'Photography'
    WHEN email LIKE '%.web@%'    THEN 'Web Design'
    WHEN email LIKE '%.elec@%'   THEN 'Electrical Repair'
    WHEN email LIKE '%.plumb@%'  THEN 'Plumbing'
    WHEN email LIKE '%.design@%' THEN 'Graphic Design'
    WHEN email LIKE '%.write@%'  THEN 'Content Writing'
    WHEN email LIKE '%.event@%'  THEN 'Event Services'
  END,
   CASE language_pref
    WHEN 'sinhala' THEN
      CONCAT('ශ්‍රී ලංකාවේ පදිංචි පළපුරුදු ',
        CASE
          WHEN email LIKE '%.tutor@%'  THEN 'ටියුටර්'
          WHEN email LIKE '%.photo@%'  THEN 'ඡායාරූප ශිල්පියෙක්'
          WHEN email LIKE '%.web@%'    THEN 'වෙබ් නිර්මාණකරුවෙක්'
          WHEN email LIKE '%.elec@%'   THEN 'විදුලි කාර්මිකයෙක්'
          WHEN email LIKE '%.plumb@%'  THEN 'ජල නල කාර්මිකයෙක්'
          WHEN email LIKE '%.design@%' THEN 'ග්‍රැෆික් නිර්මාණකරුවෙක්'
          WHEN email LIKE '%.write@%'  THEN 'අන්තර්ගත රචකයෙක්'
          WHEN email LIKE '%.event@%'  THEN 'උත්සව සම්බන්ධීකාරකයෙක්'
        END,
        'කු, ඔබේ ඊළඟ ව්‍යාපෘතියට උදව් කිරීමට සූදානම්.')
    WHEN 'tamil' THEN
      CONCAT('இலங்கையில் வசிக்கும் அனுபவமிக்க ',
        CASE
          WHEN email LIKE '%.tutor@%'  THEN 'பயிற்றுநர்'
          WHEN email LIKE '%.photo@%'  THEN 'புகைப்படக்காரர்'
          WHEN email LIKE '%.web@%'    THEN 'வெப் வடிவமைப்பாளர்'
          WHEN email LIKE '%.elec@%'   THEN 'மின் தொழிலாளர்'
          WHEN email LIKE '%.plumb@%'  THEN 'குழாய் தொழிலாளர்'
          WHEN email LIKE '%.design@%' THEN 'கிராஃபிக் வடிவமைப்பாளர்'
          WHEN email LIKE '%.write@%'  THEN 'உள்ளடக்க எழுத்தாளர்'
          WHEN email LIKE '%.event@%'  THEN 'நிகழ்வு ஒருங்கிணைப்பாளர்'
        END,
        ', உங்கள் அடுத்த திட்டத்திற்கு உதவத் தயார்.')
    ELSE
      CONCAT('Experienced ',
        CASE
          WHEN email LIKE '%.tutor@%'  THEN 'tutor'
          WHEN email LIKE '%.photo@%'  THEN 'photographer'
          WHEN email LIKE '%.web@%'    THEN 'web designer'
          WHEN email LIKE '%.elec@%'   THEN 'electrician'
          WHEN email LIKE '%.plumb@%'  THEN 'plumber'
          WHEN email LIKE '%.design@%' THEN 'graphic designer'
          WHEN email LIKE '%.write@%'  THEN 'content writer'
          WHEN email LIKE '%.event@%'  THEN 'event coordinator'
        END,
        ' based in Sri Lanka, ready to help with your next project.')
  END,
  -- Mix: 1st and 2nd user in each category verified, 3rd not (yet)
  CASE WHEN (user_id - 1) % 3 = 2 THEN 0 ELSE 1 END,
  6.9271 + (RAND() - 0.5) * 2,   -- rough lat/lng scatter around Sri Lanka
  79.8612 + (RAND() - 0.5) * 2
FROM users
WHERE role = 'freelancer';

-- ----------------------------------------------------------------------------
-- One service listing per freelancer, priced per category
-- ----------------------------------------------------------------------------
INSERT INTO services (profile_id, category_id, title, price, description)
SELECT
  fp.profile_id,
  c.category_id,
  CASE u.language_pref
    WHEN 'sinhala' THEN
      CASE c.category_key
        WHEN 'tutoring'       THEN 'පුද්ගලික ටියුෂන් පන්තිය'
        WHEN 'photography'    THEN 'උත්සව සහ පින්තූර ඡායාරූප ශිල්පය'
        WHEN 'web_design'     THEN 'අභිරුචි වෙබ් අඩවි නිර්මාණය'
        WHEN 'electrical'     THEN 'නිවාස විදුලි අළුත්වැඩියා'
        WHEN 'plumbing'       THEN 'ජල නල සවි කිරීම සහ අළුත්වැඩියා'
        WHEN 'graphic_design' THEN 'ලාංඡන සහ බ්‍රෑන්ඩින් නිර්මාණය'
        WHEN 'writing'        THEN 'SEO අන්තර්ගත රචනය'
        WHEN 'event_services' THEN 'සම්පූර්ණ උත්සව සම්බන්ධීකරණය'
      END
    WHEN 'tamil' THEN
      CASE c.category_key
        WHEN 'tutoring'       THEN 'தனிநபர் பயிற்சி வகுப்பு'
        WHEN 'photography'    THEN 'நிகழ்வு & உருவப்படப் புகைப்படம்'
        WHEN 'web_design'     THEN 'தனிப்பயன் இணையதள வடிவமைப்பு'
        WHEN 'electrical'     THEN 'வீட்டு மின் பழுது'
        WHEN 'plumbing'       THEN 'குழாய் பொருத்துதல் & பழுது'
        WHEN 'graphic_design' THEN 'லோகோ & பிராண்டிங் வடிவமைப்பு'
        WHEN 'writing'        THEN 'SEO உள்ளடக்க எழுத்து'
        WHEN 'event_services' THEN 'முழு நிகழ்வு ஒருங்கிணைப்பு'
      END
    ELSE
      CASE c.category_key
        WHEN 'tutoring'       THEN 'One-on-One Tutoring Session'
        WHEN 'photography'    THEN 'Event & Portrait Photography'
        WHEN 'web_design'     THEN 'Custom Website Design'
        WHEN 'electrical'     THEN 'Home Electrical Repair'
        WHEN 'plumbing'       THEN 'Plumbing Installation & Repair'
        WHEN 'graphic_design' THEN 'Logo & Branding Design'
        WHEN 'writing'        THEN 'SEO Content Writing'
        WHEN 'event_services' THEN 'Full Event Coordination'
      END
  END,
  CASE c.category_key
    WHEN 'tutoring'       THEN 2500
    WHEN 'photography'    THEN 15000
    WHEN 'web_design'     THEN 35000
    WHEN 'electrical'     THEN 4000
    WHEN 'plumbing'       THEN 3500
    WHEN 'graphic_design' THEN 8000
    WHEN 'writing'        THEN 3000
    WHEN 'event_services' THEN 25000
  END,
  CASE u.language_pref
    WHEN 'sinhala' THEN 'ඔබේ නිශ්චිත අවශ්‍යතා සාකච්ඡා කර අභිරුචි මිලකරණයක් ලබාගැනීමට සම්බන්ධ වන්න.'
    WHEN 'tamil'   THEN 'உங்கள் குறிப்பிட்ட தேவைகளைப் பற்றி விவாதிக்க மற்றும் தனிப்பயன் மேற்கோளைப் பெற தொடர்பு கொள்ளுங்கள்.'
    ELSE 'Reach out to discuss your specific requirements and get a tailored quote.'
  END
FROM freelancer_profiles fp
JOIN categories c ON c.category_name = fp.skill_category
JOIN users u ON u.user_id = fp.user_id;
