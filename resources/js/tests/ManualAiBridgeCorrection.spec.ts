import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import AiBridgeContext from '../pages/SystemOperations/components/ai-bridge/AiBridgeContext.vue';
import AiBridgeSurface from '../pages/SystemOperations/components/ai-bridge/AiBridgeSurface.vue';

describe('ManualAiBridgeCorrection Spec', () => {
  const mockState = {
    policy: {
      execution: 'MANUAL_ONLY',
      automatic_provider_enabled: false,
      automatic_publish: false,
      polling: false,
      embeddings: false,
    },
    results: [
      {
        id: 'res-corr-01',
        prompt_package_revision_id: 'rev-corr-01',
        portable_package_id: 'pkg-corr-01',
        result_digest: 'a'.repeat(64),
        structured_result: {
          knowledge_unit_id: 'KU-01',
          proposed_blocks: [{ type: 'paragraph', body: 'محتوى تجريبي.' }],
          citation_claim_ids: ['CLAIM-1'],
          limitations: ['Manual test.'],
          confidence: 'high',
        },
        status: 'pending_review',
        imported_at: '2026-08-28T22:00:00Z',
        prompt_package_id: 'prompt-corr-01',
        prompt_revision: 1,
        prompt_input_digest: 'b'.repeat(64),
        declared_scope: { scope: { knowledge_unit_id: 'KU-01' } },
        prompt_portable_package_id: 'prompt-pkg-01',
        prompt_purpose: 'مراجعة وصياغة KU-01',
        prompt_status: 'exported',
        returned_package_type: 'manual-ai-result',
        returned_package_digest: 'c'.repeat(64),
        returned_package_scope: { input_digest: 'b'.repeat(64) },
        returned_package_manifest: {},
        returned_package_status: 'exported',
      },
    ],
    prompt_revisions: [
      {
        id: 'rev-corr-01',
        prompt_package_id: 'prompt-corr-01',
        revision: 1,
        portable_package_id: 'prompt-pkg-01',
        input_digest: 'b'.repeat(64),
        declared_scope: { scope: { knowledge_unit_id: 'KU-01' } },
        exported_at: '2026-08-28T21:00:00Z',
        prompt_purpose: 'مراجعة وصياغة KU-01',
        prompt_status: 'exported',
        prompt_current_revision: 1,
        package_type: 'manual-ai-prompt',
        package_digest: 'd'.repeat(64),
        package_scope: {},
        package_manifest: {},
        package_status: 'exported',
      },
    ],
  };

  it('renders Manual AI Bridge governance header and form surfaces', () => {
    const wrapper = mount(AiBridgeSurface, {
      props: { state: mockState },
    });

    expect(wrapper.text()).toContain('جسر الذكاء الاصطناعي اليدوي غير المعتمد على مزودين');
    expect(wrapper.text()).toContain('تصدير حزمة موجه (Prompt Package)');
    expect(wrapper.text()).toContain('استيراد نتيجة الذكاء الاصطناعي');
    expect(wrapper.text()).toContain('✓ تشغيل يدوي بالكامل');
    expect(wrapper.text()).toContain('✕ لا API provider');
    expect(wrapper.text()).toContain('✕ لا نشر تلقائي');
  });

  it('proves file input accepts .zip result package and renders upload button', () => {
    const wrapper = mount(AiBridgeSurface, {
      props: { state: mockState },
    });

    const fileInput = wrapper.find('input[type="file"]');
    expect(fileInput.exists()).toBe(true);
    expect(fileInput.attributes('accept')).toContain('.zip');

    const submitBtn = wrapper.findAll('button.btn-primary')[1];
    expect(submitBtn.text()).toContain('استيراد النتيجة للمراجعة');
    expect(submitBtn.attributes('disabled')).toBeDefined();
  });

  it('enforces mandatory human rationale before enabling accept/reject decision controls', async () => {
    const wrapper = mount(AiBridgeSurface, {
      props: { state: mockState },
    });

    const acceptBtn = wrapper.find('button.accept-button');
    const rejectBtn = wrapper.find('button.danger-button');
    expect(acceptBtn.attributes('disabled')).toBeDefined();
    expect(rejectBtn.attributes('disabled')).toBeDefined();

    const textarea = wrapper.find('textarea[id="rationale-res-corr-01"]');
    expect(textarea.exists()).toBe(true);

    await textarea.setValue('تم التدقيق والمطابقة مع المرجع.');
    const propIdInput = wrapper.find('input[id="proposal-id-res-corr-01"]');
    await propIdInput.setValue('prop_1');
    expect(wrapper.find('button.accept-button').attributes('disabled')).toBeUndefined();
    expect(wrapper.find('button.danger-button').attributes('disabled')).toBeUndefined();
  });

  it('emits openDeep with complete result provenance on inspect click', async () => {
    const wrapper = mount(AiBridgeSurface, {
      props: { state: mockState },
    });

    const inspectBtn = wrapper.find('.result-actions button');
    await inspectBtn.trigger('click');

    const emitted = wrapper.emitted('openDeep');
    expect(emitted).toBeDefined();
    expect(emitted![0][0]).toContain('فحص نتيجة AI المستوردة — res-corr-01');
  });

  it('proves AiBridgeContext truthfully exposes manual-only governance policy', () => {
    const wrapper = mount(AiBridgeContext, {
      props: { state: mockState },
    });

    expect(wrapper.text()).toContain('نمط التنفيذ: MANUAL_ONLY');
    expect(wrapper.text()).toContain('المزود الشبكي: معطّل (Off)');
    expect(wrapper.text()).toContain('automatic_provider_enabled: false');
    expect(wrapper.text()).toContain('automatic_publish: false');
    expect(wrapper.text()).toContain('الاستطلاع التلقائي (Polling): معطّل');
  });
});
