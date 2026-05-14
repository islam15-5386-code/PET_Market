import os

import pandas as pd


def load_from_db() -> pd.DataFrame:
    database_url = os.getenv('DATABASE_URL', '').strip()
    if not database_url:
        return pd.DataFrame(columns=['question', 'intent', 'pet_type', 'category', 'age_group', 'answer'])

    try:
        import sqlalchemy as sa
    except Exception:
        return pd.DataFrame(columns=['question', 'intent', 'pet_type', 'category', 'age_group', 'answer'])

    engine = sa.create_engine(database_url)
    query = """
        SELECT question, intent, pet_type, category, age_group, answer
        FROM chatbot_training_data
        WHERE is_approved = true
    """
    return pd.read_sql(query, engine)
