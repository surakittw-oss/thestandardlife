=== THE STANDARD LIFE (Plugin) ===
Contributors: thestandard
Requires at least: 6.0
Requires PHP: 7.4
Version: 1.0.0
License: GPLv2 or later

แสดงเนื้อหา LIFE ด้วยดีไซน์นิตยสาร โดย "ไม่ยึดทั้งเว็บ" — หน้าอื่นๆ ยังใช้
ธีมปกติของเว็บตามเดิม ปลั๊กอินจะคุมเฉพาะหน้า LIFE เท่านั้น

== ทำไมเป็นปลั๊กอิน (ไม่ใช่ธีม) ==
WordPress ใช้ได้ทีละ 1 ธีมทั้งเว็บ ถ้าทำ LIFE เป็นธีม เนื้อหาอื่น (Post ปกติ)
จะถูกธีม LIFE คุมไปด้วย ทำเป็นปลั๊กอินแทนจึงแยกได้:
  - หน้า LIFE  → ดีไซน์ LIFE (ปลั๊กอินนี้)
  - หน้าอื่น   → ธีมปกติของเว็บ (ไม่ถูกแตะ)

== ติดตั้ง ==
1. บีบอัดโฟลเดอร์ thestandard-life เป็น zip (หรือใช้ thestandard-life-plugin.zip)
2. WP Admin > Plugins > Add New > Upload Plugin > เลือก zip > Install > Activate
   (หรือวางโฟลเดอร์ไว้ใน wp-content/plugins/)
3. Activate แล้ว ปลั๊กอิน flush permalink ให้อัตโนมัติ

== สิ่งที่ปลั๊กอินสร้าง ==
- Custom Post Type: LIFE Articles (slug: life_post)  → เมนู "THE STANDARD LIFE"
- Taxonomy: LIFE Categories (slug: life_category)    → เมนูย่อย Categories
- ใช้ Tag ปกติได้ (แท็ก "editors-pick" = ขึ้น Editor's Pick บนหน้า /life/)

โครงสร้าง URL:
  /life/                      → LIFE landing (hero + editor's pick + popular + หมวด)
  /life/ชื่อบทความ/           → บทความเดี่ยว
  /life-category/ชื่อหมวด/    → หน้าหมวด

== การตั้งค่า ==
1. เมนู "THE STANDARD LIFE" > Categories: สร้าง 6 หมวด
   Food & Drink, Place, Active Leisure, Body & Mind, Living, Tech & Gadget
2. เขียนบทความที่ "THE STANDARD LIFE" > Add New
   - ใส่ Featured Image + Excerpt + เลือก LIFE Category ทุกอัน
   - Cover story = โพสต์ที่ตั้ง Stick to the top (ไม่มีก็ใช้ล่าสุด)
3. หน้าแรกของ LIFE คือ /life/ (จะลิงก์จากเมนูเว็บ/โลโก้ก็ได้)

== ย้าย Post ปกติ มาเป็น LIFE ==
ใช้ปลั๊กอิน "Post Type Switcher" เปลี่ยน type เป็น LIFE Article
แล้ว "อย่าลืมกำหนด LIFE Category ให้ใหม่" (Category เดิมจะไม่ย้ายตามมา
เพราะเป็นคนละ taxonomy)

== หมายเหตุสำคัญ ==
- อย่าสร้าง post type "life_post" หรือ taxonomy "life_category" ซ้ำใน CPT UI
  จะชนกับที่ปลั๊กอินลงทะเบียนไว้
- ถ้าเคยติดตั้ง "ธีม" THE STANDARD LIFE ไว้ ให้สลับไปใช้ธีมปกติของเว็บแล้ว
  ปิด/ลบธีมนั้นได้ — ปลั๊กอินนี้แทนที่หน้าที่ของมันทั้งหมด
- ถ้า /life/ ขึ้น 404 หลังติดตั้ง: ไปที่ Settings > Permalinks แล้วกด Save
