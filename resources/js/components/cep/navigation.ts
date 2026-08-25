export type CepDestinationKey = 'today' | 'knowledge' | 'simulation' | 'progress' | 'system';

export interface CepGlobalDestination {
  key: CepDestinationKey;
  label: string;
  href: string;
}

export const CEP_GLOBAL_DESTINATIONS: CepGlobalDestination[] = [
  { key: 'today', label: 'اليوم', href: '/' },
  { key: 'knowledge', label: 'المعرفة والتعلّم', href: '/knowledge' },
  { key: 'simulation', label: 'المحاكاة والمؤسسات', href: '/simulation' },
  { key: 'progress', label: 'التقدم والأدلة', href: '/progress' },
  { key: 'system', label: 'النظام والعمليات', href: '/system' },
];
