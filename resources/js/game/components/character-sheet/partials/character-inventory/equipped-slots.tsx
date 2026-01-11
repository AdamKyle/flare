import React from 'react';

import { Position } from './enums/equipment-positions';
import { InventoryItemTypes } from './enums/inventory-item-types';
import EquippedSlot from './equipped-slot';
import EquippedSlotsProps from './types/equipped-slots-props';
import { fetchEquippedItemForSlot } from './utils/fetch-equipped-armour';

const EquippedSlots = ({ equipped_items }: EquippedSlotsProps) => {
  return (
    <div className="flex w-full flex-col items-center gap-4 sm:flex-row md:justify-center lg:w-3/4 lg:p-4">
      <div className="flex flex-col items-center space-y-4">
        <div>
          <EquippedSlot
            equipped_item={fetchEquippedItemForSlot(
              equipped_items,
              InventoryItemTypes.HELMET
            )}
            positionName={'Helmet'}
            position={Position.HELMET}
          />
        </div>

        <div className="grid grid-cols-3 gap-4">
          <EquippedSlot
            equipped_item={fetchEquippedItemForSlot(
              equipped_items,
              InventoryItemTypes.SLEEVES
            )}
            positionName={'Sleeves (Left)'}
            position={Position.SLEEVES_LEFT}
          />
          <EquippedSlot
            equipped_item={fetchEquippedItemForSlot(
              equipped_items,
              InventoryItemTypes.BODY
            )}
            positionName={'Body'}
            position={Position.BODY}
          />
          <EquippedSlot
            equipped_item={fetchEquippedItemForSlot(
              equipped_items,
              InventoryItemTypes.SLEEVES
            )}
            positionName={'Sleeves (Right)'}
            position={Position.SLEEVES_RIGHT}
          />
        </div>

        <div className="grid grid-cols-3 gap-4">
          <EquippedSlot
            equipped_item={fetchEquippedItemForSlot(
              equipped_items,
              InventoryItemTypes.GLOVES
            )}
            positionName={'Gloves (Left)'}
            position={Position.GLOVES_LEFT}
          />
          <EquippedSlot
            equipped_item={fetchEquippedItemForSlot(
              equipped_items,
              InventoryItemTypes.LEGGINGS
            )}
            positionName={'Leggings'}
            position={Position.LEGGINGS}
          />
          <EquippedSlot
            equipped_item={fetchEquippedItemForSlot(
              equipped_items,
              InventoryItemTypes.GLOVES
            )}
            positionName={'Gloves (Right)'}
            position={Position.GLOVES_RIGHT}
          />
        </div>

        <div>
          <EquippedSlot
            equipped_item={fetchEquippedItemForSlot(
              equipped_items,
              InventoryItemTypes.FEET
            )}
            positionName={'Feet'}
            position={Position.FEET}
          />
        </div>
      </div>

      <div className="grid grid-cols-3 gap-4 sm:grid-cols-2">
        <EquippedSlot
          equipped_item={undefined}
          positionName={'Weapon (Left Hand)'}
          position={Position.LEFT_HAND}
        />
        <EquippedSlot
          equipped_item={undefined}
          positionName={'Weapon (Right Hand)'}
          position={Position.RING_HAND}
        />
        <EquippedSlot
          equipped_item={undefined}
          positionName={'Ring (Ring One)'}
          position={Position.RING_TWO}
        />
        <EquippedSlot
          equipped_item={undefined}
          positionName={'Ring (Ring One)'}
          position={Position.RING_ONE}
        />
        <EquippedSlot
          equipped_item={undefined}
          positionName={'Spell (Spell One)'}
          position={Position.SPELL_TWO}
        />
        <EquippedSlot
          equipped_item={undefined}
          positionName={'Spell (Spell Two)'}
          position={Position.SPELL_ONE}
        />
        <EquippedSlot
          equipped_item={undefined}
          positionName={'Trinket'}
          position={Position.TRINKET}
        />
      </div>
    </div>
  );
};

export default EquippedSlots;
