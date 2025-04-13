import React, { useRef } from 'react';

import InputProps from 'ui/input/types/input-props';

const Input = ({ on_change, clearable }: InputProps) => {
  const inputRef = useRef<HTMLInputElement>(null);

  const handleClear = () => {
    if (!inputRef.current) {
      return;
    }

    inputRef.current.value = '';
    on_change('');
  };

  const handleTextChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    on_change(e.target.value);
  };

  const renderClearable = () => {
    if (!clearable) {
      return null;
    }

    return (
      <button
        type="button"
        onClick={handleClear}
        className="absolute inset-y-0 right-2 flex items-center text-gray-500 hover:text-gray-700 focus:outline-none"
        aria-label="Clear input"
      >
        <i className="fas fa-times"></i>
      </button>
    );
  };

  return (
    <div className="relative w-full">
      <input
        ref={inputRef}
        type="text"
        className="w-full p-2 pr-8 rounded-md border border-gray-500 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
        placeholder="Type something..."
        aria-label="Input with clear button"
        onChange={handleTextChange}
      />
      {renderClearable()}
    </div>
  );
};

export default Input;
