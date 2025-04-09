import React, { ReactNode } from 'react';

import BackPackProps from './types/backpack-props';
import { CharacterInventoryApiUrls } from '../api/enums/character-inventory-api-urls';
import { useGetCharacterInventory } from '../api/hooks/use-get-character-inventory';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const BackPack = ({ character_id }: BackPackProps): ReactNode => {
  const { data, error, loading } = useGetCharacterInventory({
    url: CharacterInventoryApiUrls.CHARACTER_INVENTORY,
    urlParams: { character: character_id },
  });

  if (error) {
    return null;
  }

  if (loading) {
    return null;
  }

  return (
    <div className="flex flex-col items-center gap-4">
      <Button
        on_click={() => {
          return data;
        }}
        label="Quest Items"
        variant={ButtonVariant.PRIMARY}
      />
      <hr className="w-full border-t border-gray-300 dark:border-gray-600" />
      <div className="w-full text-gray-800 dark:text-gray-200">
        Content Here ...
      </div>
    </div>
  );
};

export default BackPack;
