import clsx from 'clsx';
import { capitalize, isNil } from 'lodash';
import React from 'react';

import { useGetInventoryItemDetails } from './api/hooks/use-get-inventory-item-details';
import InventoryItemProps from './types/inventory-item-props';
import StatInfoToolTip from '../../../../reusable-components/item/stat-info-tool-tip';
import { backpackItemTextColors } from '../../../character-sheet/partials/character-inventory/styles/backpack-item-styles';
import { CharacterInventoryApiUrls } from '../api/enums/character-inventory-api-urls';

import { GameDataError } from 'game-data/components/game-data-error';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LinkButton from 'ui/buttons/link-button';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import Separator from 'ui/separator/separator';

const InventoryItem = ({
  item_id,
  character_id,
  close_item_view,
}: InventoryItemProps) => {
  const { error, loading, data } = useGetInventoryItemDetails({
    character_id,
    item_id,
    url: CharacterInventoryApiUrls.CHARACTER_INVENTORY_ITEM,
  });

  if (loading) {
    return (
      <div className="px-4">
        <InfiniteLoader />
      </div>
    );
  }

  if (error) {
    return null;
  }

  if (isNil(data)) {
    return (
      <div className="px-4">
        <GameDataError />
      </div>
    );
  }

  const item = data;

  const formatSignedPercent = (value: number) => {
    const pct = Math.abs(value * 100).toFixed(2);

    let sign: '' | '+' | '-' = '';
    if (value > 0) {
      sign = '+';
    }

    if (value < 0) {
      sign = '-';
    }

    return `${sign}${pct}%`;
  };

  const formatPercent = (value: number) => {
    return `${(Number(value) * 100).toFixed(2)}%`;
  };

  const formatIntWithPlus = (value: number) => {
    if (value === 0) {
      return '0';
    }

    const sign = value > 0 ? '+' : '-';
    const abs = Math.abs(value);
    return `${sign}${abs.toLocaleString('en-US')}`;
  };

  const formatFloat = (value: number) => {
    return Number(value).toLocaleString('en-US', { maximumFractionDigits: 2 });
  };

  const renderPrefixRow = () => {
    if (!item.item_prefix) {
      return null;
    }

    return (
      <>
        <Dt>Prefix Name</Dt>
        <Dd>
          <LinkButton
            label={item.item_prefix.name}
            variant={ButtonVariant.SUCCESS}
            on_click={() => console.log(item.item_prefix!.name)}
          />
        </Dd>
      </>
    );
  };

  const renderSuffixRow = () => {
    if (!item.item_suffix) {
      return null;
    }

    return (
      <>
        <Dt>Suffix Name</Dt>
        <Dd>
          <LinkButton
            label={item.item_suffix.name}
            variant={ButtonVariant.SUCCESS}
            on_click={() => console.log(item.item_suffix!.name)}
          />
        </Dd>
      </>
    );
  };

  const renderStatRow = (label: string, value: number | null) => {
    if (isNil(value) || value === 0) {
      return null;
    }

    return (
      <>
        <Dt>
          <span className="inline-flex items-center gap-2">
            <StatInfoToolTip
              label={label}
              value={value}
              renderAsPercent
              align="right"
            />
            <span className="min-w-0 break-words">{label}</span>
          </span>
        </Dt>
        <Dd>
          <span className="inline-flex items-center gap-2">
            <i
              className="fas fa-chevron-up text-emerald-600"
              aria-hidden="true"
            />
            <span className="font-semibold text-emerald-700">
              {formatSignedPercent(value)}
            </span>
          </span>
        </Dd>
      </>
    );
  };

  const renderNumberRow = (label: string, value: number) => {
    if (value === 0) {
      return null;
    }

    return (
      <>
        <Dt>
          <span className="inline-flex items-center gap-2">
            <StatInfoToolTip label={label} value={value} align="right" />
            <span className="min-w-0 break-words">{label}</span>
          </span>
        </Dt>
        <Dd>
          <span className="inline-flex items-center gap-2">
            {value > 0 ? (
              <i
                className="fas fa-chevron-up text-emerald-600"
                aria-hidden="true"
              />
            ) : null}
            <span
              className={clsx('font-semibold', value > 0 && 'text-emerald-700')}
            >
              {formatIntWithPlus(value)}
            </span>
          </span>
        </Dd>
      </>
    );
  };

  const buildBaseModMessage = (
    type: 'Damage' | 'Defence' | 'Healing',
    label: string,
    value: number
  ) => {
    const dir = value > 0 ? 'increase' : 'decrease';
    const amount = `${(Math.abs(value) * 100).toFixed(2)}%`;
    const typeLower = type.toLowerCase();
    return `This will ${dir} the overall ${label.toLowerCase()} by ${amount}. This can stack with other gear that contains this modifier to affect your overall ${typeLower}, even if that gear doesn’t increase your ${typeLower}.`;
  };

  const renderBaseModRow = (
    type: 'Damage' | 'Defence' | 'Healing',
    label: string,
    modValue: string | number | null
  ) => {
    if (isNil(modValue)) {
      return null;
    }

    const numeric = Number(modValue);

    if (numeric <= 0) {
      return null;
    }

    return (
      <>
        <Dt>
          <div className="ml-4 inline-flex items-center gap-2">
            <StatInfoToolTip
              label={buildBaseModMessage(type, label, numeric)}
              value={numeric}
              renderAsPercent
              align="right"
              custom_message
            />
            <span className="min-w-0 break-words">{label}</span>
          </div>
        </Dt>
        <Dd>
          <div className="ml-4 inline-flex items-center gap-2">
            <i
              className="fas fa-chevron-up text-emerald-600"
              aria-hidden="true"
            />
            <span className="font-semibold text-emerald-700">
              {formatSignedPercent(numeric)}
            </span>
          </div>
        </Dd>
      </>
    );
  };

  const renderAttackSection = () => {
    const attack = item.raw_damage ?? item.base_damage ?? 0;
    const hasChild = Number(item.base_damage_mod ?? 0) > 0;

    if (attack === 0 && !hasChild) {
      return null;
    }

    return (
      <div>
        <h4 className="mt-3 mb-1 text-xs font-semibold uppercase tracking-wide text-mango-tango-500 dark:text-mango-tango-300">
          Attack
        </h4>
        <Dl>
          {renderNumberRow('Attack', attack)}
          {renderBaseModRow('Damage', 'Base Damage Mod', item.base_damage_mod)}
        </Dl>
      </div>
    );
  };

  const renderDefenceSection = () => {
    const ac = item.raw_ac ?? item.base_ac ?? 0;
    const hasChild = Number(item.base_ac_mod ?? 0) > 0;

    if (ac === 0 && !hasChild) {
      return null;
    }

    return (
      <div>
        <h4 className="mt-3 mb-1 text-xs font-semibold uppercase tracking-wide text-mango-tango-500 dark:text-mango-tango-300">
          Defence
        </h4>
        <Dl>
          {renderNumberRow('AC', ac)}
          {renderBaseModRow('Defence', 'Base AC Mod', item.base_ac_mod)}
        </Dl>
      </div>
    );
  };

  const renderHealingSection = () => {
    const healing = item.raw_healing ?? item.base_healing ?? 0;
    const hasChild = Number(item.base_healing_mod ?? 0) > 0;

    if (healing === 0 && !hasChild) {
      return null;
    }

    return (
      <div>
        <h4 className="mt-3 mb-1 text-xs font-semibold uppercase tracking-wide text-mango-tango-500 dark:text-mango-tango-300">
          Healing
        </h4>
        <Dl>
          {renderNumberRow('Healing', healing)}
          {renderBaseModRow(
            'Healing',
            'Base Healing Mod',
            item.base_healing_mod
          )}
        </Dl>
      </div>
    );
  };

  const renderAffixesSection = () => {
    if (!item.item_prefix && !item.item_suffix) {
      return null;
    }

    return (
      <div>
        <h4 className="mt-3 mb-1 text-xs font-semibold uppercase tracking-wide text-mango-tango-500 dark:text-mango-tango-300">
          Affixes
        </h4>
        <Dl>
          {renderPrefixRow()}
          {renderSuffixRow()}
        </Dl>
      </div>
    );
  };

  const renderStatsSection = () => {
    const any =
      (item.str_modifier ?? 0) ||
      (item.dex_modifier ?? 0) ||
      (item.int_modifier ?? 0) ||
      (item.chr_modifier ?? 0) ||
      (item.agi_modifier ?? 0) ||
      (item.dur_modifier ?? 0) ||
      (item.focus_modifier ?? 0);

    if (!any) {
      return null;
    }

    return (
      <div>
        <h4 className="mt-3 mb-1 text-xs font-semibold uppercase tracking-wide text-mango-tango-500 dark:text-mango-tango-300">
          Stats
        </h4>
        <Dl>
          {renderStatRow('Strength', item.str_modifier)}
          {renderStatRow('Dexterity', item.dex_modifier)}
          {renderStatRow('Intelligence', item.int_modifier)}
          {renderStatRow('Charisma', item.chr_modifier)}
          {renderStatRow('Agility', item.agi_modifier)}
          {renderStatRow('Durability', item.dur_modifier)}
          {renderStatRow('Focus', item.focus_modifier)}
        </Dl>
      </div>
    );
  };

  const renderHolyStacksSection = () => {
    const total = Number(item.holy_stacks ?? 0);
    const applied = Number(item.holy_stacks_applied ?? 0);
    const statBonus = item.holy_stack_stat_bonus;
    const devourBonus = item.holy_stack_devouring_darkness;

    if (total <= 0 && applied <= 0) {
      return null;
    }

    const renderTotalRow = () => (
      <>
        <Dt>
          <span className="inline-flex items-center gap-2">
            <StatInfoToolTip
              label="This represents how many holy oils you can apply to the item."
              value={0}
              align="right"
              custom_message
            />
            <span className="min-w-0 break-words">Total Holy Stacks</span>
          </span>
        </Dt>
        <Dd>{total}</Dd>
      </>
    );

    if (applied <= 0) {
      return (
        <div>
          <h4 className="mt-3 mb-1 text-xs font-semibold uppercase tracking-wide text-mango-tango-500 dark:text-mango-tango-300">
            Holy Stacks
          </h4>
          <Dl>{renderTotalRow()}</Dl>
        </div>
      );
    }

    return (
      <div>
        <h4 className="mt-3 mb-1 text-xs font-semibold uppercase tracking-wide text-mango-tango-500 dark:text-mango-tango-300">
          Holy Stacks
        </h4>
        <Dl>
          {renderTotalRow()}

          <>
            <Dt>
              <span className="inline-flex items-center gap-2">
                <StatInfoToolTip
                  label="This is a breakdown of all the holy oils you have applied. Click the number for a deeper understanding."
                  value={applied}
                  align="right"
                  custom_message
                />
                <span className="min-w-0 break-words">
                  Total Applied Holy Stacks
                </span>
              </span>
            </Dt>
            <Dd>
              <LinkButton
                label={String(applied)}
                variant={ButtonVariant.SUCCESS}
                on_click={() => console.log('applied holy stacks')}
              />
            </Dd>
          </>

          <>
            <Dt>
              <span className="inline-flex items-center gap-2">
                <StatInfoToolTip
                  label={`This value (${formatFloat(Number(statBonus ?? 0))}) is applied to all your stats.`}
                  value={Number(statBonus ?? 0)}
                  align="right"
                  custom_message
                />
                <span className="min-w-0 break-words">
                  Holy Stack Attribute Bonus
                </span>
              </span>
            </Dt>
            <Dd>{formatFloat(Number(statBonus ?? 0))}</Dd>
          </>

          <>
            <Dt>
              <span className="inline-flex items-center gap-2">
                <StatInfoToolTip
                  label="This value affects your ability to overcome enemies’ attempts to void your enchantments, and it stacks with other items that affect Devouring Darkness."
                  value={Number(devourBonus ?? 0)}
                  align="right"
                  custom_message
                />
                <span className="min-w-0 break-words">
                  Holy Stacks Devouring Darkness Bonus
                </span>
              </span>
            </Dt>
            <Dd>{formatFloat(Number(devourBonus ?? 0))}</Dd>
          </>
        </Dl>
      </div>
    );
  };

  const renderAmbushCounterSection = () => {
    const ambush = Number(item.ambush_chance ?? 0);
    const ambushResist = Number(item.ambush_resistance_chance ?? 0);
    const counter = Number(item.counter_chance ?? 0);
    const counterResist = Number(item.counter_resistance_chance ?? 0);

    const allZero =
      ambush <= 0 && ambushResist <= 0 && counter <= 0 && counterResist <= 0;
    if (allZero) {
      return null;
    }

    const renderChanceRow = (label: string, tooltip: string, value: number) => {
      if (value <= 0) {
        return null;
      }

      return (
        <>
          <Dt>
            <span className="inline-flex items-center gap-2">
              <StatInfoToolTip
                label={tooltip}
                value={value}
                renderAsPercent
                align="right"
                custom_message
              />
              <span className="min-w-0 break-words">{label}</span>
            </span>
          </Dt>
          <Dd>{formatPercent(value)}</Dd>
        </>
      );
    };

    return (
      <div>
        <h4 className="mt-3 mb-1 text-xs font-semibold uppercase tracking-wide text-mango-tango-500 dark:text-mango-tango-300">
          Ambush and Counter
        </h4>
        <Dl>
          {renderChanceRow(
            'Ambush Chance',
            'The chance to ambush the enemy before anyone takes an action.',
            ambush
          )}
          {renderChanceRow(
            'Ambush Resistance',
            'The chance to resist the enemy’s ambush.',
            ambushResist
          )}
          {renderChanceRow(
            'Counter Chance',
            'The chance to counter an enemy’s attack.',
            counter
          )}
          {renderChanceRow(
            'Counter Resistance',
            'The chance to avoid the enemy’s counterattack.',
            counterResist
          )}
        </Dl>
      </div>
    );
  };

  const renderWithSeparator = (section: React.ReactNode) => {
    if (!section) {
      return null;
    }

    return (
      <>
        {section}
        <Separator />
      </>
    );
  };

  return (
    <>
      <div className="text-center p-4">
        <Button
          on_click={close_item_view}
          label="Close"
          variant={ButtonVariant.SUCCESS}
        />
      </div>

      <div className="px-4 flex flex-col gap-4">
        <div>
          <h2 className={clsx(backpackItemTextColors(item), 'text-lg my-2')}>
            {item.name}
          </h2>
          <Separator />
          <p className="my-4 text-gray-800 dark:text-gray-300">
            {item.description}
          </p>
          <Separator />
        </div>

        <div className="space-y-4">
          <div>
            <Dl>
              <Dt>Type</Dt>
              <Dd>{capitalize(item.type)}</Dd>
            </Dl>
          </div>

          <Separator />

          {renderWithSeparator(renderAffixesSection())}
          {renderWithSeparator(renderAttackSection())}
          {renderWithSeparator(renderDefenceSection())}
          {renderWithSeparator(renderHealingSection())}
          {renderWithSeparator(renderStatsSection())}
          {renderWithSeparator(renderHolyStacksSection())}
          {renderWithSeparator(renderAmbushCounterSection())}
        </div>
      </div>
    </>
  );
};

export default InventoryItem;
