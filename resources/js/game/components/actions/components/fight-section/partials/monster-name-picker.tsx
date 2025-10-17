import clsx from 'clsx';
import React, { useEffect, useId, useMemo, useRef, useState } from 'react';

import MonsterNamePickerProps from '../types/partials/monster-name-picker-props';

const MonsterNamePicker = ({
  display_name,
  monsters,
  current_index,
  on_select,
}: MonsterNamePickerProps) => {
  const [isPickerOpen, setIsPickerOpen] = useState<boolean>(false);
  const [searchTerm, setSearchTerm] = useState<string>('');
  const [focusedListIndex, setFocusedListIndex] = useState<number | null>(null);

  const containerRef = useRef<HTMLDivElement>(null);
  const triggerButtonRef = useRef<HTMLButtonElement>(null);
  const searchInputRef = useRef<HTMLInputElement>(null);

  const listboxId = useId();
  const liveRegionId = useId();

  const filteredMonsters = useMemo(() => {
    const baseSearchTerm: string = String(searchTerm ?? '');
    const normalizedSearchTerm: string = baseSearchTerm.trim().toLowerCase();

    if (normalizedSearchTerm === '') {
      return monsters;
    }

    const startsWithMatches = monsters.filter((monster) =>
      monster.name.toLowerCase().startsWith(normalizedSearchTerm)
    );

    const substringMatches = monsters.filter(
      (monster) =>
        !monster.name.toLowerCase().startsWith(normalizedSearchTerm) &&
        monster.name.toLowerCase().includes(normalizedSearchTerm)
    );

    return [...startsWithMatches, ...substringMatches];
  }, [searchTerm, monsters]);

  useEffect(() => {
    if (!isPickerOpen) {
      return;
    }

    const currentMonsterName = monsters[current_index]?.name;

    const indexInFiltered = filteredMonsters.findIndex(
      (monster) => monster.name === currentMonsterName
    );

    setFocusedListIndex(indexInFiltered >= 0 ? indexInFiltered : 0);

    if (searchInputRef.current) {
      searchInputRef.current.focus();
    }
  }, [isPickerOpen, filteredMonsters, monsters, current_index]);

  const handleOpenPicker = () => {
    setIsPickerOpen(true);
  };

  const handleClosePicker = () => {
    setIsPickerOpen(false);
    setSearchTerm('');
    setFocusedListIndex(null);

    if (triggerButtonRef.current) {
      triggerButtonRef.current.focus();
    }
  };

  const handleContainerBlur = (event: React.FocusEvent<HTMLDivElement>) => {
    const nextFocusedElement = event.relatedTarget as Node | null;

    if (!nextFocusedElement) {
      handleClosePicker();

      return;
    }

    const containerElement = event.currentTarget;

    if (containerElement.contains(nextFocusedElement)) {
      return;
    }

    handleClosePicker();
  };

  const handleInputKeyDown = (event: React.KeyboardEvent<HTMLInputElement>) => {
    if (!isPickerOpen) {
      return;
    }

    const eventKey = event.key;

    if (eventKey === 'Escape') {
      event.preventDefault();

      handleClosePicker();

      return;
    }

    if (eventKey === 'ArrowDown') {
      event.preventDefault();

      setFocusedListIndex((previousIndex) => {
        if (
          previousIndex === null ||
          previousIndex === filteredMonsters.length - 1
        ) {
          return 0;
        }

        return previousIndex + 1;
      });

      return;
    }

    if (eventKey === 'ArrowUp') {
      event.preventDefault();

      setFocusedListIndex((previousIndex) => {
        if (previousIndex === null || previousIndex === 0) {
          return filteredMonsters.length - 1;
        }

        return previousIndex - 1;
      });

      return;
    }

    if (eventKey === 'Home') {
      event.preventDefault();

      if (filteredMonsters.length > 0) {
        setFocusedListIndex(0);
      }

      return;
    }

    if (eventKey === 'End') {
      event.preventDefault();

      if (filteredMonsters.length > 0) {
        setFocusedListIndex(filteredMonsters.length - 1);
      }

      return;
    }

    if (eventKey === 'Enter') {
      event.preventDefault();

      if (focusedListIndex === null) {
        return;
      }

      const focusedMonster = filteredMonsters[focusedListIndex];

      if (!focusedMonster) {
        return;
      }

      const indexInFullList = monsters.findIndex(
        (monster) => monster.name === focusedMonster.name
      );

      if (indexInFullList >= 0) {
        on_select(indexInFullList);
      }

      handleClosePicker();

      return;
    }
  };

  const handleSearchChange = (event: React.ChangeEvent<HTMLInputElement>) => {
    setSearchTerm(event.target.value);
  };

  const handleClickMonster = (monsterName: string) => {
    const indexInFullList = monsters.findIndex(
      (monster) => monster.name === monsterName
    );

    if (indexInFullList >= 0) {
      on_select(indexInFullList);
    }

    handleClosePicker();
  };

  const renderTriggerButton = () => {
    const chevronClasses = clsx(
      'fas fa-chevron-down',
      'text-gray-600 dark:text-gray-300',
      'transition-transform',
      isPickerOpen ? 'rotate-180' : 'rotate-0'
    );

    return (
      <button
        ref={triggerButtonRef}
        type="button"
        aria-haspopup="listbox"
        aria-expanded={isPickerOpen}
        onClick={handleOpenPicker}
        title="Change monster"
        className="group inline-flex items-center gap-2 font-semibold cursor-pointer focus:outline-none focus:ring-2 focus:ring-danube-500 rounded-sm px-1"
      >
        <span className="group-hover:underline">{display_name}</span>
        <i className={chevronClasses} aria-hidden="true"></i>
      </button>
    );
  };

  const renderDropdownList = () => {
    if (filteredMonsters.length === 0) {
      return (
        <li className="mx-1 my-1 px-3 py-2 text-sm text-gray-500 dark:text-gray-400 rounded-md">
          No matches
        </li>
      );
    }

    return filteredMonsters.map((monster, indexForFilteredList) => {
      const isFocused = focusedListIndex === indexForFilteredList;

      const listItemClasses = clsx(
        'mx-1 my-1 px-3 py-2 cursor-pointer rounded-md transition-colors duration-100 text-sm',
        isFocused
          ? 'bg-gray-300 dark:bg-gray-700'
          : 'hover:bg-gray-300 dark:hover:bg-gray-800'
      );

      return (
        <li
          key={monster.id + '-' + monster.name + '-' + indexForFilteredList}
          id={`monster-option-${indexForFilteredList}`}
          role="option"
          aria-selected={monsters[current_index]?.name === monster.name}
          onClick={() => handleClickMonster(monster.name)}
          className={listItemClasses}
        >
          {monster.name}
        </li>
      );
    });
  };

  const renderResultsLiveRegion = () => {
    if (!isPickerOpen) {
      return null;
    }

    const hasNoResults = filteredMonsters.length === 0;

    const announcementText = hasNoResults
      ? 'No results'
      : String(filteredMonsters.length) + ' results';

    return (
      <p id={liveRegionId} aria-live="polite" className="sr-only">
        {announcementText}
      </p>
    );
  };

  const renderDropdownPanel = () => {
    if (!isPickerOpen) {
      return null;
    }

    const activeDescendantId =
      focusedListIndex !== null
        ? `monster-option-${focusedListIndex}`
        : undefined;

    return (
      <div className="absolute left-1/2 -translate-x-1/2 mt-2 w-64 z-50 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 rounded-md shadow-lg">
        <div className="p-2">
          <input
            ref={searchInputRef}
            type="text"
            value={searchTerm}
            onChange={handleSearchChange}
            onKeyDown={handleInputKeyDown}
            role="combobox"
            aria-autocomplete="list"
            aria-expanded={isPickerOpen}
            aria-controls={listboxId}
            aria-activedescendant={activeDescendantId}
            aria-label="Search monsters"
            className="w-full rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <ul
          id={listboxId}
          role="listbox"
          className="max-h-60 overflow-auto px-1 pb-2 scrollbar-thin scrollbar-thumb-primary-300 scrollbar-track-primary-100 dark:scrollbar-thumb-primary-400 dark:scrollbar-track-primary-200 scrollbar-thumb-rounded-md"
        >
          {renderDropdownList()}
        </ul>

        {renderResultsLiveRegion()}
      </div>
    );
  };

  return (
    <div
      ref={containerRef}
      className="relative"
      tabIndex={0}
      onBlur={handleContainerBlur}
    >
      {renderTriggerButton()}
      {renderDropdownPanel()}
    </div>
  );
};

export default MonsterNamePicker;
