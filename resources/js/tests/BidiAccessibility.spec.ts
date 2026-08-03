import { mount } from '@vue/test-utils';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';

import BidiText from '../components/BidiText.vue';

describe('Bidirectional and accessibility primitives', () => {
  it('isolates mixed-direction identifiers', () => {
    const wrapper = mount(BidiText, { props: { value: 'MOD-IAM / المالك' } });
    expect(wrapper.element.tagName).toBe('BDI');
    expect(wrapper.attributes('dir')).toBe('auto');
  });

  it('defines visible keyboard focus and responsive shell styles', () => {
    const css = readFileSync(resolve('resources/css/app.css'), 'utf8');
    expect(css).toContain('.focus-ring:focus-visible');
    expect(css).toContain('outline: 3px solid');
    expect(css).toContain('.skip-link:focus');
    expect(css).not.toContain('overflow-x: hidden');
    const shell = readFileSync(resolve('resources/js/components/AppShell.vue'), 'utf8');
    expect(shell).toContain('sm:flex-row');
    expect(shell).toContain('flex-wrap');
  });
});
