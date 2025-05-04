export const getColorLevel = (percent: number): [string, string] => {
  if (percent > 50) {
    return ['bg-wisp-pink-900', 'dark:bg-wisp-pink-900'];
  }

  if (percent > 25) {
    return ['bg-wisp-pink-600', 'dark:bg-wisp-pink-500'];
  }

  return ['bg-wisp-pink-400', 'dark:bg-wisp-pink-300'];
};
