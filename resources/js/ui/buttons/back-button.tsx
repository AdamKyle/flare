import React from 'react';

import BackButtonProps from 'ui/buttons/types/back-button-props';

const BackButton = ({ title, handle_back }: BackButtonProps) => {
  return (
    <div className="flex items-center space-x-2">
      <button
        className="hover:bg-gray-300 dark:hover:bg-gray-700 dark:text-gray-300 dark:hover:text-gray-800 rounded-full transition duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500 p-2"
        aria-label="Back to inventory"
        onClick={() => handle_back()}
      >
        <i className="fas fa-arrow-left"></i>
      </button>
      <span className="text-gray-800 dark:text-gray-200 whitespace-nowrap">
        {title}
      </span>
    </div>
  );
};

export default BackButton;
