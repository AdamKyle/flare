import { isNil } from 'lodash';
import React from 'react';

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
  const [isLocationDetailsOpen, setIsLocationDetailsOpen] =
    React.useState(false);

  const handleOpenLocationDetails = () => {
    setIsLocationDetailsOpen(true);
  };

  const handleCloseLocationDetails = () => {
    setIsLocationDetailsOpen(false);
  };

  const renderHowToFind = () => {
    if (is_found_at_location && !isNil(item.drop_location)) {
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

  const renderRequirements = () => {
    const quests = [
      ...(item.required_quest ? [item.required_quest] : []),
      ...(Array.isArray(item.required_quests) ? item.required_quests : []),
    ];

    if (quests.length === 0) {
      return null;
    }

    const renderQuest = (q: (typeof quests)[number]) => {
      return (
        <Dl key={`req-quest-${q.id}`}>
          <Dt>Quest</Dt>
          <Dd>{q.name}</Dd>
          {q.npc ? (
            <>
              <Dt>For NPC</Dt>
              <Dd>{q.npc}</Dd>
            </>
          ) : null}
          {q.map ? (
            <>
              <Dt>On Map</Dt>
              <Dd>{q.map}</Dd>
            </>
          ) : null}
        </Dl>
      );
    };

    return (
      <>
        <h4 className="mt-4 mb-1 text-xs font-semibold uppercase tracking-wide text-mango-tango-500 dark:text-mango-tango-300">
          Requirements
        </h4>
        <hr className="w-full border-t border-gray-300 dark:border-gray-600 mb-2" />
        <p className="italic text-gray-800 dark:text-gray-300 mb-2">
          this quest item is used in the following quest
          {quests.length > 1 ? 's' : ''}:
        </p>
        <div className="space-y-2">{quests.map(renderQuest)}</div>
      </>
    );
  };

  if (isLocationDetailsOpen && location_props) {
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
      {renderRequirements()}
      {renderHowToFind()}
    </>
  );
};

export default QuestItemDetails;
