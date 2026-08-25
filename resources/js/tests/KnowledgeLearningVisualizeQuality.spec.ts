import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';

const read = (path: string) => readFileSync(resolve(process.cwd(), path), 'utf8');

describe('knowledge visualization and research-quality contracts', () => {
  it('keeps MAP, VIEW, and OVERLAY separate and refuses fabricated overlay data', () => {
    const page = read('resources/js/pages/KnowledgeLearning/Visualize.vue');
    const overlays = read(
      'resources/js/pages/KnowledgeLearning/components/visualize/OverlayPanel.vue',
    );
    const surface = read(
      'resources/js/pages/KnowledgeLearning/components/visualize/VisualizationSurface.vue',
    );

    expect(page).toContain('MAP');
    expect(page).toContain('VIEW');
    expect(page).toContain('OVERLAY');
    for (const view of ['Tree', 'Path', 'Graph', 'Canvas']) expect(page).toContain(view);
    for (const overlay of ['coverage', 'prerequisite', 'progress', 'evidence', 'mastery']) {
      expect(overlays).toContain(overlay);
    }
    expect(overlays).toContain('لا توجد بيانات مرصودة');
    expect(surface).toContain('canonical containment');
    expect(surface).toContain('مسار قانوني مستقل');
  });

  it('keeps knowledge-quality review separate from evidence review and system truth decisions', () => {
    const page = read('resources/js/pages/KnowledgeLearning/ResearchQuality.vue');
    const workbench = read(
      'resources/js/pages/KnowledgeLearning/components/research-quality/ResearchQualityWorkbench.vue',
    );

    expect(page).toContain('Research & Quality Review != Evidence Review');
    expect(page).toContain('النظام لا يقرر حقيقة المعرفة');
    expect(workbench).toContain('system_truth_decision');
    expect(workbench).toContain('reconciliation');
    expect(workbench).toContain('Revision');
  });
});
