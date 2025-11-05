import { $convertFromMarkdownString, TRANSFORMERS } from '@lexical/markdown';
import { useLexicalComposerContext } from '@lexical/react/LexicalComposerContext';
import { COMMAND_PRIORITY_NORMAL, PASTE_COMMAND } from 'lexical';
import { useCallback, useEffect } from 'react';

import UseMarkDownPasteDefinition from 'ui/mark-down-editor/hooks/definitions/use-mark-down-paste-definition';

export const useMarkdownPaste = (
  {
    stripCode = true,
    stripStrike = true,
  }: UseMarkDownPasteDefinition = {} as UseMarkDownPasteDefinition
): void => {
  const [editor] = useLexicalComposerContext();

  const looksLikeMarkdown = useCallback((text: string): boolean => {
    const patterns = [
      /^\s{0,3}#{1,6}\s/m,
      /^\s{0,3}[-*+]\s/m,
      /^\s*\d+\.\s/m,
      /^\s{0,3}>\s/m,
      /(^|\n)```/m,
      /^\s{0,3}(?:-{3,}|\*{3,}|_{3,})\s*$/m,
    ];
    for (const pattern of patterns) {
      if (pattern.test(text)) {
        return true;
      }
    }
    return false;
  }, []);

  const sanitize = useCallback(
    (text: string): string => {
      let out = text;
      if (stripCode) {
        out = out.replace(/(^|\n)```[\s\S]*?```/g, '$1');
        out = out.replace(/`([^`]+)`/g, '$1');
      }
      if (stripStrike) {
        out = out.replace(/~~([^~]+)~~/g, '$1');
      }
      return out;
    },
    [stripCode, stripStrike]
  );

  const transformers = useCallback(() => {
    return TRANSFORMERS.filter((tr: unknown) => {
      const t = tr as { dependencies?: unknown[]; type?: unknown };
      const deps = Array.isArray(t.dependencies) ? t.dependencies : [];
      const typeName = String(t.type ?? '').toLowerCase();
      if (deps.some((d) => String(d).toLowerCase().includes('code'))) {
        return false;
      }
      if (typeName.includes('code')) {
        return false;
      }
      if (typeName.includes('strikethrough')) {
        return false;
      }
      return true;
    });
  }, []);

  useEffect(() => {
    return editor.registerCommand(
      PASTE_COMMAND,
      (event: ClipboardEvent) => {
        const text = event.clipboardData?.getData('text/plain') ?? '';
        if (!looksLikeMarkdown(text)) {
          return false;
        }
        event.preventDefault();
        const sanitized = sanitize(text);
        editor.update(() => {
          $convertFromMarkdownString(sanitized, transformers());
        });
        return true;
      },
      COMMAND_PRIORITY_NORMAL
    );
  }, [editor, looksLikeMarkdown, sanitize, transformers]);
};
