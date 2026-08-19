import type { FieldEntry, JsonMap, WorkspaceRecord } from './types';

export function asMap(value: unknown): JsonMap {
  return typeof value === 'object' && value !== null && !Array.isArray(value)
    ? (value as JsonMap)
    : {};
}

export function valueText(value: unknown): string {
  if (value === null || value === undefined || value === '') return '—';
  if (typeof value === 'boolean') return value ? 'نعم' : 'لا';
  if (typeof value === 'string' || typeof value === 'number') return String(value);
  if (Array.isArray(value)) {
    const scalar = value.every((item) => ['string', 'number', 'boolean'].includes(typeof item));
    return scalar ? value.map(valueText).join(' · ') : `${value.length} عناصر`;
  }
  const keys = Object.keys(asMap(value));
  return keys.length ? `${keys.length} حقول منظّمة` : '—';
}

export function fieldEntries(value: unknown, omitted: string[] = []): FieldEntry[] {
  return Object.entries(asMap(value))
    .filter(([key]) => !omitted.includes(key))
    .map(([key, item]) => ({ key, value: valueText(item) }));
}

export function stringList(value: unknown): string[] {
  return Array.isArray(value)
    ? value.filter((item): item is string => typeof item === 'string')
    : [];
}

export function recordLabel(record: WorkspaceRecord): string {
  if ('name_ar' in record) return record.name_ar;
  if ('title_ar' in record) return record.title_ar;
  if ('definition_title_ar' in record) return record.definition_title_ar;
  if ('run_id' in record) return `نتيجة ${record.run_id}`;
  return '—';
}

export function recordMeta(record: WorkspaceRecord): string {
  if ('slug' in record) return record.slug;
  if ('lifecycle' in record) return record.lifecycle;
  if ('outcome' in record) return record.outcome;
  return '—';
}

export function runTypeLabel(value: string): string {
  return value === 'Scenario Run' ? 'Scenario Run' : 'Standalone Lab Run';
}
