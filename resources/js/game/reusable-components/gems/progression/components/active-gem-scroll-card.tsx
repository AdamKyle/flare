import React, { ReactNode, useState } from 'react';

import GemScrollPicker from './gem-scroll-picker';
import ActiveGemScrollCardProps from './types/active-gem-scroll-card-props';
import GemScrollFamily from '../types/gem-scroll-family';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LoadingButton from 'ui/buttons/loading-button';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import TimerBar from 'ui/timer-bar/timer-bar';

const scrollTypeLabel = (gemScrollType: GemScrollFamily): string => {
  if (gemScrollType === 'xp') {
    return 'XP Scroll';
  }

  if (gemScrollType === 'currency') {
    return 'Currency Scroll';
  }

  return 'Item Scroll';
};

const ActiveGemScrollCard = ({
  scroll,
  character_id: characterId,
  acting,
  on_remove: onRemove,
  on_fill: onFill,
}: ActiveGemScrollCardProps): ReactNode => {
  const [fillMode, setFillMode] = useState(false);

  const startedAt = new Date(scroll.started_at).getTime();
  const expiresAt = new Date(scroll.expires_at).getTime();
  const totalDurationSeconds = Math.max(
    Math.round((expiresAt - startedAt) / 1000),
    0
  );

  const handleFillSelect = (alchemyBagSlotId: number): void => {
    setFillMode(false);
    onFill(alchemyBagSlotId);
  };

  const renderFillControl = (): ReactNode => {
    if (!fillMode) {
      return null;
    }

    return (
      <GemScrollPicker
        character_id={characterId}
        gem_scroll_type={scroll.gem_scroll_type}
        gem_scroll_currency_type={scroll.gem_scroll_currency_type}
        disabled={acting}
        on_select={handleFillSelect}
      />
    );
  };

  return (
    <article className="border-glacier-200 bg-glacier-50 text-glacier-900 dark:border-glacier-700 dark:bg-glacier-900 dark:text-glacier-100 w-full rounded-lg border-2 p-4">
      <div className="flex flex-col gap-2">
        <div className="flex items-center justify-between gap-2">
          <span className="text-base font-semibold">{scroll.item_name}</span>
          {scroll.is_current_profile ? (
            <span className="bg-de-york-100 text-de-york-800 dark:bg-de-york-900 dark:text-de-york-100 rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap">
              Currently Applied
            </span>
          ) : (
            <span className="bg-glacier-100 text-glacier-800 dark:bg-glacier-800 dark:text-glacier-200 rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap">
              Not Current
            </span>
          )}
        </div>

        <Dl>
          <Dt>Type</Dt>
          <Dd>{scrollTypeLabel(scroll.gem_scroll_type)}</Dd>
          {scroll.gem_scroll_currency_type && (
            <>
              <Dt>Currency</Dt>
              <Dd>{scroll.gem_scroll_currency_type}</Dd>
            </>
          )}
          <Dt>Gem World</Dt>
          <Dd>
            {scroll.generated_game_map_name ?? scroll.profile_name ?? '—'}
          </Dd>
        </Dl>

        <TimerBar
          length={totalDurationSeconds}
          complete_at={scroll.expires_at}
          detailed_time
          title="Time Remaining"
        />
      </div>

      <div className="mt-2 flex flex-wrap justify-end gap-2">
        <LoadingButton
          label="Fill Up"
          loading_label="Filling Up..."
          variant={ButtonVariant.ALCHEMY}
          disabled={acting}
          is_loading={acting}
          on_click={() => setFillMode(true)}
        />
        <LoadingButton
          label="Remove"
          loading_label="Removing..."
          variant={ButtonVariant.DANGER}
          disabled={acting}
          is_loading={acting}
          on_click={onRemove}
        />
      </div>

      {renderFillControl()}
    </article>
  );
};

export default ActiveGemScrollCard;
