import { TOGGLE_LINK_COMMAND } from '@lexical/link';
import {
  INSERT_ORDERED_LIST_COMMAND,
  INSERT_UNORDERED_LIST_COMMAND,
} from '@lexical/list';
import { useLexicalComposerContext } from '@lexical/react/LexicalComposerContext';
import { INSERT_HORIZONTAL_RULE_COMMAND } from '@lexical/react/LexicalHorizontalRuleNode';
import { $createHeadingNode, $createQuoteNode } from '@lexical/rich-text';
import { $setBlocksType } from '@lexical/selection';
import { $getSelection, $isRangeSelection, FORMAT_TEXT_COMMAND } from 'lexical';
import React, { useMemo } from 'react';

import {
  toolbar_button_classes,
  toolbar_container_classes,
} from 'ui/mark-down-editor/styles/mark-down-editor-styles';

const ToolbarPlugin = () => {
  const [editor] = useLexicalComposerContext();

  const actions = useMemo(
    () => ({
      bold: () => editor.dispatchCommand(FORMAT_TEXT_COMMAND, 'bold'),
      italic: () => editor.dispatchCommand(FORMAT_TEXT_COMMAND, 'italic'),

      h1: () =>
        editor.update(() => {
          const selection = $getSelection();
          if ($isRangeSelection(selection)) {
            $setBlocksType(selection, () => $createHeadingNode('h1'));
          }
        }),
      h2: () =>
        editor.update(() => {
          const selection = $getSelection();
          if ($isRangeSelection(selection)) {
            $setBlocksType(selection, () => $createHeadingNode('h2'));
          }
        }),
      h3: () =>
        editor.update(() => {
          const selection = $getSelection();
          if ($isRangeSelection(selection)) {
            $setBlocksType(selection, () => $createHeadingNode('h3'));
          }
        }),

      ul: () =>
        editor.dispatchCommand(INSERT_UNORDERED_LIST_COMMAND, undefined),
      ol: () => editor.dispatchCommand(INSERT_ORDERED_LIST_COMMAND, undefined),

      quote: () =>
        editor.update(() => {
          const selection = $getSelection();
          if ($isRangeSelection(selection)) {
            $setBlocksType(selection, () => $createQuoteNode());
          }
        }),

      hr: () =>
        editor.dispatchCommand(INSERT_HORIZONTAL_RULE_COMMAND, undefined),

      link: () => {
        const url = window.prompt('Enter URL');
        if (!url) {
          editor.dispatchCommand(TOGGLE_LINK_COMMAND, null);
          return;
        }
        editor.dispatchCommand(TOGGLE_LINK_COMMAND, url);
      },
    }),
    [editor]
  );

  return (
    <div
      className={`${toolbar_container_classes} flex flex-wrap items-center gap-2`}
    >
      <div role="group" aria-label="Inline" className="flex items-center gap-2">
        <button
          type="button"
          className={toolbar_button_classes}
          onClick={actions.bold}
        >
          <i className="fas fa-bold" />
        </button>
        <button
          type="button"
          className={toolbar_button_classes}
          onClick={actions.italic}
        >
          <i className="fas fa-italic" />
        </button>
      </div>

      <div
        role="group"
        aria-label="Blocks"
        className="flex items-center gap-2 lg:ml-4"
      >
        <button
          type="button"
          className={toolbar_button_classes}
          onClick={actions.h1}
        >
          <i className="fas fa-heading" />
          <span className="ml-1 text-[11px]">1</span>
        </button>
        <button
          type="button"
          className={toolbar_button_classes}
          onClick={actions.h2}
        >
          <i className="fas fa-heading" />
          <span className="ml-1 text-[11px]">2</span>
        </button>
        <button
          type="button"
          className={toolbar_button_classes}
          onClick={actions.h3}
        >
          <i className="fas fa-heading" />
          <span className="ml-1 text-[11px]">3</span>
        </button>

        <button
          type="button"
          className={toolbar_button_classes}
          onClick={actions.ul}
        >
          <i className="fas fa-list-ul" />
        </button>
        <button
          type="button"
          className={toolbar_button_classes}
          onClick={actions.ol}
        >
          <i className="fas fa-list-ol" />
        </button>

        <button
          type="button"
          className={toolbar_button_classes}
          onClick={actions.quote}
        >
          <i className="fas fa-quote-right" />
        </button>

        <button
          type="button"
          className={toolbar_button_classes}
          onClick={actions.hr}
        >
          <i className="fas fa-minus" />
        </button>

        <button
          type="button"
          className={toolbar_button_classes}
          onClick={actions.link}
        >
          <i className="fas fa-link" />
        </button>
      </div>
    </div>
  );
};

export default ToolbarPlugin;
