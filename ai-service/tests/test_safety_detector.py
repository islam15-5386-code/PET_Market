from services.safety_detector import detect_safety


def test_safety_detector_emergency():
    level, msg = detect_safety("my cat is bleeding and not eating")
    assert level in ["emergency", "warning"]
    assert msg is not None
