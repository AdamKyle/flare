export const motionLeftPositionHelper = (): string => {
  const width = window.innerWidth;

  if (width <= 667) {
    return '-150px';
  }

  if (width <= 768) {
    return '-44px';
  }

  return '-1rem';
};
