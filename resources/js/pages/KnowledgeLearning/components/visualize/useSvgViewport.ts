import { computed, ref, type Ref } from 'vue';
import type { VisualBounds } from './types';

export const useSvgViewport = (bounds: Ref<VisualBounds>) => {
  const scale = ref(1);
  const panX = ref(0);
  const panY = ref(0);

  const viewBox = computed(() => {
    const width = bounds.value.width / scale.value;
    const height = bounds.value.height / scale.value;
    return {
      x: bounds.value.x + (bounds.value.width - width) / 2 + panX.value,
      y: bounds.value.y + (bounds.value.height - height) / 2 + panY.value,
      width,
      height,
    };
  });

  const setScale = (next: number) => {
    scale.value = Math.min(2, Math.max(0.55, next));
  };
  const zoomIn = () => setScale(scale.value + 0.15);
  const zoomOut = () => setScale(scale.value - 0.15);
  const fit = () => {
    scale.value = 1;
    panX.value = 0;
    panY.value = 0;
  };
  const pan = (x: number, y: number) => {
    panX.value += x / scale.value;
    panY.value += y / scale.value;
  };
  const onKeydown = (event: KeyboardEvent) => {
    const amount = event.shiftKey ? 72 : 36;
    if (event.key === 'ArrowLeft') pan(-amount, 0);
    else if (event.key === 'ArrowRight') pan(amount, 0);
    else if (event.key === 'ArrowUp') pan(0, -amount);
    else if (event.key === 'ArrowDown') pan(0, amount);
    else if (event.key === '+' || event.key === '=') zoomIn();
    else if (event.key === '-') zoomOut();
    else if (event.key === '0') fit();
    else return;
    event.preventDefault();
  };
  const onWheel = (event: WheelEvent) => {
    event.preventDefault();
    setScale(scale.value + (event.deltaY < 0 ? 0.1 : -0.1));
  };

  return {
    scale,
    viewBox,
    zoomIn,
    zoomOut,
    fit,
    pan,
    onKeydown,
    onWheel,
  };
};
