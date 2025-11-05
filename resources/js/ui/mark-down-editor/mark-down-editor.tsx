import React, { useEffect, useMemo, useState } from 'react';
import { debounce } from 'lodash';
import ReactMarkdown from 'react-markdown';
import 'prismjs/themes/prism.css';

import { CodeNode, CodeHighlightNode } from '@lexical/code';
import { LinkNode } from '@lexical/link';
import { ListItemNode, ListNode } from '@lexical/list';
import {
  $convertFromMarkdownString,
  $convertToMarkdownString,
  TRANSFORMERS,
} from '@lexical/markdown';
import type { InitialConfigType } from '@lexical/react/LexicalComposer';
import { LexicalComposer } from '@lexical/react/LexicalComposer';
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
import remarkGfm from 'remark-gfm';

import CodeHighlighterPlugin from 'ui/mark-down-editor/code-highlighter-component';
import {
  content_editable_classes,
  editor_container_classes,
  editor_outer_classes,
  placeholder_classes,
  alpha_ol_class,
  toolbar_button_classes,
  preview_container_classes,
} from 'ui/mark-down-editor/styles/mark-down-editor-styles';
import { mark_down_editor_theme } from 'ui/mark-down-editor/styles/mark-down-editor-theme';
import ToolbarPlugin from 'ui/mark-down-editor/tool-bar';
import MarkDownEditorProps from 'ui/mark-down-editor/types/mark-down-editor-props';

function MarkDownEditor(props: MarkDownEditorProps) {
  const {
    id,
    placeholder = 'Start typing…',
    on_value_change,
    class_name,
    initial_markdown,
  } = props;

  const [alpha_ol_mode, set_alpha_ol_mode] = useState(false);
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
        CodeNode,
        CodeHighlightNode,
        HorizontalRuleNode,
      ],
      editorState: initial_markdown
        ? (editor: LexicalEditor) => {
            editor.update(() => {
              $convertFromMarkdownString(initial_markdown, TRANSFORMERS);
            });
          }
        : undefined,
      onError: (error: Error, _editor: LexicalEditor) => {
        throw error;
      },
    }),
    [initial_markdown]
  );

  const debounced_emit_change = useMemo(
    () => debounce((value: string) => on_value_change?.(value), 300),
    [on_value_change]
  );

  useEffect(() => {
    return () => {
      debounced_emit_change.cancel();
    };
  }, [debounced_emit_change]);

  const handle_change = (editor_state: EditorState) => {
    const extract_markdown_from_state = (state: EditorState): string => {
      let markdown = '';
      state.read(() => {
        markdown = $convertToMarkdownString(TRANSFORMERS);
      });
      return markdown;
    };

    const md = extract_markdown_from_state(editor_state);
    set_markdown_value(md);

    if (on_value_change) {
      debounced_emit_change(md);
    }
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
          <ReactMarkdown remarkPlugins={[remarkGfm]}>
            {markdown_value || ''}
          </ReactMarkdown>
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
        <MarkdownShortcutPlugin transformers={TRANSFORMERS} />
        <CodeHighlighterPlugin />
        <OnChangePlugin onChange={handle_change} />
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
          <ToolbarPlugin
            on_toggle_alpha_ol={() => set_alpha_ol_mode((value) => !value)}
          />

          <div className="flex items-center justify-end px-2 py-2">
            <button
              type="button"
              className={toolbar_button_classes}
              onClick={() => set_is_preview((value) => !value)}
            >
              {is_preview ? 'Edit' : 'Preview'}
            </button>
          </div>

          <div
            className={`relative ${editor_container_classes} ${
              alpha_ol_mode ? alpha_ol_class : ''
            }`}
          >
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
