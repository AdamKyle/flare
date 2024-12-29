export const motionLeftPositionHelper = (): string => {
  const width = window.innerWidth;

  if (width <= 667) {
    return '-176px';
  }

  if (width <= 768) {
    return '-88px';
  }

  return '-1rem';
};
