import React from 'react';

import GemDetailsContentProps from './types/gem-details-content-props';
import DefinitionRow from '../viewable-sections/definition-row';
import InfoLabel from '../viewable-sections/info-label';
import Section from '../viewable-sections/section';
import StatRowPercent from '../viewable-sections/stat-row-percent';

import Separator from 'ui/separator/separator';

const GemDetailsContent = ({ gem }: GemDetailsContentProps) => {
  const atonements: Record<string, number> = {
    [gem.primary_atonement_type]: gem.primary_atonement_amount,
    [gem.secondary_atonement_type]: gem.secondary_atonement_amount,
    [gem.tertiary_atonement_type]: gem.tertiary_atonement_amount,
  };

  return (
    <>
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
        <StatRowPercent label="At" value={gem.element_atoned_to_amount} />
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
        <StatRowPercent label="Fire" value={atonements.Fire ?? 0} />
        <StatRowPercent label="Ice" value={atonements.Ice ?? 0} />
        <StatRowPercent label="Water" value={atonements.Water ?? 0} />
      </Section>
    </>
  );
};

export default GemDetailsContent;
