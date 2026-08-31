/**
 * Normalize legacy HTML line-break tokens (`<br>`, `<br/>`, `<br />`,
 * case-insensitive) in a persisted Quest story string into real Markdown
 * line breaks before handing the content to the shared Markdown renderer.
 * Presentation-only: never mutates or re-saves the persisted Quest
 * description, and never strips or sanitizes any other HTML.
 */
export const normalizeQuestStoryMarkdown = (
  markdown: string | null
): string | null => {
  if (markdown === null) {
    return null;
  }

  return markdown.replace(/<br\s*\/?>/gi, '\n');
};
