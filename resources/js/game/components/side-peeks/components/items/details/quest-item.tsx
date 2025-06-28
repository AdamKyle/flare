import React from 'react';

import QuestItemDetails from './quest-item-details';
import QuestItemDetailsProps from '../types/details/quest-item-details-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

export const QuestItem = ({
  item,
  on_go_back,
  location_props,
  is_found_at_location,
}: QuestItemDetailsProps) => {
  return (
    <div className="flex flex-col h-full overflow-hidden">
      <div className="flex justify-center p-4">
        <Button
          on_click={() => on_go_back()}
          label="Go back"
          variant={ButtonVariant.PRIMARY}
        />
      </div>
      <hr className="w-full border-t border-gray-300 dark:border-gray-600" />
      <div className="pt-2 px-4">
        <QuestItemDetails
          item={item}
          is_found_at_location={is_found_at_location}
          location_props={location_props}
        />
      </div>
    </div>
  );
};

export default QuestItem;
