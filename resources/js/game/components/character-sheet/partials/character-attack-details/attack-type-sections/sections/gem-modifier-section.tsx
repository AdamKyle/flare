import React, { ReactNode } from 'react';

import { characterGemModifierLabels } from '../../../../../../reusable-components/character-gem/enums/character-gem-modifier-type';
import { GemModifierDetail } from '../../api/definitions/character-attack-break-down-definition';

import Separator from 'ui/separator/separator';

interface GemModifierSectionProps {
  details?: GemModifierDetail[];
}

const GemModifierSection = ({
  details = [],
}: GemModifierSectionProps): ReactNode => {
  if (details.length === 0) {
    return null;
  }

  return (
    <section className="mt-4 space-y-2">
      <h4 className="text-center">Gems affecting this stat</h4>
      <Separator />
      <ul className="list-inside list-disc space-y-1">
        {details.map((detail) => (
          <li key={`${detail.gem_id}-${detail.modifier_type}`}>
            {detail.gem_name} — {detail.item_name}:{' '}
            {detail.ability_name ??
              characterGemModifierLabels[detail.modifier_type]}
            {detail.amount === null
              ? ''
              : ` +${(detail.amount * 100).toFixed(2)}%`}
            {detail.attack_types?.length
              ? ` (${detail.attack_types.join(', ')})`
              : ''}
          </li>
        ))}
      </ul>
    </section>
  );
};

export default GemModifierSection;
