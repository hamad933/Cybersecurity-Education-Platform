import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import LessonContentRenderer from '../pages/KnowledgeLearning/components/content/LessonContentRenderer.vue';
import {
  compareLessonBlocks,
  inlineLessonTokens,
  normalizeLessonBlocks,
} from '../pages/KnowledgeLearning/components/content/lessonContent';
import { lessonContentContractFixture } from './fixtures/lessonContentContract';

const bidiCorpus = [
  'هذا السطر يختبر كلمة resource داخل نص عربي مع المحافظة على ترتيب النص.',
  'Validate the authorization decision ثم اعرض السبب بالعربية without reordering tokens.',
  'الوحدة KU-D05-0021 مرتبطة بالقدرة CAP-D05-0009 والإصدار v0.1.',
  'افتح https://example.test/api/v1/objects/42 ثم راجع status=403 وtimestamp 2026-08-30T15:00:00Z.',
  'قرار التفويض يحتاج subject, action, resource (owner/admin) مع أقواس / slashes: [read/write].',
  'المسار C:\\Lab\\Evidence\\result.json والرمز user@example.test يجب أن يبقيا LTR atoms داخل العربية.',
  'استخدم `GET /api/users/{id}` ثم اشرح النتيجة بالعربية داخل السطر نفسه.',
  'English prefix — عبارة عربية وسطية — then English suffix (v2.3).',
];

describe('W02 mixed-direction content', () => {
  it('preserves the prepared corpus in logical order and isolates technical atoms', () => {
    const blocks = bidiCorpus.map((body) => ({ type: 'paragraph', body, depth: 0 }));
    const wrapper = mount(LessonContentRenderer, {
      props: { blocks, contract: lessonContentContractFixture },
    });

    for (const value of bidiCorpus.filter((value) => !value.includes('`'))) {
      expect(wrapper.text()).toContain(value);
    }
    expect(wrapper.text()).toContain(
      'استخدم GET /api/users/{id} ثم اشرح النتيجة بالعربية داخل السطر نفسه.',
    );
    expect(wrapper.findAll('p[dir="auto"]')).toHaveLength(bidiCorpus.length);
    expect(wrapper.find('code[dir="ltr"]').text()).toBe('GET /api/users/{id}');
    expect(normalizeLessonBlocks(blocks).map((block) => block.body)).toEqual(bidiCorpus);
  });

  it('keeps bold, emphasis, links, and inline code as ordered inline tokens', () => {
    const value =
      'ابدأ **بحد الثقة** ثم _راجع resource_ وافتح [المرجع](https://example.test/ref) واستخدم `KU-D05-0021`.';
    const tokens = inlineLessonTokens(value);

    expect(tokens.map((token) => token.kind)).toEqual([
      'text',
      'strong',
      'text',
      'emphasis',
      'text',
      'link',
      'text',
      'code',
      'text',
    ]);
    expect(tokens.map((token) => token.text).join('')).toContain('KU-D05-0021');
  });

  it('normalizes legacy identities deterministically and distinguishes a moved V2 block', () => {
    const legacy = [{ type: 'paragraph', body: 'Legacy block', depth: 0 }];
    expect(normalizeLessonBlocks(legacy)[0]?.id).toBe(normalizeLessonBlocks(legacy)[0]?.id);
    expect(normalizeLessonBlocks(legacy)[0]?.id).toMatch(/^legacy_[0-9a-f]{17}$/);

    const first = { id: 'AAAAAAAAAAAAAAAAAAAAAAAA', type: 'paragraph', body: 'First', depth: 0 };
    const second = { id: 'BBBBBBBBBBBBBBBBBBBBBBBB', type: 'paragraph', body: 'Second', depth: 0 };
    const rows = compareLessonBlocks([second, first], [first, second]);
    expect(rows.map((row) => row.state)).toEqual(['moved', 'moved']);
  });
});
