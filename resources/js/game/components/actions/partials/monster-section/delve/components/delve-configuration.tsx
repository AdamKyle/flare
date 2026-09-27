import { isNil } from 'lodash';
import React, { ReactNode, useEffect, useId, useRef, useState } from 'react';

import DelveConfigurationProps from './types/delve-configuration-props';
import ExplorationBusyWarning from '../../exploration/components/exploration-busy-warning';
import { explorationAttackTypeOptions } from '../../exploration/utils/exploration-attack-type-options';
import { useStartDelve } from '../api/hooks/use-start-delve';
import { resolveDelvePackSize } from '../utils/resolve-delve-pack-size';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import InfiniteLoaderRoseDanube from 'ui/infinite-scroll/infinite-loader-rose-danube';
import Input from 'ui/input/input';

const DelveConfiguration = ({
  character_id: characterId,
  can_set_pack_size: canSetPackSize,
  active_automation: activeAutomation,
  on_close: onClose,
  on_started: onStarted,
}: DelveConfigurationProps): ReactNode => {
  const { starting, error, start } = useStartDelve(characterId);

  const [selectedAttackType, setSelectedAttackType] = useState<string | null>(
    null
  );
  const [packSizeInput, setPackSizeInput] = useState('1');

  const legendRef = useRef<HTMLLegendElement | null>(null);
  const packSizeId = useId();
  const packSizeHelpId = useId();

  const packSize = resolveDelvePackSize(canSetPackSize, packSizeInput);
  const isPackSizeInvalid = isNil(packSize);
  const canBeginDelve =
    !isNil(selectedAttackType) &&
    !isPackSizeInvalid &&
    !starting &&
    isNil(activeAutomation);

  const handleAttackTypeSelected = (selectedValue: DropdownItem) => {
    setSelectedAttackType(String(selectedValue.value));
  };

  const handleBeginDelve = async () => {
    if (isNil(selectedAttackType) || isNil(packSize)) {
      return;
    }

    const started = await start({
      attack_type: selectedAttackType,
      pack_size: packSize,
    });

    if (!started) {
      return;
    }

    onStarted();
  };

  useEffect(() => {
    legendRef.current?.focus({ preventScroll: true });
  }, []);

  const renderBusyWarning = (): ReactNode => {
    if (isNil(activeAutomation)) {
      return null;
    }

    return (
      <ExplorationBusyWarning automation_name={activeAutomation.name} blocked />
    );
  };

  const renderError = (): ReactNode => {
    if (isNil(error)) {
      return null;
    }

    return <Alert variant={AlertVariant.DANGER}>{error}</Alert>;
  };

  const renderPackSize = (): ReactNode => {
    if (!canSetPackSize) {
      return null;
    }

    return (
      <div className="space-y-1">
        <label
          htmlFor={packSizeId}
          className="block text-sm font-medium text-gray-800 dark:text-gray-200"
        >
          Pack Size
        </label>
        <Input
          id={packSizeId}
          value={packSizeInput}
          on_change={setPackSizeInput}
          place_holder="Number of enemies per round"
          described_by={packSizeHelpId}
          invalid={isPackSizeInvalid}
        />
        <p
          id={packSizeHelpId}
          className="text-xs text-gray-600 dark:text-gray-400"
        >
          {isPackSizeInvalid
            ? 'Enter a whole number of enemies, one or more.'
            : 'How many of the same creature you fight each round.'}
        </p>
      </div>
    );
  };

  if (starting) {
    return (
      <div role="status" aria-live="polite">
        <InfiniteLoaderRoseDanube />
        <p className="sr-only">Starting Delve...</p>
      </div>
    );
  }

  return (
    <div className="space-y-3">
      {renderBusyWarning()}
      {renderError()}

      <fieldset className="space-y-3">
        <legend
          ref={legendRef}
          tabIndex={-1}
          className="mb-1 text-sm font-semibold text-gray-800 outline-none dark:text-gray-200"
        >
          Delve Settings
        </legend>

        <Alert variant={AlertVariant.INFO}>
          Monsters are chosen at random while Delving in this location.
        </Alert>

        <Dropdown
          aria_label="Attack Type"
          items={explorationAttackTypeOptions}
          on_select={handleAttackTypeSelected}
          selection_placeholder="Select the attack type"
          disabled={!isNil(activeAutomation)}
        />

        {renderPackSize()}
      </fieldset>

      <Button
        on_click={() => void handleBeginDelve()}
        label="Begin Delve"
        variant={ButtonVariant.SUCCESS}
        additional_css="w-full"
        disabled={!canBeginDelve}
      />
      <Button
        on_click={onClose}
        label="Back to Manual Fighting"
        variant={ButtonVariant.DANGER}
        additional_css="w-full"
      />
    </div>
  );
};

export default DelveConfiguration;
