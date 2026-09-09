import React, { Fragment, ReactNode } from 'react';

import QuestChildQuestsSection from './quest-child-quests-section';
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

import DetailGridRow from 'ui/detail-grid/detail-grid-row';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import Separator from 'ui/separator/separator';

const QuestDetail = ({
  quest,
  navigation,
  completed_quest_ids: completedQuestIds,
  quest_item_ownership: questItemOwnership,
  presentation = 'page',
}: QuestDetailProps): ReactNode => {
  const isSidePeek = presentation === 'side-peek';

  const renderRow = (sections: ReactNode[]): ReactNode => {
    const visibleSections = sections.filter((section) => section !== null);

    if (visibleSections.length === 0) {
      return null;
    }

    if (isSidePeek) {
      return (
        <div className="flex flex-col gap-6">
          {visibleSections.map((section, index) => (
            <Fragment key={index}>
              {index > 0 && <Separator additional_css="my-1" />}
              {section}
            </Fragment>
          ))}
        </div>
      );
    }

    return (
      <DetailGridRow>
        {visibleSections.map((section, index) => (
          <Fragment key={index}>{section}</Fragment>
        ))}
      </DetailGridRow>
    );
  };

  const hasStory = Boolean(
    quest.story.before_completion_markdown ||
    quest.story.after_completion_markdown
  );

  const hasQuestGiver = Boolean(quest.npc);

  const hasChildQuests = quest.structure.child_quests.length > 0;

  const hasOtherDependencies =
    Boolean(quest.structure.parent_quest) ||
    Boolean(quest.structure.required_quest) ||
    quest.structure.required_quest_chain.length > 0;

  const hasRequirements =
    Boolean(quest.requirements.primary_item) ||
    Boolean(quest.requirements.secondary_item) ||
    quest.requirements.reincarnated_times !== null ||
    Boolean(quest.requirements.access_to_map) ||
    Boolean(quest.requirements.faction) ||
    Boolean(quest.requirements.faction_loyalty) ||
    (quest.requirements.currencies.gold ?? 0) !== 0 ||
    (quest.requirements.currencies.gold_dust ?? 0) !== 0 ||
    (quest.requirements.currencies.shards ?? 0) !== 0 ||
    (quest.requirements.currencies.copper_coins ?? 0) !== 0;

  const hasRewards =
    Boolean(quest.rewards.item) ||
    Boolean(quest.rewards.skill) ||
    Boolean(quest.rewards.feature) ||
    Boolean(quest.rewards.passive) ||
    (quest.rewards.gold ?? 0) !== 0 ||
    (quest.rewards.gold_dust ?? 0) !== 0 ||
    (quest.rewards.shards ?? 0) !== 0 ||
    (quest.rewards.xp ?? 0) !== 0;

  const hasRestrictions =
    quest.availability.raid !== null ||
    quest.availability.only_for_event !== null;

  const renderRaid = (): ReactNode => {
    if (!quest.availability.raid) {
      return null;
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
      return null;
    }

    return isEventType(eventValue)
      ? getEventTypeName(eventValue)
      : String(eventValue);
  };

  const renderRestrictionsContent = (): ReactNode => (
    <div>
      <h3 className="text-marigold-700 dark:text-marigold-500 mb-2 text-base font-semibold">
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
  );

  const hasGiverOrChildRow = hasQuestGiver || hasChildQuests;
  const hasRequirementsOrRewardsRow = hasRequirements || hasRewards;

  const rows: { present: boolean; node: ReactNode }[] = [
    {
      present: hasStory,
      node: <QuestStorySection quest={quest} navigation={navigation} />,
    },
    {
      present: hasGiverOrChildRow,
      node: renderRow([
        hasQuestGiver ? (
          <QuestGiverSection quest={quest} navigation={navigation} />
        ) : null,
        hasChildQuests ? (
          <QuestChildQuestsSection
            quest={quest}
            navigation={navigation}
            completed_quest_ids={completedQuestIds}
          />
        ) : null,
      ]),
    },
    {
      present: hasRequirementsOrRewardsRow,
      node: renderRow([
        hasRequirements ? (
          <QuestRequirementsSection
            quest={quest}
            navigation={navigation}
            quest_item_ownership={questItemOwnership}
          />
        ) : null,
        hasRewards ? (
          <QuestRewardsSection
            quest={quest}
            navigation={navigation}
            quest_item_ownership={questItemOwnership}
          />
        ) : null,
      ]),
    },
    {
      present: hasOtherDependencies,
      node: (
        <QuestDependenciesSection
          quest={quest}
          navigation={navigation}
          completed_quest_ids={completedQuestIds}
        />
      ),
    },
    { present: hasRestrictions, node: renderRestrictionsContent() },
  ];

  const renderSections = (): ReactNode => {
    let hasRenderedSection = false;

    return rows.map((row, index) => {
      if (!row.present) {
        return null;
      }

      const showSeparator = hasRenderedSection;
      hasRenderedSection = true;

      return (
        <Fragment key={index}>
          {showSeparator && <Separator additional_css="my-1" />}
          {row.node}
        </Fragment>
      );
    });
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

      {renderSections()}
    </div>
  );
};

export default QuestDetail;
