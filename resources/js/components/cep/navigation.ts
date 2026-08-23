export const CEP_GLOBAL_DESTINATIONS = [
  { key: 'today', label: 'اليوم', href: '/' },
  { key: 'knowledge', label: 'المعرفة والتعلّم', href: '/knowledge' },
  { key: 'simulation', label: 'المحاكاة والمؤسسات', href: '/simulation' },
  { key: 'progress', label: 'التقدم والأدلة', href: '/progress' },
  { key: 'system', label: 'النظام والعمليات', href: '/system' },
] as const;

export type CepDestination = (typeof CEP_GLOBAL_DESTINATIONS)[number];
export type CepDestinationKey = CepDestination['key'];
