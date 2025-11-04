import React from 'react';

import ToolBarProps from 'ui/mark-down-editor/types/tool-bar-props';

const ToolbarButton = ({ label, onClick, isPrimary }: ToolBarProps) => {
  return (
    <button
      type="button"
      onClick={onClick}
      className={`rounded-md border px-2 py-1 text-sm transition ${
        isPrimary
          ? 'bg-danube-500 border-danube-600 hover:bg-danube-600 dark:bg-danube-600 dark:hover:bg-danube-700 text-white'
          : 'hover:bg-danube-50 border-gray-300 text-gray-800 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800/60'
      }`}
    >
      {label}
    </button>
  );
};

export default ToolbarButton;
