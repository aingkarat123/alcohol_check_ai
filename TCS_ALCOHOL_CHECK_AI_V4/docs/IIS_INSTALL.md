# ติดตั้งบน IIS 10

เอกสารนี้ใช้สำหรับ Test/UAT ก่อน Production ควรให้ทีม Infrastructure, DBA, Safety และผู้รับผิดชอบข้อมูลส่วนบุคคลตรวจสอบร่วมกัน

## 1. ติดตั้ง PHP

1. ติดตั้ง PHP 8.x x64 แบบ Non-Thread-Safe
2. เปิด IIS CGI/FastCGI และเพิ่ม php-cgi.exe
3. เปิด extension: fileinfo, curl, mbstring, openssl, pdo_sqlsrv และ sqlsrv
4. ติดตั้ง Microsoft ODBC Driver 18 for SQL Server ให้ตรงกับระบบปฏิบัติการ
5. ตั้ง date.timezone เป็น Asia/Bangkok

## 2. สร้าง IIS Application

1. สร้าง Application Pool แบบ No Managed Code
2. ชี้ Physical Path ไปยังโฟลเดอร์โปรเจกต์
3. ให้ Application Pool Identity มี Read/Execute ทั้งโปรเจกต์
4. ให้ Modify เฉพาะ storage/evidence และโฟลเดอร์ PHP session
5. ห้ามให้ Modify กับ config, database และ public assets
6. ตรวจว่า web.config ปิดการเข้าถึง config, database, docs, includes, ocr_service, storage และ tests

## 3. Environment Variables

ตั้งค่าที่ Application Pool หรือ System Environment โดยอ้างอิง .env.example ห้ามเก็บรหัสผ่านใน source code

ค่าหลัก:

    TCS_AC_DEMO=false
    TCS_AC_BASE_URL=https://host-name/tcs-alcohol-check
    TCS_AC_OCR_URL=http://127.0.0.1:8010
    TCS_AC_DB_SERVER=server-name
    TCS_AC_DB_NAME=TCS_ALCOHOL_CHECK
    TCS_AC_DB_AUTH=windows
    TCS_AC_DB_ENCRYPT=true
    TCS_AC_DB_TRUST_CERT=false

หลังแก้ Environment Variables ให้ Recycle Application Pool

## 4. HTTPS

1. ขอใบรับรองจาก CA ที่อุปกรณ์บริษัทเชื่อถือ
2. เพิ่ม HTTPS Binding ใน IIS และเลือกใบรับรอง
3. Redirect HTTP ไป HTTPS
4. ทดสอบจากมือถือจริงว่าไม่มี Certificate Warning
5. Camera API ทำงานบน HTTPS หรือ localhost เท่านั้น
6. Production ไม่ควรเปิด TrustServerCertificate หาก SQL Server มีใบรับรองที่ถูกต้อง

## 5. Python OCR Service

ติดตั้งเป็น Windows Service ด้วยบัญชีสิทธิ์ต่ำและ Bind เฉพาะ 127.0.0.1:8010 ไม่เปิด Port นี้จาก Internet

    cd D:\TCS_ALCOHOL_CHECK_AI_V4\ocr_service
    py -3.11 -m venv .venv
    .\.venv\Scripts\Activate.ps1
    python -m pip install --upgrade pip
    pip install -r requirements.txt
    python app.py

หากใช้ Tesseract ให้ติดตั้ง executable ภายในเครื่องและกำหนด PATH ของ Service Account

ตรวจสุขภาพ:

    Invoke-RestMethod http://127.0.0.1:8010/health

## 6. SQL Server

1. สร้างฐาน Test/UAT แยกจาก Production
2. ให้ DBA ตรวจ database/migrations/001_create_ac_schema.sql
3. รัน Migration ด้วยบัญชี DBA เฉพาะช่วงติดตั้ง
4. สร้างบัญชี Runtime สิทธิ์ต่ำสำหรับ SELECT/INSERT/UPDATE ตาราง AC_*
5. Employee Master ใช้บัญชีหรือ View แบบ Read-only เท่านั้น
6. ห้ามให้ Runtime Account มี ALTER, DROP หรือสิทธิ์แก้ Employee Master
7. ทดสอบด้วยคำสั่ง php database/check-connection.php

## 7. ทดสอบมือถือ

1. เชื่อมมือถือกับเครือข่ายที่เข้าถึง HTTPS Host ได้
2. เปิดด้วย iPhone Safari และ Android Chrome
3. อนุญาตกล้องเมื่อ Browser ถาม
4. ทดสอบ Portrait ที่ 320, 360, 375, 390, 414 และ 430 CSS pixels
5. ทดสอบกล้องหน้า กล้องหลัง ไฟ Flash (ถ้ามี) และการหมุนจอ
6. ตรวจว่า Bottom Navigation และปุ่มบันทึกไม่ถูก Safe Area บัง

## 8. ก่อน Production

- เชื่อม Authentication กับระบบบัญชีของบริษัทและทดสอบ RBAC
- ยืนยันชื่อ View/Column ของ Employee Master กับ DBA
- อนุมัติ Threshold และ Conversion ของแต่ละ Device Profile
- กำหนด Retention Policy ภาพและผลตรวจ
- เปิด Audit/Monitoring/Backup และ Incident Response
- ทำ Security Test และ User Acceptance Test
- ปรับ Device Profile ด้วยภาพจริงของเครื่องรุ่นที่จะใช้งาน
