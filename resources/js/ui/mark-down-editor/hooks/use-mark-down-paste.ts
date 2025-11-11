import { TRANSFORMERS } from '@lexical/markdown';
import { useMemo } from 'react';

export const useMarkdownPaste = () => {
  const transformers = useMemo(() => {
    return TRANSFORMERS.filter((transformer: unknown) => {
      const lexiconTransformer = transformer as {
        dependencies?: unknown[];
        type?: unknown;
      };
      const dependencies = Array.isArray(lexiconTransformer.dependencies)
        ? lexiconTransformer.dependencies
        : [];
      const typeName = String(lexiconTransformer.type ?? '').toLowerCase();

      if (
        dependencies.some((dependency) =>
          String(dependency).toLowerCase().includes('code')
        )
      ) {
        return false;
      }

      if (typeName.includes('code')) {
        return false;
      }

      return !typeName.includes('strikethrough');
    });
  }, []);

  return {
    transformers,
  };
};
