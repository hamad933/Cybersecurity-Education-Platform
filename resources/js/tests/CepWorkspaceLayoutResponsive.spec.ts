import { mount } from '@vue/test-utils';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { beforeEach, describe, expect, it } from 'vitest';

import CepWorkspaceLayout from '../layouts/CepWorkspaceLayout.vue';

describe('CEP workspace layout responsive shell', () => {
  beforeEach(() => {
    localStorage.clear();
  });

  it('marks only visible side panels for responsive grid placement', async () => {
    const wrapper = mount(CepWorkspaceLayout, {
      props: {
        activeDestination: 'today',
        initialLeftCollapsed: false,
        initialRightCollapsed: false,
      },
      slots: {
        left: '<p>Structure</p>',
        default: '<div style="height: 4000px">Long primary work surface</div>',
        right: '<p>Context</p>',
      },
    });

    const grid = wrapper.find('.cep-workspace-grid');
    expect(grid.classes()).toContain('cep-workspace-grid--has-left');
    expect(grid.classes()).toContain('cep-workspace-grid--has-right');

    await wrapper.findAll('.cep-panel-toggle')[0].trigger('click');

    expect(grid.classes()).not.toContain('cep-workspace-grid--has-left');
    expect(grid.classes()).toContain('cep-workspace-grid--has-right');
  });

  it('keeps responsive panels before the center and removes material shell motion', () => {
    const css = readFileSync(resolve('resources/css/app.css'), 'utf8');

    expect(css).toContain("'structure context'\n      'center center'");
    expect(css).toContain("'structure'\n      'context'\n      'center'");
    expect(css).toContain('max-height: min(18rem, 40vh)');
    expect(css).toContain('flex-basis: 100%');
    expect(css).toContain('@media (max-width: 24.375rem)');
    expect(css).toContain('@media (prefers-reduced-motion: reduce)');
    expect(css).toContain('transition-duration: 0.01ms !important');
    expect(css).not.toContain('overflow-x: hidden');
  });
});
