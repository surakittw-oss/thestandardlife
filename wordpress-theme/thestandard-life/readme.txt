=== THE STANDARD LIFE ===
Contributors: thestandard
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 7.4
Version: 1.0.0
License: GPLv2 or later

Editorial lifestyle magazine theme — the urban guide to well-being.
Converted from the hand-coded THE STANDARD LIFE prototype (home + article).

== ติดตั้ง (Install) ==

1. WP Admin > Appearance > Themes > Add New > Upload Theme
2. เลือกไฟล์ thestandard-life.zip แล้ว Install > Activate

หรือแตกไฟล์ zip แล้ววางโฟลเดอร์ `thestandard-life/` ไว้ใน `wp-content/themes/`

== เนื้อหา LIFE เป็น Custom Post Type ==

ธีมนี้ลงทะเบียน Custom Post Type ชื่อ "LIFE Articles" (slug: life_post)
และ taxonomy ของตัวเอง "LIFE Categories" (slug: life_category) ไว้ในโค้ด
(functions.php) — แยกออกจาก Post/Category ปกติของเว็บ เผื่อเว็บนี้มี
เนื้อหาประเภทอื่นปนอยู่ (เนื้อหา LIFE จะไม่ไปปนกับเนื้อหาอื่น)

⚠️ สำคัญ: อย่าสร้าง post type ชื่อ life_post หรือ taxonomy ชื่อ
life_category ซ้ำใน CPT UI — จะชนกับที่ธีมลงทะเบียนไว้แล้ว ถ้าจะใช้
CPT UI ให้ใช้กับ content type อื่นที่ไม่ใช่ LIFE แทน

โครงสร้าง URL ที่ได้:
   /life/                      → คลังบทความ LIFE ทั้งหมด (post type archive)
   /life/ชื่อบทความ/           → หน้าบทความเดี่ยว (single-life_post.php)
   /life-category/food-drink/  → หน้าหมวด (ใช้ archive.php ทั่วไป)

== ตั้งค่าให้เหมือนดีไซน์ (Setup) ==

1) หน้าแรก (Front page)
   Settings > Reading > "Your homepage displays" = A static page
   - Homepage: สร้างหน้าเปล่าชื่อ "Home" แล้วเลือกเป็น homepage
     (ธีมจะใช้ front-page.php อัตโนมัติ — ไม่ต้องใส่เนื้อหาในหน้านั้น)
   - Posts page: ปล่อยว่างได้ — index.php ใช้เป็น fallback ทั่วไปเท่านั้น
     เพราะเนื้อหา LIFE มีหน้าคลังของตัวเองที่ /life/ อยู่แล้ว

2) หมวดหมู่ LIFE (เมนูซ้ายมือ Admin: "THE STANDARD LIFE" > Categories)
   สร้าง LIFE Category ให้ตรงเมนู 6 หมวด:
   Food & Drink, Place, Active Leisure, Body & Mind, Living, Tech & Gadget
   (คนละอันกับ Category ปกติของ WordPress — อย่าใช้ Category เดิม)

3) เมนู (Appearance > Menus)
   - Primary Menu: ใส่ลิงก์ไปหน้า LIFE Category ทั้ง 6 หมวดข้างบน
   - Footer — Sections / More / About / The Family: สร้างเมนูตามต้องการ
   (ถ้ายังไม่ตั้ง Primary ธีมจะแสดงรายการหมวดปกติ — ควรตั้งเมนูเองเพื่อให้ลิงก์ไปหมวด LIFE ที่ถูกต้อง)

4) โพสต์ — สร้างที่เมนู "THE STANDARD LIFE" ใน Admin (ไม่ใช่ Posts ปกติ)
   - ทุกโพสต์ควรมี Featured Image (ใช้เป็นรูปการ์ด/รูปปก) และ Excerpt (คำโปรย)
   - ใส่ LIFE Category ให้ทุกโพสต์ (ไม่ใช่ Category ปกติ)
   - Cover story บนหน้าแรก = โพสต์ที่ตั้ง "Stick to the top" (ถ้าไม่มี ใช้โพสต์ล่าสุด)
   - Editor's Pick = โพสต์ที่ติดแท็ก `editors-pick` (Tag ปกติ ใช้ร่วมกับ CPT ได้)

5) โลโก้ / ไอคอน
   - Appearance > Customize > Site Identity: อัปโหลด Logo และ Site Icon ได้
   - ถ้าไม่อัปโหลด Logo จะใช้ logo-tsl.png ที่มากับธีม
   - ถ้าไม่ตั้ง Site Icon จะใช้ favicon "LIFE" (SVG) อัตโนมัติ

6) Permalink
   ธีม flush rewrite rules ให้อัตโนมัติรอบแรกที่ activate แต่ถ้า URL /life/
   ยังขึ้น 404 ให้ไปที่ Settings > Permalinks แล้วกด Save (ไม่ต้องแก้อะไร)
   เพื่อบังคับ flush อีกรอบ

== ทดสอบหน้า Article ==
เปิดไฟล์ SAMPLE-thaicoon-post.html (อยู่นอกโฟลเดอร์ธีม ในแพ็กเกจ)
สร้างโพสต์ใหม่จากเมนู "THE STANDARD LIFE" (ไม่ใช่ Posts ปกติ) แล้ว
คัดลอกเนื้อหาไปวาง (Custom HTML block) เพื่อดูหน้า single-life_post.php
หัวข้อ H2 จะถูกสร้างเป็นสารบัญ (TOC) อัตโนมัติ อย่าลืมใส่ LIFE Category
และ Featured Image ให้โพสต์ทดสอบด้วย

== ฟีเจอร์ที่พอร์ตมาจากต้นฉบับ ==
- Dark / Light mode toggle (จำค่าใน localStorage)
- Sticky navigation bar (ค้างบนเวลาเลื่อน)
- Mobile hamburger drawer + โลโก้กึ่งกลาง
- Reading progress bar + Table of Contents อัตโนมัติ (หน้า article)
- Responsive ครบ desktop / tablet / mobile

== หมายเหตุ ==
- screenshot.png (thumbnail ใน admin) ยังไม่ได้ใส่มา เพิ่มได้เองขนาด 1200x900
- บล็อก Editor's Letter / Podcast / Upcoming Event ในดีไซน์เดิม
  แนะนำต่อยอดด้วย ACF หรือ Custom Post Type
