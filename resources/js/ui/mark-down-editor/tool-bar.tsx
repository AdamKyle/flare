import { TOGGLE_LINK_COMMAND } from '@lexical/link';
import {
  INSERT_ORDERED_LIST_COMMAND,
  INSERT_UNORDERED_LIST_COMMAND,
} from '@lexical/list';
import { useLexicalComposerContext } from '@lexical/react/LexicalComposerContext';
import { INSERT_HORIZONTAL_RULE_COMMAND } from '@lexical/react/LexicalHorizontalRuleNode';
import { $createHeadingNode } from '@lexical/rich-text';
import { $setBlocksType } from '@lexical/selection';
import {
  FORMAT_TEXT_COMMAND,
  FORMAT_ELEMENT_COMMAND,
  INDENT_CONTENT_COMMAND,
  OUTDENT_CONTENT_COMMAND,
  $getSelection,
  $isRangeSelection,
} from 'lexical';
import React, { useMemo } from 'react';

import {
  toolbar_container_classes,
  toolbar_button_classes,
} from 'ui/mark-down-editor/styles/mark-down-editor-styles';

interface ToolbarPluginProps {
  on_toggle_alpha_ol: () => void;
}

const ToolbarPlugin = (props: ToolbarPluginProps) => {
  const { on_toggle_alpha_ol } = props;
  const [editor] = useLexicalComposerContext();

  const actions = useMemo(
    () => ({
      bold: () => editor.dispatchCommand(FORMAT_TEXT_COMMAND, 'bold'),
      italic: () => editor.dispatchCommand(FORMAT_TEXT_COMMAND, 'italic'),
      underline: () => editor.dispatchCommand(FORMAT_TEXT_COMMAND, 'underline'),
      strike: () =>
        editor.dispatchCommand(FORMAT_TEXT_COMMAND, 'strikethrough'),

      left: () => editor.dispatchCommand(FORMAT_ELEMENT_COMMAND, 'left'),
      center: () => editor.dispatchCommand(FORMAT_ELEMENT_COMMAND, 'center'),
      right: () => editor.dispatchCommand(FORMAT_ELEMENT_COMMAND, 'right'),
      justify: () => editor.dispatchCommand(FORMAT_ELEMENT_COMMAND, 'justify'),
      start: () => editor.dispatchCommand(FORMAT_ELEMENT_COMMAND, 'start'),
      end: () => editor.dispatchCommand(FORMAT_ELEMENT_COMMAND, 'end'),

      indent: () => editor.dispatchCommand(INDENT_CONTENT_COMMAND, undefined),
      outdent: () => editor.dispatchCommand(OUTDENT_CONTENT_COMMAND, undefined),

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

      ol: () => editor.dispatchCommand(INSERT_ORDERED_LIST_COMMAND, undefined),
      ul: () =>
        editor.dispatchCommand(INSERT_UNORDERED_LIST_COMMAND, undefined),

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
      <div
        role="group"
        aria-label="Formatting & Alignment"
        className="flex items-center gap-2"
      >
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
        <button
          type="button"
          className={toolbar_button_classes}
          onClick={actions.underline}
        >
          <i className="fas fa-underline" />
        </button>
        <button
          type="button"
          className={toolbar_button_classes}
          onClick={actions.strike}
        >
          <i className="fas fa-strikethrough" />
        </button>

        <button
          type="button"
          className={toolbar_button_classes}
          onClick={actions.left}
        >
          <i className="fas fa-align-left" />
        </button>
        <button
          type="button"
          className={toolbar_button_classes}
          onClick={actions.center}
        >
          <i className="fas fa-align-center" />
        </button>
        <button
          type="button"
          className={toolbar_button_classes}
          onClick={actions.right}
        >
          <i className="fas fa-align-right" />
        </button>
        <button
          type="button"
          className={toolbar_button_classes}
          onClick={actions.justify}
        >
          <i className="fas fa-align-justify" />
        </button>
        <button
          type="button"
          className={toolbar_button_classes}
          onClick={actions.start}
        >
          <i className="fas fa-align-left" />
        </button>
        <button
          type="button"
          className={toolbar_button_classes}
          onClick={actions.end}
        >
          <i className="fas fa-align-right" />
        </button>
      </div>

      <div
        role="group"
        aria-label="Structure & Insert"
        className="flex basis-full items-center gap-2 md:basis-auto lg:ml-4"
      >
        <button
          type="button"
          className={toolbar_button_classes}
          onClick={actions.indent}
        >
          <i className="fas fa-indent" />
        </button>
        <button
          type="button"
          className={toolbar_button_classes}
          onClick={actions.outdent}
        >
          <i className="fas fa-outdent" />
        </button>

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
