import clsx from 'clsx';
import React, {
  KeyboardEvent,
  MouseEvent,
  UIEvent,
  useCallback,
  useEffect,
  useLayoutEffect,
  useMemo,
  useRef,
  useState,
  useId,
} from 'react';
import { createPortal } from 'react-dom';
import { match } from 'ts-pattern';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import DropdownProps from 'ui/drop-down/types/drop-down-props';
import InfiniteScroll from 'ui/infinite-scroll/infinite-scroll';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const MIN_MENU_HEIGHT = 200;
const MAX_MENU_HEIGHT = 384;
const VIEWPORT_MARGIN = 8;
const FLOATING_Z_INDEX = 100001;

const filterDropdownItems = (
  items: DropdownItem[],
  searchTerm: string
): DropdownItem[] => {
  const normalizedSearchTerm = String(searchTerm ?? '')
    .trim()
    .toLowerCase();

  if (normalizedSearchTerm === '') {
    return items;
  }

  const startsWithMatches = items.filter((item) =>
    String(item.label).toLowerCase().startsWith(normalizedSearchTerm)
  );

  const substringMatches = items.filter((item) => {
    const labelText = String(item.label).toLowerCase();
    return (
      !labelText.startsWith(normalizedSearchTerm) &&
      labelText.includes(normalizedSearchTerm)
    );
  });

  return [...startsWithMatches, ...substringMatches];
};

const Dropdown = ({
  items,
  on_select,
  on_clear,
  selection_placeholder,
  pre_selected_item,
  force_clear,
  disabled,
  focus_selected_on_open,
  id,
  aria_label,
  aria_labelled_by,
  header_slot,
  searchable,
  search_value,
  on_search,
  can_load_more,
  is_loading_more,
  on_end_reached,
  empty_message,
  search_placeholder,
}: DropdownProps) => {
  const generatedId = useId().replace(/:/g, '');
  const triggerId = id ?? `dropdown-trigger-${generatedId}`;
  const listboxId = `dropdown-listbox-${generatedId}`;
  const searchId = `dropdown-search-${generatedId}`;
  const [isOpen, setIsOpen] = useState(false);
  const [selectedValue, setSelectedValue] = useState<string | number>('');
  const [focusedIndex, setFocusedIndex] = useState<number | null>(null);
  const [internalSearchTerm, setInternalSearchTerm] = useState('');
  const [menuStyle, setMenuStyle] = useState<React.CSSProperties>({
    position: 'fixed',
    visibility: 'hidden',
  });

  const containerRef = useRef<HTMLDivElement>(null);
  const triggerRef = useRef<HTMLButtonElement>(null);
  const listRef = useRef<HTMLUListElement>(null);
  const menuRef = useRef<HTMLDivElement>(null);
  const prevForceClearRef = useRef<boolean | undefined>(undefined);

  const isSearchControlled = typeof on_search === 'function';
  const searchTerm = isSearchControlled
    ? (search_value ?? '')
    : internalSearchTerm;
  const isSearchable = Boolean(searchable);
  const isEndReachable = typeof on_end_reached === 'function';

  const displayItems: DropdownItem[] = useMemo(() => {
    if (isSearchControlled || !isSearchable) {
      return items;
    }

    return filterDropdownItems(items, internalSearchTerm);
  }, [items, isSearchControlled, isSearchable, internalSearchTerm]);

  const recalculatePosition = useCallback(() => {
    if (!containerRef.current) {
      return;
    }

    const rect = containerRef.current.getBoundingClientRect();
    const spaceBelow = window.innerHeight - rect.bottom - VIEWPORT_MARGIN;
    const spaceAbove = rect.top - VIEWPORT_MARGIN;
    const flipUp = spaceBelow < MIN_MENU_HEIGHT && spaceAbove > spaceBelow;
    const availableSpace = flipUp ? spaceAbove : spaceBelow;
    const maxHeight = Math.max(
      Math.min(availableSpace, MAX_MENU_HEIGHT),
      Math.min(availableSpace, MIN_MENU_HEIGHT)
    );

    if (flipUp) {
      setMenuStyle({
        position: 'fixed',
        bottom: window.innerHeight - rect.top + VIEWPORT_MARGIN,
        left: rect.left,
        width: rect.width,
        maxHeight,
        zIndex: FLOATING_Z_INDEX,
      });

      return;
    }

    setMenuStyle({
      position: 'fixed',
      top: rect.bottom + VIEWPORT_MARGIN,
      left: rect.left,
      width: rect.width,
      maxHeight,
      zIndex: FLOATING_Z_INDEX,
    });
  }, []);

  const closeMenu = useCallback(() => {
    setIsOpen(false);
    setFocusedIndex(null);
    setInternalSearchTerm('');
  }, []);

  useLayoutEffect(() => {
    if (!isOpen) {
      return;
    }

    recalculatePosition();
  }, [isOpen, recalculatePosition]);

  useEffect(() => {
    if (!isOpen) {
      return;
    }

    const handleResize = () => recalculatePosition();

    const handleScroll = (event: Event) => {
      const target = event.target as Node | null;

      if (target && menuRef.current?.contains(target)) {
        return;
      }

      closeMenu();
    };

    window.addEventListener('resize', handleResize);
    window.addEventListener('scroll', handleScroll, true);

    let resizeObserver: ResizeObserver | undefined;

    if (containerRef.current && typeof ResizeObserver !== 'undefined') {
      resizeObserver = new ResizeObserver(handleResize);
      resizeObserver.observe(containerRef.current);
    }

    return () => {
      window.removeEventListener('resize', handleResize);
      window.removeEventListener('scroll', handleScroll, true);
      resizeObserver?.disconnect();
    };
  }, [isOpen, recalculatePosition, closeMenu]);

  useEffect(() => {
    if (!isOpen) {
      return;
    }

    const handlePointerDown = (event: PointerEvent) => {
      const target = event.target as Node;

      if (containerRef.current?.contains(target)) {
        return;
      }

      if (menuRef.current?.contains(target)) {
        return;
      }

      closeMenu();
    };

    document.addEventListener('pointerdown', handlePointerDown);

    return () => {
      document.removeEventListener('pointerdown', handlePointerDown);
    };
  }, [isOpen, closeMenu]);

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

  const handleKeyDown = (event: KeyboardEvent<HTMLElement>) => {
    if (disabled) {
      return;
    }

    if (!isOpen) {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        setIsOpen(true);
        return;
      }

      if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
        event.preventDefault();
        setIsOpen(true);
        setFocusedIndex(
          event.key === 'ArrowDown' ? 0 : Math.max(displayItems.length - 1, 0)
        );
      }

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
        closeMenu();
        triggerRef.current?.focus();
      })
      .with('Tab', () => {
        closeMenu();
      })
      .otherwise(() => {});
  };

  const handleSearchChange = (value: string) => {
    if (isSearchControlled) {
      on_search?.(value);
      return;
    }

    setInternalSearchTerm(value);
  };

  const handleEndReachedScroll = (event: UIEvent<HTMLDivElement>) => {
    if (!isEndReachable || !can_load_more || is_loading_more) {
      return;
    }

    const { scrollTop, scrollHeight, clientHeight } = event.currentTarget;

    if (scrollTop + clientHeight >= scrollHeight - 10) {
      on_end_reached?.();
    }
  };

  const handleClearSelection = (mouseEvent: MouseEvent<HTMLButtonElement>) => {
    if (disabled) {
      return;
    }

    mouseEvent.stopPropagation();
    setSelectedValue('');
    closeMenu();

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
        setInternalSearchTerm('');
      }

      return nextOpen;
    });
  };

  const handleSelectItem = (item: DropdownItem) => {
    setSelectedValue(item.value);
    on_select(item);
    closeMenu();
  };

  const renderIcon = () => (
    <i
      aria-hidden="true"
      className={clsx(
        'fas fa-chevron-down',
        disabled
          ? 'text-gray-400 dark:text-gray-500'
          : 'text-gray-500 dark:text-gray-300'
      )}
    />
  );

  const renderItems = () =>
    displayItems.map((item, index) => (
      <li
        key={item.value + '-' + index}
        id={`${listboxId}-option-${index}`}
        role="option"
        aria-selected={selectedValue === item.value}
        tabIndex={-1}
        onClick={() => handleSelectItem(item)}
        className={clsx(
          'mx-1 my-1 cursor-pointer rounded-lg px-4 py-3 break-words whitespace-normal transition-colors duration-100',
          focusedIndex === index
            ? 'bg-gray-300 dark:bg-gray-700'
            : 'hover:bg-gray-300 dark:hover:bg-gray-800',
          item.class_name
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
        <span className={current.class_name ?? 'text-gray-900 dark:text-white'}>
          {current.label}
        </span>
      );
    }

    return (
      <span
        className={clsx(
          disabled ? 'text-gray-400 dark:text-gray-500' : 'text-gray-400'
        )}
      >
        {selection_placeholder || 'Select an option'}
      </span>
    );
  };

  const renderSearchInput = () => {
    if (!isSearchable) {
      return null;
    }

    return (
      <div className="border-b border-gray-200 p-2 dark:border-gray-700">
        <input
          id={searchId}
          type="text"
          value={searchTerm}
          onChange={(e) => handleSearchChange(e.target.value)}
          placeholder={search_placeholder ?? 'Search...'}
          aria-label={search_placeholder ?? 'Search'}
          className="w-full rounded-md border border-gray-500 bg-transparent px-3 py-2 text-sm text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:outline-none dark:border-gray-600 dark:text-gray-100 dark:placeholder-gray-400"
        />
      </div>
    );
  };

  const renderLoadingMore = () => {
    if (!is_loading_more) {
      return null;
    }

    return (
      <div className="px-2" role="status" aria-live="polite">
        <InfiniteLoader />
      </div>
    );
  };

  const renderList = () => (
    <ul
      id={listboxId}
      role="listbox"
      ref={listRef}
      className="w-full text-black dark:text-white"
    >
      {header_slot}
      {displayItems.length === 0 ? (
        <li className="px-4 py-3 text-gray-600 dark:text-gray-300">
          {empty_message ?? 'No options available.'}
        </li>
      ) : (
        renderItems()
      )}
    </ul>
  );

  const renderMenuBody = () => {
    if (isEndReachable) {
      return (
        <InfiniteScroll
          handle_scroll={handleEndReachedScroll}
          additional_css="scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-200 dark:scrollbar-thumb-gray-600 dark:scrollbar-track-gray-800 scrollbar-thumb-rounded-md"
        >
          {renderList()}
          {renderLoadingMore()}
        </InfiniteScroll>
      );
    }

    return (
      <div className="scrollbar-thumb-rounded-md max-h-60 scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-200 overflow-auto dark:scrollbar-thumb-gray-600 dark:scrollbar-track-gray-800">
        {renderList()}
      </div>
    );
  };

  const renderDropdownList = () => {
    if (disabled || !isOpen) {
      return null;
    }

    return createPortal(
      <div
        ref={menuRef}
        style={{ ...menuStyle, display: 'flex', flexDirection: 'column' }}
        onKeyDown={handleKeyDown}
        className="overflow-hidden rounded-md border border-gray-500 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"
      >
        {renderSearchInput()}
        {renderMenuBody()}
      </div>,
      document.body
    );
  };

  return (
    <div ref={containerRef} className="relative w-full min-w-0">
      <div className="relative flex w-full items-center">
        <button
          ref={triggerRef}
          id={triggerId}
          type="button"
          disabled={disabled}
          aria-haspopup="listbox"
          aria-expanded={disabled ? false : isOpen}
          aria-controls={disabled ? undefined : listboxId}
          aria-label={
            aria_label ??
            (aria_labelled_by
              ? undefined
              : (selection_placeholder ?? 'Select an option'))
          }
          aria-labelledby={aria_labelled_by}
          aria-activedescendant={
            isOpen && focusedIndex !== null
              ? `${listboxId}-option-${focusedIndex}`
              : undefined
          }
          onClick={disabled ? undefined : handleTriggerClick}
          onKeyDown={disabled ? undefined : handleKeyDown}
          className={clsx(
            'relative flex w-full min-w-0 items-center rounded-md border p-2 pr-16 pl-3 text-left',
            disabled
              ? 'cursor-not-allowed border-gray-300 bg-gray-100 text-gray-400 opacity-80 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-500'
              : 'border-gray-500 bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800'
          )}
        >
          <span className="min-w-0 flex-1 truncate text-left">
            {renderSelectionText()}
          </span>
          <span className="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2">
            {renderIcon()}
          </span>
        </button>
        {selectedValue !== '' && !disabled && (
          <button
            type="button"
            aria-label="Clear selection"
            onClick={handleClearSelection}
            className="absolute top-1/2 right-9 z-10 -translate-y-1/2 rounded p-2 text-gray-500 hover:text-gray-900 focus:ring-2 focus:ring-blue-500 focus:outline-none dark:text-gray-300 dark:hover:text-white"
          >
            <i aria-hidden="true" className="fas fa-times" />
          </button>
        )}
      </div>

      {renderDropdownList()}
    </div>
  );
};

export default Dropdown;
