import React, { useState } from 'react';

import LocationDetails from '../../../map-actions/location-details/location-details';
import QuestItemProps from '../types/details/quest-item-props';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LinkButton from 'ui/buttons/link-button';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const QuestItemDetails = ({
  is_found_at_location,
  item,
  location_props,
}: QuestItemProps) => {
  const [isLocationDetailsVisible, setIsLocationDetailsVisible] =
    useState(false);

  const handleOpenLocationDetails = () => {
    setIsLocationDetailsVisible(true);
  };

  const handleCloseLocationDetails = () => {
    setIsLocationDetailsVisible(false);
  };

  const renderHowToFindSection = () => {
    if (is_found_at_location) {
      return null;
    }

    if (!item.drop_location) {
      return null;
    }

    return (
      <Dl>
        <Dt>Drops From Monsters At</Dt>
        <Dd>
          <LinkButton
            label={item.drop_location.name}
            variant={ButtonVariant.PRIMARY}
            on_click={handleOpenLocationDetails}
          />
        </Dd>
      </Dl>
    );
  };

  const renderRequiredMonsterSection = () => {
    const requiredMonster = item.required_monster;

    if (!requiredMonster) {
      return null;
    }

    return (
      <Dl>
        <Dt>Monster</Dt>
        <Dd>{requiredMonster.name}</Dd>
        {requiredMonster.map ? (
          <>
            <Dt>On Map</Dt>
            <Dd>{requiredMonster.map}</Dd>
          </>
        ) : null}
      </Dl>
    );
  };

  const renderRequiredQuestsSection = () => {
    const requiredQuests =
      !item.required_quest &&
      (!Array.isArray(item.required_quests) ||
        item.required_quests.length === 0)
        ? null
        : [
            ...(item.required_quest ? [item.required_quest] : []),
            ...(Array.isArray(item.required_quests)
              ? item.required_quests
              : []),
          ];

    if (!requiredQuests) {
      return null;
    }

    const renderRequiredQuest = (
      requiredQuest: (typeof requiredQuests)[number]
    ) => {
      return (
        <Dl key={`req-quest-${requiredQuest.id}`}>
          <Dt>Quest</Dt>
          <Dd>{requiredQuest.name}</Dd>
          {requiredQuest.npc ? (
            <>
              <Dt>For NPC</Dt>
              <Dd>{requiredQuest.npc}</Dd>
            </>
          ) : null}
          {requiredQuest.map ? (
            <>
              <Dt>On Map</Dt>
              <Dd>{requiredQuest.map}</Dd>
            </>
          ) : null}
        </Dl>
      );
    };

    return (
      <>
        <p className="italic text-gray-800 dark:text-gray-300 mb-2">
          this quest item is used in the following quest
          {requiredQuests.length > 1 ? 's' : ''}:
        </p>
        <div className="space-y-2">
          {requiredQuests.map(renderRequiredQuest)}
        </div>
      </>
    );
  };

  const renderRequirementsSection = () => {
    const hasAnyRequirement =
      Boolean(item.required_monster) ||
      Boolean(item.required_quest) ||
      (Array.isArray(item.required_quests) && item.required_quests.length > 0);

    if (!hasAnyRequirement) {
      return null;
    }

    return (
      <>
        <h4 className="mt-4 mb-1 text-xs font-semibold uppercase tracking-wide text-mango-tango-500 dark:text-mango-tango-300">
          Requirements
        </h4>
        <hr className="w-full border-t border-gray-300 dark:border-gray-600 mb-2" />
        {renderRequiredMonsterSection()}
        {renderRequiredQuestsSection()}
      </>
    );
  };

  if (isLocationDetailsVisible && location_props && item.drop_location) {
    return (
      <LocationDetails
        character_id={location_props.character_id}
        location_id={item.drop_location.id}
        character_x={location_props.character_x}
        character_y={location_props.character_y}
        character_gold={location_props.character_gold}
        is_open={true}
        title={item.drop_location.name}
        on_close={handleCloseLocationDetails}
        show_title={true}
      />
    );
  }

  return (
    <>
      <h2 className="text-lg text-gray-800 dark:text-gray-300 my-4">
        {item.name}
      </h2>
      <p className="text-gray-800 dark:text-gray-300 mb-4">
        {item.description}
      </p>
      <hr className="w-full border-t border-gray-300 dark:border-gray-600" />
      {renderRequirementsSection()}
      {renderHowToFindSection()}
    </>
  );
};

export default QuestItemDetails;
