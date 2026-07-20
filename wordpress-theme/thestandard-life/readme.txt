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

== ตั้งค่าให้เหมือนดีไซน์ (Setup) ==

1) หน้าแรก (Front page)
   Settings > Reading > "Your homepage displays" = A static page
   - Homepage: สร้างหน้าเปล่าชื่อ "Home" แล้วเลือกเป็น homepage
     (ธีมจะใช้ front-page.php อัตโนมัติ — ไม่ต้องใส่เนื้อหาในหน้านั้น)
   - Posts page: สร้างหน้า "Blog" เลือกเป็นหน้าโพสต์

2) หมวดหมู่ (Categories) — สร้างให้ตรงเมนู
   Food & Drink, Place, Active Leisure, Body & Mind, Living, Tech & Gadget

3) เมนู (Appearance > Menus)
   - Primary Menu: ใส่ 6 หมวดข้างบน
   - Footer — Sections / More / About / The Family: สร้างเมนูตามต้องการ
   (ถ้ายังไม่ตั้ง Primary ธีมจะแสดงรายการหมวดอัตโนมัติแทน)

4) โพสต์
   - ทุกโพสต์ควรมี Featured Image (ใช้เป็นรูปการ์ด/รูปปก) และ Excerpt (คำโปรย)
   - Cover story บนหน้าแรก = โพสต์ที่ตั้ง "Stick to the top" (ถ้าไม่มี ใช้โพสต์ล่าสุด)
   - Editor's Pick = โพสต์ที่ติดแท็ก `editors-pick` (ถ้าไม่มี ใช้ 3 โพสต์ล่าสุด)

5) โลโก้ / ไอคอน
   - Appearance > Customize > Site Identity: อัปโหลด Logo และ Site Icon ได้
   - ถ้าไม่อัปโหลด Logo จะใช้ logo-tsl.png ที่มากับธีม
   - ถ้าไม่ตั้ง Site Icon จะใช้ favicon "LIFE" (SVG) อัตโนมัติ

== ทดสอบหน้า Article ==
เปิดไฟล์ SAMPLE-thaicoon-post.html (อยู่นอกโฟลเดอร์ธีม ในแพ็กเกจ)
คัดลอกเนื้อหาไปวางในโพสต์ใหม่ (Custom HTML block) เพื่อดูหน้า single.php
หัวข้อ H2 จะถูกสร้างเป็นสารบัญ (TOC) อัตโนมัติ

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
