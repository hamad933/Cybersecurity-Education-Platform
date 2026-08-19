import { readFileSync } from 'node:fs';
import { Buffer } from 'node:buffer';
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

  const payload = Buffer.from(JSON.stringify(formatted), 'utf8').toString('base64');
  console.log(`W03_PRETTIER_CAPTURE_BEGIN:${payload}:W03_PRETTIER_CAPTURE_END`);
  expect(Object.keys(formatted)).toHaveLength(files.length);
});
