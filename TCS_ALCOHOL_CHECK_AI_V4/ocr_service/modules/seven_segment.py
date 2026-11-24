from __future__ import annotations

from dataclasses import dataclass
from typing import Optional

import cv2
import numpy as np


SEGMENT_DIGITS = {
    (1, 1, 1, 0, 1, 1, 1): "0",
    (0, 0, 1, 0, 0, 1, 0): "1",
    (1, 0, 1, 1, 1, 0, 1): "2",
    (1, 0, 1, 1, 0, 1, 1): "3",
    (0, 1, 1, 1, 0, 1, 0): "4",
    (1, 1, 0, 1, 0, 1, 1): "5",
    (1, 1, 0, 1, 1, 1, 1): "6",
    (1, 0, 1, 0, 0, 1, 0): "7",
    (1, 1, 1, 1, 1, 1, 1): "8",
    (1, 1, 1, 1, 0, 1, 1): "9",
}


@dataclass
class SevenSegmentResult:
    value: Optional[str]
    confidence: float
    debug: str


def _segment_state(digit: np.ndarray) -> tuple[tuple[int, ...], float]:
    height, width = digit.shape
    thickness = max(2, int(min(width, height) * 0.16))
    half = height // 2
    regions = [
        (thickness, 0, width - thickness, thickness),
        (0, thickness, thickness, half - thickness // 2),
        (width - thickness, thickness, width, half - thickness // 2),
        (thickness, half - thickness // 2, width - thickness, half + thickness // 2),
        (0, half + thickness // 2, thickness, height - thickness),
        (width - thickness, half + thickness // 2, width, height - thickness),
        (thickness, height - thickness, width - thickness, height),
    ]
    ratios: list[float] = []
    states: list[int] = []
    for x1, y1, x2, y2 in regions:
        roi = digit[max(y1, 0):max(y2, 1), max(x1, 0):max(x2, 1)]
        ratio = cv2.countNonZero(roi) / float(max(1, roi.size))
        ratios.append(ratio)
        states.append(1 if ratio > 0.35 else 0)
    confidence = float(np.mean([r if s else 1.0 - r for r, s in zip(ratios, states)]))
    return tuple(states), confidence


def recognize(binary: np.ndarray) -> SevenSegmentResult:
    if binary.ndim != 2:
        return SevenSegmentResult(None, 0.0, "binary image required")
    contours, _ = cv2.findContours(binary.copy(), cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
    height, width = binary.shape
    boxes = []
    dots = []
    for contour in contours:
        x, y, w, h = cv2.boundingRect(contour)
        if h > height * 0.28 and w > width * 0.015:
            boxes.append((x, y, w, h))
        elif h < height * 0.25 and w < width * 0.12 and h > 2 and w > 2:
            dots.append((x, y, w, h))
    boxes.sort(key=lambda item: item[0])
    if not boxes:
        return SevenSegmentResult(None, 0.0, "no digit contours")

    digits: list[str] = []
    confidences: list[float] = []
    for x, y, w, h in boxes:
        pad = max(1, int(min(w, h) * 0.03))
        digit = binary[max(0, y - pad):min(height, y + h + pad), max(0, x - pad):min(width, x + w + pad)]
        digit = cv2.resize(digit, (80, 140), interpolation=cv2.INTER_NEAREST)
        state, confidence = _segment_state(digit)
        value = SEGMENT_DIGITS.get(state)
        if value is None:
            return SevenSegmentResult(None, 0.0, "unknown segment pattern")
        digits.append(value)
        confidences.append(confidence)

    if dots:
        dot_x = sorted(dots, key=lambda item: item[0])[0][0]
        insert_at = sum(1 for box in boxes if box[0] < dot_x)
        if 0 < insert_at < len(digits):
            digits.insert(insert_at, ".")
    value = "".join(digits)
    return SevenSegmentResult(value, float(np.mean(confidences)), "ok")
