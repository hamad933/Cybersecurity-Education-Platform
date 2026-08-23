# CEP AI Rendering Exclusions

Status: `EXECUTION_REFERENCE_RULE`
Authority role: execution/reviewer guidance only. Google Drive remains the canonical visual/reference owner.

AI-generated reference images express product intent, composition, information ownership, hierarchy,
interaction intent, density, and visual grammar. They are NOT pixel-perfect implementation specifications.

Never copy these image-generation artifacts into the product:

- garbled Arabic or malformed glyphs;
- wrong RTL/LTR ordering caused by rendering;
- fake metrics, fake identifiers, fake statuses, or invented product data;
- duplicate controls or impossible/non-functional controls;
- accidental text duplication;
- malformed geometry, clipping, broken connectors, or distorted topology;
- inconsistent spacing/alignment that is clearly generation noise;
- unrealistic content density produced only by the generator;
- decorative elements that conflict with approved product semantics;
- image-specific labels contradicted by product architecture or approved visual contract;
- visual artifacts created by image compression/generation.

Conflict precedence for execution:
1. approved product architecture and real application semantics;
2. `CEP_VIS_001_FINAL_VISUAL_AND_INTERACTION_CONTRACT_v1.0_APPROVED.md`;
3. applicable per-reference note;
4. the image itself.

Visual similarity alone is not acceptance evidence.
