import React, { useRef } from 'react';

import InputProps from 'ui/input/types/input-props';

const Input = ({
  on_change,
  clearable,
  place_holder,
  value,
  disabled,
}: InputProps) => {
  const inputRef = useRef<HTMLInputElement>(null);

  const handleClear = () => {
    if (!inputRef.current) return;
    inputRef.current.value = '';
    on_change('');
  };

  const handleTextChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    on_change(e.target.value);
  };

  const renderClearableIcon = () => {
    if (!clearable) {
      return null;
    }

    return (
      <button
        type="button"
        onClick={handleClear}
        aria-label="Clear input"
        className="absolute top-1/2 right-3 -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none dark:hover:text-white"
        disabled={disabled}
      >
        <i className="fas fa-times"></i>
      </button>
    );
  };

  const placeHolder = place_holder ? place_holder : 'Type something ...';

  return (
    <div className="relative w-full">
      <input
        ref={inputRef}
        type="text"
        onChange={handleTextChange}
        aria-label="Input field"
        className="w-full rounded-sm border border-gray-500 bg-white p-2 pr-10 text-gray-900 focus:ring-2 focus:ring-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white"
        placeholder={placeHolder}
        value={value}
        disabled={disabled}
      />
      {renderClearableIcon()}
    </div>
  );
};

export default Input;
