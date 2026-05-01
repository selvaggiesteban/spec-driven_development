##### 5.7.1. General Requirements for Elementor JSON Templates

- The file is in valid JSON format, without syntax errors.
- The file is encoded in UTF-8 (without strange characters or problematic BOM).
- The "type" field is correctly defined as "section" or "page" as appropriate.
- The "version" field is present and consistent with the Elementor export version.
- The "content" field exists and contains the structure of sections/columns/widgets.
- Each element has its unique id within the JSON (no duplicate IDs).
- Sections ("elType": "section") are correctly nested and there are no orphan elements.
- Heading texts respect a logical title hierarchy (H1, H2, H3, etc.).
- Images have defined alt attribute.
