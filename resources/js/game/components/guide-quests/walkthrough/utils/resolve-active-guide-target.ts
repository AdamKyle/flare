import { GuideTarget } from '../registry/guide-targets';

const isVisible = (element: HTMLElement): boolean => {
  const bounds = element.getBoundingClientRect();
  const style = window.getComputedStyle(element);

  return (
    bounds.width > 0 &&
    bounds.height > 0 &&
    style.display !== 'none' &&
    style.visibility !== 'hidden' &&
    style.opacity !== '0'
  );
};

export const resolveActiveGuideTarget = (
  target: GuideTarget
): HTMLElement | null => {
  const candidates = document.querySelectorAll<HTMLElement>(
    `[data-guide-target="${target}"]`
  );

  for (const candidate of candidates) {
    const hiddenAncestor = candidate.closest('[aria-hidden="true"], [inert]');

    if (!candidate.isConnected || hiddenAncestor || !isVisible(candidate)) {
      continue;
    }

    return candidate;
  }

  return null;
};

export const waitForActiveGuideTarget = async (
  target: GuideTarget,
  timeoutMs: number = 6000
): Promise<HTMLElement | null> => {
  const startedAt = Date.now();

  while (Date.now() - startedAt < timeoutMs) {
    const element = resolveActiveGuideTarget(target);

    if (element) {
      return element;
    }

    await new Promise((resolve) => window.setTimeout(resolve, 100));
  }

  return null;
};
