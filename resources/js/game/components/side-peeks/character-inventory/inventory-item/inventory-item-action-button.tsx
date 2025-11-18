import React, { useCallback } from 'react';

import DropdownButton from 'ui/buttons/drop-down-button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const InventoryItemActionButton = () => {
  const handleSell = useCallback((): void => {
    console.log('Sell');
  }, []);

  const handleList = useCallback((): void => {
    console.log('List');
  }, []);

  const handleDestroy = useCallback((): void => {
    console.log('Destroy');
  }, []);

  const handleDisenchant = useCallback((): void => {
    console.log('Disenchant');
  }, []);

  const itemClassName =
    'inline-flex w-full items-center justify-start rounded-md px-3 py-2 text-left transition-colors ' +
    'bg-gray-300 dark:bg-gray-500 hover:bg-gray-400 hover:text-gray-600 ' +
    'dark:bg-gray-400 dark:hover:bg-gray-400 hover:text-gray-800 text-gray-600 dark:text-gray-800 ' +
    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-400 dark:focus-visible:ring-gray-600 my-1';

  return (
    <DropdownButton label="Actions" variant={ButtonVariant.PRIMARY}>
      <button
        type="button"
        onClick={handleSell}
        className={itemClassName}
        role="menuitem"
      >
        Sell
      </button>
      <button
        type="button"
        onClick={handleList}
        className={itemClassName}
        role="menuitem"
      >
        List
      </button>
      <button
        type="button"
        onClick={handleDestroy}
        className={itemClassName}
        role="menuitem"
      >
        Destroy
      </button>
      <button
        type="button"
        onClick={handleDisenchant}
        className={itemClassName}
        role="menuitem"
      >
        Disenchant
      </button>
    </DropdownButton>
  );
};

export default InventoryItemActionButton;
