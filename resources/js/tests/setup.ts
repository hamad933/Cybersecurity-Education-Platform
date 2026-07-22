import { config } from '@vue/test-utils';
import { defineComponent, reactive } from 'vue';
import { vi } from 'vitest';

Object.defineProperty(window, 'matchMedia', {
  writable: true,
  value: vi.fn().mockImplementation((query: string) => ({
    matches: false,
    media: query,
    addEventListener: vi.fn(),
    removeEventListener: vi.fn(),
  })),
});

vi.mock('@inertiajs/vue3', () => ({
  Head: defineComponent({ template: '<span data-head />' }),
  Link: defineComponent({ template: '<button><slot /></button>' }),
  usePage: () =>
    reactive({
      props: {
        auth: { owner: { id: '0190-test', display_name: 'المالك' } },
        environment: { name: 'testing', profile: 'test', localOnly: true },
      },
    }),
  useForm: (values: Record<string, unknown>) =>
    reactive({ ...values, errors: {}, processing: false, post: vi.fn(), reset: vi.fn() }),
}));

config.global.renderStubDefaultSlot = true;
