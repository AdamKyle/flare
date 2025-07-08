import React from 'react';

import SimpleItemDetailsProps from './types/simple-item-details-props';
import {
  armourPositions,
  InventoryItemTypes,
} from '../../components/character-sheet/partials/character-inventory/enums/inventory-item-types';
import { formatNumberWithCommas } from '../../util/format-number';
import { decodeHtmlEntities } from '../util/decode-string';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Card from 'ui/cards/card';
import ContainerWithTitle from 'ui/container/container-with-title';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import Separator from 'ui/separator/separator';
import InfoToolTip from 'ui/tool-tips/info-tool-tip';

const SimpleItemDetails = ({
  item,
  on_close,
  show_advanced_button,
  show_shop_actions,
}: SimpleItemDetailsProps) => {
  const isArmour = armourPositions.includes(item.type as InventoryItemTypes);
  const isSpell =
    item.type === InventoryItemTypes.SPELL_HEALING ||
    item.type === InventoryItemTypes.SPELL_DAMAGE;
  const isRing = item.type === InventoryItemTypes.RING;

  const craftCategory = isArmour
    ? 'Armour'
    : isSpell
      ? 'Spell'
      : isRing
        ? 'Ring'
        : 'Weapon';

  const statEntries = [
    { label: 'Strength', value: item.str_modifier, isPercent: true },
    { label: 'Durability', value: item.dur_modifier, isPercent: true },
    { label: 'Intelligence', value: item.int_modifier, isPercent: true },
    { label: 'Dexterity', value: item.dex_modifier, isPercent: true },
    { label: 'Charisma', value: item.chr_modifier, isPercent: true },
    { label: 'Agility', value: item.agi_modifier, isPercent: true },
    { label: 'Focus', value: item.focus_modifier, isPercent: true },
  ];

  const getInfoText = (label: string, display: string) => {
    switch (label) {
      case 'Cost':
        return `This costs ${display} gold.`;
      case 'Crafting (Req.)':
        return `Requires ${craftCategory.toLowerCase()} crafting skill at level: ${display}.`;
      case 'Crafting (Trivial)':
        return `${craftCategory} crafting becomes trivial at level: ${display}.`;
      case 'Damage':
        return `Increases damage by ${display}.`;
      case 'AC':
        return `Increases AC (armour) by ${display}.`;
      default:
        return `This raises the character’s ${label.toLowerCase()} by ${display}.`;
    }
  };

  const renderItem = (label: string, rawValue?: number, isPercent = false) => {
    if (rawValue == null || rawValue <= 0) return null;
    let display = isPercent
      ? `${(rawValue * 100).toFixed(2)}%`
      : formatNumberWithCommas(rawValue);
    const isCrafting = label.startsWith('Crafting');
    if (!isCrafting && label !== 'Cost') display = `+${display}`;

    const labelColor = isCrafting
      ? 'text-mango-tango-500 dark:text-mango-tango-300'
      : 'text-danube-600 dark:text-danube-300';
    const valueColor = isCrafting
      ? 'text-mango-tango-500 dark:text-mango-tango-300'
      : 'text-emerald-500 dark:text-emerald-300';

    return (
      <React.Fragment key={label}>
        <Dt>
          <div className="flex items-center space-x-2">
            <InfoToolTip info_text={getInfoText(label, display)} />
            <span className={labelColor}>{label}</span>
          </div>
        </Dt>
        <Dd>
          <div className="flex justify-end">
            <span className={valueColor}>{display}</span>
          </div>
        </Dd>
      </React.Fragment>
    );
  };

  const renderAdvancedButton = () => {
    if (!show_advanced_button) {
      return null;
    }

    return (
      <div className="mt-6">
        <Button
          on_click={() => {}}
          label="Advanced Details"
          variant={ButtonVariant.PRIMARY}
        />
      </div>
    );
  };

  const renderShopActions = () => {
    if (!show_shop_actions) {
      return null;
    }

    return (
      <div className="mt-4 flex flex-wrap gap-2">
        <Button
          on_click={() => {}}
          label="Compare"
          variant={ButtonVariant.SUCCESS}
        />
        <Button
          on_click={() => {}}
          label="Buy"
          variant={ButtonVariant.PRIMARY}
        />
        <Button
          on_click={() => {}}
          label="Buy Multiple"
          variant={ButtonVariant.PRIMARY}
        />
      </div>
    );
  };

  return (
    <ContainerWithTitle manageSectionVisibility={on_close} title={item.name}>
      <Card>
        <p className="mb-4 text-sm text-gray-700 dark:text-gray-300">
          {decodeHtmlEntities(item.description)}
        </p>
        <Separator />

        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 auto-rows-min items-start">
          <div>
            <h4 className="mb-2 text-sm font-semibold text-mango-tango-500 dark:text-mango-tango-300">
              Cost & Crafting
            </h4>
            <Separator />
            <Dl>
              {renderItem('Cost', item.cost)}
              {renderItem('Crafting (Req.)', item.skill_level_req)}
              {renderItem('Crafting (Trivial)', item.skill_level_trivial)}
            </Dl>
          </div>

          <div>
            <h4 className="mb-2 text-sm font-semibold text-danube-600 dark:text-danube-300">
              Stats
            </h4>
            <Separator />
            <Dl>
              {statEntries.map((e) =>
                renderItem(e.label, e.value, e.isPercent)
              )}
            </Dl>
          </div>

          <div>
            <h4 className="mb-2 text-sm font-semibold text-danube-600 dark:text-danube-300">
              Damage / AC
            </h4>
            <Separator />
            <Dl>
              {renderItem('Damage', item.base_damage)}
              {isArmour && renderItem('AC', item.base_ac!)}
            </Dl>
          </div>
        </div>
        {renderAdvancedButton()}
        {renderShopActions()}
      </Card>
    </ContainerWithTitle>
  );
};

export default SimpleItemDetails;
