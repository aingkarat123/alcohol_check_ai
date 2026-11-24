from __future__ import annotations

import json
from pathlib import Path

import cv2
import numpy as np

from app import normalize_candidate, preprocess


def main() -> None:
    profile_path = Path(__file__).parent / "device_profiles" / "generic_lcd.json"
    profile = json.loads(profile_path.read_text(encoding="utf-8"))
    image = np.zeros((460, 1200, 3), dtype=np.uint8)
    cv2.rectangle(image, (35, 35), (1165, 425), (70, 80, 90), 16)
    cv2.putText(image, "0.08", (300, 315), cv2.FONT_HERSHEY_SIMPLEX, 5.5, (145, 225, 95), 18, cv2.LINE_AA)
    gray, binary = preprocess(image, profile)
    assert gray.size > 0 and binary.size > 0
    assert normalize_candidate(" O.O8 mg/L ") == "0.08"
    assert normalize_candidate("ERROR") is None
    print("OCR preprocessing smoke test: PASS")


if __name__ == "__main__":
    main()
