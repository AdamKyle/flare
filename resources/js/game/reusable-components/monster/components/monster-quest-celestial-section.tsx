import React, { ReactNode } from 'react';

import FactualLink from '../../quest-item/partials/factual-link';
import MonsterDetailProps from '../types/monster-detail-props';

import Card from 'ui/cards/card';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const MonsterQuestCelestialSection = ({
  monster,
  navigation,
}: MonsterDetailProps): ReactNode => {
  const { quest_and_celestial: section } = monster;

  const renderQuestItem = (): ReactNode => {
    if (!section.quest_item) {
      return 'None';
    }

    return (
      <FactualLink
        id={section.quest_item.item_id}
        label={section.quest_item.name}
        on_click={navigation?.on_open_item}
      />
    );
  };

  return (
    <Card>
      <h2 className="text-glacier-900 dark:text-glacier-100 mb-2 text-sm font-semibold">
        Quest &amp; Celestial
      </h2>
      <Dl>
        <Dt>Quest Item</Dt>
        <Dd>{renderQuestItem()}</Dd>
        <Dt>Quest Item Drop Chance</Dt>
        <Dd>{section.quest_item_drop_chance ?? 0}</Dd>
        <Dt>Celestial Entity</Dt>
        <Dd>{section.is_celestial_entity ? 'Yes' : 'No'}</Dd>
        <Dt>Celestial Type</Dt>
        <Dd>{section.celestial_type ?? 'None'}</Dd>
        <Dt>Gold Cost</Dt>
        <Dd>{section.gold_cost ?? 0}</Dd>
        <Dt>Gold Dust Cost</Dt>
        <Dd>{section.gold_dust_cost ?? 0}</Dd>
        <Dt>Shards</Dt>
        <Dd>{section.shards ?? 0}</Dd>
      </Dl>
    </Card>
  );
};

export default MonsterQuestCelestialSection;
