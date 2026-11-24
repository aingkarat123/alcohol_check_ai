from __future__ import annotations

import json
import os
import re
import time
from pathlib import Path
from typing import Any

import cv2
import numpy as np
import pytesseract
from fastapi import FastAPI, File, Form, Header, HTTPException, UploadFile

from modules.seven_segment import recognize as recognize_seven_segment


BASE_DIR = Path(__file__).resolve().parent
PROFILE_DIR = BASE_DIR / "device_profiles"
MAX_IMAGE_BYTES = 4 * 1024 * 1024
app = FastAPI(title="TCS Alcohol OCR Service", docs_url=None, redoc_url=None)


def load_profile(name: str) -> dict[str, Any]:
    safe_name = re.sub(r"[^a-zA-Z0-9_-]", "", name)
    path = PROFILE_DIR / f"{safe_name}.json"
    if not path.is_file():
        raise HTTPException(status_code=422, detail="Unknown device profile")
    return json.loads(path.read_text(encoding="utf-8"))


def crop_roi(image: np.ndarray, profile: dict[str, Any]) -> np.ndarray:
    height, width = image.shape[:2]
    roi = profile["roi"]
    x1 = max(0, int(width * float(roi["x"])))
    y1 = max(0, int(height * float(roi["y"])))
    x2 = min(width, x1 + int(width * float(roi["width"])))
    y2 = min(height, y1 + int(height * float(roi["height"])))
    return image[y1:y2, x1:x2]


def preprocess(image: np.ndarray, profile: dict[str, Any]) -> tuple[np.ndarray, np.ndarray]:
    roi = crop_roi(image, profile)
    gray = cv2.cvtColor(roi, cv2.COLOR_BGR2GRAY)
    gray = cv2.bilateralFilter(gray, 7, 55, 55)
    gray = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8, 8)).apply(gray)
    block_size = int(profile["threshold"]["block_size"])
    if block_size % 2 == 0:
        block_size += 1
    binary = cv2.adaptiveThreshold(
        gray,
        255,
        cv2.ADAPTIVE_THRESH_GAUSSIAN_C,
        cv2.THRESH_BINARY,
        block_size,
        int(profile["threshold"]["c"]),
    )
    if cv2.countNonZero(binary) > binary.size * 0.62:
        binary = cv2.bitwise_not(binary)
    kernel = cv2.getStructuringElement(cv2.MORPH_RECT, (2, 2))
    binary = cv2.morphologyEx(binary, cv2.MORPH_CLOSE, kernel)
    return gray, binary


def normalize_candidate(text: str) -> str | None:
    cleaned = text.upper().strip()
    replacements = {"O": "0", "I": "1", "L": "1", ",": ".", " ": ""}
    for source, target in replacements.items():
        cleaned = cleaned.replace(source, target)
    match = re.search(r"(?<!\d)(\d{1,2}\.\d{2,3})(?!\d)", cleaned)
    return match.group(1) if match else None


def tesseract_read(gray: np.ndarray) -> tuple[str | None, float, str]:
    config = "--psm 7 -c tessedit_char_whitelist=0123456789."
    data = pytesseract.image_to_data(gray, config=config, output_type=pytesseract.Output.DICT)
    candidates = []
    confidences = []
    raw_parts = []
    for text, confidence in zip(data["text"], data["conf"]):
        raw_parts.append(text)
        candidate = normalize_candidate(text)
        if candidate:
            candidates.append(candidate)
            try:
                confidences.append(max(0.0, min(1.0, float(confidence) / 100.0)))
            except ValueError:
                confidences.append(0.0)
    if not candidates:
        combined = normalize_candidate("".join(raw_parts))
        return combined, 0.45 if combined else 0.0, " ".join(raw_parts)
    best_index = int(np.argmax(confidences))
    return candidates[best_index], confidences[best_index], " ".join(raw_parts)


@app.get("/health")
def health() -> dict[str, Any]:
    return {"status": "ok", "service": "tcs-alcohol-ocr"}


@app.post("/api/ocr/alcohol")
async def read_alcohol(
    image: UploadFile = File(...),
    device_profile: str = Form("generic_lcd"),
    test_session_id: str = Form(...),
    x_internal_service: str | None = Header(default=None),
) -> dict[str, Any]:
    if x_internal_service != "tcs-alcohol-check":
        raise HTTPException(status_code=403, detail="Internal service header required")
    started = time.perf_counter()
    body = await image.read(MAX_IMAGE_BYTES + 1)
    if not body or len(body) > MAX_IMAGE_BYTES:
        raise HTTPException(status_code=413, detail="Image is empty or too large")
    profile = load_profile(device_profile)
    decoded = cv2.imdecode(np.frombuffer(body, np.uint8), cv2.IMREAD_COLOR)
    if decoded is None:
        raise HTTPException(status_code=422, detail="Invalid image content")

    gray, binary = preprocess(decoded, profile)
    seven = recognize_seven_segment(binary)
    candidate = normalize_candidate(seven.value or "")
    confidence = seven.confidence
    engine = "seven_segment"
    raw_text = seven.debug

    if candidate is None:
        try:
            candidate, confidence, raw_text = tesseract_read(gray)
            engine = "tesseract"
        except pytesseract.TesseractNotFoundError:
            candidate = None
            raw_text = "Tesseract executable not installed"

    upper_raw = raw_text.upper()
    has_error_word = any(word in upper_raw for word in profile.get("error_words", []))
    valid_pattern = candidate is not None and re.fullmatch(profile["value_pattern"], candidate) is not None
    in_range = False
    if valid_pattern:
        numeric = float(candidate)
        in_range = float(profile["min_value"]) <= numeric <= float(profile["max_value"])

    success = bool(valid_pattern and in_range and not has_error_word)
    processing_ms = int((time.perf_counter() - started) * 1000)
    if not success:
        return {
            "success": False,
            "detected_value": None,
            "detected_unit": profile["unit"],
            "confidence": round(float(confidence), 4),
            "processing_time_ms": processing_ms,
            "needs_review": True,
            "error_message": "อ่านตัวเลขไม่ชัดเจน กรุณาสแกนใหม่",
            "engine": engine,
        }
    return {
        "success": True,
        "detected_value": candidate,
        "detected_unit": profile["unit"],
        "confidence": round(float(confidence), 4),
        "processing_time_ms": processing_ms,
        "needs_review": float(confidence) < 0.80,
        "error_message": None,
        "engine": engine,
    }


if __name__ == "__main__":
    import uvicorn

    uvicorn.run(app, host=os.getenv("OCR_HOST", "127.0.0.1"), port=int(os.getenv("OCR_PORT", "8010")))
