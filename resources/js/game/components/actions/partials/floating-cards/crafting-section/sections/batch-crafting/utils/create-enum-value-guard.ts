export const createEnumValueGuard = <T extends string>(
  enumObject: Record<string, T>
) => {
  const values = new Set<string>(Object.values(enumObject));

  return (value: string): value is T => values.has(value);
};
