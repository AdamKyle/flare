import React from 'react';

import SelectedCelestialDetailsProps from './types/selected-celestial-details-props';

import { formatNumberWithCommas } from 'game-utils/format-number';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import Separator from 'ui/separator/separator';

const SelectedCelestialDetails = ({
  monster,
  on_view_details,
}: SelectedCelestialDetailsProps) => {
  return (
    <div className="mt-4 space-y-2 rounded-lg border border-solid border-gray-200 bg-gray-100 p-4 text-sm dark:border-gray-800 dark:bg-gray-700">
      <h3 className="text-base font-semibold text-gray-900 dark:text-gray-100">
        {monster.name}
      </h3>

      <Separator />

      <Dl>
        <Dt>Strength:</Dt>
        <Dd>{formatNumberWithCommas(monster.str)}</Dd>

        <Dt>Dexterity:</Dt>
        <Dd>{formatNumberWithCommas(monster.dex)}</Dd>

        <Dt>Intelligence:</Dt>
        <Dd>{formatNumberWithCommas(monster.int)}</Dd>

        <Dt>Durability:</Dt>
        <Dd>{formatNumberWithCommas(monster.dur)}</Dd>

        <Dt>Agility:</Dt>
        <Dd>{formatNumberWithCommas(monster.agi)}</Dd>

        <Dt>Charisma:</Dt>
        <Dd>{formatNumberWithCommas(monster.chr)}</Dd>

        <Dt>Focus:</Dt>
        <Dd>{formatNumberWithCommas(monster.focus)}</Dd>

        <Dt>AC:</Dt>
        <Dd>{formatNumberWithCommas(monster.ac)}</Dd>

        <Dt>Health Range:</Dt>
        <Dd>{monster.health_range}</Dd>

        <Dt>Attack Range:</Dt>
        <Dd>{monster.attack_range}</Dd>
      </Dl>

      <Button
        on_click={on_view_details}
        label="See Additional Details"
        variant={ButtonVariant.SUCCESS}
        additional_css={'w-full'}
      />
    </div>
  );
};

export default SelectedCelestialDetails;
