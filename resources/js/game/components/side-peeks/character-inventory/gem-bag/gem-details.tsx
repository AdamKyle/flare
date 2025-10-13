import clsx from 'clsx';
import React from 'react';

import GemDetailsProps from './types/gem-details-props';
import DefinitionRow from '../../../../reusable-components/viewable-sections/definition-row';
import InfoLabel from '../../../../reusable-components/viewable-sections/info-label';
import Section from '../../../../reusable-components/viewable-sections/section';
import { formatPercent } from '../../../../util/format-number';
import { getGemSlotTitleTextColor } from '../../../character-sheet/partials/character-inventory/styles/gem-slot-styles';

import Separator from 'ui/separator/separator';

const GemDetails = ({ gem }: GemDetailsProps) => {
  const itemColor = getGemSlotTitleTextColor(gem);

  const atonements: Record<string, number> = {
    [gem.primary_atonement_type]: gem.primary_atonement_amount,
    [gem.secondary_atonement_type]: gem.secondary_atonement_amount,
    [gem.tertiary_atonement_type]: gem.tertiary_atonement_amount,
  };

  return (
    <>
      <div className="px-4 flex flex-col gap-2">
        <h2 className={clsx('text-lg my-2', itemColor)}>{gem.name}</h2>
        <Separator />

        <Section title="Basic Info" showSeparator>
          <DefinitionRow
            left={<InfoLabel label="Tier" />}
            right={
              <span className="text-gray-800 dark:text-gray-200">
                {gem.tier}
              </span>
            }
          />
          <DefinitionRow
            left={<InfoLabel label="Atoned To" />}
            right={
              <span className="text-gray-800 dark:text-gray-200">
                {gem.element_atoned_to}
              </span>
            }
          />
          <DefinitionRow
            left={<InfoLabel label="At" />}
            right={
              <span className="text-gray-800 dark:text-gray-200">
                {formatPercent(gem.element_atoned_to_amount)}
              </span>
            }
          />
        </Section>

        <p className="text-gray-800 dark:text-gray-300">
          When equipped to an item, the item becomes atoned to whichever gem (or
          combination of gems) provides the highest atonement. This is useful
          against enemies that are weak to that element; for example, in this
          case the gem is strong against {gem.strong_against} but weak against{' '}
          {gem.weak_against}. You’ll deal more damage against enemies that are
          weak to this element and less against those that are strong against
          it.
        </p>
        <p className="text-gray-800 dark:text-gray-300">
          This also applies to your defense: if the enemy is of type{' '}
          {gem.weak_against}, they will deal reduced damage—at a 75% atonement
          cap you take 25% of that element’s damage. Conversely, an enemy of
          type {gem.strong_against} will deal increased damage to you.
        </p>
        <p className="text-gray-800 dark:text-gray-300">
          Damage uses the combined atonement type from all gems across all
          items, multiplied by your weapon damage. For example, if your combined
          atonement is 75% {gem.element_atoned_to}, you’ll deal 75% of your
          weapon damage as {gem.element_atoned_to}.
        </p>

        <Separator />

        <Section title="Elemental Atonements" showSeparator={false}>
          <DefinitionRow
            left={<InfoLabel label="Fire" />}
            right={
              <span className="text-gray-800 dark:text-gray-200">
                {formatPercent(atonements.Fire ?? 0)}
              </span>
            }
          />
          <DefinitionRow
            left={<InfoLabel label="Ice" />}
            right={
              <span className="text-gray-800 dark:text-gray-200">
                {formatPercent(atonements.Ice ?? 0)}
              </span>
            }
          />
          <DefinitionRow
            left={<InfoLabel label="Water" />}
            right={
              <span className="text-gray-800 dark:text-gray-200">
                {formatPercent(atonements.Water ?? 0)}
              </span>
            }
          />
        </Section>
      </div>
    </>
  );
};

export default GemDetails;
