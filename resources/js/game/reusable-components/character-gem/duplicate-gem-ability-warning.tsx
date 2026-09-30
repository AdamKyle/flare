import clsx from 'clsx';
import React, { ReactNode } from 'react';

import DuplicateGemAbilityWarningProps from './types/duplicate-gem-ability-warning-props';
import { DuplicateGemAbilityItemDefinition } from '../../api-definitions/gems/duplicate-gem-ability-definition';
import { planeTextItemColors } from '../../components/character-sheet/partials/character-inventory/styles/backpack-item-styles';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';

const DuplicateGemAbilityWarning = ({
  duplicates,
  context,
  on_open_item: onOpenItem,
}: DuplicateGemAbilityWarningProps): ReactNode => {
  const renderItemLinks = (
    items: DuplicateGemAbilityItemDefinition[]
  ): ReactNode => (
    <div className="space-y-1">
      {items.map((item) => (
        <button
          key={item.item_id}
          type="button"
          onClick={() => onOpenItem(item.item_id)}
          className={clsx(
            'focus-visible:ring-brand-600 dark:focus-visible:ring-brand-400 block w-fit rounded-sm font-medium underline underline-offset-2 focus:outline-none focus-visible:ring-2',
            planeTextItemColors(item)
          )}
        >
          {item.name}
        </button>
      ))}
    </div>
  );

  if (duplicates.length === 0) {
    return null;
  }

  return (
    <Alert variant={AlertVariant.WARNING}>
      <div className="space-y-3">
        <div>
          <p className="font-semibold">
            {context === 'set'
              ? 'Duplicate Gem Abilities'
              : 'Duplicate Gem Ability'}
          </p>
          {context === 'set' ? (
            <p className="mt-1 text-sm">
              This set can still be equipped. One or more Gem abilities also
              appear on other Gems you own. Only one copy of a duplicated
              ability can trigger when those copies are part of the active
              equipment configuration. All other Gem modifiers still apply.
            </p>
          ) : null}
        </div>

        {duplicates.map((duplicate) => {
          const equippedItems = duplicate.items.filter(
            (item) => item.source === 'equipped'
          );
          const inventoryItems = duplicate.items.filter(
            (item) => item.source === 'inventory'
          );
          const setItemsBySet = new Map<
            number | null,
            DuplicateGemAbilityItemDefinition[]
          >();

          duplicate.items
            .filter((item) => item.source === 'set')
            .forEach((item) => {
              const setItems = setItemsBySet.get(item.set_id) ?? [];

              setItemsBySet.set(item.set_id, [...setItems, item]);
            });

          const setGroups = Array.from(setItemsBySet.values());

          return (
            <div key={duplicate.ability.id} className="space-y-1">
              <p className="font-semibold">{duplicate.ability.name}</p>
              {context === 'item' ? (
                <p className="text-sm">
                  {duplicate.ability.name} appears on more than one Gem you own.
                  Only one copy can trigger when duplicate copies are part of
                  the active equipment configuration. All other Gem modifiers
                  still apply.
                </p>
              ) : null}
              <p className="text-sm font-medium">Found on:</p>
              <div className="space-y-2">
                {equippedItems.length > 0 ? (
                  <div className="space-y-1">
                    <p className="text-sm font-semibold text-gray-700 dark:text-gray-200">
                      Equipped
                    </p>
                    {renderItemLinks(equippedItems)}
                  </div>
                ) : null}
                {inventoryItems.length > 0 ? (
                  <div className="space-y-1">
                    <p className="text-sm font-semibold text-gray-700 dark:text-gray-200">
                      Inventory
                    </p>
                    {renderItemLinks(inventoryItems)}
                  </div>
                ) : null}
                {setGroups.map((setItems) => (
                  <div key={setItems[0].set_id} className="space-y-1">
                    <p className="text-sm font-semibold text-gray-700 dark:text-gray-200">
                      Set: {setItems[0].set_name}
                    </p>
                    {renderItemLinks(setItems)}
                  </div>
                ))}
              </div>
            </div>
          );
        })}
      </div>
    </Alert>
  );
};

export default DuplicateGemAbilityWarning;
