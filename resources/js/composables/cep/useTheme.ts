import { ref } from 'vue';

export type CepTheme = 'dark' | 'light';

const STORAGE_KEY = 'cep-theme';

const theme = ref<CepTheme>('dark');

function getInitialTheme(): CepTheme {
  if (typeof window === 'undefined') return 'dark';
  try {
    const saved = localStorage.getItem(STORAGE_KEY);
    if (saved === 'dark' || saved === 'light') {
      return saved;
    }
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) {
      return 'light';
    }
  } catch {
    // LocalStorage or matchMedia not accessible
  }
  return 'dark';
}

function applyTheme(newTheme: CepTheme) {
  theme.value = newTheme;
  if (typeof document !== 'undefined' && document.documentElement) {
    document.documentElement.setAttribute('data-theme', newTheme);
    if (newTheme === 'dark') {
      document.documentElement.classList.add('dark');
      document.documentElement.classList.remove('light');
    } else {
      document.documentElement.classList.add('light');
      document.documentElement.classList.remove('dark');
    }
  }
}

export function useTheme() {
  function initTheme() {
    const initial = getInitialTheme();
    applyTheme(initial);
  }

  function toggleTheme() {
    const next = theme.value === 'dark' ? 'light' : 'dark';
    setTheme(next);
  }

  function setTheme(newTheme: CepTheme) {
    applyTheme(newTheme);
    try {
      localStorage.setItem(STORAGE_KEY, newTheme);
    } catch {
      // Ignore storage errors
    }
  }

  return {
    theme,
    initTheme,
    toggleTheme,
    setTheme,
  };
}
