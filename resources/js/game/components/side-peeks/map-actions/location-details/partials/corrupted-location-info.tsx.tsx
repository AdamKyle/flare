import React from 'react';

import LocationInfoSectionProps from '../types/partials/location-info-section-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const CorruptedLocationInfo = ({
  handel_close_info_section,
}: LocationInfoSectionProps) => {
  return (
    <div className={'prose dark:prose-dark dark:text-white py-2 px-4'}>
      <h2>Corrupted Locations</h2>
      <p>
        Corrupted locations only happen when a raid for the plane the location
        is on corrupts that specific location.{' '}
      </p>
      <p>
        When a location becomes corrupted players can still travel to the
        location and receive any items that would drop or bonuses from that
        location, including quest items.
      </p>
      <p>
        Players will, however, find that the enemy list changes to Raid
        Monsters, and in one specific instance they may find the Raid Boss at
        that location.
      </p>
      <p>
        Corrupted Locations contain extremely tough enemies that will, in most
        cases, require the player to reincarnate at least a few times to
        increase their over all attack power.
      </p>
      <Button
        on_click={handel_close_info_section}
        label={'Close'}
        variant={ButtonVariant.PRIMARY}
        additional_css={'w-full'}
      />
    </div>
  );
};

export default CorruptedLocationInfo;
