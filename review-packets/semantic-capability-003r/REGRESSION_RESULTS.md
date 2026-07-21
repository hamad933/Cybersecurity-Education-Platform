# Regression Results

- Task 001 unit regression: PASS (8 tests expected by prior packet)
- Task 002 unit regression: PASS (18 tests expected by prior packet)
- Task 003 deterministic validator: PASS in a write-blocked wrapper
- Task 003R unit validation: PASS (8 tests)
- Task 001 recorded-output hashes: PASS
- Task 002 recorded-output hashes: PASS
- Prior Task 003 handoff hashes: PASS

The Task 003 wrapper intercepts all writes to the preserved Task 003 packet, so the existing validator logic runs without modifying prior audit outputs.

```text
Task 001 unit regression: PASS
test_binary_detection (test_inventory.InventoryTests.test_binary_detection) ... ok
test_empty_duplicate_and_large_threshold (test_inventory.InventoryTests.test_empty_duplicate_and_large_threshold) ... ok
test_nested_directories_and_text_line_count (test_inventory.InventoryTests.test_nested_directories_and_text_line_count) ... ok
test_no_writes_under_source_root (test_inventory.InventoryTests.test_no_writes_under_source_root) ... ok
test_overlapping_output_root_is_rejected (test_inventory.InventoryTests.test_overlapping_output_root_is_rejected) ... ok
test_portable_read_error_handling (test_inventory.InventoryTests.test_portable_read_error_handling) ... ok
test_unicode_arabic_filename_and_deterministic_ids (test_inventory.InventoryTests.test_unicode_arabic_filename_and_deterministic_ids) ... ok
test_university_course_aggregation (test_inventory.InventoryTests.test_university_course_aggregation) ... ok

----------------------------------------------------------------------
Ran 8 tests in 0.517s

OK

Task 002 unit regression: PASS
test_01_markdown_nested_headings_and_stable_section_ids (test_structural_index.StructuralIndexTests.test_01_markdown_nested_headings_and_stable_section_ids) ... ok
test_02_heading_free_deterministic_chunk_boundaries (test_structural_index.StructuralIndexTests.test_02_heading_free_deterministic_chunk_boundaries) ... ok
test_03_very_large_line_is_bounded_and_counted (test_structural_index.StructuralIndexTests.test_03_very_large_line_is_bounded_and_counted) ... ok
test_04_arabic_unicode_heading_and_filename (test_structural_index.StructuralIndexTests.test_04_arabic_unicode_heading_and_filename) ... ok
test_05_markdown_table_code_fence_and_link_counts (test_structural_index.StructuralIndexTests.test_05_markdown_table_code_fence_and_link_counts) ... ok
test_06_csv_tsv_streaming_header_and_rows (test_structural_index.StructuralIndexTests.test_06_csv_tsv_streaming_header_and_rows) ... ok
test_07_json_and_yaml_structural_key_inventory (test_structural_index.StructuralIndexTests.test_07_json_and_yaml_structural_key_inventory) ... ok
test_08_python_ast_symbols_without_execution (test_structural_index.StructuralIndexTests.test_08_python_ast_symbols_without_execution) ... ok
test_09_pdf_page_count_and_text_status (test_structural_index.StructuralIndexTests.test_09_pdf_page_count_and_text_status) ... ok
test_10_empty_text_pdf_classified_without_ocr (test_structural_index.StructuralIndexTests.test_10_empty_text_pdf_classified_without_ocr) ... ok
test_11_university_sequence_inference_and_gap (test_structural_index.StructuralIndexTests.test_11_university_sequence_inference_and_gap) ... ok
test_12_placeholder_zero_byte_not_flagged (test_structural_index.StructuralIndexTests.test_12_placeholder_zero_byte_not_flagged) ... ok
test_13_task_like_zero_byte_yaml_flagged (test_structural_index.StructuralIndexTests.test_13_task_like_zero_byte_yaml_flagged) ... ok
test_14_multiple_provisional_domain_candidates (test_structural_index.StructuralIndexTests.test_14_multiple_provisional_domain_candidates) ... ok
test_15_unclassified_when_signal_is_insufficient (test_structural_index.StructuralIndexTests.test_15_unclassified_when_signal_is_insufficient) ... ok
test_16_output_root_overlap_rejected (test_structural_index.StructuralIndexTests.test_16_output_root_overlap_rejected) ... ok
test_17_no_writes_under_originals (test_structural_index.StructuralIndexTests.test_17_no_writes_under_originals) ... ok
test_18_deterministic_rerun_hashes (test_structural_index.StructuralIndexTests.test_18_deterministic_rerun_hashes) ... ok

----------------------------------------------------------------------
Ran 18 tests in 0.431s

OK

Task 003 deterministic validator in read-only wrapper: PASS
PASS assertions=1640 sources=80 units=205 copies=77 bytes=208304970

Task 003R unit validation: PASS
test_capability_to_ku_is_not_one_to_one (test_refined_semantic_baseline.RefinedSemanticBaselineTests.test_capability_to_ku_is_not_one_to_one) ... ok
test_exact_repetition_is_reported_for_human_review (test_refined_semantic_baseline.RefinedSemanticBaselineTests.test_exact_repetition_is_reported_for_human_review) ... ok
test_exact_required_schemas_are_present (test_refined_semantic_baseline.RefinedSemanticBaselineTests.test_exact_required_schemas_are_present) ... ok
test_full_validator_passes_many_assertions (test_refined_semantic_baseline.RefinedSemanticBaselineTests.test_full_validator_passes_many_assertions) ... ok
test_handoff_zip_exists_and_passes (test_refined_semantic_baseline.RefinedSemanticBaselineTests.test_handoff_zip_exists_and_passes) ... ok
test_ku_count_and_vs001_ids (test_refined_semantic_baseline.RefinedSemanticBaselineTests.test_ku_count_and_vs001_ids) ... ok
test_ocr_sources_are_semantically_deferred (test_refined_semantic_baseline.RefinedSemanticBaselineTests.test_ocr_sources_are_semantically_deferred) ... ok
test_product_charter_support_is_not_technical (test_refined_semantic_baseline.RefinedSemanticBaselineTests.test_product_charter_support_is_not_technical) ... ok

----------------------------------------------------------------------
Ran 8 tests in 1.531s

OK
```
