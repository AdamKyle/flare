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
    <article className="border-wisp-pink-800 dark:border-wisp-pink-500 bg-wisp-pink-100 hover:bg-wisp-pink-200 dark:bg-wisp-pink-100 dark:hover:bg-wisp-pink-200 text-wisp-pink-900 dark:text-wisp-pink-900 w-full rounded-lg border-2 p-4">
      <button
        type="button"
        onClick={onViewSourceItem}
        aria-label={`View ${boon.boon_applied.name} details`}
        className="focus-visible:ring-danube-500 dark:focus-visible:ring-danube-300 flex w-full flex-col gap-2 text-left focus:outline-none focus-visible:ring-2"
      >
        <span className="text-wisp-pink-900 dark:text-wisp-pink-900 text-base font-semibold">
          {boon.boon_applied.name}
        </span>

        <Dl>
          <Dt text_class="text-wisp-pink-800 dark:text-wisp-pink-800">
            Amount Used
          </Dt>
          <Dd text_class="text-wisp-pink-900 dark:text-wisp-pink-900">
            {boon.amount_used}
          </Dd>
          <Dt text_class="text-wisp-pink-800 dark:text-wisp-pink-800">
            Amount Left
          </Dt>
          <Dd text_class="text-wisp-pink-900 dark:text-wisp-pink-900">
            {boon.amount_left}
          </Dd>
        </Dl>

        <TimerBar
          length={totalDurationSeconds}
          complete_at={boon.complete}
          detailed_time
          title="Time Remaining"
          text_class="text-wisp-pink-800 dark:text-wisp-pink-800"
        />
      </button>

      <div className="mt-2 flex flex-wrap justify-end gap-2">
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
    </article>
  );
};

export default ActiveBoonCard;
