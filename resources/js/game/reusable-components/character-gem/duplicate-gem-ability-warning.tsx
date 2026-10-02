import clsx from 'clsx';
import React, { ReactNode } from 'react';

import { DuplicateGemAbilityWarningContext } from './enums/duplicate-gem-ability-warning-context';
import DuplicateGemAbilityWarningProps from './types/duplicate-gem-ability-warning-props';
import PreparedDuplicateGemAbility, {
  DuplicateGemAbilitySetGroup,
} from './types/prepared-duplicate-gem-ability';
import { prepareDuplicateGemAbilities } from './utils/prepare-duplicate-gem-abilities';
import { DuplicateGemAbilityItemDefinition } from '../../api-definitions/gems/duplicate-gem-ability-definition';
import { planeTextItemColors } from '../../components/character-sheet/partials/character-inventory/styles/backpack-item-styles';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';

const DuplicateGemAbilityWarning = ({
  duplicates,
  context,
  on_open_item: onOpenItem,
}: DuplicateGemAbilityWarningProps): ReactNode => {
  const isSetContext = context === DuplicateGemAbilityWarningContext.SET;

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

  const renderItemGroup = (
    heading: string,
    items: DuplicateGemAbilityItemDefinition[]
  ): ReactNode => {
    if (items.length === 0) {
      return null;
    }

    return (
      <div className="space-y-1">
        <p className="text-sm font-semibold text-gray-700 dark:text-gray-200">
          {heading}
        </p>
        {renderItemLinks(items)}
      </div>
    );
  };

  const renderSetGroup = (setGroup: DuplicateGemAbilitySetGroup): ReactNode => (
    <div key={setGroup.set_id} className="space-y-1">
      <p className="text-sm font-semibold text-gray-700 dark:text-gray-200">
        Set: {setGroup.set_name}
      </p>
      {renderItemLinks(setGroup.items)}
    </div>
  );

  const renderSetContextDescription = (): ReactNode => {
    if (!isSetContext) {
      return null;
    }

    return (
      <p className="mt-1 text-sm">
        This set can still be equipped. One or more Gem abilities also appear on
        other Gems you own. Only one copy of a duplicated ability can trigger
        when those copies are part of the active equipment configuration. All
        other Gem modifiers still apply.
      </p>
    );
  };

  const renderAbilityDescription = (abilityName: string): ReactNode => {
    if (isSetContext) {
      return null;
    }

    return (
      <p className="text-sm">
        {abilityName} appears on more than one Gem you own. Only one copy can
        trigger when duplicate copies are part of the active equipment
        configuration. All other Gem modifiers still apply.
      </p>
    );
  };

  const renderDuplicate = (
    duplicate: PreparedDuplicateGemAbility
  ): ReactNode => (
    <div key={duplicate.ability.id} className="space-y-1">
      <p className="font-semibold">{duplicate.ability.name}</p>
      {renderAbilityDescription(duplicate.ability.name)}
      <p className="text-sm font-medium">Found on:</p>
      <div className="space-y-2">
        {renderItemGroup('Equipped', duplicate.equipped_items)}
        {renderItemGroup('Inventory', duplicate.inventory_items)}
        {duplicate.set_groups.map(renderSetGroup)}
      </div>
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
            {isSetContext ? 'Duplicate Gem Abilities' : 'Duplicate Gem Ability'}
          </p>
          {renderSetContextDescription()}
        </div>

        {prepareDuplicateGemAbilities(duplicates).map(renderDuplicate)}
      </div>
    </Alert>
  );
};

export default DuplicateGemAbilityWarning;
