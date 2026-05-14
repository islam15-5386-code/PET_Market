from services.intent_classifier import classify_intent


def test_intent_classifier_returns_valid_shape():
    intent, conf = classify_intent("which food is good for my puppy")
    assert isinstance(intent, str)
    assert isinstance(conf, float)
