import React, { ReactNode } from 'react';

import { Position } from './enums/equipment-positions';
import { InventoryItemTypes } from './enums/inventory-item-types';
import EquippedSlot from './equipped-slot';
import InventorySectionProps from './types/inventory-section-props';
import { fetchEquippedArmour } from './utils/fetch-equipped-armour';
import { inventoryIconButtons } from './utils/inventory-icon-buttons';

import { MobileIconContainer } from 'ui/icon-container/mobile-icon-container';

const InventorySection = ({
  character_id,
}: InventorySectionProps): ReactNode => {
  // TODO: this is just temporary
  const data = { equipped: [] };

  return (
    <div className="relative">
      <MobileIconContainer
        icon_buttons={inventoryIconButtons({ character_id: character_id })}
      />

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
