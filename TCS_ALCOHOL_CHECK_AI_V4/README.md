# TCS ALCOHOL CHECK AI V4

ระบบตรวจวัดแอลกอฮอล์พนักงานสำหรับ Tantawan Construction and Service Co., Ltd. พัฒนาด้วย PHP 8, HTML, CSS, JavaScript, SQL Server และ Python OCR Service โดยออกแบบ Mobile First สำหรับเจ้าหน้าที่ Safety

รุ่นนี้เปิดใน DEMO MODE โดยค่าเริ่มต้น เพื่อให้ทดลอง Workflow ได้โดยไม่อ่านหรือแก้ไขฐานข้อมูล Production

## Workflow ที่พร้อมทดลอง

กรอกรหัสพนักงาน → แสดงชื่อ รหัส และตำแหน่ง → ถ่ายภาพพนักงาน → สแกนหน้าจอเครื่องตรวจ → OCR อ่านค่า หรือใช้ผลจำลองที่ติดป้ายชัดเจน → เจ้าหน้าที่ยืนยัน → บันทึก → พร้อมตรวจคนถัดไป

ฟังก์ชันหลัก:

- Employee lookup แบบ debounce, Enter, รองรับเลขศูนย์นำหน้า และป้องกันผล request เก่า
- เปิดกล้องหลังครั้งเดียว ถ่ายภาพพนักงานแล้วเล็งเครื่องตรวจต่อในแผงเดียว ระบบเก็บภาพหลักฐานสองภาพ
- Crop ROI ก่อนส่ง OCR, รองรับ Flash เมื่ออุปกรณ์อนุญาต
- Python OCR แยก Service พร้อม OpenCV, Seven-Segment และ Tesseract fallback
- ไม่เปลี่ยนค่าที่อ่านไม่ได้เป็น 0.00
- แก้ไขค่า OCR ได้โดยเก็บค่าเดิม เหตุผล ผู้แก้ไข และเวลา
- คำนวณ PASS/FAIL จาก Settings; Unit ไม่ตรงหรือไม่มี Threshold เป็น REVIEW
- ป้องกันบันทึกซ้ำและตรวจข้อมูลครบก่อนบันทึก
- ประวัติแบบ Card บนมือถือและ Table บน Desktop
- Dashboard KPI อ้างอิง Demo Test Roster
- Export Excel และหน้าพิมพ์/บันทึก PDF
- CSRF, Secure Session, RBAC, File Content Validation และ Security Headers

## เปิดทดสอบแบบเร็ว

ต้องมี PHP 8 พร้อม fileinfo:

    cd D:\TCS_ALCOHOL_CHECK_AI_V4
    php -S 127.0.0.1:8788

เปิด http://127.0.0.1:8788 และใช้รหัส 1001–1005 หรือ 001001

หมายเหตุ: Camera API ใช้ได้บน localhost หรือ HTTPS การเปิดผ่าน IP แบบ HTTP บนมือถืออาจถูก Browser ปฏิเสธ

บน Windows สามารถดับเบิลคลิก START_TCS_ALCOHOL_CHECK.cmd เพื่อเปิด Server และ Browser โดยอัตโนมัติ

## ผลจริงกับผลจำลอง

- ปุ่ม “ถ่ายและอ่านค่าจริง” ส่งภาพไป Python OCR และแสดง Source เป็น Python OCR
- ปุ่ม “ใช้ผลจำลอง (Demo)” สร้างภาพ LCD ตัวอย่างและคืนค่าจำลองที่ติดป้าย DEMO
- ระบบไม่แสดงผลจำลองว่าเป็น OCR จริง
- ข้อมูล Demo อยู่ใน PHP Session และไม่ปะปนกับฐาน Production

## โครงสร้างโปรเจกต์

    api/                         API employee, OCR, save และ settings
    config/                      Environment-driven configuration
    database/migrations/         SQL Server schema เฉพาะ AC_*
    database/seed/               ข้อมูลอ้างอิงสำหรับ Test เท่านั้น
    docs/                        คู่มือติดตั้ง IIS/SQL/HTTPS/Mobile
    includes/                    Bootstrap, security, layout และ Demo store
    ocr_service/                 FastAPI + OpenCV + OCR modules
    public/assets/               CSS, JavaScript, icons และโลโก้
    storage/evidence/            ที่เก็บภาพนอก public root สำหรับ Production
    tests/                       Smoke tests
    index.php                    หน้าตรวจหลัก
    history.php                  ประวัติการตรวจ
    test-detail.php              หลักฐานและ Audit ของรายการ
    dashboard.php                KPI สำหรับผู้มีสิทธิ์
    settings.php                 จุดตรวจ เครื่อง และ Threshold
    export.php                   Excel และ Print/PDF

## SQL Script

database/migrations/001_create_ac_schema.sql สร้างเฉพาะ:

- AC_TestRecords
- AC_TestImages
- AC_Devices
- AC_Checkpoints
- AC_Settings
- AC_Users
- AC_AuditLogs
- AC_OCRLogs
- AC_TestRoster

Migration ใช้ Transaction, Foreign Key, Check Constraint, Index และ DECIMAL สำหรับค่าผลตรวจ ไม่แก้ไข Employee Master

## ติดตั้ง OCR Service

ดู ocr_service/README.md และ docs/IIS_INSTALL.md ต้อง Bind Service ที่ localhost และไม่ส่งภาพไป External AI Service

## ติดตั้ง IIS, SQL Server และ HTTPS

ดู docs/IIS_INSTALL.md ก่อนเปิด Production ต้องตั้ง Environment Variables, ใช้ SQL Account สิทธิ์ต่ำ, เชื่อม Employee Master แบบ Read-only, ตั้ง HTTPS Certificate ที่เชื่อถือได้ และให้ App Pool เขียนได้เฉพาะพื้นที่จัดเก็บที่จำเป็น

## ตรวจสอบโค้ด

    php tests/smoke.php
    Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
    node --check public/assets/js/common.js
    node --check public/assets/js/check.js
    node --check public/assets/js/settings.js

OCR preprocessing smoke test ต้องติดตั้ง Python dependencies ก่อน:

    cd ocr_service
    python test_pipeline.py

## ข้อจำกัดและสิ่งที่ต้องปรับกับเครื่องจริง

- ยังไม่มีภาพหรือเครื่องตรวจแอลกอฮอล์รุ่นจริง จึงยังไม่อ้างว่า OCR ผ่านการทดสอบกับอุปกรณ์จริง
- ต้องสร้าง Device Profile แยกตามรุ่น หน่วย ตำแหน่งจอ Error Message และสภาพแสง
- ต้องเก็บชุดภาพอ้างอิงที่มีค่าจริงเพื่อวัด Accuracy, False Read และ Decimal Error
- Auto Capture มีค่าควบคุม Stable Frames/Confidence เตรียมไว้ แต่ควรเปิดใช้หลังปรับ Profile และทดสอบประสิทธิภาพกับมือถือจริง
- Production ต้องเชื่อม Authentication, Employee Master และ Repository SQL ตาม Schema ที่ DBA อนุมัติ
- Threshold ใน Demo เป็นเพียงค่าทดสอบ ไม่ใช่นโยบายบริษัท
