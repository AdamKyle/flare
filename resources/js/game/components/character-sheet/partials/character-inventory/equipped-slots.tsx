import React from 'react';

import {
  InventoryPositionDefinition,
  Position,
} from './enums/equipment-positions';
import {
  nonArmourEquippableItems,
  InventoryItemTypes,
  weaponTypes,
} from './enums/inventory-item-types';
import EquippedSlot from './equipped-slot';
import EquippedSlotsProps from './types/equipped-slots-props';
import { fetchEquippedItemForSlot } from './utils/fetch-equipped-item-for-slot';

const EquippedSlots = ({
  equipped_items,
  on_open_item_details,
  on_open_empty_slot,
}: EquippedSlotsProps) => {
  return (
    <div className="flex w-full flex-col items-center gap-4 sm:flex-row md:justify-center lg:w-3/4 lg:p-4">
      <div className="flex flex-col items-center space-y-4">
        <div>
          <EquippedSlot
            equipped_item={fetchEquippedItemForSlot(
              equipped_items,
              InventoryItemTypes.HELMET,
              InventoryPositionDefinition.HELMET
            )}
            positionName={'Helmet'}
            position={Position.HELMET}
            empty_slot_item_types={[InventoryItemTypes.HELMET]}
            equip_position={InventoryPositionDefinition.HELMET}
            on_open_item_details={on_open_item_details}
            on_open_empty_slot={on_open_empty_slot}
          />
        </div>

        <div className="grid grid-cols-3 gap-4">
          <EquippedSlot
            equipped_item={fetchEquippedItemForSlot(
              equipped_items,
              InventoryItemTypes.SLEEVES,
              InventoryPositionDefinition.SLEEVES
            )}
            positionName={'Sleeves (Left)'}
            position={Position.SLEEVES_LEFT}
            empty_slot_item_types={[InventoryItemTypes.SLEEVES]}
            equip_position={InventoryPositionDefinition.SLEEVES}
            on_open_item_details={on_open_item_details}
            on_open_empty_slot={on_open_empty_slot}
          />
          <EquippedSlot
            equipped_item={fetchEquippedItemForSlot(
              equipped_items,
              InventoryItemTypes.BODY,
              InventoryPositionDefinition.BODY
            )}
            positionName={'Body'}
            position={Position.BODY}
            empty_slot_item_types={[InventoryItemTypes.BODY]}
            equip_position={InventoryPositionDefinition.BODY}
            on_open_item_details={on_open_item_details}
            on_open_empty_slot={on_open_empty_slot}
          />
          <EquippedSlot
            equipped_item={fetchEquippedItemForSlot(
              equipped_items,
              InventoryItemTypes.SLEEVES,
              InventoryPositionDefinition.SLEEVES
            )}
            positionName={'Sleeves (Right)'}
            position={Position.SLEEVES_RIGHT}
            empty_slot_item_types={[InventoryItemTypes.SLEEVES]}
            equip_position={InventoryPositionDefinition.SLEEVES}
            on_open_item_details={on_open_item_details}
            on_open_empty_slot={on_open_empty_slot}
          />
        </div>

        <div className="grid grid-cols-3 gap-4">
          <EquippedSlot
            equipped_item={fetchEquippedItemForSlot(
              equipped_items,
              InventoryItemTypes.GLOVES,
              InventoryPositionDefinition.GLOVES
            )}
            positionName={'Gloves (Left)'}
            position={Position.GLOVES_LEFT}
            empty_slot_item_types={[InventoryItemTypes.GLOVES]}
            equip_position={InventoryPositionDefinition.GLOVES}
            on_open_item_details={on_open_item_details}
            on_open_empty_slot={on_open_empty_slot}
          />
          <EquippedSlot
            equipped_item={fetchEquippedItemForSlot(
              equipped_items,
              InventoryItemTypes.LEGGINGS,
              InventoryPositionDefinition.LEGGINGS
            )}
            positionName={'Leggings'}
            position={Position.LEGGINGS}
            empty_slot_item_types={[InventoryItemTypes.LEGGINGS]}
            equip_position={InventoryPositionDefinition.LEGGINGS}
            on_open_item_details={on_open_item_details}
            on_open_empty_slot={on_open_empty_slot}
          />
          <EquippedSlot
            equipped_item={fetchEquippedItemForSlot(
              equipped_items,
              InventoryItemTypes.GLOVES,
              InventoryPositionDefinition.GLOVES
            )}
            positionName={'Gloves (Right)'}
            position={Position.GLOVES_RIGHT}
            empty_slot_item_types={[InventoryItemTypes.GLOVES]}
            equip_position={InventoryPositionDefinition.GLOVES}
            on_open_item_details={on_open_item_details}
            on_open_empty_slot={on_open_empty_slot}
          />
        </div>

        <div>
          <EquippedSlot
            equipped_item={fetchEquippedItemForSlot(
              equipped_items,
              InventoryItemTypes.FEET,
              InventoryPositionDefinition.FEET
            )}
            positionName={'Feet'}
            position={Position.FEET}
            empty_slot_item_types={[InventoryItemTypes.FEET]}
            equip_position={InventoryPositionDefinition.FEET}
            on_open_item_details={on_open_item_details}
            on_open_empty_slot={on_open_empty_slot}
          />
        </div>
      </div>

      <div className="grid grid-cols-3 gap-4 sm:grid-cols-2">
        <EquippedSlot
          equipped_item={fetchEquippedItemForSlot(
            equipped_items,
            nonArmourEquippableItems,
            InventoryPositionDefinition.LEFT_HAND
          )}
          positionName={'Weapon (Left Hand)'}
          position={Position.LEFT_HAND}
          empty_slot_item_types={weaponTypes}
          equip_position={InventoryPositionDefinition.LEFT_HAND}
          on_open_item_details={on_open_item_details}
          on_open_empty_slot={on_open_empty_slot}
        />
        <EquippedSlot
          equipped_item={fetchEquippedItemForSlot(
            equipped_items,
            nonArmourEquippableItems,
            InventoryPositionDefinition.RIGHT_HAND
          )}
          positionName={'Weapon (Right Hand)'}
          position={Position.RING_HAND}
          empty_slot_item_types={weaponTypes}
          equip_position={InventoryPositionDefinition.RIGHT_HAND}
          on_open_item_details={on_open_item_details}
          on_open_empty_slot={on_open_empty_slot}
        />
        <EquippedSlot
          equipped_item={fetchEquippedItemForSlot(
            equipped_items,
            nonArmourEquippableItems,
            InventoryPositionDefinition.RING_TWO
          )}
          positionName={'Ring (Ring Two)'}
          position={Position.RING_TWO}
          empty_slot_item_types={[InventoryItemTypes.RING]}
          equip_position={InventoryPositionDefinition.RING_TWO}
          on_open_item_details={on_open_item_details}
          on_open_empty_slot={on_open_empty_slot}
        />
        <EquippedSlot
          equipped_item={fetchEquippedItemForSlot(
            equipped_items,
            nonArmourEquippableItems,
            InventoryPositionDefinition.RING_ONE
          )}
          positionName={'Ring (Ring One)'}
          position={Position.RING_ONE}
          empty_slot_item_types={[InventoryItemTypes.RING]}
          equip_position={InventoryPositionDefinition.RING_ONE}
          on_open_item_details={on_open_item_details}
          on_open_empty_slot={on_open_empty_slot}
        />
        <EquippedSlot
          equipped_item={fetchEquippedItemForSlot(
            equipped_items,
            nonArmourEquippableItems,
            InventoryPositionDefinition.SPELL_ONE
          )}
          positionName={'Spell (Spell One)'}
          position={Position.SPELL_ONE}
          empty_slot_item_types={[
            InventoryItemTypes.SPELL_DAMAGE,
            InventoryItemTypes.SPELL_HEALING,
          ]}
          equip_position={InventoryPositionDefinition.SPELL_ONE}
          on_open_item_details={on_open_item_details}
          on_open_empty_slot={on_open_empty_slot}
        />
        <EquippedSlot
          equipped_item={fetchEquippedItemForSlot(
            equipped_items,
            nonArmourEquippableItems,
            InventoryPositionDefinition.SPELL_TWO
          )}
          positionName={'Spell (Spell Two)'}
          position={Position.SPELL_TWO}
          empty_slot_item_types={[
            InventoryItemTypes.SPELL_DAMAGE,
            InventoryItemTypes.SPELL_HEALING,
          ]}
          equip_position={InventoryPositionDefinition.SPELL_TWO}
          on_open_item_details={on_open_item_details}
          on_open_empty_slot={on_open_empty_slot}
        />
        <EquippedSlot
          equipped_item={fetchEquippedItemForSlot(
            equipped_items,
            nonArmourEquippableItems,
            InventoryPositionDefinition.TRINKET
          )}
          positionName={'Trinket'}
          position={Position.TRINKET}
          empty_slot_item_types={[InventoryItemTypes.TRINKET]}
          equip_position={InventoryPositionDefinition.TRINKET}
          on_open_item_details={on_open_item_details}
          on_open_empty_slot={on_open_empty_slot}
        />
        <EquippedSlot
          equipped_item={fetchEquippedItemForSlot(
            equipped_items,
            InventoryItemTypes.ARTIFACT,
            InventoryPositionDefinition.ARTIFACT
          )}
          positionName={'Artifact'}
          position={Position.ARTIFACT}
          empty_slot_item_types={[InventoryItemTypes.ARTIFACT]}
          equip_position={InventoryPositionDefinition.ARTIFACT}
          on_open_item_details={on_open_item_details}
          on_open_empty_slot={on_open_empty_slot}
        />
      </div>
    </div>
  );
};

export default EquippedSlots;
