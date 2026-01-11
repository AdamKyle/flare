import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode } from 'react';

import { CharacterEquippedApiUrls } from './api/enums/character-equipped-api-urls';
import useCharacterEquippedItemsApi from './api/hooks/use-character-equipped-items-api';
import EquippedSlots from './equipped-slots';
import { useOpenCharacterBackpack } from './hooks/use-open-character-backpack';
import { useOpenCharacterGemBag } from './hooks/use-open-character-gem-bag';
import { useOpenCharacterSets } from './hooks/use-open-character-sets';
import { useOpenCharacterUsableInventory } from './hooks/use-open-character-usable-inventory';
import InventorySectionProps from './types/inventory-section-props';
import { inventoryIconButtons } from './utils/inventory-icon-buttons';

import { GameDataError } from 'game-data/components/game-data-error';

import { shortenNumber } from 'game-utils/format-number';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import { MobileIconContainer } from 'ui/icon-container/mobile-icon-container';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import Separator from 'ui/separator/separator';

const InventorySection = ({
  character_id,
}: InventorySectionProps): ReactNode => {
  const { openBackpack } = useOpenCharacterBackpack();
  const { openUsableInventory } = useOpenCharacterUsableInventory({
    character_id,
  });
  const { openGemBag } = useOpenCharacterGemBag({ character_id });
  const { openSets } = useOpenCharacterSets({ character_id });

  const { data, loading, error } = useCharacterEquippedItemsApi({
    url: CharacterEquippedApiUrls.CHARACTER_EQUIPPED,
    urlParams: {
      character: character_id,
    },
  });

  if (error) {
    return <ApiErrorAlert apiError={error.message} />;
  }

  if (loading) {
    return (
      <div className="h-96">
        <InfiniteLoader />
      </div>
    );
  }

  if (!data) {
    return <GameDataError />;
  }

  return (
    <div className="relative">
      <MobileIconContainer
        icon_buttons={inventoryIconButtons({
          openBackpack,
          openUsableInventory,
          openGemBag,
          openSets,
        })}
      />

      <div className="flex justify-center">
        <EquippedSlots equipped_items={data.equipped_items} />
      </div>

      <div className={'mx-auto w-full md:w-3/5'}>
        <Separator />
        <h4 className={'text-mango-tango-700 dark:text-mango-tango-500'}>
          Basic Run Down
        </h4>

        <p className={'mt-2 mb-4 text-sm italic'}>
          Below is some basic details about your gear currently equipped.
        </p>
        <Separator />
        <p className={'mt-2 mb-4 text-sm italic'}>
          <strong>Currently Equipped</strong>:{' '}
          <span className={'text-mango-tango-700 dark:text-mango-tango-500'}>{data.set_name ?? 'Inventory Items'}</span>
        </p>
        <Dl>
          <Dt>Weapon Damage</Dt>
          <Dd>{shortenNumber(data.weapon_damage)}</Dd>
          <Dt>Spell Damage</Dt>
          <Dd>{shortenNumber(data.spell_damage)}</Dd>
          <Dt>Total Healing Amount</Dt>
          <Dd>{shortenNumber(data.healing_amount)}</Dd>
          <Dt>Total Defence (AC)</Dt>
          <Dd>{shortenNumber(data.defence_amount)}</Dd>
        </Dl>
      </div>
    </div>
  );
};

export default InventorySection;
