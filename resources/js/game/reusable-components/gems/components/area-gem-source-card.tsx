import React, { ReactNode } from 'react';

import AreaGemSourceCardProps from '../types/area-gem-source-card-props';

/**
 * One whole-card native button summarizing a single resolved Area Gem
 * source. Clicking opens the concrete rolled Gem's factual detail.
 */
const AreaGemSourceCard = ({
  source,
  on_click: onClick,
}: AreaGemSourceCardProps): ReactNode => {
  const isMapGem = source.type === 'map_gem';
  const sourceLabel = isMapGem ? 'Map Gem' : 'Location Gem';
  const identityLabel = isMapGem ? 'Game Map' : 'Location';
  const identityName = isMapGem ? source.game_map_name : source.location_name;

  return (
    <button
      type="button"
      onClick={() => onClick(source)}
      aria-label={`${sourceLabel}: ${source.profile_name}`}
      className="focus-visible:ring-danube-500 dark:focus-visible:ring-danube-300 flex w-full flex-col gap-1 rounded-md border border-gray-200 bg-white p-3 text-left hover:bg-gray-50 focus:outline-none focus-visible:ring-2 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700"
    >
      <span className="text-glacier-700 dark:text-glacier-300 text-xs font-semibold tracking-wide uppercase">
        {sourceLabel}
      </span>
      <span className="font-semibold text-gray-900 dark:text-gray-100">
        {source.profile_name}
      </span>
      <span className="text-sm text-gray-600 dark:text-gray-400">
        Rolled Gem: {source.rolled_gem_name}
      </span>
      {identityName && (
        <span className="text-sm text-gray-600 dark:text-gray-400">
          {identityLabel}: {identityName}
        </span>
      )}
      <span className="text-glacier-600 dark:text-glacier-400 text-xs">
        {`Monster ×${source.monster_multiplier} · Rewards ×${source.reward_multiplier}`}
        {source.reduction_multiplier !== null &&
          ` · Reduction ×${source.reduction_multiplier}`}
      </span>
    </button>
  );
};

export default AreaGemSourceCard;
