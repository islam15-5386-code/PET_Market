from services.query_parser import parse_query


def parse_search_query(query: str) -> dict:
    return parse_query(query)
