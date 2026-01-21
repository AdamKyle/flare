import clsx from 'clsx';
import React, {
  useRef,
  useState,
  useEffect,
  useMemo,
  KeyboardEvent,
  FocusEvent,
  UIEvent,
  MouseEvent,
} from 'react';
import { match } from 'ts-pattern';

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
  is_in_modal,
  force_clear,
  disabled,
  focus_selected_on_open,
}: DropdownProps) => {
  const [isOpen, setIsOpen] = useState(false);
  const [selectedValue, setSelectedValue] = useState<string | number>('');
  const [focusedIndex, setFocusedIndex] = useState<number | null>(null);
  const [searchTerm, setSearchTerm] = useState('');

  const containerRef = useRef<HTMLDivElement>(null);
  const listRef = useRef<HTMLUListElement>(null);
  const prevForceClearRef = useRef<boolean | undefined>(undefined);

  const displayItems: DropdownItem[] = useMemo(() => {
    const baseSearchTerm: string = String(searchTerm ?? '');
    const normalizedSearchTerm: string = baseSearchTerm.trim().toLowerCase();

    if (normalizedSearchTerm === '') {
      return items;
    }

    const startsWithMatches = items.filter((item) => {
      const labelText = String(item.label).toLowerCase();
      return labelText.startsWith(normalizedSearchTerm);
    });

    const substringMatches = items.filter((item) => {
      const labelText = String(item.label).toLowerCase();
      return (
        !labelText.startsWith(normalizedSearchTerm) &&
        labelText.includes(normalizedSearchTerm)
      );
    });

    return [...startsWithMatches, ...substringMatches];
  }, [items, searchTerm]);

  useEffect(() => {
    if (isOpen && focusedIndex !== null && listRef.current) {
      const element = listRef.current.children[focusedIndex] as HTMLElement;
      element?.scrollIntoView({ block: 'nearest' });
    }

    if (pre_selected_item && selectedValue === '') {
      setSelectedValue(pre_selected_item.value);
    }

    if (focusedIndex !== null && focusedIndex > displayItems.length - 1) {
      setFocusedIndex(displayItems.length > 0 ? displayItems.length - 1 : null);
    }

    const wasForceClear = prevForceClearRef.current === true;
    const isForceClear = Boolean(force_clear);

    if (!wasForceClear && isForceClear) {
      setSelectedValue('');

      if (on_clear) {
        on_clear();
      }
    }

    prevForceClearRef.current = isForceClear;
  }, [
    isOpen,
    focusedIndex,
    pre_selected_item,
    selectedValue,
    force_clear,
    on_clear,
    displayItems.length,
  ]);

  const handleBlur = (event: FocusEvent<HTMLDivElement>) => {
    if (!all_click_outside) {
      return;
    }

    if (!event.currentTarget.contains(event.relatedTarget)) {
      setIsOpen(false);
      setFocusedIndex(null);
      setSearchTerm('');
    }
  };

  const handleKeyDown = (event: KeyboardEvent<HTMLDivElement>) => {
    if (disabled) {
      return;
    }

    if (!isOpen) {
      return;
    }

    match(event.key as string)
      .with('ArrowDown', () => {
        event.preventDefault();
        setFocusedIndex((prev) =>
          prev === null || prev === displayItems.length - 1 ? 0 : prev + 1
        );
      })
      .with('ArrowUp', () => {
        event.preventDefault();
        setFocusedIndex((prev) =>
          prev === null || prev === 0 ? displayItems.length - 1 : prev - 1
        );
      })
      .with('Enter', ' ', () => {
        event.preventDefault();
        if (focusedIndex !== null) {
          const item = displayItems[focusedIndex];
          handleSelectItem(item);
        }
      })
      .with('Escape', () => {
        event.preventDefault();
        setIsOpen(false);
        setFocusedIndex(null);
        setSearchTerm('');
      })
      .otherwise(() => {});
  };

  const onScroll = (event: UIEvent<HTMLDivElement>) => {
    if (handle_scroll) {
      handle_scroll(event);
    }
  };

  const handleClearSelection = (mouseEvent: MouseEvent<HTMLElement>) => {
    if (disabled) {
      return;
    }

    mouseEvent.stopPropagation();
    setSelectedValue('');
    setIsOpen(false);

    if (on_clear) {
      on_clear();
    }
  };

  const handleTriggerClick = () => {
    if (disabled) {
      return;
    }

    setIsOpen((previousOpen) => {
      const nextOpen = !previousOpen;

      if (
        nextOpen &&
        focus_selected_on_open &&
        (selectedValue !== '' || pre_selected_item)
      ) {
        const valueToFind =
          selectedValue !== '' ? selectedValue : pre_selected_item?.value;

        const indexToFocus = displayItems.findIndex(
          (it) => it.value === valueToFind
        );

        setFocusedIndex(indexToFocus >= 0 ? indexToFocus : 0);
      }

      if (!nextOpen) {
        setSearchTerm('');
      }

      return nextOpen;
    });
  };

  const handleSelectItem = (item: DropdownItem) => {
    setSelectedValue(item.value);
    on_select(item);
    setIsOpen(false);
    setFocusedIndex(null);
    setSearchTerm('');
  };

  const renderIcon = () => {
    if (disabled) {
      return (
        <i className="fas fa-chevron-down text-gray-400 dark:text-gray-500" />
      );
    }

    return selectedValue === '' ? (
      <i className="fas fa-chevron-down text-gray-500 dark:text-gray-300" />
    ) : (
      <i
        className="fas fa-times cursor-pointer text-gray-500 dark:text-gray-300"
        onClick={handleClearSelection}
      />
    );
  };

  const renderItems = () =>
    displayItems.map((item, index) => (
      <li
        key={item.value + '-' + index}
        id={`dropdown-item-${index}`}
        role="option"
        aria-selected={selectedValue === item.value}
        tabIndex={-1}
        onClick={() => handleSelectItem(item)}
        className={clsx(
          'mx-1 my-1 cursor-pointer rounded-lg px-4 py-4 transition-colors duration-100',
          focusedIndex === index
            ? 'bg-gray-300 dark:bg-gray-700'
            : 'hover:bg-gray-300 dark:hover:bg-gray-800'
        )}
      >
        {item.label}
      </li>
    ));

  const renderSelectionText = () => {
    const current =
      selectedValue !== ''
        ? items.find((it) => it.value === selectedValue)
        : pre_selected_item;

    if (current) {
      return (
        <div className="text-gray-900 dark:text-white">{current.label}</div>
      );
    }

    return (
      <div
        className={clsx(
          disabled ? 'text-gray-400 dark:text-gray-500' : 'text-gray-400'
        )}
      >
        {selection_placeholder || 'Select an option'}
      </div>
    );
  };

  const renderDropdownList = () => {
    if (disabled || !isOpen) {
      return null;
    }

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
            'scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-200 dark:scrollbar-thumb-gray-600 dark:scrollbar-track-gray-800 scrollbar-thumb-rounded-md'
        )}
      >
        {renderItems()}
      </ul>
    );

    const wrapperClasses = clsx(
      'absolute w-full mt-2 border border-gray-500 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-md',
      is_in_modal ? 'z-[9999]' : 'z-50'
    );

    if (use_pagination) {
      return (
        <div className={wrapperClasses}>
          <div className="p-2">
            <input
              type="text"
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              placeholder="Search..."
              aria-label="Search"
              className="w-full rounded-sm border border-solid border-gray-500 bg-transparent px-3 py-2 text-sm text-gray-500 focus:ring-2 focus:ring-blue-500 focus:outline-none dark:border-gray-300 dark:text-gray-300"
            />
          </div>
          <InfiniteScroll
            handle_scroll={onScroll}
            additional_css={clsx(
              'max-h-60',
              additional_scroll_css,
              'scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-200 dark:scrollbar-thumb-gray-600 dark:scrollbar-track-gray-800 scrollbar-thumb-rounded-md'
            )}
          >
            {listMarkup}
          </InfiniteScroll>
        </div>
      );
    }

    return (
      <div className={wrapperClasses}>
        <div className="p-2">
          <input
            type="text"
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            placeholder="Search..."
            aria-label="Search"
            className="my-2 w-full rounded-md border-1 border-gray-500 bg-transparent px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none dark:border-gray-600"
          />
        </div>
        {listMarkup}
      </div>
    );
  };

  return (
    <div
      ref={containerRef}
      onBlur={handleBlur}
      tabIndex={disabled ? -1 : 0}
      onKeyDown={disabled ? undefined : handleKeyDown}
      className={clsx('relative w-full', is_in_modal && 'overflow-visible')}
    >
      <div
        tabIndex={disabled ? -1 : 0}
        role="button"
        aria-haspopup="listbox"
        aria-disabled={disabled || undefined}
        aria-expanded={disabled ? false : isOpen}
        aria-controls={disabled ? undefined : 'dropdown-listbox'}
        onClick={disabled ? undefined : handleTriggerClick}
        className={clsx(
          'relative flex w-full items-center rounded-md border p-2 pr-10 pl-3',
          disabled
            ? 'cursor-not-allowed border-gray-300 bg-gray-100 text-gray-400 opacity-80 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-500'
            : 'border-gray-500 bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800'
        )}
      >
        <span className="flex-1 truncate">{renderSelectionText()}</span>
        <span className="absolute top-1/2 right-3 -translate-y-1/2">
          {renderIcon()}
        </span>
      </div>

      {renderDropdownList()}
    </div>
  );
};

export default Dropdown;
