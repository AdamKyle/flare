import ApiErrorAlert from 'api-handler/components/api-error-alert';
import { isNil } from 'lodash';
import React, { ReactNode, useEffect, useRef, useState } from 'react';

import useBeginExplorationApi from './api/hooks/use-begin-exploration-api';
import ExplorationBusyWarning from './exploration/components/exploration-busy-warning';
import { ExplorationAttackType } from './exploration/enums/exploration-attack-type';
import MonsterExplorationConfigurationProps from './types/monster-exploration-configuration-props';
import { ChatStreamEvent } from '../../../chat-section/events/enums/chat-stream-event';
import { useExplorationMessageEmitter } from '../../../chat-section/events/hooks/use-exploration-message-emitter';

import { AutomationType } from 'game-data/api-data-definitions/character/automation-type';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import InfiniteLoaderRoseDanube from 'ui/infinite-scroll/infinite-loader-rose-danube';

const timeSelection: DropdownItem[] = [
  { label: 'One Hour', value: 1 },
  { label: 'Two Hours', value: 2 },
  { label: 'Four Hours', value: 4 },
  { label: 'Eight Hours', value: 8 },
];

const attackTypes: DropdownItem[] = [
  { label: 'Attack', value: ExplorationAttackType.ATTACK },
  { label: 'Cast', value: ExplorationAttackType.CAST },
  {
    label: 'Cast and Attack',
    value: ExplorationAttackType.CAST_AND_ATTACK,
  },
  {
    label: 'Attack and Cast',
    value: ExplorationAttackType.ATTACK_AND_CAST,
  },
  { label: 'Defend', value: ExplorationAttackType.DEFEND },
];

const MonsterExplorationConfiguration = ({
  character_id,
  selected_monster_id: selectedMonsterId,
  active_automation: activeAutomation,
  on_close,
  on_started: onStarted,
}: MonsterExplorationConfigurationProps): ReactNode => {
  const explorationMessageEmitter = useExplorationMessageEmitter();

  const {
    loading,
    error,
    successMessage,
    explorationMessage,
    setRequestParams,
  } = useBeginExplorationApi({ character_id });

  const [selectedTimeLength, setSelectedTimeLength] = useState<number | null>(
    null
  );
  const [selectedAttackType, setSelectedAttackType] = useState<string | null>(
    null
  );

  const legendRef = useRef<HTMLLegendElement | null>(null);
  const handledStartMessageIdRef = useRef<string | null>(null);

  const blockingAutomation =
    activeAutomation && activeAutomation.type !== AutomationType.EXPLORING
      ? activeAutomation
      : null;
  const canBeginExploration =
    !isNil(selectedMonsterId) &&
    !isNil(selectedTimeLength) &&
    !isNil(selectedAttackType) &&
    !loading &&
    !blockingAutomation;

  const handleTimeSelection = (selectedValue: DropdownItem) => {
    setSelectedTimeLength(Number(selectedValue.value));
  };

  const handleAttackTypeSelected = (selectedValue: DropdownItem) => {
    setSelectedAttackType(String(selectedValue.value));
  };

  const handleBeginExploration = () => {
    if (
      isNil(selectedMonsterId) ||
      isNil(selectedTimeLength) ||
      isNil(selectedAttackType)
    ) {
      return;
    }

    setRequestParams({
      selected_monster_id: selectedMonsterId,
      auto_attack_length: selectedTimeLength,
      attack_type: selectedAttackType,
    });
  };

  useEffect(() => {
    legendRef.current?.focus({ preventScroll: true });
  }, []);

  useEffect(() => {
    if (isNil(successMessage) || isNil(explorationMessage)) {
      return;
    }

    if (handledStartMessageIdRef.current === explorationMessage.messageId) {
      return;
    }

    handledStartMessageIdRef.current = explorationMessage.messageId;

    explorationMessageEmitter.emit(
      ChatStreamEvent.EXPLORATION_MESSAGE_RECEIVED,
      explorationMessage
    );

    onStarted();
  }, [
    successMessage,
    explorationMessage,
    explorationMessageEmitter,
    onStarted,
  ]);

  if (loading || !isNil(successMessage)) {
    return (
      <div role="status" aria-live="polite">
        <InfiniteLoaderRoseDanube />
        <p className="sr-only">{successMessage ?? 'Starting Exploration...'}</p>
      </div>
    );
  }

  return (
    <div className="space-y-3">
      {blockingAutomation && (
        <ExplorationBusyWarning
          automation_name={blockingAutomation.name}
          blocked
        />
      )}

      {error && <ApiErrorAlert apiError={error.message} />}

      <fieldset className="space-y-3">
        <legend
          ref={legendRef}
          tabIndex={-1}
          className="mb-1 text-sm font-semibold text-gray-800 outline-none dark:text-gray-200"
        >
          Exploration Settings
        </legend>

        <Dropdown
          aria_label="Duration"
          items={timeSelection}
          on_select={handleTimeSelection}
          selection_placeholder="Select length of time"
          disabled={!!blockingAutomation}
        />
        <Dropdown
          aria_label="Attack Type"
          items={attackTypes}
          on_select={handleAttackTypeSelected}
          selection_placeholder="Select the attack type"
          disabled={!!blockingAutomation}
        />

        {isNil(selectedMonsterId) && !blockingAutomation && (
          <p className="text-xs text-gray-600 dark:text-gray-400">
            Select a monster above to begin Exploration.
          </p>
        )}
      </fieldset>

      <Button
        on_click={handleBeginExploration}
        label="Begin Exploration"
        variant={ButtonVariant.SUCCESS}
        additional_css="w-full"
        disabled={!canBeginExploration}
      />
      <Button
        on_click={on_close}
        label="Back to Manual Fighting"
        variant={ButtonVariant.DANGER}
        additional_css="w-full"
      />
    </div>
  );
};

export default MonsterExplorationConfiguration;
