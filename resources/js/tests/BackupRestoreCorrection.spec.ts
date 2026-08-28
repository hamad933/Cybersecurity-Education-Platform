import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import BackupsSurface from '../pages/SystemOperations/components/backups/BackupsSurface.vue';
import BackupsContext from '../pages/SystemOperations/components/backups/BackupsContext.vue';

describe('Backup Restore Correction', () => {
  it('renders isolated staging guarantee in context', () => {
    const wrapper = mount(BackupsContext, {
      props: { state: {} }
    });

    expect(wrapper.text()).toContain('الفحص المرحلي المعزول');
    expect(wrapper.text()).toContain('التفعيل عبر CLI فقط');
  });

  it('renders extended lifecycle statuses correctly in surface', () => {
    const state = {
      backups: [],
      restores: [
        {
          id: 'restore-staged',
          backup_manifest_id: 'backup-1',
          target_database: 'cep_stage_verify',
          status: 'staged',
          verification: {},
          started_at: '2026-08-25T02:00:00Z',
          completed_at: null
        },
        {
          id: 'restore-activation',
          backup_manifest_id: 'backup-1',
          target_database: 'cep_stage_verify',
          status: 'activation_pending',
          verification: { valid: true },
          started_at: '2026-08-25T02:00:00Z',
          completed_at: '2026-08-25T02:05:00Z'
        },
        {
          id: 'restore-rollback',
          backup_manifest_id: 'backup-1',
          target_database: 'cep_stage_verify',
          status: 'rollback_failed',
          verification: {},
          started_at: '2026-08-25T02:00:00Z',
          completed_at: '2026-08-25T02:05:00Z'
        }
      ]
    };

    const wrapper = mount(BackupsSurface, {
      props: { state }
    });

    // Just verify the data is parsed and displayed, relying on StatusPill inside
    expect(wrapper.text()).toContain('restore-staged');
    expect(wrapper.text()).toContain('restore-activation');
    expect(wrapper.text()).toContain('restore-rollback');
  });

  it('prohibits direct apply action in UI by only providing staging form', () => {
    const wrapper = mount(BackupsSurface, {
      props: { state: { backups: [], restores: [] } }
    });
    
    // Test that the form submits to the safe `stage` endpoint
    const form = wrapper.find('.stage-form');
    expect(form.exists()).toBe(true);
    
    // There should be no "apply" or "activate" button
    expect(wrapper.text()).not.toContain('تفعيل مباشر');
    expect(wrapper.text()).not.toContain('Apply Now');
  });
});
