import React, { ReactNode } from 'react';

import SeerGemComparisonProps from './types/seer-gem-comparison-props';
import {
  AttachedGemDefinition,
  ElementalAtonementDefinition,
  ReplacementAttributeDifferencesDefinition,
} from '../api/definitions/gem-comparison-api-response-definition';

const renderAtonements = (gem: AttachedGemDefinition): ReactNode => (
  <dl className="grid grid-cols-1 gap-1 text-sm sm:grid-cols-3">
    <div>
      <dt className="font-semibold">{gem.primary_atonement_name}</dt>
      <dd>{gem.primary_atonement_amount}</dd>
    </div>
    <div>
      <dt className="font-semibold">{gem.secondary_atonement_name}</dt>
      <dd>{gem.secondary_atonement_amount}</dd>
    </div>
    <div>
      <dt className="font-semibold">{gem.tertiary_atonement_name}</dt>
      <dd>{gem.tertiary_atonement_amount}</dd>
    </div>
  </dl>
);

const renderElementalResult = (
  data: ElementalAtonementDefinition
): ReactNode => (
  <div className="space-y-2">
    <dl className="grid grid-cols-2 gap-2 text-sm sm:grid-cols-3">
      {Object.entries(data.atonements).map(([name, amount]) => (
        <div key={name}>
          <dt className="font-semibold">{name}</dt>
          <dd>{amount}</dd>
        </div>
      ))}
    </dl>
    <p>
      <span className="font-semibold">Strongest elemental damage:</span>{' '}
      {data.elemental_damage.name} ({data.elemental_damage.amount})
    </p>
  </div>
);

const getDifferenceDirectionLabel = (amount: number): string => {
  if (amount > 0) {
    return 'increase';
  }

  if (amount < 0) {
    return 'decrease';
  }

  return 'no change';
};

const renderDifference = (amount: number): ReactNode => {
  const direction = getDifferenceDirectionLabel(amount);
  const signedAmount = amount > 0 ? `+${amount}` : `${amount}`;

  return (
    <span>
      {signedAmount} ({direction})
    </span>
  );
};

const renderDifferences = (
  difference: ReplacementAttributeDifferencesDefinition
): ReactNode => {
  const values = [
    [difference.primary_atonement_type, difference.primary_atonement_amount],
    [
      difference.secondary_atonement_type,
      difference.secondary_atonement_amount,
    ],
    [difference.tertiary_atonement_type, difference.tertiary_atonement_amount],
  ] as const;

  return (
    <ul className="list-disc space-y-1 pl-5 text-sm">
      {values.map(([name, amount], index) =>
        name !== undefined && amount !== undefined ? (
          <li key={`${name}-${index}`}>
            {name}: {renderDifference(amount)}
          </li>
        ) : null
      )}
    </ul>
  );
};

const SeerGemComparison = ({
  comparison,
}: SeerGemComparisonProps): ReactNode => {
  const hasAttachedGems = comparison.attached_gems.length > 0;
  const replacementAtonements = comparison.if_replacing_atonements ?? [];

  const renderNoAttachedGems = (): ReactNode => (
    <p>No Gems are currently attached.</p>
  );

  const renderAttachedGemsList = (): ReactNode => (
    <div className="space-y-3">
      <h4 className="font-semibold">Currently attached Gems</h4>
      {comparison.attached_gems.map((gem) => {
        const difference = comparison.when_replacing.find(
          (entry) => entry.gem_you_have_id === gem.id
        );

        return (
          <article
            key={gem.id}
            className="space-y-2 rounded-md border border-gray-300 p-3 dark:border-gray-700"
          >
            <h5 className="font-semibold">
              {gem.name} (Tier {gem.tier})
            </h5>
            {renderAtonements(gem)}
            <h6 className="font-semibold">Replacement differences</h6>
            {difference ? (
              renderDifferences(difference)
            ) : (
              <p>No matching atonement types; no numeric difference applies.</p>
            )}
          </article>
        );
      })}
    </div>
  );

  const renderAttachedGems = (): ReactNode =>
    hasAttachedGems ? renderAttachedGemsList() : renderNoAttachedGems();

  const renderOriginalAtonement = (): ReactNode => {
    if (!comparison.original_atonement) {
      return null;
    }

    return (
      <section className="space-y-2">
        <h4 className="font-semibold">Original item atonement</h4>
        {renderElementalResult(comparison.original_atonement)}
      </section>
    );
  };

  const renderReplacementAtonements = (): ReactNode =>
    replacementAtonements.map((replacement) => (
      <section
        key={replacement.gem_id}
        className="space-y-2 rounded-md border border-gray-300 p-3 dark:border-gray-700"
      >
        <h4 className="font-semibold">
          After replacing {replacement.name_to_replace}
        </h4>
        {renderElementalResult(replacement.data)}
      </section>
    ));

  return (
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
      <section className="space-y-2 rounded-md bg-gray-100 p-3 dark:bg-gray-800">
        <h4 className="font-semibold">
          Gem being attached: {comparison.gem_to_attach.name}
        </h4>
        <p>Tier {comparison.gem_to_attach.tier}</p>
        {renderAtonements(comparison.gem_to_attach)}
      </section>
      {renderAttachedGems()}
      {renderOriginalAtonement()}
      {renderReplacementAtonements()}
    </section>
  );
};

export default SeerGemComparison;
