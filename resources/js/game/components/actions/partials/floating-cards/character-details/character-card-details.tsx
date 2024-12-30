import React, { ReactNode } from 'react';

import { useManageCharacterInventoryVisibility } from './hooks/use-manage-character-inventory-visibility';
import { useManageCharacterSheetVisibility } from '../../../../hooks/use-manage-character-sheet-visibility';
import XpBar from '../../../components/character-details/xp-bar';

import Button from 'ui/buttons/button';
import ProgressButton from 'ui/buttons/button-progress';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Separator from 'ui/seperatror/separator';

const CharacterCardDetails = (): ReactNode => {
  const { openCharacterSheet } = useManageCharacterSheetVisibility();
  const { openCharacterInventory } = useManageCharacterInventoryVisibility();

  return (
    <>
      <XpBar current_xp={150} max_xp={1000} />
      <div className="grid grid-cols-2 gap-2">
        <div>
          <h4 className="text-danube-500 dark:text-danube-700">Stats</h4>
          <Separator />
          <dl className="text-gray-600 dark:text-gray-700">
            <dt className="font-bold">Str</dt>
            <dd>1.0K</dd>
            <dt className="font-bold">Dex</dt>
            <dd>1.0K</dd>
            <dt className="font-bold">Int</dt>
            <dd>1.0K</dd>
            <dt className="font-bold">Agi</dt>
            <dd>1.0K</dd>
            <dt className="font-bold">Chr</dt>
            <dd>1.0K</dd>
            <dt className="font-bold">Focus</dt>
            <dd>1.0K</dd>
          </dl>
        </div>
        <div>
          <h4 className="text-danube-500 dark:text-danube-700">Health & Atk</h4>
          <Separator />
          <dl className="text-gray-600 dark:text-gray-700">
            <dt className="font-bold">HP</dt>
            <dd>100K</dd>
            <dt className="font-bold">ATK</dt>
            <dd>100K</dd>
            <dt className="font-bold">Healing</dt>
            <dd>100K</dd>
            <dt className="font-bold">Def</dt>
            <dd>100K</dd>
          </dl>
        </div>
      </div>
      <div className="my-4">
        <h4 className="text-danube-500 dark:text-danube-700">Currencies</h4>
        <Separator />
        <dl className="text-gray-600 dark:text-gray-700">
          <dt className="font-bold">Gold</dt>
          <dd>2.0T</dd>
          <dt className="font-bold">Gold Dust</dt>
          <dd>1.0M</dd>
          <dt className="font-bold">Shards</dt>
          <dd>1.0M</dd>
          <dt className="font-bold">Copper Coins</dt>
          <dd>1.0M</dd>
        </dl>
      </div>
      <Separator />
      <ProgressButton
        progress={10}
        on_click={() => openCharacterInventory()}
        label="Manage Inventory (56/75)"
        variant={ButtonVariant.SUCCESS}
        additional_css="w-full my-2"
      />
      <Button
        label="See More Details"
        on_click={() => {
          openCharacterSheet();
        }}
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full"
      />
    </>
  );
};

export default CharacterCardDetails;
