import React, { useEffect, useMemo, useState } from 'react';
import { debounce } from 'lodash';
import 'prismjs/themes/prism.css';

import { CodeNode, CodeHighlightNode } from '@lexical/code';
import { HorizontalRuleNode } from '@lexical/extension';
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
import { LinkPlugin } from '@lexical/react/LexicalLinkPlugin';
import { ListPlugin } from '@lexical/react/LexicalListPlugin';
import { MarkdownShortcutPlugin } from '@lexical/react/LexicalMarkdownShortcutPlugin';
import { OnChangePlugin } from '@lexical/react/LexicalOnChangePlugin';
import { RichTextPlugin } from '@lexical/react/LexicalRichTextPlugin';
import { TablePlugin } from '@lexical/react/LexicalTablePlugin';
import { HeadingNode, QuoteNode } from '@lexical/rich-text';
import { TableNode, TableCellNode, TableRowNode } from '@lexical/table';
import type { EditorState, LexicalEditor } from 'lexical';

import CodeHighlighterPlugin from 'ui/mark-down-editor/code-highlighter-component';
import {
  content_editable_classes,
  editor_container_classes,
  editor_outer_classes,
  placeholder_classes,
  alpha_ol_class,
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

  const initial_config: InitialConfigType = {
    namespace: 'mark-down-editor',
    theme: mark_down_editor_theme,
    nodes: [
      HeadingNode,
      QuoteNode,
      ListNode,
      ListItemNode,
      CodeNode,
      CodeHighlightNode,
      LinkNode,
      HorizontalRuleNode,
      TableNode,
      TableCellNode,
      TableRowNode,
    ],
    onError: (error) => {
      throw error;
    },
    editorState: initial_markdown
      ? (editor: LexicalEditor) => {
          editor.update(() => {
            $convertFromMarkdownString(initial_markdown, TRANSFORMERS);
          });
        }
      : undefined,
  };

  const debounced_emit_change = useMemo(
    () => debounce((value: string) => on_value_change?.(value), 300),
    // eslint-disable-next-line react-hooks/exhaustive-deps
    []
  );

  useEffect(() => {
    return () => {
      debounced_emit_change.cancel();
    };
  }, [debounced_emit_change]);

  const handle_change = (editor_state: EditorState) => {
    if (!on_value_change) {
      return;
    }

    const extract_markdown_from_state = (editor_state: EditorState): string => {
      let markdown = '';

      editor_state.read(() => {
        markdown = $convertToMarkdownString(TRANSFORMERS);
      });

      return markdown;
    };

    debounced_emit_change(extract_markdown_from_state(editor_state));
  };

  return (
    <div className={class_name}>
      <LexicalComposer initialConfig={initial_config}>
        <div className={editor_outer_classes}>
          <ToolbarPlugin
            on_toggle_alpha_ol={() => set_alpha_ol_mode((v) => !v)}
          />
          <div
            className={`relative ${editor_container_classes} ${alpha_ol_mode ? alpha_ol_class : ''}`}
          >
            <RichTextPlugin
              contentEditable={
                <ContentEditable id={id} className={content_editable_classes} />
              }
              placeholder={
                <div className={placeholder_classes}>{placeholder}</div>
              }
              ErrorBoundary={LexicalErrorBoundary}
            />
            <HistoryPlugin />
            <ListPlugin />
            <LinkPlugin />
            <TablePlugin />
            <MarkdownShortcutPlugin transformers={TRANSFORMERS} />
            <CodeHighlighterPlugin />
            <OnChangePlugin onChange={handle_change} />
          </div>
        </div>
      </LexicalComposer>
    </div>
  );
}

export default MarkDownEditor;
