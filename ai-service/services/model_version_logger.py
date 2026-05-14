import os
from datetime import datetime


def log_model_version(result: dict, status: str = 'trained') -> bool:
    database_url = os.getenv('DATABASE_URL', '').strip()
    if not database_url:
        return False

    try:
        import sqlalchemy as sa
    except Exception:
        return False

    engine = sa.create_engine(database_url)
    now = datetime.utcnow()

    payload = {
        'model_name': 'pet_chatbot_intent_model',
        'model_path': result.get('model_path', ''),
        'vectorizer_path': result.get('vectorizer_path'),
        'training_rows_count': int(result.get('rows', 0)),
        'accuracy': float(result.get('accuracy', 0)) if result.get('accuracy') is not None else None,
        'status': status,
        'trained_at': now,
        'created_at': now,
        'updated_at': now,
    }

    try:
        with engine.begin() as conn:
            conn.execute(
                sa.text(
                    """
                    INSERT INTO chatbot_model_versions
                    (model_name, model_path, vectorizer_path, training_rows_count, accuracy, status, trained_at, created_at, updated_at)
                    VALUES
                    (:model_name, :model_path, :vectorizer_path, :training_rows_count, :accuracy, :status, :trained_at, :created_at, :updated_at)
                    """
                ),
                payload,
            )
        return True
    except Exception:
        return False
