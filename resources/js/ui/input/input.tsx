import React from 'react';
import InputProps from "ui/input/types/input-props";

const Input = ({on_change, value}: InputProps) => {
  return (
    <input
      type="text"
      className="w-full p-2 pr-8 rounded-md border border-gray-500 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
      placeholder="Type something..."
      aria-label="Input with clear button"
      onChange={on_change}
      value={value}
    />
  )
}

export default Input;