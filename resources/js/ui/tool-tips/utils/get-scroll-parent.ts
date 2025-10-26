export const getScrollParent = (
  node: HTMLElement | null
): HTMLElement | null => {
  let element: HTMLElement | null = node ? node.parentElement : null;

  while (element) {
    const style = getComputedStyle(element);
    const overflowY = style.overflowY;
    const overflow = style.overflow;

    if (
      overflowY === 'auto' ||
      overflowY === 'scroll' ||
      overflow === 'auto' ||
      overflow === 'scroll'
    ) {
      return element;
    }

    element = element.parentElement;
  }

  return null;
};
