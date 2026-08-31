import React, { ReactNode } from 'react';

import QuestDependenciesSection from './quest-dependencies-section';
import QuestGiverSection from './quest-giver-section';
import QuestRequirementsSection from './quest-requirements-section';
import QuestRewardsSection from './quest-rewards-section';
import QuestStorySection from './quest-story-section';
import FactualLink from '../../quest-item/partials/factual-link';
import { QUEST_KIND_LABELS } from '../enums/quest-kind';
import QuestDetailProps from '../types/quest-detail-props';

import Card from 'ui/cards/card';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

/**
 * Shared, permission-neutral factual Quest detail presentation. Never
 * checks Admin permission, imports Admin APIs, or mutates data; navigation
 * is entirely driven by the optional callbacks in `navigation`.
 */
const QuestDetail = ({ quest, navigation }: QuestDetailProps): ReactNode => {
  const renderRaid = (): ReactNode => {
    if (!quest.availability.raid) {
      return 'None';
    }

    return (
      <FactualLink
        id={quest.availability.raid.id}
        label={quest.availability.raid.name}
        on_click={navigation?.on_open_raid}
      />
    );
  };

  return (
    <div className="flex flex-col gap-6">
      <div>
        <h1 className="text-glacier-900 dark:text-glacier-100 text-xl font-semibold">
          {quest.name}
        </h1>
        <p className="text-glacier-600 dark:text-glacier-400 text-sm">
          {QUEST_KIND_LABELS[quest.kind]}
        </p>
      </div>

      <QuestStorySection quest={quest} navigation={navigation} />
      <QuestGiverSection quest={quest} navigation={navigation} />
      <QuestDependenciesSection quest={quest} navigation={navigation} />
      <QuestRequirementsSection quest={quest} navigation={navigation} />
      <QuestRewardsSection quest={quest} navigation={navigation} />

      <Card>
        <h2 className="text-glacier-900 dark:text-glacier-100 mb-2 text-sm font-semibold">
          Availability
        </h2>
        <Dl>
          <Dt>Raid</Dt>
          <Dd>{renderRaid()}</Dd>
          <Dt>Event Restriction</Dt>
          <Dd>{quest.availability.only_for_event ?? 'None'}</Dd>
        </Dl>
      </Card>
    </div>
  );
};

export default QuestDetail;
