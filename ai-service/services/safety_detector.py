EMERGENCY_KEYWORDS = [
    'bleeding', 'breathing', 'unconscious', 'seizure', 'poison', 'severe injury',
    'continuous diarrhea', 'high fever', 'vomiting', 'not eating',
    'রক্ত', 'শ্বাস', 'খাচ্ছে না', 'খাবার খাচ্ছে না', 'বমি', 'জ্বর', 'khacche na', 'khabar khacche na',
]

HEALTH_KEYWORDS = [
    'medicine', 'fever', 'vomit', 'diarrhea', 'not eating', 'injury', 'pain', 'ill',
    'medical', 'dose', 'med', 'doctor', 'vet', 'সর্দি', 'জ্বর', 'ঔষধ', 'ডোজ', 'khacche na', 'khabar khacche na',
]


def detect_safety(message: str) -> tuple[str, str | None]:
    text = message.lower().strip()

    if any(k in text for k in EMERGENCY_KEYWORDS):
        return 'emergency', 'This may be serious. Please contact a veterinarian immediately.'

    if any(k in text for k in HEALTH_KEYWORDS):
        return 'warning', 'Consult a veterinarian for medical concerns.'

    return 'safe', None
