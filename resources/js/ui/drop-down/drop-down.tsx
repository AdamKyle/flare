import { isEmpty } from 'lodash';
import React, { ReactNode, useRef, useState, useEffect } from 'react';

import DropdownProps from 'ui/drop-down/types/drop-down-props';

const Dropdown = ({
  items,
  on_select,
  all_click_outside,
  on_clear,
}: DropdownProps) => {
  const [open, setOpen] = useState(false);
  const [selected, setSelected] = useState('');
  const [focusedIndex, setFocusedIndex] = useState<number | null>(null);
  const ref = useRef<HTMLDivElement>(null);
  const listRef = useRef<HTMLUListElement>(null);

  const handleBlur = (e: React.FocusEvent<HTMLDivElement>) => {
    if (!all_click_outside) return;
    if (!e.currentTarget.contains(e.relatedTarget)) {
      setOpen(false);
      setFocusedIndex(null);
    }
  };

  const handleKeyDown = (e: React.KeyboardEvent<HTMLDivElement>) => {
    if (!open) return;
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      setFocusedIndex((prev) =>
        prev === null || prev === items.length - 1 ? 0 : prev + 1
      );
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      setFocusedIndex((prev) =>
        prev === null || prev === 0 ? items.length - 1 : prev - 1
      );
    } else if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      if (focusedIndex !== null) {
        const item = items[focusedIndex];
        const [label] = Object.keys(item);
        setSelected(label);
        on_select(item);
        setOpen(false);
        setFocusedIndex(null);
      }
    } else if (e.key === 'Escape') {
      e.preventDefault();
      setOpen(false);
      setFocusedIndex(null);
    }
  };

  useEffect(() => {
    if (open && focusedIndex !== null && listRef.current) {
      const el = listRef.current.children[focusedIndex] as HTMLElement;
      el?.scrollIntoView({ block: 'nearest' });
    }
  }, [focusedIndex, open]);

  const renderDropdownIcon = (): ReactNode => {
    if (isEmpty(selected)) {
      return (
        <i className="fas fa-chevron-down text-gray-500 dark:text-gray-300"></i>
      );
    }

    return (
      <i
        className="fas fa-times text-gray-500 dark:text-gray-300 cursor-pointer"
        onClick={(e) => {
          e.stopPropagation();
          setSelected('');
          on_clear();
          setOpen(false);
        }}
      ></i>
    );
  };

  const renderItemsForDropdown = (): ReactNode => {
    return items.map((item, idx) => {
      const [label] = Object.keys(item);
      const itemId = `dropdown-item-${idx}`;
      return (
        <li
          key={idx}
          id={itemId}
          role="option"
          aria-selected={selected === label}
          tabIndex={-1}
          onClick={() => {
            setSelected(label);
            on_select(item);
            setOpen(false);
            setFocusedIndex(null);
          }}
          className={`px-4 py-2 cursor-pointer ${
            focusedIndex === idx ? 'bg-gray-200 dark:bg-gray-700 rounded-md' : ''
          } hover:bg-gray-200 dark:hover:bg-gray-700`}
        >
          {label}
        </li>
      );
    });
  };

  const renderDropdown = (): ReactNode => {
    if (!open) return null;

    return (
      <ul
        id="dropdown-listbox"
        role="listbox"
        ref={listRef}
        aria-activedescendant={
          focusedIndex !== null ? `dropdown-item-${focusedIndex}` : undefined
        }
        className="absolute z-50 w-full mt-1 max-h-60 overflow-auto border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-black dark:text-white"
      >
        {renderItemsForDropdown()}
      </ul>
    );
  };

  return (
    <div
      ref={ref}
      onBlur={handleBlur}
      tabIndex={0}
      onKeyDown={handleKeyDown}
      className="relative w-full"
    >
      <div
        tabIndex={0}
        role="button"
        aria-haspopup="listbox"
        aria-expanded={open}
        aria-controls="dropdown-listbox"
        onClick={() => setOpen((prev) => !prev)}
        className="w-full p-2 pr-10 pl-3 rounded-md border border-gray-500 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center relative"
      >
        <span className="flex-1 truncate">
          {selected || 'Select an option'}
        </span>
        <span className="absolute right-3 top-1/2 -translate-y-1/2">
          {renderDropdownIcon()}
        </span>
      </div>

      {renderDropdown()}
    </div>
  );
};

export default Dropdown;
