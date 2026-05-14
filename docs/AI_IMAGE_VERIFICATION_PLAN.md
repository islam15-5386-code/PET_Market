# AI Image Verification Plan (Future Upgrade)

## Goal
Detect category/image mismatch automatically, such as:
- Dog product using cat image
- Medicine product using toy image
- Food product using bed/collar image

## Proposed Pipeline
1. On product create/update, run async image verification job.
2. Generate image labels/embeddings from the product image.
3. Compare predicted labels against expected taxonomy:
   - `pet_type`
   - `category_type`
   - `sub_category`
4. Store confidence + mismatch flag in a new table (example: `product_image_audits`).
5. Show admin warnings for low-confidence or mismatched items.

## Candidate Models/Providers
- **CLIP** (OpenCLIP / ViT-B-32):
  - Encode image and text labels.
  - Compute cosine similarity to expected labels.
- **MobileNet / EfficientNet classifier**:
  - Lightweight on-prem option for coarse category detection.
- **OpenAI Vision**:
  - Use structured prompt to classify pet type/category/subcategory.
- **Tagging APIs**:
  - Fallback for quick label extraction.

## Suggested Scoring
- `score_pet_type`
- `score_category`
- `score_sub_category`
- `overall_score = weighted average`

Thresholds:
- `>= 0.80`: valid
- `0.60 - 0.79`: review recommended
- `< 0.60`: likely mismatch

## Data Model (proposed)
`product_image_audits`
- `id`
- `product_id`
- `image_url`
- `predicted_pet_type`
- `predicted_category`
- `predicted_sub_category`
- `expected_pet_type`
- `expected_category`
- `expected_sub_category`
- `overall_score`
- `is_mismatch`
- `raw_payload` (jsonb)
- timestamps

## Rollout Strategy
1. Start with manual review mode (no blocking).
2. Enable warning badges in admin panel.
3. Add periodic batch audit for existing catalog.
4. Optionally auto-switch to fallback image for severe mismatch.

