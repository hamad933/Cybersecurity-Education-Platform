import { mount } from '@vue/test-utils';

import Workspace from '../pages/SystemOperations/Workspace.vue';

describe('System & Operations workspace', () => {
  it('renders real health inputs without invented uptime or deployment claims', () => {
    const wrapper = mount(Workspace, {
      props: {
        surface: 'health',
        state: {
          foundation: {
            checks: { database: 'ok', queue: 'failed' },
            healthy: false,
            failed_checks: ['queue'],
          },
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

  it('distinguishes requested processing input from executed state', () => {
    const inputDigest = 'a'.repeat(64);
    const wrapper = mount(Workspace, {
      props: {
        surface: 'processing',
        state: {
          processing: {
            counts: { failed: 1 },
            runs: [
              {
                id: 'run-1',
                type: 'fixture.validation',
                input_digest: inputDigest,
                status: 'failed',
                attempt_count: 2,
                max_attempts: 3,
                worker_identifier: 'worker-7',
                started_at: '2026-08-18T00:00:00Z',
                completed_at: '2026-08-18T00:01:00Z',
                cancelled_at: null,
                error_category: 'fixture',
                safe_error_message: 'Captured failure.',
                created_at: '2026-08-18T00:00:00Z',
              },
            ],
          },
          outbox: { counts: {}, messages: [] },
        },
      },
    });

    expect(wrapper.text()).toContain('REQUESTED → EXECUTED / CURRENT STATE');
    expect(wrapper.text()).toContain(inputDigest);
    expect(wrapper.text()).toContain('worker-7');
    expect(wrapper.text()).toContain('Captured failure.');
  });

  it('keeps Manual AI manual-only and places complete proposal review before decision controls', () => {
    const inputDigest = 'b'.repeat(64);
    const resultDigest = 'c'.repeat(64);
    const structuredResult = {
      knowledge_unit_id: 'KU-42',
      proposed_blocks: [{ type: 'paragraph', body: 'Material operator-review content.' }],
      citation_claim_ids: ['claim-42'],
      derived_from_revision_id: null,
      authority_baseline_id: 'baseline-42',
      limitations: ['Manual review required.'],
      confidence: 'bounded',
    };
    const wrapper = mount(Workspace, {
      props: {
        surface: 'ai-bridge',
        state: {
          policy: {
            execution: 'MANUAL_ONLY',
            automatic_provider_enabled: false,
            automatic_publish: false,
            polling: false,
            embeddings: false,
          },
          prompts: [],
          prompt_revisions: [
            {
              id: 'revision-1',
              prompt_package_id: 'prompt-1',
              revision: 1,
              portable_package_id: 'prompt-package-1',
              input_digest: inputDigest,
              declared_scope: {
                scope: { knowledge_unit_id: 'KU-42' },
                manual_execution_only: true,
                automatic_network_provider: false,
              },
              exported_at: '2026-08-18T00:00:00Z',
              prompt_purpose: 'Review KU-42',
              prompt_status: 'result_imported',
              prompt_current_revision: 1,
              package_type: 'manual-ai-prompt',
              package_digest: 'd'.repeat(64),
              package_scope: { knowledge_unit_id: 'KU-42' },
              package_manifest: { files: [{ path: 'prompt.json' }] },
              package_status: 'exported',
            },
          ],
          results: [
            {
              id: 'result-1',
              prompt_package_revision_id: 'revision-1',
              portable_package_id: 'result-package-1',
              result_digest: resultDigest,
              structured_result: structuredResult,
              status: 'pending_review',
              imported_at: '2026-08-18T00:02:00Z',
              prompt_package_id: 'prompt-1',
              prompt_revision: 1,
              prompt_input_digest: inputDigest,
              declared_scope: { scope: { knowledge_unit_id: 'KU-42' } },
              prompt_portable_package_id: 'prompt-package-1',
              prompt_purpose: 'Review KU-42',
              prompt_status: 'result_imported',
              returned_package_type: 'manual-ai-result',
              returned_package_digest: 'e'.repeat(64),
              returned_package_scope: {
                prompt_package_id: 'prompt-1',
                prompt_revision: 1,
                input_digest: inputDigest,
              },
              returned_package_manifest: { files: [{ path: 'result.json' }] },
              returned_package_status: 'exported',
            },
          ],
          decisions: [],
        },
      },
    });

    expect(wrapper.text()).toContain('MANUAL_ONLY / PROVIDER-NEUTRAL');
    expect(wrapper.text()).toContain('REQUESTED');
    expect(wrapper.text()).toContain(inputDigest);
    expect(wrapper.text()).toContain(resultDigest);
    expect(wrapper.text()).toContain('Material operator-review content.');
    expect(wrapper.text()).toContain('manual_execution_only');
    expect(wrapper.text()).toContain('لا API provider');
    expect(wrapper.text()).toContain('لا polling');

    const review = wrapper.find('details.proposal-review');
    expect(review.exists()).toBe(true);
    expect(review.attributes('open')).toBeUndefined();
    expect(review.find('pre.proposal-payload').text()).toContain(
      'Material operator-review content.',
    );
    expect(review.find('button.danger-button').text()).toBe('رفض');
    expect(review.findAll('button').some((button) => button.text() === 'قبول كمسودة')).toBe(true);
  });

  it('renders audit actor, target, correlation, metadata and chain context', () => {
    const wrapper = mount(Workspace, {
      props: {
        surface: 'audit',
        state: {
          chain: { valid: true, count: 1 },
          records: [
            {
              id: 'audit-1',
              sequence_no: 7,
              actor_identifier: 'owner-42',
              action: 'manual_ai.result.imported',
              target_type: 'imported_ai_result',
              target_identifier: 'result-42',
              correlation_id: 'correlation-42',
              outcome: 'success',
              safe_metadata: { result_digest: 'f'.repeat(64), prompt_revision: 1 },
              occurred_at: '2026-08-18T00:02:00Z',
              previous_hash: '1'.repeat(64),
              record_hash: '2'.repeat(64),
            },
          ],
        },
      },
    });

    expect(wrapper.text()).toContain('owner-42');
    expect(wrapper.text()).toContain('result-42');
    expect(wrapper.text()).toContain('correlation-42');
    expect(wrapper.text()).toContain('result_digest');
    expect(wrapper.text()).toContain('1'.repeat(64));
    expect(wrapper.text()).toContain('2'.repeat(64));
  });

  it('renders recorded release package scope and manifest without deployment authority', () => {
    const wrapper = mount(Workspace, {
      props: {
        surface: 'releases',
        state: {
          readiness: { ready: false, checks: {} },
          packages: [
            {
              id: 'package-42',
              package_type: 'release-evidence',
              schema_version: 1,
              owner_module: 'MOD-PLT',
              scope: {
                target: 'release-candidate:W05',
                handling: 'package-and-release-validation-only',
              },
              manifest: { files: [{ path: 'evidence.json' }] },
              package_digest: '9'.repeat(64),
              status: 'exported',
              created_at: '2026-08-18T00:00:00Z',
            },
          ],
          authorization: {
            deployment_authorized: false,
            deployment_workflow_available: false,
            scope: 'PACKAGE_AND_RELEASE_VALIDATION_ONLY',
          },
        },
      },
    });

    expect(wrapper.text()).toContain('release-candidate:W05');
    expect(wrapper.text()).toContain('package-and-release-validation-only');
    expect(wrapper.text()).toContain('evidence.json');
    expect(wrapper.text()).toContain('لا Deployment');
  });

  it('keeps restore staging behind closed progressive disclosure', () => {
    const wrapper = mount(Workspace, {
      props: {
        surface: 'backups',
        state: {
          backups: [],
          restores: [],
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
