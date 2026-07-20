# แผนแปลง THE STANDARD LIFE → WordPress Classic Theme

เอกสารนี้คือแผนงานเริ่มต้น (ตอนเขียนยังไม่ได้ลงมือ) สำหรับแปลง `index.html` +
`articles/thaicoon.html` ให้เป็น WordPress theme แบบ Classic PHP

> **อัปเดต:** theme สร้างเสร็จแล้วที่ `wordpress-theme/thestandard-life/`
> (ดู `readme.txt` ในนั้นสำหรับขั้นตอนติดตั้งจริง) และภายหลังได้เปลี่ยนสถาปัตยกรรม
> จาก Post+Category ธรรมดา → **Custom Post Type `life_post` + taxonomy
> `life_category`** เพราะเว็บปลายทางมีเนื้อหาประเภทอื่นปนอยู่ด้วย
> (แยกเนื้อหา LIFE ออกจากเนื้อหาอื่นอย่างชัดเจน ไม่ใช้ Post/Category ที่ใช้ร่วมกับเนื้อหาอื่น)
> เอกสารด้านล่างนี้เก็บไว้เป็นบันทึกแนวคิดตั้งต้น ส่วน readme.txt คือของจริงที่ใช้งาน

---

## 1. โครงสร้างไฟล์ theme

```
thestandard-life/                  ← โฟลเดอร์ theme (ไปวางใน wp-content/themes/)
├── style.css                      # theme header (ชื่อ/เวอร์ชัน) + CSS ทั้งหมดจาก <style> ของ index
├── functions.php                  # โหลด CSS/JS/ฟอนต์, register เมนู, featured image, read-time
├── header.php                     # <!doctype>…<head>, masthead, nav, ปุ่ม dark mode, เปิด <body>
├── footer.php                     # footer + <script> (toggle/sticky/hamburger) + ปิด body/html
├── front-page.php                 # หน้า HOME: hero, Editor's Pick, Popular, category rows
├── home.php                       # หน้า listing บทความ (เมื่อคลิกหมวด/หน้า blog รวม)
├── single.php                     # หน้าบทความเดี่ยว: breadcrumb, TOC, prose, byline, related
├── archive.php                    # หน้า archive หมวด/แท็ก (ใช้การ์ดแบบ list-grid)
├── page.php                       # หน้า static ทั่วไป
├── index.php                      # fallback (WP บังคับต้องมี)
├── searchform.php                 # ฟอร์มค้นหา (ปุ่มแว่นขยายใน nav)
├── template-parts/
│   ├── site-header.php            # masthead + nav (ถ้าอยากแยกจาก header.php)
│   ├── card-small.php             # การ์ดเล็กใน hero ("In this issue" / Listen)
│   ├── card-pick.php              # การ์ด Editor's Pick (3 ใบเท่ากัน)
│   ├── card-lead.php              # การ์ดใหญ่ซ้ายของ category row (.cat-lead)
│   ├── card-stack.php             # การ์ดในคอลัมน์ขวา (.cat-stack .card)
│   ├── row-category.php           # 1 category row เต็ม (หัวข้อ + lead + 2 stack) — reuse ได้
│   ├── list-item.php              # การ์ดใน list-grid (PLACE/TECH)
│   └── content-single.php         # เนื้อหาบทความ
├── inc/
│   └── template-tags.php          # helper: read-time, breadcrumb, TOC จาก H2
├── assets/
│   ├── js/theme.js                # dark mode toggle, sticky shadow, hamburger, TOC scroll-spy
│   ├── img/logo-tsl.png           # โลโก้
│   └── (fonts/ ถ้าจะ self-host แทน Google Fonts)
└── screenshot.png                 # ภาพ preview ของ theme (1200×900)
```

---

## 2. แต่ละไฟล์เอาอะไรจากของเดิมไปใส่

### `style.css`
- บรรทัดบนสุดต้องมี theme header comment:
  ```css
  /*
  Theme Name: THE STANDARD LIFE
  Author: ...
  Version: 1.0
  Text Domain: thestandard-life
  */
  ```
- ตามด้วย CSS **ทั้งหมด** จาก `<style>…</style>` ใน `index.html` (ตัวแปร :root, dark theme, ทุก component, media queries)

### `header.php`
- จาก `<!doctype>` ถึงจบ `<nav class="nav">…</nav>`
- เปลี่ยนส่วน dynamic:
  - `<title>` → `<?php wp_title(); ?>` / ใช้ `add_theme_support('title-tag')`
  - โหลด CSS/ฟอนต์/favicon → ย้ายไป enqueue ใน `functions.php` + `<?php wp_head(); ?>`
  - โลโก้ → `<?php echo get_template_directory_uri(); ?>/assets/img/logo-tsl.png` หรือ custom-logo
  - เมนู nav `<ul>` → `<?php wp_nav_menu(['theme_location'=>'primary']); ?>`
  - favicon "LIFE" (SVG) → ใส่ผ่าน `wp_head` hook หรือ Site Icon

### `footer.php`
- footer ทั้งบล็อก + `<script>` 3 ตัว (ย้าย JS ไป `assets/js/theme.js` แล้ว enqueue)
- ปิดด้วย `<?php wp_footer(); ?>` ก่อน `</body>`
- เมนู footer (Sections/More/About/The Family) → wp_nav_menu หลายตำแหน่ง หรือ widget areas

### `front-page.php` (หน้า HOME)
เรียง section เดิม แต่เปลี่ยนเนื้อหา hardcode เป็น loop:
| Section | มาจาก WordPress |
|---|---|
| Cover story (hero กลาง) | โพสต์ที่ตั้ง **Sticky** หรือแท็ก `cover-story` (query 1 โพสต์) |
| In this issue (hero ซ้าย) | `WP_Query` ล่าสุด 3 โพสต์ |
| Editor's Letter / Listen / Event | Custom Post Type หรือ ACF fields (ระยะแรกใส่ static ไปก่อนได้) |
| Editor's Pick (3 ใบ) | แท็ก `editors-pick` หรือหมวดพิเศษ (query 3) |
| Popular this week | เรียงตามยอดวิว (ปลั๊กอิน เช่น WP-PostViews) หรือ query ล่าสุด 8 |
| Category rows (FOOD/PLACE/BODY&MIND/…) | วนแต่ละ Category → query โพสต์ล่าสุดในหมวดนั้น ด้วย `template-parts/row-category.php` |

### `single.php` (หน้าบทความ)
- breadcrumb → `Home / [หมวด] / [ชื่อโพสต์]` สร้างจาก `get_the_category()`
- title/byline/วันที่ → `the_title()`, `get_the_author()`, `get_the_date()`
- read-time → helper คำนวณจาก `get_the_content()`
- รูปปก → `the_post_thumbnail()`
- เนื้อหา → `the_content()`
- **TOC** → JS อ่าน H2 ในเนื้อหาแล้วสร้างลิสต์อัตโนมัติ (แทน #s1–#s5 ที่ hardcode)
- related → `WP_Query` โพสต์ในหมวดเดียวกัน

### `functions.php` (แกนหลัก)
```php
- add_theme_support: title-tag, post-thumbnails, custom-logo, html5, automatic-feed-links
- register_nav_menus: primary, footer-sections, footer-more, footer-about, footer-family
- wp_enqueue_style: Google Fonts, style.css
- wp_enqueue_script: theme.js (footer)
- add_image_size: 'cover' → 1200×628 crop (ตรงกับ --cover ratio ปัจจุบัน)
- helper read-time (นับคำ ÷ 200)
- inject favicon SVG "LIFE" ผ่าน wp_head
```

---

## 3. สิ่งที่ต้องตั้งใน WordPress (ฝั่ง admin)

1. **Categories** ให้ตรงกับหมวดในดีไซน์:
   Food & Drink, Place, Active Leisure, Body & Mind, Living, Tech & Gadget
2. **Menus**: สร้างเมนู Primary (6 หมวด) + เมนู Footer
3. **Settings → Reading**: ตั้งหน้าแรกเป็น "front page" (จะได้ใช้ `front-page.php`)
4. **Featured image** ทุกโพสต์ (แทนรูป Unsplash)
5. (ตัวเลือก) ปลั๊กอินยอดวิว สำหรับ Popular this week
6. (ตัวเลือก) ACF สำหรับ Editor's Letter / Podcast / Event block

---

## 4. ประเด็นเฉพาะโปรเจกต์นี้

- **ไฟล์ `articles/thaicoon.html` เป็น bundle** (ฟอนต์ base64 + template JSON) → **ไม่ยกทั้งก้อน**
  ขึ้น WP จะใช้ **HTML ที่ decode แล้ว** เป็นต้นแบบ `single.php` + เนื้อหาจริงจาก `the_content()`
  และใช้ Google Fonts แบบ `<link>` เหมือน index (ไม่ต้องฝัง base64)
- **TOC** เปลี่ยนจาก hardcode → generate อัตโนมัติจาก H2 (JS)
- **Dark mode / sticky nav / hamburger** ย้าย JS มาได้ตรงๆ ไม่ต้องแก้ logic
- **display:contents บน .masthead** (ที่ทำให้ nav sticky) ใช้ได้เหมือนเดิมใน WP

---

## 5. ลำดับการทำ (ถ้าลงมือ)

1. สร้างโครง theme + `style.css` + `functions.php` (enqueue) → ให้ theme activate ได้
2. `header.php` + `footer.php` (static ก่อน แล้วค่อยใส่ wp_nav_menu)
3. `front-page.php` — ทำทีละ section, เริ่มจาก category rows (loop) → hero → popular
4. `single.php` + `content-single.php` + TOC
5. `archive.php` / `home.php` / `search`
6. เก็บงาน: read-time, breadcrumb, related, favicon
7. ทดสอบบน WP จริง (local เช่น LocalWP/XAMPP) → ปรับ

---

## 6. ข้อจำกัดในการทดสอบ
สร้างไฟล์ theme + zip ให้ได้ในเซสชันนี้ และตรวจ PHP syntax/logic ได้
แต่ **การรัน loop จริงต้องติดตั้งบน WordPress** (ที่นี่เป็น static preview ไม่มี PHP/WP)
