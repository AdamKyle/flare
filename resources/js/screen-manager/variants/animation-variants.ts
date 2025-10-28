export const slideVariants = {
  hidden: { x: '100%', opacity: 0 },
  enter: {
    x: 0,
    opacity: 1,
    transition: {
      x: { type: 'tween', ease: 'easeOut', duration: 0.45 },
      opacity: { duration: 0.2 },
    },
  },
  exit: {
    x: 0,
    opacity: 0,
    transition: {
      opacity: { duration: 0.25, ease: 'easeIn' },
    },
  },
};
