import React, { ReactNode } from 'react';

import GameMapEditorToolbarProps from '../types/game-map-editor-toolbar-props';

const GameMapEditorToolbar = ({
  selected_label,
  on_reset_view,
}: GameMapEditorToolbarProps): ReactNode => {
  return (
    <div className="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-md border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900">
      <p
        className="text-sm text-gray-700 dark:text-gray-300"
        aria-live="polite"
      >
        {selected_label
          ? `Selected coordinate: ${selected_label}`
          : 'No coordinate selected.'}
      </p>
      <button
        type="button"
        onClick={on_reset_view}
        className="focus:ring-danube-500 rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 focus:ring-2 focus:outline-none dark:border-gray-600 dark:text-gray-200"
      >
        Reset View
      </button>
    </div>
  );
};

export default GameMapEditorToolbar;
