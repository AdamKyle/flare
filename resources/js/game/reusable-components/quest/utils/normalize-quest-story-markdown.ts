/**
 * Normalize legacy `<br>` tokens for Markdown display without mutating persisted content.
 */
export const normalizeQuestStoryMarkdown = (
  markdown: string | null
): string | null => {
  if (markdown === null) {
    return null;
  }

  return markdown.replace(/<br\s*\/?>/gi, '\n');
};
