import { useMarkdownPaste } from 'ui/mark-down-editor/hooks/use-mark-down-paste';

const MarkdownPastePlugin = (): null => {
  useMarkdownPaste({});
  return null;
};

export default MarkdownPastePlugin;
