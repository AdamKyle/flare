import clsx from 'clsx';
import { isEmpty } from 'lodash';
import React, { ReactNode, useRef, useState, useEffect } from 'react';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import DropdownProps from 'ui/drop-down/types/drop-down-props';
import InfiniteScroll from 'ui/infinite-scroll/infinite-scroll';

const Dropdown = ({
  items,
  on_select,
  all_click_outside,
  on_clear,
  selection_placeholder,
  use_pagination,
  handle_scroll,
  additional_scroll_css,
  pre_selected_item,
}: DropdownProps) => {
  const [open, setOpen] = useState(false);
  const [selected, setSelected] = useState<string | number>('');
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

  useEffect(() => {
    if (!pre_selected_item) {
      return;
    }

    setSelected(pre_selected_item.value);
  }, [pre_selected_item]);

  const onScroll = (e: React.UIEvent<HTMLDivElement>) => {
    if (!handle_scroll) {
      return;
    }

    handle_scroll(e);
  };

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
    return items.map((item: DropdownItem, idx) => {
      const itemId = `dropdown-item-${idx}`;
      return (
        <li
          key={idx}
          id={itemId}
          role="option"
          aria-selected={selected === item.value}
          tabIndex={-1}
          onClick={() => {
            setSelected(item.value);
            on_select(item);
            setOpen(false);
            setFocusedIndex(null);
          }}
          className={clsx(
            'mx-1 my-1 px-4 py-4 cursor-pointer rounded-lg transition-colors duration-100',
            focusedIndex === idx
              ? 'bg-gray-300 dark:bg-gray-700'
              : 'hover:bg-gray-300 dark:hover:bg-gray-800'
          )}
        >
          {item.label}
        </li>
      );
    });
  };

  const renderSelectedItem = () => {
    const found = items.find((item) => item.value === selected);
    return found ? found.label : '';
  };

  const renderDropdown = (): ReactNode => {
    if (!open) return null;

    const list = (
      <ul
        id="dropdown-listbox"
        role="listbox"
        ref={listRef}
        aria-activedescendant={
          focusedIndex !== null ? `dropdown-item-${focusedIndex}` : undefined
        }
        className="w-full max-h-60 overflow-auto text-black dark:text-white"
      >
        {renderItemsForDropdown()}
      </ul>
    );

    if (use_pagination) {
      return (
        <div className="absolute z-50 w-full mt-1 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 rounded-md">
          <InfiniteScroll
            handle_scroll={onScroll}
            additional_css={clsx(
              'max-h-60',
              additional_scroll_css,
              'scrollbar-thin scrollbar-thumb-primary-300 scrollbar-track-primary-100 dark:scrollbar-thumb-primary-400 dark:scrollbar-track-primary-200 scrollbar-thumb-rounded-md'
            )}
          >
            {list}
          </InfiniteScroll>
        </div>
      );
    }

    return (
      <ul
        id="dropdown-listbox"
        role="listbox"
        ref={listRef}
        aria-activedescendant={
          focusedIndex !== null ? `dropdown-item-${focusedIndex}` : undefined
        }
        className="absolute z-50 w-full mt-1 max-h-60 overflow-auto border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-black dark:text-white rounded-md scrollbar-thin scrollbar-thumb-primary-300 scrollbar-track-primary-100 dark:scrollbar-thumb-primary-400 dark:scrollbar-track-primary-200 scrollbar-thumb-rounded-md"
      >
        {renderItemsForDropdown()}
      </ul>
    );
  };

  const renderSelectionText = () => {
    if (selected) {
      return (
        <div className="text-gray-900 dark:text-white">
          {renderSelectedItem()}
        </div>
      );
    }

    if (selection_placeholder) {
      return <div className="text-gray-400">{selection_placeholder}</div>;
    }

    return <div className="text-gray-400">Select an option</div>;
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
        className="w-full p-2 pr-10 pl-3 rounded-md border border-gray-500 dark:border-gray-700 bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center relative"
      >
        <span className="flex-1 truncate">{renderSelectionText()}</span>
        <span className="absolute right-3 top-1/2 -translate-y-1/2">
          {renderDropdownIcon()}
        </span>
      </div>

      {renderDropdown()}
    </div>
  );
};

export default Dropdown;
