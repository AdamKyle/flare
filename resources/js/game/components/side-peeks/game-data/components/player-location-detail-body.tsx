import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import PlayerGameMapGemSection from './player-game-map-gem-section';
import PlayerLocationDetailBodyProps from './types/player-location-detail-body-props';
import GemContextEffectsDetail from '../../../../reusable-components/gems/components/gem-context-effects-detail';
import GemContextProfileDetail from '../../../../reusable-components/gems/components/gem-context-profile-detail';
import FactualLink from '../../../../reusable-components/quest-item/partials/factual-link';
import QuestItemFactualPresentation from '../../../../reusable-components/quest-item/quest-item-factual-presentation';
import { LocationApiUrls } from '../../map-actions/api/enums/location-api-urls';
import { useFetchLocationDetailsApi } from '../../map-actions/api/hooks/use-fetch-location-details-api';

import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import Separator from 'ui/separator/separator';

type ActiveGemDetail = 'profile' | 'effects' | null;

const PlayerLocationDetailBody = ({
  location_id: locationId,
  on_open_map: onOpenMap,
}: PlayerLocationDetailBodyProps): ReactNode => {
  const { data, loading, error } = useFetchLocationDetailsApi({
    location_id: locationId,
    url: LocationApiUrls.LOCATION_INFO,
  });
  const [activeGemDetail, setActiveGemDetail] = useState<ActiveGemDetail>(null);

  if (loading) {
    return (
      <div className="px-4">
        <InfiniteLoader />
      </div>
    );
  }

  if (error || !data) {
    return (
      <div className="px-4">
        <ApiErrorAlert
          apiError={error?.message ?? 'Unable to load this Location.'}
        />
      </div>
    );
  }

  const renderGemSection = (): ReactNode => {
    if (!data.gem_context) {
      return null;
    }

    return (
      <>
        <Separator />
        <PlayerGameMapGemSection
          gem_context={data.gem_context}
          on_view_gem_profile={() => setActiveGemDetail('profile')}
          on_view_gem_effects={() => setActiveGemDetail('effects')}
        />
      </>
    );
  };

  const renderGemDetail = (): ReactNode => {
    if (!data.gem_context || activeGemDetail === null) {
      return null;
    }

    return (
      <StackedCard
        on_close={() => setActiveGemDetail(null)}
        aria_label={
          activeGemDetail === 'profile' ? 'Gem Profile' : 'Gem Effects'
        }
        content_mode={StackedCardContentMode.FULL_BLEED}
      >
        {activeGemDetail === 'profile' ? (
          <GemContextProfileDetail
            context={data.gem_context}
            description="These rolled Gems define the modifiers and rules applied at this Location."
          />
        ) : (
          <GemContextEffectsDetail context={data.gem_context} />
        )}
      </StackedCard>
    );
  };

  return (
    <div className="flex flex-col gap-4 px-4">
      <div>
        <h1 className="text-glacier-900 dark:text-glacier-100 text-xl font-semibold">
          {data.name}
        </h1>
        <p className="mt-1 text-gray-700 dark:text-gray-300">
          {data.description}
        </p>
      </div>

      <Dl>
        <Dt>Game Map</Dt>
        <Dd>
          <FactualLink
            id={data.game_map.id}
            label={data.game_map.name}
            on_click={onOpenMap}
          />
        </Dd>
        <Dt>Coordinates</Dt>
        <Dd>
          X {data.x}, Y {data.y}
        </Dd>
        <Dt>Auto Battle</Dt>
        <Dd>{data.can_auto_battle ? 'Available' : 'Unavailable'}</Dd>
        <Dt>Corrupted</Dt>
        <Dd>{data.is_corrupted ? 'Yes' : 'No'}</Dd>
      </Dl>

      {data.required_quest_item ? (
        <>
          <Separator />
          <section>
            <h2 className="text-marigold-700 dark:text-marigold-500 mb-2 text-base font-semibold">
              Required Quest Item
            </h2>
            <QuestItemFactualPresentation item={data.required_quest_item} />
          </section>
        </>
      ) : null}

      {data.quest_reward_item?.data ? (
        <>
          <Separator />
          <section>
            <h2 className="text-marigold-700 dark:text-marigold-500 mb-2 text-base font-semibold">
              Quest Item Reward
            </h2>
            <QuestItemFactualPresentation item={data.quest_reward_item.data} />
          </section>
        </>
      ) : null}

      {renderGemSection()}
      {renderGemDetail()}
    </div>
  );
};

export default PlayerLocationDetailBody;
