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
  console.log(item);
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
      return;
    }

    return (
      <Dl>
        <Dt>Drops From Monsters At:</Dt>
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
      <h2 className={'text-lg text-gray-800 dark:text-gray-300 my-4'}>
        {item.name}
      </h2>
      <p className={'text-gray-800 dark:text-gray-300 mb-4'}>
        {item.description}
      </p>
      <hr className="w-full border-t border-gray-300 dark:border-gray-600" />
      {renderHowToFind()}
    </>
  );
};

export default QuestItemDetails;
