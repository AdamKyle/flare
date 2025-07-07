import { use } from 'react';
import type { Context } from 'react';

export const useCustomContext = <T>(
  contextObject: Context<T | undefined>,
  componentName: string
): T => {
  const context = use(contextObject);

  if (!context) {
    throw new Error(
      `${componentName} context must be used within a ${componentName}Provider`
    );
  }

  return context;
};
