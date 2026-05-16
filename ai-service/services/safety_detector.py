EMERGENCY_KEYWORDS = [
    "not eating", "vomiting", "bleeding", "breathing", "poison", "seizure", "unconscious",
    "severe injury", "continuous diarrhea", "high fever", "খাচ্ছে না", "বমি", "রক্ত", "শ্বাস",
    "khacche na", "khabar khacche na", "cannot breathe", "breathing problem",
]

HEALTH_KEYWORDS = [
    "fever", "pain", "diarrhea", "injury", "medicine", "dose", "doctor", "vet", "ill",
    "জ্বর", "ঔষধ", "ডোজ",
]


def detect_safety(message: str) -> tuple[str, str | None]:
    text = message.lower().strip()
    if any(k in text for k in EMERGENCY_KEYWORDS):
        return "emergency", "This may be serious. Please contact a veterinarian immediately."
    if any(k in text for k in HEALTH_KEYWORDS):
        return "warning", "Consult a veterinarian for medical concerns."
    return "safe", None
