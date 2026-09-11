import React, { ReactNode } from 'react';

import GemWorldIntroductionProps from './types/gem-world-introduction-props';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LoadingButton from 'ui/buttons/loading-button';

const GemWorldIntroduction = ({
  loading,
  error,
  on_acknowledge: onAcknowledge,
}: GemWorldIntroductionProps): ReactNode => (
  <div className="flex flex-col gap-3">
    <h3 className="text-base font-semibold text-gray-800 dark:text-gray-200">
      Welcome to Gem World Progression
    </h3>
    <ul className="list-disc space-y-1 pl-5 text-sm text-gray-700 dark:text-gray-300">
      <li>
        Every generated Gem World tracks a Global level (shared by every
        Character) and a Personal level (yours alone) from 1 up to their
        respective caps.
      </li>
      <li>
        Leveling up strengthens the Gem effects already active for that Map or
        Location, and unlocks Personal bonuses such as drop-chance increases
        and, at higher Personal levels, enhanced equipment.
      </li>
      <li>
        Gem Scrolls are consumable Alchemy Bag items that temporarily boost your
        XP, a chosen currency, or Item rewards while inside this Gem World, up
        to a combined 2000% active bonus cap.
      </li>
      <li>
        You can activate, fill up, and remove your own active Gem Scrolls from
        this panel at any time.
      </li>
    </ul>
    {error && <Alert variant={AlertVariant.DANGER}>{error}</Alert>}
    <LoadingButton
      label="I Understand"
      loading_label="Saving…"
      variant={ButtonVariant.PRIMARY}
      is_loading={loading}
      on_click={onAcknowledge}
    />
  </div>
);

export default GemWorldIntroduction;
