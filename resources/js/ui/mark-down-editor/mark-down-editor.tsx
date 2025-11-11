import { LinkNode } from '@lexical/link';
import { ListItemNode, ListNode } from '@lexical/list';
import {
  $convertFromMarkdownString,
  $convertToMarkdownString,
} from '@lexical/markdown';
import { LexicalComposer } from '@lexical/react/LexicalComposer';
import type { InitialConfigType } from '@lexical/react/LexicalComposer';
import { ContentEditable } from '@lexical/react/LexicalContentEditable';
import { LexicalErrorBoundary } from '@lexical/react/LexicalErrorBoundary';
import { HistoryPlugin } from '@lexical/react/LexicalHistoryPlugin';
import { HorizontalRuleNode } from '@lexical/react/LexicalHorizontalRuleNode';
import { LinkPlugin } from '@lexical/react/LexicalLinkPlugin';
import { ListPlugin } from '@lexical/react/LexicalListPlugin';
import { MarkdownShortcutPlugin } from '@lexical/react/LexicalMarkdownShortcutPlugin';
import { OnChangePlugin } from '@lexical/react/LexicalOnChangePlugin';
import { RichTextPlugin } from '@lexical/react/LexicalRichTextPlugin';
import { HeadingNode, QuoteNode } from '@lexical/rich-text';
import { AnimatePresence, motion } from 'framer-motion';
import type { EditorState, LexicalEditor } from 'lexical';
import React, { useMemo, useState } from 'react';
import ReactMarkdown from 'react-markdown';

import { useMarkdownPaste } from 'ui/mark-down-editor/hooks/use-mark-down-paste';
import MarkdownPastePlugin from 'ui/mark-down-editor/plugins/mark-down-paste-plugin';
import {
  content_editable_classes,
  editor_container_classes,
  editor_outer_classes,
  placeholder_classes,
  preview_container_classes,
} from 'ui/mark-down-editor/styles/mark-down-editor-styles';
import { mark_down_editor_theme } from 'ui/mark-down-editor/styles/mark-down-editor-theme';
import ToolbarPlugin from 'ui/mark-down-editor/tool-bar';
import MarkDownEditorProps from 'ui/mark-down-editor/types/mark-down-editor-props';

function MarkDownEditor({
  id,
  placeholder = 'Start typing…',
  on_value_change,
  class_name,
  initial_markdown,
}: MarkDownEditorProps) {
  const { transformers } = useMarkdownPaste();

  const [is_preview, set_is_preview] = useState(false);
  const [markdown_value, set_markdown_value] = useState(initial_markdown ?? '');

  const initial_config: InitialConfigType = useMemo(
    () => ({
      namespace: 'mark-down-editor',
      theme: mark_down_editor_theme,
      nodes: [
        HeadingNode,
        QuoteNode,
        ListNode,
        ListItemNode,
        LinkNode,
        HorizontalRuleNode,
      ],
      editorState: initial_markdown
        ? (editor: LexicalEditor) => {
            const sanitized = (initial_markdown ?? '')
              .replace(/(^|\n)```[\s\S]*?```/g, '$1')
              .replace(/`([^`]+)`/g, '$1')
              .replace(/~~([^~]+)~~/g, '$1');
            editor.update(() => {
              $convertFromMarkdownString(sanitized, transformers);
            });
          }
        : undefined,
      onError: (error: Error) => {
        throw error;
      },
    }),
    [initial_markdown, transformers]
  );

  const handle_change = (editor_state: EditorState) => {
    let markdown = '';

    editor_state.read(() => {
      markdown = $convertToMarkdownString(transformers);
    });

    set_markdown_value(markdown);
    on_value_change?.(markdown);
  };

  const renderPreview = () => {
    return (
      <motion.div
        key="preview"
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        exit={{ opacity: 0 }}
        transition={{ duration: 0.2 }}
      >
        <div className={preview_container_classes}>
          <ReactMarkdown>{markdown_value || ''}</ReactMarkdown>
        </div>
      </motion.div>
    );
  };

  const renderEditor = () => {
    return (
      <motion.div
        key="editor"
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        exit={{ opacity: 0 }}
        transition={{ duration: 0.2 }}
      >
        <RichTextPlugin
          contentEditable={
            <ContentEditable id={id} className={content_editable_classes} />
          }
          placeholder={<div className={placeholder_classes}>{placeholder}</div>}
          ErrorBoundary={LexicalErrorBoundary}
        />
        <HistoryPlugin />
        <ListPlugin />
        <LinkPlugin />
        <MarkdownShortcutPlugin transformers={transformers} />
        <OnChangePlugin onChange={handle_change} />
        <MarkdownPastePlugin />
      </motion.div>
    );
  };

  const renderContent = () => {
    if (is_preview) {
      return renderPreview();
    }
    return renderEditor();
  };

  return (
    <div className={class_name}>
      <LexicalComposer initialConfig={initial_config}>
        <div className={editor_outer_classes}>
          <ToolbarPlugin />
          <div className="flex items-center justify-end px-2 py-2">
            <button
              type="button"
              className="bg-danube-100 hover:bg-danube-200 active:bg-danube-300 dark:bg-danube-900/50 dark:hover:bg-danube-800 dark:active:bg-danube-700 rounded-md border border-gray-300 px-2 py-1 text-sm text-gray-800 dark:border-gray-700 dark:text-gray-100"
              onClick={() => set_is_preview((value) => !value)}
            >
              {is_preview ? 'Edit' : 'Preview'}
            </button>
          </div>
          <div className={`relative ${editor_container_classes}`}>
            <AnimatePresence mode="wait" initial={false}>
              {renderContent()}
            </AnimatePresence>
          </div>
        </div>
      </LexicalComposer>
    </div>
  );
}

export default MarkDownEditor;
