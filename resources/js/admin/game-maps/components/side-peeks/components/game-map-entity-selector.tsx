import React, { ReactNode, useState } from 'react';

import GameMapEntitySelectorProps from '../types/game-map-entity-selector-props';

import Input from 'ui/input/input';

const GameMapEntitySelector = ({
  entities,
  search_label: searchLabel,
  empty_message: emptyMessage,
  on_select: onSelect,
}: GameMapEntitySelectorProps): ReactNode => {
  const [searchText, setSearchText] = useState('');

  const filteredEntities = entities.filter((entity) =>
    entity.label.toLowerCase().includes(searchText.trim().toLowerCase())
  );

  const renderEmptyState = (): ReactNode => {
    if (entities.length > 0) {
      return (
        <p className="text-glacier-600 dark:text-glacier-300 p-3 text-sm">
          No matching entries found.
        </p>
      );
    }

    return (
      <p className="text-glacier-600 dark:text-glacier-300 p-3 text-sm">
        {emptyMessage}
      </p>
    );
  };

  const renderEntities = (): ReactNode => {
    if (filteredEntities.length === 0) {
      return renderEmptyState();
    }

    return (
      <ul className="flex flex-col gap-2">
        {filteredEntities.map((entity) => (
          <li key={entity.id}>
            <button
              type="button"
              onClick={() => onSelect(entity.id)}
              className="focus-visible:ring-glacier-400 border-glacier-200 hover:bg-glacier-50 dark:border-glacier-700 dark:hover:bg-glacier-900 flex w-full items-center justify-between gap-2 rounded-md border p-2 text-left text-sm focus:outline-none focus-visible:ring-2"
            >
              <span className="text-glacier-900 dark:text-glacier-100">
                {entity.label}
              </span>
              <span className="text-glacier-600 dark:text-glacier-400">
                X {entity.x}, Y {entity.y}
              </span>
            </button>
          </li>
        ))}
      </ul>
    );
  };

  return (
    <div className="space-y-3">
      <label htmlFor="game-map-entity-selector-search" className="sr-only">
        {searchLabel}
      </label>
      <Input
        id="game-map-entity-selector-search"
        aria_label={searchLabel}
        value={searchText}
        on_change={setSearchText}
        place_holder={searchLabel}
        clearable
      />
      {renderEntities()}
    </div>
  );
};

export default GameMapEntitySelector;
