import React from 'react';

import QuestItemDetailsProps from './types/quest-item-details-props';
import { formatPercent } from '../../util/format-number';
import DefinitionRow from '../viewable-sections/definition-row';
import InfoLabel from '../viewable-sections/info-label';
import Section from '../viewable-sections/section';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';

const QuestItemDetails = ({ item }: QuestItemDetailsProps) => {
  const renderInfoAlerts = (messages: Array<string | null | undefined>) => {
    const visible = messages.filter(Boolean) as string[];

    if (visible.length === 0) {
      return null;
    }

    return (
      <div className="w-full space-y-2">
        {visible.map((message, index) => (
          <Alert key={`info-${index}`} variant={AlertVariant.INFO}>
            {message}
          </Alert>
        ))}
      </div>
    );
  };

  const renderTextRow = (
    label: string,
    value?: string | null,
    skip: Array<string | null | undefined> = []
  ) => {
    if (value == null) {
      return null;
    }

    if (skip.length > 0 && skip.includes(value)) {
      return null;
    }

    return (
      <DefinitionRow
        left={<InfoLabel label={label} />}
        right={
          <span className="text-gray-800 dark:text-gray-200">{value}</span>
        }
      />
    );
  };

  const renderLocationRow = (
    heading: string,
    name: string,
    map?: string | null
  ) => {
    return (
      <>
        <DefinitionRow
          left={<InfoLabel label={heading} />}
          right={
            <span className="text-gray-800 dark:text-gray-200">{name}</span>
          }
        />
        {renderTextRow('While On Map', map)}
      </>
    );
  };

  const renderQuestMapRow = (map?: string | null) => {
    if (map == null) {
      return null;
    }

    return (
      <DefinitionRow
        left={<InfoLabel label="While On Map" />}
        right={<span className="text-gray-800 dark:text-gray-200">{map}</span>}
      />
    );
  };

  const renderQuestRows = (
    heading: string,
    name: string,
    npc?: string | null,
    map?: string | null
  ) => {
    return (
      <>
        <DefinitionRow
          left={<InfoLabel label={heading} />}
          right={
            <span className="text-gray-800 dark:text-gray-200">{name}</span>
          }
        />
        {renderTextRow('For NPC', npc)}
        {renderQuestMapRow(map)}
      </>
    );
  };

  const renderModifiersSection = (showSeparator: boolean) => {
    const hasMove = item.move_time_out_mod_bonus !== 0;
    const hasFight = item.fight_time_out_mod_bonus !== 0;

    if (!hasMove && !hasFight) {
      return null;
    }

    const messages: string[] = [];
    if (hasFight) {
      messages.push(
        'This quest item stacks with other items and skills to decrease the time-out between manual fights.'
      );
    }
    if (hasMove) {
      messages.push(
        'This quest item stacks with other items and skills to decrease the time between movement on all maps, including teleport and directional movement.'
      );
    }

    const renderModifierRow = (label: string, percentValue: number) => {
      if (percentValue === 0) {
        return null;
      }

      const proportion = percentValue / 100;

      return (
        <DefinitionRow
          left={<InfoLabel label={label} />}
          right={
            <span className="font-semibold text-emerald-700 dark:text-emerald-300">
              {formatPercent(proportion)}
            </span>
          }
        />
      );
    };

    return (
      <Section
        title="Modifiers"
        showSeparator={showSeparator}
        lead={renderInfoAlerts(messages)}
      >
        {renderModifierRow(
          'Fight Timeout Modifier',
          item.fight_time_out_mod_bonus
        )}
        {renderModifierRow(
          'Move Timeout Modifier',
          item.move_time_out_mod_bonus
        )}
      </Section>
    );
  };

  const renderMonsterDropsSection = (showSeparator: boolean) => {
    if (item.required_monster == null) {
      return null;
    }

    const lead = (
      <Alert variant={AlertVariant.INFO}>
        The monster {item.required_monster.name} has a chance to drop this quest
        item when defeated in manual fights or during automated exploration.
      </Alert>
    );

    return (
      <Section
        title="Monster That Drops It"
        showSeparator={showSeparator}
        lead={lead}
      >
        {renderLocationRow(
          'Monster',
          item.required_monster.name,
          item.required_monster.map
        )}
      </Section>
    );
  };

  const renderQuestsThatUseSection = (showSeparator: boolean) => {
    const single = item.required_quest != null;
    const list = item.required_quests || [];
    const total = (single ? 1 : 0) + list.length;

    if (total === 0) {
      return null;
    }

    let lead = null;
    if (total > 1) {
      lead = (
        <Alert variant={AlertVariant.INFO}>
          This quest item is used in the following quests as a required item.
        </Alert>
      );
    } else if (
      item.required_quest &&
      item.required_quest.npc &&
      item.required_quest.map
    ) {
      lead = (
        <Alert variant={AlertVariant.INFO}>
          The NPC {item.required_quest.npc} lives on this map:{' '}
          {item.required_quest.map}.
        </Alert>
      );
    } else if (list.length === 1 && list[0].npc && list[0].map) {
      lead = (
        <Alert variant={AlertVariant.INFO}>
          The NPC {list[0].npc} lives on this map: {list[0].map}.
        </Alert>
      );
    }

    const renderRequiredQuestSingle = () => {
      if (item.required_quest == null) {
        return null;
      }

      return renderQuestRows(
        'Used In Quest',
        item.required_quest.name,
        item.required_quest.npc,
        item.required_quest.map
      );
    };

    const renderRequiredQuestsList = () => {
      if (list.length === 0) {
        return null;
      }

      return list.map((requiredQuest) => (
        <React.Fragment key={`required-quest-${requiredQuest.id}`}>
          {renderQuestRows(
            'Used In Quest',
            requiredQuest.name,
            requiredQuest.npc,
            requiredQuest.map
          )}
        </React.Fragment>
      ));
    };

    return (
      <Section
        title="Quests That Use This Item"
        showSeparator={showSeparator}
        lead={lead}
      >
        {renderRequiredQuestSingle()}
        {renderRequiredQuestsList()}
      </Section>
    );
  };

  const renderLocationsRequireSection = (showSeparator: boolean) => {
    const requiredLocations = item.required_locations || [];

    if (requiredLocations.length === 0) {
      return null;
    }

    return (
      <Section
        title="Locations That Require This Item"
        showSeparator={showSeparator}
      >
        {requiredLocations.map((requiredLocation) => (
          <React.Fragment key={`required-location-${requiredLocation.id}`}>
            {renderLocationRow(
              'Required Location',
              requiredLocation.name,
              requiredLocation.map
            )}
          </React.Fragment>
        ))}
      </Section>
    );
  };

  const renderRewardLocationsSection = (showSeparator: boolean) => {
    const rewardLocations = item.reward_locations || [];

    if (rewardLocations.length === 0) {
      return null;
    }

    const isPlural = rewardLocations.length !== 1;
    const sectionTitle = isPlural
      ? 'Locations That Reward for Visiting'
      : 'Location That Rewards for Visiting';
    const rowLabel = isPlural ? 'Reward Location(s)' : 'Reward Location';

    const lead = renderInfoAlerts([
      'When you visit this location by teleporting or simply moving to it, you will be instantly rewarded with this item.',
    ]);

    return (
      <Section title={sectionTitle} showSeparator={showSeparator} lead={lead}>
        {rewardLocations.map((rewardLocation) => (
          <React.Fragment key={`reward-location-${rewardLocation.id}`}>
            <DefinitionRow
              left={<InfoLabel label={rowLabel} />}
              right={
                <span className="text-gray-800 dark:text-gray-200">
                  {rewardLocation.name}
                </span>
              }
            />
            {renderTextRow('While On Map', rewardLocation.map)}
          </React.Fragment>
        ))}
      </Section>
    );
  };

  const renderRewardQuestsSection = (showSeparator: boolean) => {
    const rewardQuests = item.reward_quests || [];

    if (rewardQuests.length === 0) {
      return null;
    }

    return (
      <Section
        title="Quests That Reward for Completing"
        showSeparator={showSeparator}
      >
        {rewardQuests.map((rewardQuest) => (
          <React.Fragment key={`reward-quest-${rewardQuest.id}`}>
            {renderQuestRows(
              'Reward Quest',
              rewardQuest.name,
              rewardQuest.npc,
              rewardQuest.map
            )}
          </React.Fragment>
        ))}
      </Section>
    );
  };

  const renderDropSection = (showSeparator: boolean) => {
    if (item.drop_location == null) {
      return null;
    }

    return (
      <Section title="Drop" showSeparator={showSeparator}>
        {renderLocationRow(
          'Drops At',
          item.drop_location.name,
          item.drop_location.map
        )}
      </Section>
    );
  };

  const builders = [
    renderModifiersSection,
    renderMonsterDropsSection,
    renderQuestsThatUseSection,
    renderLocationsRequireSection,
    renderRewardLocationsSection,
    renderRewardQuestsSection,
    renderDropSection,
  ];

  const enabledBuilders = builders.filter((build) => build(true) != null);
  const lastIndex = enabledBuilders.length - 1;

  return (
    <div className="max-w-none flex flex-col gap-2">
      {enabledBuilders.map((build, index) => build(index !== lastIndex))}
    </div>
  );
};

export default QuestItemDetails;
