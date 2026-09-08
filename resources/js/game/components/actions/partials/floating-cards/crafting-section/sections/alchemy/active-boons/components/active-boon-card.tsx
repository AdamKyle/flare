import React, { ReactNode } from 'react';

import ActiveBoonCardProps from './types/active-boon-card-props';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LoadingButton from 'ui/buttons/loading-button';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import TimerBar from 'ui/timer-bar/timer-bar';

const ActiveBoonCard = ({
  boon,
  filling,
  removing,
  on_view_source_item: onViewSourceItem,
  on_fill_up: onFillUp,
  on_remove: onRemove,
}: ActiveBoonCardProps): ReactNode => {
  const totalDurationSeconds = boon.last_for_minutes * 60;

  const fillUpDisabled = boon.amount_left === 0 || filling || removing;
  const removeDisabled = filling || removing;

  return (
    <article className="border-wisp-pink-800 dark:border-wisp-pink-500 bg-wisp-pink-200 dark:bg-wisp-pink-950/55 text-wisp-pink-700 dark:text-wisp-pink-200 w-full rounded-lg border-2 p-4">
      <div className="flex flex-col gap-3">
        <button
          type="button"
          onClick={onViewSourceItem}
          className="focus:ring-danube-500 dark:focus:ring-danube-300 text-danube-700 dark:text-danube-300 rounded-sm text-left text-base font-semibold hover:underline focus:underline focus:ring-2 focus:outline-none"
        >
          {boon.boon_applied.name}
        </button>

        <Dl>
          <Dt>Amount Used</Dt>
          <Dd>{boon.amount_used}</Dd>
          <Dt>Amount Left</Dt>
          <Dd>{boon.amount_left}</Dd>
        </Dl>

        <TimerBar
          length={totalDurationSeconds}
          complete_at={boon.complete}
          detailed_time
          title="Time Remaining"
        />

        <div className="flex flex-wrap justify-end gap-2">
          <LoadingButton
            label="Fill Up"
            loading_label="Filling Up..."
            variant={ButtonVariant.ALCHEMY}
            disabled={fillUpDisabled}
            is_loading={filling}
            on_click={onFillUp}
          />
          <LoadingButton
            label="Remove Boon"
            loading_label="Removing..."
            variant={ButtonVariant.DANGER}
            disabled={removeDisabled}
            is_loading={removing}
            on_click={onRemove}
          />
        </div>
      </div>
    </article>
  );
};

export default ActiveBoonCard;
