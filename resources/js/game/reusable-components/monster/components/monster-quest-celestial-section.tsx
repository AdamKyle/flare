import React, { ReactNode } from 'react';

import CurrencyDisplay from '../../currency/currency-display';
import { CurrencyDisplayMode } from '../../currency/enums/currency-display-mode';
import { CurrencyType } from '../../currency/enums/currency-type';
import FactualLink from '../../quest-item/partials/factual-link';
import MonsterCurrencyCostRow from '../types/monster-currency-cost-row';
import MonsterDetailProps from '../types/monster-detail-props';

import { formatPercent } from 'game-utils/format-number';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const MonsterQuestCelestialSection = ({
  monster,
  navigation,
}: MonsterDetailProps): ReactNode => {
  const { quest_and_celestial: section } = monster;

  const costRows: MonsterCurrencyCostRow[] = [
    {
      label: 'Gold Cost',
      currency: CurrencyType.GOLD,
      value: section.gold_cost ?? 0,
    },
    {
      label: 'Gold Dust Cost',
      currency: CurrencyType.GOLD_DUST,
      value: section.gold_dust_cost ?? 0,
    },
    {
      label: 'Shards',
      currency: CurrencyType.SHARDS,
      value: section.shards ?? 0,
    },
  ].filter((row) => row.value > 0);

  const renderCostRow = (row: MonsterCurrencyCostRow): ReactNode => (
    <React.Fragment key={row.label}>
      <Dt>{row.label}</Dt>
      <Dd>
        <CurrencyDisplay
          currency={row.currency}
          amount={row.value}
          display_mode={CurrencyDisplayMode.EXACT}
          show_label={false}
        />
      </Dd>
    </React.Fragment>
  );

  const dropChance = section.quest_item_drop_chance ?? 0;

  const hasRows =
    Boolean(section.quest_item) ||
    dropChance !== 0 ||
    section.is_celestial_entity ||
    section.celestial_type !== null ||
    costRows.length > 0;

  if (!hasRows) {
    return null;
  }

  return (
    <div>
      <h2 className="text-marigold-700 dark:text-marigold-500 mb-2 text-base font-semibold">
        Quest &amp; Celestial
      </h2>
      <Dl>
        {section.quest_item && (
          <>
            <Dt>Quest Item</Dt>
            <Dd>
              <FactualLink
                id={section.quest_item.item_id}
                label={section.quest_item.name}
                on_click={navigation?.on_open_item}
              />
            </Dd>
          </>
        )}
        {dropChance !== 0 && (
          <>
            <Dt>Quest Item Drop Chance</Dt>
            <Dd>{formatPercent(dropChance)}</Dd>
          </>
        )}
        {section.is_celestial_entity && (
          <>
            <Dt>Celestial Entity</Dt>
            <Dd>Yes</Dd>
          </>
        )}
        {section.celestial_type !== null && (
          <>
            <Dt>Celestial Type</Dt>
            <Dd>{section.celestial_type}</Dd>
          </>
        )}
        {costRows.map(renderCostRow)}
      </Dl>
    </div>
  );
};

export default MonsterQuestCelestialSection;
