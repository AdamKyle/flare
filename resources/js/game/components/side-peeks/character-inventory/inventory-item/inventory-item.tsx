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
    const pct = (value * 100).toFixed(2);
    const sign = value > 0 ? '+' : value < 0 ? '-' : '';
    return `${sign}${sign ? pct.replace('-', '') : pct}%`;
  };

  const formatIntWithPlus = (value: number) => {
    if (value === 0) return '0';
    const sign = value > 0 ? '+' : '-';
    const abs = Math.abs(value);
    return `${sign}${abs.toLocaleString('en-US')}`;
  };

  const renderPrefixRow = () => {
    if (!item.item_prefix) return null;

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
    if (!item.item_suffix) return null;

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
    if (isNil(value) || value === 0) return null;

    return (
      <>
        <Dt>{label}</Dt>
        <Dd>
          <span className="inline-flex items-center gap-2">
            <i
              className="fas fa-chevron-up text-emerald-600"
              aria-hidden="true"
            />
            <span className="font-semibold text-emerald-700">
              {formatSignedPercent(value)}
            </span>
            <StatInfoToolTip label={label} value={value} renderAsPercent />
          </span>
        </Dd>
      </>
    );
  };

  const renderAttackAcHealing = () => {
    const attack = item.raw_damage ?? item.base_damage ?? 0;
    const ac = item.raw_ac ?? item.base_ac ?? 0;
    const healing = item.raw_healing ?? item.base_healing ?? 0;

    const renderNumberRow = (label: string, value: number) => {
      if (value === 0) return null;

      return (
        <>
          <Dt>{label}</Dt>
          <Dd>
            <span className="inline-flex items-center gap-2">
              {value > 0 ? (
                <i
                  className="fas fa-chevron-up text-emerald-600"
                  aria-hidden="true"
                />
              ) : null}
              <span
                className={clsx(
                  'font-semibold',
                  value > 0 && 'text-emerald-700'
                )}
              >
                {formatIntWithPlus(value)}
              </span>
              <StatInfoToolTip label={label} value={value} />
            </span>
          </Dd>
        </>
      );
    };

    if (attack === 0 && ac === 0 && healing === 0) {
      return null;
    }

    return (
      <Dl>
        {renderNumberRow('Attack', attack)}
        {renderNumberRow('AC', ac)}
        {renderNumberRow('Healing', healing)}
      </Dl>
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
          <div>
            <h3 className="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">
              Affixes
            </h3>
            <Dl>
              {renderPrefixRow()}
              {renderSuffixRow()}
            </Dl>
          </div>

          <Separator />

          <div>
            <h3 className="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">
              Attack / AC / Healing
            </h3>
            {renderAttackAcHealing()}
          </div>

          <Separator />

          <div>
            <h3 className="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">
              Stats
            </h3>
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
        </div>
      </div>
    </>
  );
};

export default InventoryItem;
