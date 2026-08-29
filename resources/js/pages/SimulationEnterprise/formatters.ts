export function jsonText(value: unknown): string {
  return JSON.stringify(value ?? {}, null, 2);
}

export function shortDigest(value: string): string {
  if (value.length <= 18) return value;
  return `${value.slice(0, 9)}…${value.slice(-7)}`;
}

export function runTypeLabel(value: string): string {
  return value === 'Scenario Run' ? 'تشغيل سيناريو' : 'تشغيل مختبر مستقل';
}

export function lifecycleTone(value: string): string {
  if (['RUNNING', 'READY'].includes(value)) return 'active';
  if (['COMPLETED', 'SEALED'].includes(value)) return 'success';
  if (['FAILED', 'STOPPED'].includes(value)) return 'danger';
  if (value === 'PAUSED') return 'warning';
  return 'neutral';
}
