import React, { ReactNode } from 'react';

import {
  formatNumberWithCommas,
  formatPercent,
} from '../../../util/format-number';
import FactualLink from '../../quest-item/partials/factual-link';
import MonsterDetailProps from '../types/monster-detail-props';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const MonsterQuestCelestialSection = ({
  monster,
  navigation,
}: MonsterDetailProps): ReactNode => {
  const { quest_and_celestial: section } = monster;

  const costRows: { label: string; value: number }[] = [
    { label: 'Gold Cost', value: section.gold_cost ?? 0 },
    { label: 'Gold Dust Cost', value: section.gold_dust_cost ?? 0 },
    { label: 'Shards', value: section.shards ?? 0 },
  ].filter((row) => row.value > 0);

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
      <h2 className="text-glacier-900 dark:text-glacier-100 mb-2 text-sm font-semibold">
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
        {costRows.map((row) => (
          <React.Fragment key={row.label}>
            <Dt>{row.label}</Dt>
            <Dd>{formatNumberWithCommas(row.value)}</Dd>
          </React.Fragment>
        ))}
      </Dl>
    </div>
  );
};

export default MonsterQuestCelestialSection;
