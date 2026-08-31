import React, { ReactNode } from 'react';

import QuestDependenciesSection from './quest-dependencies-section';
import QuestGiverSection from './quest-giver-section';
import QuestRequirementsSection from './quest-requirements-section';
import QuestRewardsSection from './quest-rewards-section';
import QuestStorySection from './quest-story-section';
import {
  getEventTypeName,
  isEventType,
} from '../../../components/announcements/enums/EventType';
import FactualLink from '../../quest-item/partials/factual-link';
import { QUEST_KIND_LABELS } from '../enums/quest-kind';
import QuestDetailProps from '../types/quest-detail-props';

import Card from 'ui/cards/card';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import Separator from 'ui/separator/separator';

/**
 * Shared, permission-neutral factual Quest detail presentation. Never
 * checks Admin permission, imports Admin APIs, or mutates data; navigation
 * is entirely driven by the optional callbacks in `navigation`.
 */
const QuestDetail = ({ quest, navigation }: QuestDetailProps): ReactNode => {
  const hasRestrictions =
    quest.availability.raid !== null ||
    quest.availability.only_for_event !== null;

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

  const renderEventRestriction = (): ReactNode => {
    const eventValue = quest.availability.only_for_event;

    if (eventValue === null) {
      return 'None';
    }

    return isEventType(eventValue)
      ? getEventTypeName(eventValue)
      : String(eventValue);
  };

  const renderRestrictions = (): ReactNode => {
    if (!hasRestrictions) {
      return null;
    }

    return (
      <>
        <Separator />
        <div>
          <h3 className="text-glacier-900 dark:text-glacier-100 mb-2 text-sm font-semibold">
            Restrictions
          </h3>
          <Dl>
            {quest.availability.raid && (
              <>
                <Dt>Raid</Dt>
                <Dd>{renderRaid()}</Dd>
              </>
            )}
            {quest.availability.only_for_event !== null && (
              <>
                <Dt>Event Restriction</Dt>
                <Dd>{renderEventRestriction()}</Dd>
              </>
            )}
          </Dl>
        </div>
      </>
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

      <Card>
        <QuestStorySection quest={quest} navigation={navigation} />
      </Card>

      <Card>
        <h2 className="text-glacier-900 dark:text-glacier-100 mb-4 text-sm font-semibold">
          Quest Details
        </h2>
        <div className="flex flex-col gap-4">
          <QuestGiverSection quest={quest} navigation={navigation} />
          <Separator />
          <QuestDependenciesSection quest={quest} navigation={navigation} />
          <Separator />
          <QuestRequirementsSection quest={quest} navigation={navigation} />
          <Separator />
          <QuestRewardsSection quest={quest} navigation={navigation} />
          {renderRestrictions()}
        </div>
      </Card>
    </div>
  );
};

export default QuestDetail;
