import React, { ReactNode, useState } from 'react';

import GemScrollPicker from './gem-scroll-picker';
import ActiveGemScrollCardProps from './types/active-gem-scroll-card-props';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LoadingButton from 'ui/buttons/loading-button';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import TimerBar from 'ui/timer-bar/timer-bar';

const scrollTypeLabel = (gemScrollType: string): string => {
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
        gem_scroll_type={scroll.gem_scroll_type as 'xp' | 'currency' | 'item'}
        gem_scroll_currency_type={scroll.gem_scroll_currency_type}
        disabled={acting}
        on_select={handleFillSelect}
      />
    );
  };

  return (
    <article className="border-glacier-800 dark:border-glacier-500 bg-glacier-100 dark:bg-glacier-100 text-glacier-900 dark:text-glacier-900 w-full rounded-lg border-2 p-4">
      <div className="flex flex-col gap-2">
        <span className="text-base font-semibold">{scroll.item_name}</span>

        <Dl>
          <Dt>Type</Dt>
          <Dd>{scrollTypeLabel(scroll.gem_scroll_type)}</Dd>
          {scroll.gem_scroll_currency_type && (
            <>
              <Dt>Currency</Dt>
              <Dd>{scroll.gem_scroll_currency_type}</Dd>
            </>
          )}
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
