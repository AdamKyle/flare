import React, { ReactNode } from 'react';

import FactualLink from '../../quest-item/partials/factual-link';
import QuestDetailProps from '../types/quest-detail-props';

import Card from 'ui/cards/card';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const QuestRequirementsSection = ({
  quest,
  navigation,
}: QuestDetailProps): ReactNode => {
  const { requirements } = quest;

  const renderPrimaryItem = (): ReactNode => {
    if (!requirements.primary_item) {
      return 'None';
    }

    return (
      <FactualLink
        id={requirements.primary_item.item_id}
        label={requirements.primary_item.name}
        on_click={navigation?.on_open_item}
      />
    );
  };

  const renderSecondaryItem = (): ReactNode => {
    if (!requirements.secondary_item) {
      return 'None';
    }

    return (
      <FactualLink
        id={requirements.secondary_item.item_id}
        label={requirements.secondary_item.name}
        on_click={navigation?.on_open_item}
      />
    );
  };

  const renderAccessMap = (): ReactNode => {
    if (!requirements.access_to_map) {
      return 'None';
    }

    return (
      <FactualLink
        id={requirements.access_to_map.id}
        label={requirements.access_to_map.name}
        on_click={navigation?.on_open_map}
      />
    );
  };

  const renderFaction = (): ReactNode => {
    if (!requirements.faction) {
      return 'None';
    }

    return (
      <>
        <FactualLink
          id={requirements.faction.game_map.id}
          label={requirements.faction.game_map.name}
          on_click={navigation?.on_open_map}
        />
        {requirements.faction.required_level !== null &&
          ` (level ${requirements.faction.required_level})`}
      </>
    );
  };

  const renderFactionLoyalty = (): ReactNode => {
    if (!requirements.faction_loyalty) {
      return 'None';
    }

    return (
      <>
        <FactualLink
          id={requirements.faction_loyalty.npc.id}
          label={requirements.faction_loyalty.npc.name}
          on_click={navigation?.on_open_npc}
        />
        {requirements.faction_loyalty.required_fame_level !== null &&
          ` (fame ${requirements.faction_loyalty.required_fame_level})`}
      </>
    );
  };

  return (
    <Card>
      <h2 className="text-glacier-900 dark:text-glacier-100 mb-2 text-sm font-semibold">
        Requirements
      </h2>
      <Dl>
        <Dt>Primary Quest Item</Dt>
        <Dd>{renderPrimaryItem()}</Dd>
        <Dt>Secondary Quest Item</Dt>
        <Dd>{renderSecondaryItem()}</Dd>
        <Dt>Reincarnated Times</Dt>
        <Dd>{requirements.reincarnated_times ?? 'None'}</Dd>
        <Dt>Access To Map</Dt>
        <Dd>{renderAccessMap()}</Dd>
        <Dt>Faction</Dt>
        <Dd>{renderFaction()}</Dd>
        <Dt>Faction Loyalty</Dt>
        <Dd>{renderFactionLoyalty()}</Dd>
        <Dt>Gold</Dt>
        <Dd>{requirements.currencies.gold ?? 0}</Dd>
        <Dt>Gold Dust</Dt>
        <Dd>{requirements.currencies.gold_dust ?? 0}</Dd>
        <Dt>Shards</Dt>
        <Dd>{requirements.currencies.shards ?? 0}</Dd>
        <Dt>Copper Coins</Dt>
        <Dd>{requirements.currencies.copper_coins ?? 0}</Dd>
      </Dl>
    </Card>
  );
};

export default QuestRequirementsSection;
