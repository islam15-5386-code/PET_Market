import os


class TokenBudgetManager:
    def __init__(self) -> None:
        self.max_output_tokens = int(os.getenv("MAX_OUTPUT_TOKENS", "450"))

    def allowed_output_tokens(self, feature: str) -> int:
        if feature == "product_description":
            return min(self.max_output_tokens, 700)
        if feature == "pet_chatbot":
            return min(self.max_output_tokens, 350)
        return min(self.max_output_tokens, 250)
