export const shallowEqual = <TF extends Record<string, unknown>>(
  a: TF,
  b: TF
): boolean => {
  const aKeys = Object.keys(a);
  const bKeys = Object.keys(b);

  if (aKeys.length !== bKeys.length) {
    return false;
  }

  for (const keyName of aKeys) {
    if (a[keyName] !== b[keyName]) {
      return false;
    }
  }

  return true;
};