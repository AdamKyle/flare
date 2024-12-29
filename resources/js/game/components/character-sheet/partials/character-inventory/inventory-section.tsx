import React, { ReactNode } from 'react';

import { Position } from './enums/equipment-positions';
import EquippedSlot from './equipped-slot';

const InventorySection = (): ReactNode => {
  return (
    <div className="flex justify-center">
      <div className="flex items-center p-4 space-x-8">
        <div className="flex flex-col items-center space-y-4">
          <div>
            <EquippedSlot positionName={'Helmet'} position={Position.HELMET} />
          </div>

          <div className="grid grid-cols-3 gap-4">
            <EquippedSlot
              positionName={'Sleeves (Left)'}
              position={Position.SLEEVES_LEFT}
            />
            <EquippedSlot positionName={'Body'} position={Position.BODY} />
            <EquippedSlot
              positionName={'Sleeves (Right)'}
              position={Position.SLEEVES_RIGHT}
            />
          </div>

          <div className="grid grid-cols-3 gap-4">
            <EquippedSlot
              positionName={'Gloves (Left)'}
              position={Position.GLOVES_LEFT}
            />
            <EquippedSlot
              positionName={'Leggings'}
              position={Position.LEGGINGS}
            />
            <EquippedSlot
              positionName={'Gloves (Right)'}
              position={Position.GLOVES_RIGHT}
            />
          </div>

          <div>
            <EquippedSlot positionName={'Feet'} position={Position.FEET} />
          </div>
        </div>

        <div className="grid grid-cols-2 gap-4">
          <EquippedSlot
            positionName={'Weapon (Left Hand)'}
            position={Position.WEAPON_LEFT}
          />
          <EquippedSlot
            positionName={'Weapon (Right Hand)'}
            position={Position.WEAPON_RIGHT}
          />
          <EquippedSlot
            positionName={'Ring (Ring One)'}
            position={Position.RING_LEFT}
          />
          <EquippedSlot
            positionName={'Ring (Ring One)'}
            position={Position.RING_RIGHT}
          />
          <EquippedSlot
            positionName={'Spell (Spell One)'}
            position={Position.SPELL_LEFT}
          />
          <EquippedSlot
            positionName={'Spell (Spell Two)'}
            position={Position.SPELL_RIGHT}
          />
          <EquippedSlot positionName={'Trinket'} position={Position.TRINKET} />
        </div>
      </div>
    </div>
  );
};

export default InventorySection;
