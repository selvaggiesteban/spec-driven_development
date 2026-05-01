# Image Analysis Agent for Prompt Generation

## Purpose
This agent is designed to analyze inspiration images downloaded from the internet and extract structured information in JSON format, feeding AI image generation prompts (subject.json, scene.json, image.json).

## Agent Architecture

### Recommended Models
- **Claude 3.5 Sonnet / Opus 4**: Excellent for detailed analysis and structured output
- **GPT-4 Vision / GPT-4o**: High precision in visual element identification
- **Gemini 2.5 Pro**: Outstanding at tabular and structured data extraction

### Analysis Approach
The agent uses a **multi-layer analysis** approach, breaking down the image into three main dimensions:

1. **Subject** (subject.json) - Biometric and physical characteristics
2. **Scene** (scene.json) - Context, environment, and spatial composition
3. **Image** (image.json) - Photographic technique, lighting, and artistic style

---

## Analysis Methodology

### Fundamental Principles

Based on research from authoritative sources ([DeepLearning.AI](https://www.deeplearning.ai/short-courses/prompt-engineering-for-vision-models/), [Edge AI Vision Alliance](https://www.edge-ai-vision.com/2025/03/vision-language-model-prompt-engineering-guide-for-image-and-video-understanding/)), the analysis follows these principles:

1. **Image-First Structure**: Place the image before text in the prompt for better performance
2. **Structured Output**: Use JSON Schema to ensure output consistency
3. **Multi-Pass Analysis**: Analyze the image in multiple passes, each focused on a specific aspect
4. **Progressive Granularity**: Start with overview, then dive into specific details

### The 3 Pillars of Photographic Analysis

According to [Photography Tips](https://photographytips.com/the-3-pillars-of-photography-light-composition-and-subject/):

1. **Light**: Direction, quality, temperature, contrast
2. **Composition**: Framing, rules, visual balance
3. **Subject**: The main element and its characteristics

---

## Layered Prompt System

### LAYER 1: Subject Analysis

**Objective**: Extract biometric, physical, and presentation characteristics of the main subject.

**Structured Prompt**:

```
Analyze this image and extract detailed information about the MAIN SUBJECT following this exact JSON structure.

INSTRUCTIONS:
- Focus ONLY on the main human subject in the image
- Provide precise and objective descriptions
- Use appropriate photographic and anatomical terminology
- DO NOT invent information that is not visible

OUTPUT STRUCTURE (JSON):
{
  "subject_profile": {
    "type": "[human/animal/object]",
    "gender_presentation": "[masculine/feminine/androgynous/neutral]",
    "age_range": "[infant/child/teen/young adult/middle-aged/senior]",

    "skin": {
      "tone": "[descriptive color: light/medium/dark + undertone]",
      "texture_details": ["[visible characteristic 1]", "[visible characteristic 2]"]
    },

    "face": {
      "face_shape": {
        "overall": "[oval/round/square/heart/oblong/diamond]",
        "jawline": "[soft/defined/angular/rounded]",
        "cheekbones": "[high/moderate/low, prominent/subtle]"
      },

      "eyes": {
        "color": "[color visible or 'not clearly visible']",
        "shape": "[almond/round/hooded/monolid/upturned/downturned]",
        "gaze": "[direct/averted/down/up/closed]"
      },

      "eyebrows": {
        "density": "[thin/moderate/thick/full]",
        "shape": "[straight/arched/angled/curved]"
      },

      "nose": {
        "bridge": "[straight/curved/wide/narrow]",
        "tip": "[pointed/rounded/bulbous]"
      },

      "lips": {
        "fullness": "[thin/moderate/full]",
        "shape": "[defined/soft cupid's bow, symmetry]"
      }
    },

    "hair": {
      "color": "[specific color or color range]",
      "texture": "[straight/wavy/curly/coily]",
      "length": "[short/medium/long + specific description]",
      "style": "[specific hairstyle observed]"
    },

    "facial_hair": {
      "type": "[none/stubble/beard/mustache/goatee]",
      "density": "[light/medium/heavy]"
    },

    "expression": "[detailed description of facial expression]",

    "clothing": {
      "visible_garments": [
        {
          "type": "[garment type]",
          "color": "[color]",
          "details": "[texture, pattern, fit, notable features]"
        }
      ]
    },

    "distinctive_details": ["[unique feature 1]", "[unique feature 2]"]
  }
}

IMPORTANT: Respond ONLY with JSON. Do not add additional explanations.
```

---

### LAYER 2: Scene Analysis

**Objective**: Capture environmental context, background elements, atmosphere, and spatial composition.

**Structured Prompt**:

```
Analyze this image and extract detailed information about the SCENE and ENVIRONMENTAL CONTEXT following this exact JSON structure.

INSTRUCTIONS:
- Describe the location, environment, and background elements
- Identify spatial relationships between elements
- Capture atmosphere and mood details
- Analyze composition and framing

OUTPUT STRUCTURE (JSON):
{
  "scene": {
    "location": "[indoor/outdoor + specific setting type]",
    "setting_type": "[detailed environment description]",
    "spatial_depth": "[shallow/medium/deep, background characteristics]",

    "background": {
      "elements": ["[element 1]", "[element 2]", "[element 3]"],
      "treatment": "[in-focus/bokeh/blurred/dark/bright]",

      "elements_detailed": [
        {
          "item": "[specific object or area]",
          "position": "[location in frame]",
          "distance": "[foreground/midground/background]",
          "size": "[relative size]",
          "specific_features": "[notable characteristics]"
        }
      ],

      "wall_surface": {
        "material": "[material type if visible]",
        "texture": "[smooth/rough/patterned]",
        "color": "[color description]"
      },

      "floor_surface": {
        "material": "[material type if visible]",
        "color": "[color description]"
      }
    },

    "atmosphere": ["[atmospheric element 1]", "[atmospheric element 2]"],

    "lighting": {
      "type": "[natural/artificial/mixed]",
      "direction": "[front/side/back/top/bottom + angle]",
      "quality": "[hard/soft/diffused]",
      "highlights": "[description of highlight areas]",
      "shadows": "[description of shadow characteristics]"
    }
  },

  "subject_analysis": {
    "positioning": "[center/left/right/rule of thirds]",
    "scale": "[extreme close-up/close-up/medium/medium-wide/wide]",
    "interaction": "[what the subject is doing]",

    "body_positioning": {
      "posture": "[sitting/standing/lying/leaning + specifics]",
      "angle": "[facing camera/turned/profile + degrees]",
      "shoulders": "[relaxed/tense/raised/back]"
    },

    "hands_and_gestures": {
      "visible_hands": "[description of hand positioning]",
      "gesture": "[specific gesture or activity]",
      "details": "[nail appearance, accessories, etc.]"
    }
  },

  "composition": {
    "shot_type": "[specific type: headshot/portrait/full body/etc.]",
    "framing": "[tight/loose, what's included]",
    "camera_angle": "[eye level/high angle/low angle + specifics]",
    "depth_of_field": "[shallow/medium/deep + focus description]"
  },

  "generation_parameters": {
    "prompts": ["[main descriptive prompt]", "[alternative short prompt]"],
    "keywords": ["[keyword 1]", "[keyword 2]", "[keyword 3]"],
    "technical_settings": "[camera/lens estimation, aperture, etc.]"
  }
}

IMPORTANT: Respond ONLY with JSON. Do not add additional explanations.
```

---

### LAYER 3: Image Technical Analysis

**Objective**: Extract technical specifications, detailed lighting, color profile, and artistic elements.

**Structured Prompt**:

```
Analyze this image from a TECHNICAL and ARTISTIC perspective following this exact JSON structure.

INSTRUCTIONS:
- Analyze technical photography aspects: lighting, color, composition
- Identify artistic style and mood
- Describe processing and post-production characteristics
- Provide precise analysis as a professional photographer

According to [DIY Photography](https://www.diyphotography.net/analyzing-light-breakdown-lighting-photo/), analyze lighting in 4 groups:
1. Catchlights (reflections in eyes)
2. Shadows (direction and density)
3. Highlights (location and treatment)
4. Background lights (background illumination)

OUTPUT STRUCTURE (JSON):
{
  "output": {
    "aspect_ratio": "[ratio like 16:9, 4:5, 1:1]",
    "orientation": "[horizontal/vertical/square]",
    "resolution": "[estimated: low/medium/high/professional]"
  },

  "metadata": {
    "image_type": "[photograph/illustration/render/mixed]",
    "primary_purpose": "[commercial/editorial/social media/artistic/portrait/etc.]"
  },

  "composition": {
    "rule_applied": "[rule of thirds/center/golden ratio/leading lines/etc.]",
    "layout": "[single subject/multiple subjects/environmental]",
    "focal_points": ["[primary focus]", "[secondary focus]"],
    "visual_hierarchy": "[description of how eye moves through image]",
    "balance": "[symmetric/asymmetric + description]"
  },

  "color_profile": {
    "dominant_colors": [
      {
        "color": "[color name]",
        "hex": "[hex code if identifiable]",
        "percentage": "[estimated percentage]",
        "role": "[where this color appears]"
      }
    ],
    "color_palette": "[warm/cool/neutral/vibrant/muted + description]",
    "temperature": "[warm/cool/neutral + specific characteristics]",
    "saturation": "[low/moderate/high/vivid]",
    "contrast": "[low/medium/high + specific areas]"
  },

  "lighting": {
    "type": "[natural sunlight/studio/practical/mixed]",
    "source_count": "[single/multiple + description]",
    "direction": "[specific direction and angle]",
    "directionality": "[highly directional/diffused/omnidirectional]",
    "quality": "[hard/soft/mixed + characteristics]",
    "intensity": "[dim/moderate/bright/dramatic]",
    "contrast_ratio": "[low/medium/high contrast]",
    "mood": "[mood created by lighting]",
    "light_temperature": "[warm/neutral/cool + Kelvin estimate if possible]",
    "ambient_fill": "[none/low/moderate/high]",

    "shadows": {
      "type": "[hard-edged/soft/gradient]",
      "density": "[light/medium/dark/black]",
      "placement": "[where shadows appear]",
      "length": "[short/medium/long]"
    },

    "highlights": {
      "treatment": "[blown out/controlled/subtle]",
      "placement": "[where highlights appear]"
    }
  },

  "technical_specs": {
    "medium": "[digital/film + camera type estimate]",
    "style": "[documentary/editorial/commercial/fine art/lifestyle/etc.]",
    "sharpness": "[soft/sharp/very sharp + specific areas]",
    "grain": "[none/subtle/moderate/heavy]",
    "depth_of_field": "[very shallow/shallow/medium/deep]",
    "perspective": "[normal/wide/telephoto/fisheye effect]"
  },

  "camera_emulation": {
    "device_look": "[smartphone/DSLR/mirrorless/film camera estimation]",
    "lens_equivalent": "[focal length estimation in mm]",
    "processing_notes": ["[processing characteristic 1]", "[processing characteristic 2]"]
  },

  "post_processing": {
    "color_grade": "[description of color grading applied]",
    "grain": "[film grain characteristics if present]",
    "avoid": ["[artifact 1 to avoid]", "[artifact 2 to avoid]"]
  },

  "artistic_elements": {
    "genre": "[specific photography genre]",
    "influences": ["[style influence 1]", "[style influence 2]"],
    "mood": "[overall emotional tone]",
    "atmosphere": "[atmospheric description]",
    "visual_style": "[description of visual aesthetic]"
  },

  "typography": {
    "present": false,
    "fonts": [],
    "placement": "",
    "integration": ""
  }
}

IMPORTANT: Respond ONLY with JSON. Do not add additional explanations.
```

---

## Complete Workflow

### Step 1: Preparation
1. Download inspiration image from the internet
2. Ensure the image is high quality and clear
3. Identify which aspects you want to replicate (subject, scene, photographic technique)

### Step 2: Layer-by-Layer Analysis
Execute the 3 prompts in order using a vision AI model:

```
SESSION 1: Subject Analysis
[Image] + [LAYER 1 Prompt]
→ Save JSON output as subject-analysis.json

SESSION 2: Scene Analysis
[Image] + [LAYER 2 Prompt]
→ Save JSON output as scene-analysis.json

SESSION 3: Technical Analysis
[Image] + [LAYER 3 Prompt]
→ Save JSON output as image-analysis.json
```

### Step 3: Integration
Copy relevant values from analysis files to your main prompt files:
- `subject-analysis.json` → `subject.json`
- `scene-analysis.json` → `scene.json`
- `image-analysis.json` → `image.json`

### Step 4: Refinement
1. Review extracted values
2. Adjust or complement information according to your creative objective
3. Add or modify fields as needed for customization

### Step 5: Generation
Use the updated JSON prompts to generate your AI image.

---

## Best Practices

### 1. Prompt Optimization

According to [Claude Vision Documentation](https://docs.claude.com/en/docs/build-with-claude/vision):
- Place the image BEFORE the text prompt
- Use few-shot examples to improve precision
- Specify output format explicitly (JSON Schema)

### 2. Multiple Image Handling

If analyzing multiple inspiration images:
```
Prompt: "Analyze these [N] images and extract COMMON elements in style,
composition, and treatment. Provide a consolidated JSON that captures
recurring characteristics."
```

### 3. Incremental Extraction

For complex images, use incremental analysis:
1. First pass: Overview
2. Second pass: Specific details
3. Third pass: Refinement and verification

### 4. Results Validation

Tools to validate extraction:
- **[ImagePrompt.org](https://imageprompt.org/image-to-prompt)**: Compare your analysis with specialized AI
- **JSON validators**: Verify syntax before using
- **Side-by-side comparison**: Compare original image with generated description

---

## Specific Use Cases

### Case 1: Replicate Portrait Photography Style

**Objective**: Copy lighting style and composition from a professional portrait

**Focused prompts**:
```
LAYER 2 (Scene): Emphasis on lighting.direction, lighting.quality, composition.camera_angle
LAYER 3 (Technical): Emphasis on lighting.shadows, lighting.highlights, color_profile.temperature
```

### Case 2: Extract Model Characteristics

**Objective**: Document biometric characteristics of models for consistency

**Focused prompts**:
```
LAYER 1 (Subject): Complete analysis with maximum detail
Add: "Provide relative facial measurements (proportions between eyes, nose, mouth)"
```

### Case 3: Analyze Mood and Atmosphere

**Objective**: Capture mood from lifestyle/commercial images

**Focused prompts**:
```
LAYER 2 (Scene): Emphasis on atmosphere, subject_analysis.interaction
LAYER 3 (Technical): Emphasis on artistic_elements.mood, color_profile, lighting.mood
```

### Case 4: Instagram/Pinterest Reference Analysis

**Objective**: Convert social media images into structured prompts

**Specific workflow**:
1. Download multiple images from the same style
2. Analyze each with the 3 prompts
3. Consolidate common patterns
4. Create a JSON "style profile"

---

## Complementary Tools

### Online Tools

1. **[SceneXplain Image-to-JSON](https://jina.ai/news/scenexplains-image-json-extract-structured-data-images-precision/)**
   - Automated extraction with custom JSON Schema
   - 95% accuracy in visual elements

2. **[Image Prompt Generator](https://imageprompt.app/)**
   - Specific analysis for Midjourney, DALL-E, Stable Diffusion
   - Specialized in photographic style recreation

3. **[Apify Image-to-JSON Extractor](https://apify.com/apitale/image-to-json-extractor)**
   - Automation at scale
   - API for batch processing

### APIs and Frameworks

1. **Gemini Structured Output** ([Firebase AI Logic](https://firebase.google.com/docs/ai-logic/generate-structured-output))
   - Native JSON Schema support
   - Multimodal input (image + text + audio)

2. **Outlines Library** ([ADaSci](https://adasci.org/ai-powered-image-to-json-conversion-for-llm-fine-tuning-using-outlines/))
   - Python library for LLM interactions
   - Validated JSON generation from images

3. **MAX Serve Multimodal** ([Modular Builds](https://builds.modular.com/recipes/max-serve-multimodal-structured-output))
   - Llama 3.2 Vision with Pydantic
   - Type-safe JSON output

---

## Advanced Prompt: Complete Analysis in Single Pass

For advanced users who prefer consolidated analysis:

```
You are a professional photographer and expert image analyst. Analyze this image
EXHAUSTIVELY and extract structured information in JSON following EXACTLY
this complete schema.

Your analysis must cover:
1. SUBJECT: Complete biometric and physical characteristics
2. SCENE: Environment, context, spatial composition
3. TECHNIQUE: Photographic aspects, lighting, color, artistic style

ANALYSIS METHODOLOGY:
According to professional photography principles (3 pillars: light, composition, subject):

A. Light Analysis:
   - Identify catchlights (reflections in eyes)
   - Determine shadow direction
   - Evaluate highlight treatment
   - Analyze background lighting

B. Composition Analysis:
   - Applied rules (thirds, center, leading lines)
   - Visual balance
   - Element hierarchy
   - Framing and perspective

C. Subject Analysis:
   - Observable physical characteristics
   - Expression and body language
   - Clothing and accessories
   - Interaction with environment

OUTPUT FORMAT - CONSOLIDATED JSON:
{
  "subject_profile": { /* Complete LAYER 1 here */ },
  "scene": { /* Complete LAYER 2 here */ },
  "subject_analysis": { /* Positioning and gestures */ },
  "composition": { /* Photographic composition */ },
  "output": { /* Output configuration */ },
  "color_profile": { /* Color analysis */ },
  "lighting": { /* Detailed lighting analysis */ },
  "technical_specs": { /* Technical specifications */ },
  "camera_emulation": { /* Camera emulation */ },
  "artistic_elements": { /* Artistic elements */ },
  "generation_parameters": { /* Generation parameters */ }
}

IMPORTANT:
- Respond ONLY with valid JSON
- Do not add comments, explanations or markdown
- Use "n/a" or "not visible" for non-observable information
- Be precise and objective
- Use appropriate photographic technical terminology
```

---

## Troubleshooting

### Problem: Incomplete or truncated output

**Solution**:
- Split analysis into the 3 separate layers
- Increase model token limit
- Use consolidated prompt only with long-context models (Claude Opus, GPT-4)

### Problem: Generic or imprecise descriptions

**Solution**:
- Add few-shot examples with reference analysis
- Specify: "Provide PRECISE and SPECIFIC descriptions, not generalizations"
- Use refinement prompts: "Review the JSON and increase specificity in [field]"

### Problem: Invalid JSON in response

**Solution**:
- Emphasize: "Respond ONLY with valid JSON, no markdown, no explanations"
- Use model's native JSON mode if available (Gemini, GPT-4o)
- Post-process: Extract ```json``` blocks if present

### Problem: Invented information (hallucinations)

**Solution**:
- Add: "DO NOT invent information. Use 'not visible' if you cannot determine something"
- Validate output by comparing with original image
- Use multiple analyses and compare consistency

---

## References and Sources

### Research Resources

**Prompt Engineering for Vision AI**:
- [DeepLearning.AI - Prompt Engineering for Vision Models](https://www.deeplearning.ai/short-courses/prompt-engineering-for-vision-models/)
- [Edge AI Vision Alliance - Vision Language Model Prompt Engineering Guide](https://www.edge-ai-vision.com/2025/03/vision-language-model-prompt-engineering-guide-for-image-and-video-understanding/)
- [GitHub - Prompt Engineering for Vision Models](https://github.com/ksm26/Prompt-Engineering-for-Vision-Models)

**Structured Output Techniques**:
- [SceneXplain Image-to-JSON](https://jina.ai/news/scenexplains-image-json-extract-structured-data-images-precision/)
- [Firebase AI Logic - Generate Structured Output](https://firebase.google.com/docs/ai-logic/generate-structured-output)
- [Medium - Extracting Structured Data from Images Using GPT-4 Vision](https://medium.com/@foxmike/extracting-structured-data-from-images-using-openais-gpt-4-vision-and-jason-liu-s-instructor-ec7f54ee0a91)

**Vision AI Platforms**:
- [Claude Vision Documentation](https://docs.claude.com/en/docs/build-with-claude/vision)
- [Google Gemini 3 Pro Vision](https://blog.google/technology/developers/gemini-3-pro-vision/)
- [Anthropic - Claude 3 Family](https://www.anthropic.com/news/claude-3-family)

**Photography Analysis Techniques**:
- [Photography Tips - The 3 Pillars of Photography](https://photographytips.com/the-3-pillars-of-photography-light-composition-and-subject/)
- [DIY Photography - Analyzing Light](https://www.diyphotography.net/analyzing-light-breakdown-lighting-photo/)
- [PetaPixel - Key Elements of Composition](https://petapixel.com/2022/06/03/the-key-elements-of-composition-light-and-the-relationships-of-forms/)

**Image-to-Prompt Tools**:
- [ImagePrompt.org](https://imageprompt.org/image-to-prompt)
- [Image Prompt App](https://imageprompt.app/)
- [Apify Image-to-JSON Extractor](https://apify.com/apitale/image-to-json-extractor)

**Technical Resources**:
- [Medium - Image Analysis with Claude Opus 4](https://collin-smith.medium.com/image-analysis-with-claude-opus-4-0a5e935ad9ac)
- [Towards Data Science - Building LLM Apps with Multimodal Input](https://towardsdatascience.com/building-llm-apps-that-can-see-think-and-integrate-using-o3-with-multimodal-input-and-structured-output/)
- [Gemini 2.5 Pro and Claude Sonnet 4 Excel at Image Table Data Extraction](https://eval.16x.engineer/blog/image-table-data-extraction-evaluation-results)

---

## Changelog

**v1.0** (2026-01-04)
- Initial agent release
- 3 analysis layers (Subject, Scene, Image)
- Structured prompts with JSON Schema
- Integration with subject.json, scene.json, image.json
- Complete documentation with references

---

## Support and Contributions

This agent is a living document that will evolve with:
- New prompt engineering techniques
- Vision AI model updates
- Real-world usage feedback
- Additional use cases

For improvements and updates, consider:
1. Testing with different models and comparing results
2. Adding specialized prompts for specific niches
3. Developing automation scripts for batch processing
4. Direct integration with image generation tools

---

**Note**: This agent is optimized for Claude 3.5 Sonnet/Opus 4, GPT-4 Vision/4o, and Gemini 2.5 Pro. Results may vary with other models.
