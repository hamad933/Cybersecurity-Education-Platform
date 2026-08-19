import { mkdirSync, readFileSync, writeFileSync } from 'node:fs';

import { format } from 'prettier';
import { expect, it } from 'vitest';

it('captures the exact CI Prettier output for W03 UI files', async () => {
  const files = [
    'resources/js/pages/SimulationEnterprise/components/ContextInspector.vue',
    'resources/js/pages/SimulationEnterprise/components/DeepWorkspace.vue',
    'resources/js/pages/SimulationEnterprise/components/EnterpriseCanvas.vue',
    'resources/js/pages/SimulationEnterprise/components/LabCanvas.vue',
    'resources/js/pages/SimulationEnterprise/components/ResultsCanvas.vue',
    'resources/js/pages/SimulationEnterprise/components/RunCanvas.vue',
    'resources/js/pages/SimulationEnterprise/components/ScenarioCanvas.vue',
    'resources/js/pages/SimulationEnterprise/Workspace.css',
    'resources/js/pages/SimulationEnterprise/Workspace.vue',
    'resources/js/tests/SimulationEnterpriseWorkspace.spec.ts',
  ];

  const formatted: Record<string, string> = {};
  for (const file of files) {
    formatted[file] = await format(readFileSync(file, 'utf8'), { filepath: file });
  }

  mkdirSync('evidence/frontend', { recursive: true });
  writeFileSync('evidence/frontend/prettier-capture.json', JSON.stringify(formatted));
  expect(Object.keys(formatted)).toHaveLength(files.length);
});