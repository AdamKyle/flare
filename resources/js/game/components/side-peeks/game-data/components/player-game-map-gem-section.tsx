import React, { ReactNode } from 'react';

import PlayerGameMapGemSectionProps from './types/player-game-map-gem-section-props';
import GemWorldSourceDefinition from '../../../../reusable-components/gems/api/definitions/gem-world-source-definition';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const PlayerGameMapGemSection = ({
  gem_context: gemContext,
  on_view_gem_profile: onViewGemProfile,
  on_view_gem_effects: onViewGemEffects,
}: PlayerGameMapGemSectionProps): ReactNode => {
  const renderSource = (source: GemWorldSourceDefinition): ReactNode => (
    <React.Fragment key={`${source.type}-${source.profile_id}`}>
      <Dt>{source.type === 'map_gem' ? 'Map Gem' : 'Location Gem'}</Dt>
      <Dd>{source.rolled_gem.name}</Dd>
    </React.Fragment>
  );

  return (
    <section className="col-span-full">
      <h2 className="text-glacier-900 dark:text-glacier-100 mb-2 text-sm font-semibold">
        Gems
      </h2>
      <Dl>{gemContext.sources.map(renderSource)}</Dl>
      <div className="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
        <Button
          label="View Gem Profile"
          variant={ButtonVariant.PRIMARY}
          additional_css="w-full"
          on_click={onViewGemProfile}
        />
        <Button
          label="View Gem Effects"
          variant={ButtonVariant.PRIMARY}
          additional_css="w-full"
          on_click={onViewGemEffects}
        />
      </div>
    </section>
  );
};

export default PlayerGameMapGemSection;
