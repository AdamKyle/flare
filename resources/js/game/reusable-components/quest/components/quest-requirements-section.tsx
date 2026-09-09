import React, { ReactNode } from 'react';

import ReadOnlyItemCard from '../../../components/side-peeks/components/items/read-only-item-card';
import FactualLink from '../../quest-item/partials/factual-link';
import { QuestRelatedItemDefinition } from '../api/definitions/quest-detail-definition';
import QuestDetailProps from '../types/quest-detail-props';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const QuestRequirementsSection = ({
  quest,
  navigation,
  quest_item_ownership: questItemOwnership,
}: QuestDetailProps): ReactNode => {
  const { requirements } = quest;

  const renderQuestItemCard = (item: QuestRelatedItemDefinition): ReactNode => (
    <ReadOnlyItemCard
      item_id={item.item_id}
      name={item.name}
      description={item.description}
      effect={item.effect}
      usable={item.usable}
      ownership_state={questItemOwnership?.[item.item_id]}
      on_click={navigation?.on_open_item}
    />
  );

  const currencyRows: { label: string; value: number }[] = [
    { label: 'Gold', value: requirements.currencies.gold ?? 0 },
    { label: 'Gold Dust', value: requirements.currencies.gold_dust ?? 0 },
    { label: 'Shards', value: requirements.currencies.shards ?? 0 },
    { label: 'Copper Coins', value: requirements.currencies.copper_coins ?? 0 },
  ].filter((row) => row.value !== 0);

  const hasRows =
    Boolean(requirements.primary_item) ||
    Boolean(requirements.secondary_item) ||
    requirements.reincarnated_times !== null ||
    Boolean(requirements.access_to_map) ||
    Boolean(requirements.faction) ||
    Boolean(requirements.faction_loyalty) ||
    currencyRows.length > 0;

  if (!hasRows) {
    return null;
  }

  const hasSimpleRows =
    requirements.reincarnated_times !== null ||
    Boolean(requirements.access_to_map) ||
    Boolean(requirements.faction) ||
    Boolean(requirements.faction_loyalty) ||
    currencyRows.length > 0;

  const fieldLabelClassName =
    'text-glacier-600 dark:text-glacier-400 text-xs font-semibold tracking-wide uppercase';

  return (
    <div className="flex flex-col gap-4">
      <h3 className="text-marigold-700 dark:text-marigold-500 text-base font-semibold">
        Requirements
      </h3>
      {requirements.primary_item && (
        <div>
          <p className={fieldLabelClassName}>Primary Quest Item</p>
          <div className="mt-1">
            {renderQuestItemCard(requirements.primary_item)}
          </div>
        </div>
      )}
      {requirements.secondary_item && (
        <div>
          <p className={fieldLabelClassName}>Secondary Quest Item</p>
          <div className="mt-1">
            {renderQuestItemCard(requirements.secondary_item)}
          </div>
        </div>
      )}
      {hasSimpleRows && (
        <Dl>
          {requirements.reincarnated_times !== null && (
            <>
              <Dt>Reincarnated Times</Dt>
              <Dd>{requirements.reincarnated_times}</Dd>
            </>
          )}
          {requirements.access_to_map && (
            <>
              <Dt>Access To Map</Dt>
              <Dd>
                <FactualLink
                  id={requirements.access_to_map.id}
                  label={requirements.access_to_map.name}
                  on_click={navigation?.on_open_map}
                />
              </Dd>
            </>
          )}
          {requirements.faction && (
            <>
              <Dt>Faction</Dt>
              <Dd>
                <FactualLink
                  id={requirements.faction.game_map.id}
                  label={requirements.faction.game_map.name}
                  on_click={navigation?.on_open_map}
                />
                {requirements.faction.required_level !== null &&
                  ` (level ${requirements.faction.required_level})`}
              </Dd>
            </>
          )}
          {requirements.faction_loyalty && (
            <>
              <Dt>Faction Loyalty</Dt>
              <Dd>
                <FactualLink
                  id={requirements.faction_loyalty.npc.id}
                  label={requirements.faction_loyalty.npc.name}
                  on_click={navigation?.on_open_npc}
                />
                {requirements.faction_loyalty.required_fame_level !== null &&
                  ` (fame ${requirements.faction_loyalty.required_fame_level})`}
              </Dd>
            </>
          )}
          {currencyRows.map((row) => (
            <React.Fragment key={row.label}>
              <Dt>{row.label}</Dt>
              <Dd>{row.value}</Dd>
            </React.Fragment>
          ))}
        </Dl>
      )}
    </div>
  );
};

export default QuestRequirementsSection;
