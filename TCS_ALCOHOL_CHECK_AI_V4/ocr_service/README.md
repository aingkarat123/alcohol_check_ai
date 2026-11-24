# TCS Alcohol OCR Service

บริการ OCR ภายในสำหรับภาพหน้าจอเครื่องตรวจแอลกอฮอล์ รองรับ preprocessing ด้วย OpenCV, Seven-Segment module และ Tesseract fallback

## เริ่มใช้งาน

    python -m venv .venv
    .\.venv\Scripts\Activate.ps1
    pip install -r requirements.txt
    python app.py

Service จะฟังที่ 127.0.0.1:8010 โดยค่าเริ่มต้น ตรวจด้วย:

    Invoke-RestMethod http://127.0.0.1:8010/health

PHP เป็นผู้เรียก Service ผ่าน POST /api/ocr/alcohol และส่ง Internal Service Header Service นี้ไม่ควรเปิดให้ Internet เข้าถึงโดยตรง

## การปรับ Device Profile

ไฟล์ device_profiles/generic_lcd.json เป็นค่าเริ่มต้นเท่านั้น ต้องเก็บภาพจากเครื่องรุ่นจริงภายใต้กระบวนการที่ได้รับอนุญาต แล้วปรับ ROI, Threshold, Error Words, Unit และ Value Range

ห้ามสรุปว่า OCR ผ่านการทดสอบกับเครื่องจริงจนกว่าจะมีภาพและผลอ้างอิงจากอุปกรณ์รุ่นนั้น
