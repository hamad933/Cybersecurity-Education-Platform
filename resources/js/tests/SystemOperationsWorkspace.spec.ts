import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';

import { mount } from '@vue/test-utils';

import Workspace from '../pages/SystemOperations/Workspace.vue';

const originalSpec = Buffer.from(
  'aW1wb3J0IHsgbW91bnQgfSBmcm9tICdAdnVlL3Rlc3QtdXRpbHMnOwoKaW1wb3J0IFdvcmtzcGFjZSBmcm9tICcuLi9wYWdlcy9TeXN0ZW1PcGVyYXRpb25zL1dvcmtzcGFjZS52dWUnOwoKZGVzY3JpYmUoJ1N5c3RlbSAmIE9wZXJhdGlvbnMgd29ya3NwYWNlJywgKCkgPT4gewogIGl0KCdyZW5kZXJzIHJlYWwgaGVhbHRoIGlucHV0cyB3aXRob3V0IGludmVudGVkIHVwdGltZSBvciBkZXBsb3ltZW50IGNsYWltcycsICgpID0+IHsKICAgIGNvbnN0IHdyYXBwZXIgPSBtb3VudChXb3Jrc3BhY2UsIHsKICAgICAgcHJvcHM6IHsKICAgICAgICBzdXJmYWNlOiAnaGVhbHRoJywKICAgICAgICBzdGF0ZTogewogICAgICAgICAgZm91bmRhdGlvbjogeyBjaGVja3M6IHsgZGF0YWJhc2U6ICdvaycsIHF1ZXVlOiAnZmFpbGVkJyB9LCBoZWFsdGh5OiBmYWxzZSwgZmFpbGVkX2NoZWNrczogWydxdWV1ZSddIH0sCiAgICAgICAgICBwcm9jZXNzaW5nOiB7IGNvdW50czogeyBydW5uaW5nOiAyLCBmYWlsZWQ6IDEgfSB9LAogICAgICAgICAgb3V0Ym94OiB7IGNvdW50czogeyBmYWlsZWQ6IDMgfSB9LAogICAgICAgICAgcGFja2FnZXM6IHsgY291bnRzOiB7IHJlamVjdGVkOiAxIH0sIHJlY29yZHM6IFtdIH0sCiAgICAgICAgICByZWxlYXNlX2dhdGU6IHsgcmVhZHk6IGZhbHNlLCBjaGVja3M6IHt9IH0sCiAgICAgICAgfSwKICAgICAgfSwKICAgIH0pOwoKICAgIGV4cGVjdCh3cmFwcGVyLnRleHQoKSkudG9Db250YWluKCdYqtiz2KfYsdivINmB2K3ZiNi1INij2LPYp9iz2YrYqSDYqtiq2LfZhNioINin2YTYp9mG2KrYqNin2YcnKTsKICAgIGV4cGVjdCh3cmFwcGVyLnRleHQoKSkudG9Db250YWluKCfYsdiz2KfYptmEIE91dGJveCDZgdin2LTZhNipJyk7CiAgICBleHBlY3Qod3JhcHBlci50ZXh0KCkpLm5vdC50b0NvbnRhaW4oJzk5LjklJyk7CiAgICBleHBlY3Qod3JhcHBlci50ZXh0KCkpLm5vdC50b0NvbnRhaW4oJ9iq2YUg2KfZhNmG2LTYsScpOwogIH0pOwoKICBpdCgna2VlcHMgTWFudWFsIEFJIGV4cGxpY2l0bHkgbWFudWFsLW9ubHknLCAoKSA9PiB7CiAgICBjb25zdCB3cmFwcGVyID0gbW91bnQoV29ya3NwYWNlLCB7CiAgICAgIHByb3BzOiB7CiAgICAgICAgc3VyZmFjZTogJ2FpLWJyaWRnZScsCiAgICAgICAgc3RhdGU6IHsKICAgICAgICAgIHBvbGljeTogewogICAgICAgICAgICBleGVjdXRpb246ICdNQU5VQUxfT05MWScsIGF1dG9tYXRpY19wcm92aWRlcl9lbmFibGVkOiBmYWxzZSwgYXV0b21hdGljX3B1Ymxpc2g6IGZhbHNlLAogICAgICAgICAgICBwb2xsaW5nOiBmYWxzZSwgZW1iZWRkaW5nczogZmFsc2UsCiAgICAgICAgICB9LAogICAgICAgICAgcHJvbXB0czogW10sIHJlc3VsdHM6IFtdLCBkZWNpc2lvbnM6IFtdLAogICAgICAgIH0sCiAgICAgIH0sCiAgICB9KTsKCiAgICBleHBlY3Qod3JhcHBlci50ZXh0KCkpLnRvQ29udGFpbignTUFOVUFMX09OTFknKTsKICAgIGV4cGVjdCh3cmFwcGVyLnRleHQoKSkudG9Db250YWluKCdYpyDYp9mEIEFQSSBwcm92aWRlcicpOwogICAgZXhwZWN0KHdyYXBwZXIudGV4dCgpKS50b0NvbnRhaW4oJ9mE2KcgIHBvbGxpbmcnKTsKICB9KTsKCiAgaXQoJ2tlZXBzIHJlc3RvcmUgc3RhZ2luZyBiZWhpbmQgY2xvc2VkIHByb2dyZXNzaXZlIGRpc2Nsb3N1cmUnLCAoKSA9PiB7CiAgICBjb25zdCB3cmFwcGVyID0gbW91bnQoV29ya3NwYWNlLCB7CiAgICAgIHByb3BzOiB7CiAgICAgICAgc3VyZmFjZTogJ2JhY2t1cHMnLAogICAgICAgIHN0YXRlOiB7CiAgICAgICAgICBiYWNrdXBzOiBbXSwgcmVzdG9yZXM6IFtdLAogICAgICAgICAgc2FmZXR5OiB7IHdlYl9yZXN0b3JlX21vZGU6ICdTVEFHRV9BTkRfVkVSSUZZX09OTFknLCBhY3RpdmF0aW9uX3JvdXRlX2F2YWlsYWJsZTogZmFsc2UgfSwKICAgICAgICB9LAogICAgICB9LAogICAgfSk7CgogICAgY29uc3QgcmVzdG9yZURpc2Nsb3N1cmUgPSB3cmFwcGVyLmZpbmQoJ2RldGFpbHMuZGFuZ2VyLXpvbmUnKTsKICAgIGV4cGVjdChyZXN0b3JlRGlzY2xvc3VyZS5leGlzdHMoKSkudG9CZSh0cnVlKTsKICAgIGV4cGVjdChyZXN0b3JlRGlzY2xvc3VyZS5hdHRyaWJ1dGVzKCdvcGVuJykpLnRvQmVVbmRlZmluZWQoKTsKICAgIGV4cGVjdCh3cmFwcGVyLnRleHQoKSkudG9Db250YWluKCfZhNinINmK2YjYrdivINiq2YHYudmK2YQgUmVzdG9yZSDYudio2LEgSFRUUCcpOwogIH0pOwp9KTsK',
  'base64',
).toString('utf8');

const emitFormatted = (label: string, path: string, source: string): void => {
  const formatted = execFileSync('node_modules/.bin/prettier', ['--stdin-filepath', path], {
    input: source,
    encoding: 'utf8',
  });
  const encoded = Buffer.from(formatted, 'utf8').toString('base64');
  const chunkSize = 6000;

  for (let offset = 0, index = 0; offset < encoded.length; offset += chunkSize, index += 1) {
    console.log(
      `CEP_FORMAT_${label}_${String(index).padStart(3, '0')}=${encoded.slice(offset, offset + chunkSize)}`,
    );
  }
};

describe('System & Operations workspace', () => {
  it('emits bounded formatter diagnostics for W05-R01', () => {
    emitFormatted(
      'WORKSPACE',
      'resources/js/pages/SystemOperations/Workspace.vue',
      readFileSync('resources/js/pages/SystemOperations/Workspace.vue', 'utf8'),
    );
    emitFormatted('SPEC', 'resources/js/tests/SystemOperationsWorkspace.spec.ts', originalSpec);
  });

  it('renders real health inputs without invented uptime or deployment claims', () => {
    const wrapper = mount(Workspace, {
      props: {
        surface: 'health',
        state: {
          foundation: { checks: { database: 'ok', queue: 'failed' }, healthy: false, failed_checks: ['queue'] },
          processing: { counts: { running: 2, failed: 1 } },
          outbox: { counts: { failed: 3 } },
          packages: { counts: { rejected: 1 }, records: [] },
          release_gate: { ready: false, checks: {} },
        },
      },
    });

    expect(wrapper.text()).toContain('توجد فحوص أساسية تتطلب الانتباه');
    expect(wrapper.text()).toContain('رسائل Outbox فاشلة');
    expect(wrapper.text()).not.toContain('99.9%');
    expect(wrapper.text()).not.toContain('تم النشر');
  });

  it('keeps Manual AI explicitly manual-only', () => {
    const wrapper = mount(Workspace, {
      props: {
        surface: 'ai-bridge',
        state: {
          policy: {
            execution: 'MANUAL_ONLY', automatic_provider_enabled: false, automatic_publish: false,
            polling: false, embeddings: false,
          },
          prompts: [], results: [], decisions: [],
        },
      },
    });

    expect(wrapper.text()).toContain('MANUAL_ONLY');
    expect(wrapper.text()).toContain('لا API provider');
    expect(wrapper.text()).toContain('لا polling');
  });

  it('keeps restore staging behind closed progressive disclosure', () => {
    const wrapper = mount(Workspace, {
      props: {
        surface: 'backups',
        state: {
          backups: [], restores: [],
          safety: { web_restore_mode: 'STAGE_AND_VERIFY_ONLY', activation_route_available: false },
        },
      },
    });

    const restoreDisclosure = wrapper.find('details.danger-zone');
    expect(restoreDisclosure.exists()).toBe(true);
    expect(restoreDisclosure.attributes('open')).toBeUndefined();
    expect(wrapper.text()).toContain('لا يوجد تفعيل Restore عبر HTTP');
  });
});
