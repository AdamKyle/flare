/**
 * Focused DOM-only concern shared by monitoring dashboards: scrolling a
 * named element into view after a summary tile applies a filter. Kept
 * separate from data-fetch orchestration so feature hooks stay free of DOM
 * behavior.
 */
export default function useScrollToElement() {
  const scrollToElement = (elementId: string): void => {
    window.setTimeout(() => {
      document.getElementById(elementId)?.scrollIntoView({
        behavior: 'smooth',
        block: 'start',
      });
    }, 0);
  };

  return { scroll_to_element: scrollToElement };
}
