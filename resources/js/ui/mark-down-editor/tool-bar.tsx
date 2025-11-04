import { TOGGLE_LINK_COMMAND } from '@lexical/link';
import {
  INSERT_ORDERED_LIST_COMMAND,
  INSERT_UNORDERED_LIST_COMMAND,
} from '@lexical/list';
import { useLexicalComposerContext } from '@lexical/react/LexicalComposerContext';
import { INSERT_HORIZONTAL_RULE_COMMAND } from '@lexical/react/LexicalHorizontalRuleNode';
import { $createHeadingNode } from '@lexical/rich-text';
import { $setBlocksType } from '@lexical/selection';
import { INSERT_TABLE_COMMAND } from '@lexical/table';
import {
  FORMAT_TEXT_COMMAND,
  FORMAT_ELEMENT_COMMAND,
  $getSelection,
  $isRangeSelection,
} from 'lexical';
import React, { useMemo } from 'react';

import {
  toolbar_container_classes,
  toolbar_button_classes,
  toolbar_split_classes,
} from 'ui/mark-down-editor/styles/mark-down-editor-styles';

type ToolbarPluginProps = {
  on_toggle_alpha_ol: () => void;
};

function ToolbarPlugin(props: ToolbarPluginProps) {
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
      alpha_ol_toggle: () => on_toggle_alpha_ol(),
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
      table: () => {
        const rowsInput = window.prompt('Rows?', '2');
        const colsInput = window.prompt('Columns?', '2');

        const rowsNum = Math.max(1, Number(rowsInput ?? '2'));
        const colsNum = Math.max(1, Number(colsInput ?? '2'));

        const rows = Number.isFinite(rowsNum) ? rowsNum : 2;
        const columns = Number.isFinite(colsNum) ? colsNum : 2;

        editor.dispatchCommand(INSERT_TABLE_COMMAND, {
          rows: String(rows),
          columns: String(columns),
        });
      },
    }),
    [editor, on_toggle_alpha_ol]
  );

  return (
    <div className={toolbar_container_classes}>
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

      <span className={toolbar_split_classes} />

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

      <span className={toolbar_split_classes} />

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
        onClick={actions.alpha_ol_toggle}
      >
        <i className="fas fa-font" />
      </button>

      <span className={toolbar_split_classes} />

      <button
        type="button"
        className={toolbar_button_classes}
        onClick={actions.table}
      >
        <i className="fas fa-table" />
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
  );
}

export default ToolbarPlugin;
