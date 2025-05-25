import clsx from 'clsx';
import React, {
  ReactNode,
  useRef,
  useState,
  useEffect,
  KeyboardEvent,
  FocusEvent,
  UIEvent,
  MouseEvent,
} from 'react';
import { match } from 'ts-pattern';

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
  is_in_modal,
  force_clear,
}: DropdownProps) => {
  const [isOpen, setIsOpen] = useState(false);
  const [selectedValue, setSelectedValue] = useState<string | number>('');
  const [focusedIndex, setFocusedIndex] = useState<number | null>(null);
  const containerRef = useRef<HTMLDivElement>(null);
  const listRef = useRef<HTMLUListElement>(null);

  const handleBlur = (event: FocusEvent<HTMLDivElement>) => {
    if (!all_click_outside) {
      return;
    }
    if (!event.currentTarget.contains(event.relatedTarget)) {
      setIsOpen(false);
      setFocusedIndex(null);
    }
  };

  const handleKeyDown = (event: KeyboardEvent<HTMLDivElement>) => {
    if (!isOpen) return;

    match(event.key as string)
      .with('ArrowDown', () => {
        event.preventDefault();
        setFocusedIndex((prev) =>
          prev === null || prev === items.length - 1 ? 0 : prev + 1
        );
      })
      .with('ArrowUp', () => {
        event.preventDefault();
        setFocusedIndex((prev) =>
          prev === null || prev === 0 ? items.length - 1 : prev - 1
        );
      })
      .with('Enter', ' ', () => {
        event.preventDefault();
        if (focusedIndex !== null) {
          const item = items[focusedIndex];
          setSelectedValue(item.value);
          on_select(item);
          setIsOpen(false);
          setFocusedIndex(null);
        }
      })
      .with('Escape', () => {
        event.preventDefault();
        setIsOpen(false);
        setFocusedIndex(null);
      })
      .otherwise(() => {});
  };

  useEffect(() => {
    if (isOpen && focusedIndex !== null && listRef.current) {
      const element = listRef.current.children[focusedIndex] as HTMLElement;
      element?.scrollIntoView({ block: 'nearest' });
    }
  }, [focusedIndex, isOpen]);

  useEffect(() => {
    if (!pre_selected_item) {
      return;
    }

    setSelectedValue(pre_selected_item.value);
  }, [pre_selected_item]);

  useEffect(
    () => {
      if (!force_clear) {
        return;
      }

      setSelectedValue('');

      if (on_clear) {
        on_clear();
      }
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [force_clear]
  );

  const onScroll = (event: UIEvent<HTMLDivElement>) => {
    if (handle_scroll) {
      handle_scroll(event);
    }
  };

  const handleClearSelection = (e: MouseEvent<HTMLElement>) => {
    e.stopPropagation();
    setSelectedValue('');
    setIsOpen(false);
    if (on_clear) {
      on_clear();
    }
  };

  const renderIcon = (): ReactNode =>
    selectedValue === '' ? (
      <i className="fas fa-chevron-down text-gray-500 dark:text-gray-300" />
    ) : (
      <i
        className="fas fa-times text-gray-500 dark:text-gray-300 cursor-pointer"
        onClick={handleClearSelection}
      />
    );

  const renderItems = (): ReactNode =>
    items.map((item, index) => (
      <li
        key={item.value + '-' + index}
        id={`dropdown-item-${index}`}
        role="option"
        aria-selected={selectedValue === item.value}
        tabIndex={-1}
        onClick={() => {
          setSelectedValue(item.value);
          on_select(item);
          setIsOpen(false);
          setFocusedIndex(null);
        }}
        className={clsx(
          'mx-1 my-1 px-4 py-4 cursor-pointer rounded-lg transition-colors duration-100',
          focusedIndex === index
            ? 'bg-gray-300 dark:bg-gray-700'
            : 'hover:bg-gray-300 dark:hover:bg-gray-800'
        )}
      >
        {item.label}
      </li>
    ));

  const renderSelectionText = (): ReactNode => {
    if (selectedValue) {
      const found = items.find((it) => it.value === selectedValue);
      return (
        <div className="text-gray-900 dark:text-white">{found?.label}</div>
      );
    }
    return (
      <div className="text-gray-400">
        {selection_placeholder || 'Select an option'}
      </div>
    );
  };

  const renderDropdownList = (): ReactNode => {
    if (!isOpen) return null;

    const listMarkup = (
      <ul
        id="dropdown-listbox"
        role="listbox"
        ref={listRef}
        aria-activedescendant={
          focusedIndex !== null ? `dropdown-item-${focusedIndex}` : undefined
        }
        className={clsx(
          'w-full text-black dark:text-white',
          !use_pagination && 'max-h-60 overflow-auto',
          !use_pagination &&
            'border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 rounded-md scrollbar-thin scrollbar-thumb-primary-300 scrollbar-track-primary-100 dark:scrollbar-thumb-primary-400 dark:scrollbar-track-primary-200 scrollbar-thumb-rounded-md'
        )}
      >
        {renderItems()}
      </ul>
    );

    const wrapperClasses = clsx(
      'absolute w-full mt-1',
      is_in_modal ? 'z-[9999]' : 'z-50',
      use_pagination &&
        'border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 rounded-md'
    );

    if (use_pagination) {
      return (
        <div className={wrapperClasses}>
          <InfiniteScroll
            handle_scroll={onScroll}
            additional_css={clsx(
              'max-h-60',
              additional_scroll_css,
              'scrollbar-thin scrollbar-thumb-primary-300 scrollbar-track-primary-100 dark:scrollbar-thumb-primary-400 dark:scrollbar-track-primary-200 scrollbar-thumb-rounded-md'
            )}
          >
            {listMarkup}
          </InfiniteScroll>
        </div>
      );
    }

    return <div className={wrapperClasses}>{listMarkup}</div>;
  };

  return (
    <div
      ref={containerRef}
      onBlur={handleBlur}
      tabIndex={0}
      onKeyDown={handleKeyDown}
      className={clsx('relative w-full', is_in_modal && 'overflow-visible')}
    >
      <div
        tabIndex={0}
        role="button"
        aria-haspopup="listbox"
        aria-expanded={isOpen}
        aria-controls="dropdown-listbox"
        onClick={() => setIsOpen((prev) => !prev)}
        className="w-full p-2 pr-10 pl-3 rounded-md border border-gray-500 dark:border-gray-700 bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center relative"
      >
        <span className="flex-1 truncate">{renderSelectionText()}</span>
        <span className="absolute right-3 top-1/2 -translate-y-1/2">
          {renderIcon()}
        </span>
      </div>
      {renderDropdownList()}
    </div>
  );
};

export default Dropdown;
