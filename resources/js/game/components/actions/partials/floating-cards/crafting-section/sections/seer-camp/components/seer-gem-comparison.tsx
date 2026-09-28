import React, { ReactNode } from 'react';

import SeerGemComparisonProps from './types/seer-gem-comparison-props';
import CharacterGemModifierList from '../../../../../../../../reusable-components/character-gem/character-gem-modifier-list';

const SeerGemComparison = ({
  comparison,
  selectedGemId,
}: SeerGemComparisonProps): ReactNode => (
  <section
    aria-live="polite"
    className="space-y-4 rounded-md border border-gray-300 p-3 dark:border-gray-700"
  >
    <h3 className="text-lg font-semibold">Gem comparison</h3>
    <dl className="grid grid-cols-1 gap-2 sm:grid-cols-2">
      <div>
        <dt className="font-semibold">Selected item</dt>
        <dd>{comparison.socket_data.item_name}</dd>
      </div>
      <div>
        <dt className="font-semibold">Sockets used</dt>
        <dd>
          {comparison.socket_data.current_used_slots} of{' '}
          {comparison.socket_data.item_sockets}
        </dd>
      </div>
    </dl>

    {comparison.attached_gems.length === 0 ? (
      <p>No Gem will be removed from an empty socket.</p>
    ) : selectedGemId === null ? (
      <p>Select an attached Gem to preview what will be removed.</p>
    ) : (
      <section className="space-y-3">
        <h4 className="font-semibold">Will remove</h4>
        {comparison.attached_gems
          .filter((gem) => gem.id === selectedGemId)
          .map((gem) => (
            <article key={gem.id} className="space-y-1">
              <h5 className="font-semibold">
                {gem.name} (Tier {gem.tier})
              </h5>
              <CharacterGemModifierList modifiers={gem.modifiers} />
            </article>
          ))}
      </section>
    )}

    <section className="space-y-1">
      <h4 className="font-semibold">Will add</h4>
      <h5 className="font-semibold">
        {comparison.added_gem.name} (Tier {comparison.added_gem.tier})
      </h5>
      <CharacterGemModifierList modifiers={comparison.added_gem.modifiers} />
    </section>
  </section>
);

export default SeerGemComparison;
