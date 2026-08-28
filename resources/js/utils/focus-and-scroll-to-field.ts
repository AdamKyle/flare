/**
 * Focus and scroll a form field into view by its element id, skipping elements that are
 * currently hidden/inert (for example a field on an inactive wizard step).
 */
export const focusAndScrollToField = (elementId: string): void => {
  const element = document.getElementById(elementId);

  if (!element || element.closest('[inert]')) {
    return;
  }

  element.scrollIntoView({ behavior: 'smooth', block: 'center' });
  element.focus();
};
