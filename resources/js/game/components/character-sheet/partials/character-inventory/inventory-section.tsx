import React, { ReactNode } from 'react';

import Backpack from './backpack';
import { Position } from './enums/equipment-positions';
import { InventoryItemTypes } from './enums/inventory-item-types';
import EquippedSlot from './equipped-slot';
import GemBag from './gem-bag';
import { useCharacterBackpackVisibility } from './hooks/use-character-backpack-visibility';
import { useCharacterGemBagVisibility } from './hooks/use-character-gem-bag-visibility';
import { useCharacterUsableInventoryVisibility } from './hooks/use-character-usable-inventory-visibility';
import InventorySectionProps from './types/inventory-section-props';
import UsableInventory from './usable-inventory';
import { fetchEquippedArmour } from './utils/fetch-equipped-armour';
import { inventoryIconButtons } from './utils/inventory-icon-buttons';
import { CharacterInventoryApiUrls } from '../../../side-peeks/character-inventory/api/enums/character-inventory-api-urls';
import { useGetCharacterInventory } from '../../../side-peeks/character-inventory/api/hooks/use-get-character-inventory';

import { GameDataError } from 'game-data/components/game-data-error';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import { MobileIconContainer } from 'ui/icon-container/mobile-icon-container';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const InventorySection = ({
  character_id,
}: InventorySectionProps): ReactNode => {
  const { showBackpack, closeBackpack } = useCharacterBackpackVisibility();
  const { showUsableInventory, closeUsableInventory } =
    useCharacterUsableInventoryVisibility();
  const { showGemBag, closeGemBag } = useCharacterGemBagVisibility();

  const { data, error, loading } = useGetCharacterInventory({
    url: CharacterInventoryApiUrls.CHARACTER_INVENTORY,
    urlParams: { character: character_id },
  });

  if (loading) {
    return <InfiniteLoader />;
  }

  if (error) {
    return <Alert variant={AlertVariant.DANGER}>{error.message}</Alert>;
  }

  if (data === null) {
    return <GameDataError />;
  }

  if (showBackpack) {
    return (
      <Backpack
        close_backpack={closeBackpack}
        inventory_items={data.inventory}
        quest_items={data.quest_items}
      />
    );
  }

  if (showUsableInventory) {
    return (
      <UsableInventory
        close_usable_Section={closeUsableInventory}
        usable_items={data.usable_items}
      />
    );
  }

  if (showGemBag) {
    return <GemBag close_gem_bag={closeGemBag} character_id={character_id} />;
  }

  return (
    <div className="relative">
      <MobileIconContainer icon_buttons={inventoryIconButtons()} />

      <div className="flex justify-center">
        <div className="flex items-center lg:p-4 space-x-4 w-full lg:w-3/4 md:justify-center">
          <div className="flex flex-col items-center space-y-4">
            <div>
              <EquippedSlot
                equipped_item={fetchEquippedArmour(
                  data.equipped,
                  InventoryItemTypes.HELMET
                )}
                positionName={'Helmet'}
                position={Position.HELMET}
              />
            </div>

            <div className="grid grid-cols-3 gap-4">
              <EquippedSlot
                equipped_item={fetchEquippedArmour(
                  data.equipped,
                  InventoryItemTypes.SLEEVES
                )}
                positionName={'Sleeves (Left)'}
                position={Position.SLEEVES_LEFT}
              />
              <EquippedSlot
                equipped_item={fetchEquippedArmour(
                  data.equipped,
                  InventoryItemTypes.BODY
                )}
                positionName={'Body'}
                position={Position.BODY}
              />
              <EquippedSlot
                equipped_item={fetchEquippedArmour(
                  data.equipped,
                  InventoryItemTypes.SLEEVES
                )}
                positionName={'Sleeves (Right)'}
                position={Position.SLEEVES_RIGHT}
              />
            </div>

            <div className="grid grid-cols-3 gap-4">
              <EquippedSlot
                equipped_item={fetchEquippedArmour(
                  data.equipped,
                  InventoryItemTypes.GLOVES
                )}
                positionName={'Gloves (Left)'}
                position={Position.GLOVES_LEFT}
              />
              <EquippedSlot
                equipped_item={fetchEquippedArmour(
                  data.equipped,
                  InventoryItemTypes.LEGGINGS
                )}
                positionName={'Leggings'}
                position={Position.LEGGINGS}
              />
              <EquippedSlot
                equipped_item={fetchEquippedArmour(
                  data.equipped,
                  InventoryItemTypes.GLOVES
                )}
                positionName={'Gloves (Right)'}
                position={Position.GLOVES_RIGHT}
              />
            </div>

            <div>
              <EquippedSlot
                equipped_item={fetchEquippedArmour(
                  data.equipped,
                  InventoryItemTypes.FEET
                )}
                positionName={'Feet'}
                position={Position.FEET}
              />
            </div>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <EquippedSlot
              equipped_item={undefined}
              positionName={'Weapon (Left Hand)'}
              position={Position.WEAPON_LEFT}
            />
            <EquippedSlot
              equipped_item={undefined}
              positionName={'Weapon (Right Hand)'}
              position={Position.WEAPON_RIGHT}
            />
            <EquippedSlot
              equipped_item={undefined}
              positionName={'Ring (Ring One)'}
              position={Position.RING_LEFT}
            />
            <EquippedSlot
              equipped_item={undefined}
              positionName={'Ring (Ring One)'}
              position={Position.RING_RIGHT}
            />
            <EquippedSlot
              equipped_item={undefined}
              positionName={'Spell (Spell One)'}
              position={Position.SPELL_LEFT}
            />
            <EquippedSlot
              equipped_item={undefined}
              positionName={'Spell (Spell Two)'}
              position={Position.SPELL_RIGHT}
            />
            <EquippedSlot
              equipped_item={undefined}
              positionName={'Trinket'}
              position={Position.TRINKET}
            />
          </div>
        </div>
      </div>
    </div>
  );
};

export default InventorySection;
