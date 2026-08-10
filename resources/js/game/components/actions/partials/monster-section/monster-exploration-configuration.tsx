import ApiErrorAlert from 'api-handler/components/api-error-alert';
import { isNil } from 'lodash';
import React, { useEffect, useState } from 'react';

import useBeginExplorationApi from './api/hooks/use-begin-exploration-api';
import MonsterExplorationConfigurationProps from './types/monster-exploration-configuration-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import InfiniteLoaderRoseDanube from 'ui/infinite-scroll/infinite-loader-rose-danube';

const MonsterExplorationConfiguration = ({
  character_id,
  on_close,
}: MonsterExplorationConfigurationProps) => {
  const [selectedTimeLength, setSelectedTimeLength] = useState<number | null>(
    null
  );
  const [selectedAttackType, setSelectedAttackType] = useState<string | null>(
    null
  );

  const { loading, error, successMessage, setRequestParams } =
    useBeginExplorationApi({ character_id });

  useEffect(() => {
    if (isNil(successMessage)) {
      return;
    }

    on_close();
  }, [successMessage, on_close]);

  const timeSelection = [
    {
      label: 'One Hour',
      value: 1,
    },
    {
      label: 'Two Hours',
      value: 2,
    },
    {
      label: 'Four Hours',
      value: 4,
    },
    {
      label: 'Eight Hours',
      value: 8,
    },
  ];

  const attackTypes = [
    {
      label: 'Attack',
      value: 'attack',
    },
    {
      label: 'Cast',
      value: 'cast',
    },
    {
      label: 'Cast and Attack',
      value: 'cast-and-attack',
    },
    {
      label: 'Attack and Cast',
      value: 'attack-and-cast',
    },
    {
      label: 'Defend',
      value: 'defend',
    },
  ];

  const handleTimeSelection = (selectedValue: DropdownItem) => {
    setSelectedTimeLength(Number(selectedValue.value));
  };

  const handleAttackTypeSelected = (selectedValue: DropdownItem) => {
    setSelectedAttackType(String(selectedValue.value));
  };

  const canBeginExploration =
    !isNil(selectedTimeLength) && !isNil(selectedAttackType) && !loading;

  const handleBeginExploration = () => {
    if (isNil(selectedTimeLength) || isNil(selectedAttackType)) {
      return;
    }

    setRequestParams({
      auto_attack_length: selectedTimeLength,
      attack_type: selectedAttackType,
    });
  };

  const renderError = () => {
    if (isNil(error)) {
      return null;
    }

    return <ApiErrorAlert apiError={error.message} />;
  };

  if (loading) {
    return <InfiniteLoaderRoseDanube />;
  }

  return (
    <div className="my-4 space-y-4">
      {renderError()}
      <Dropdown
        items={timeSelection}
        on_select={handleTimeSelection}
        selection_placeholder={'Select length of time'}
      />
      <Dropdown
        items={attackTypes}
        on_select={handleAttackTypeSelected}
        selection_placeholder={'Select the attack type'}
      />
      <Button
        on_click={handleBeginExploration}
        label={'Begin Exploration'}
        variant={ButtonVariant.SUCCESS}
        additional_css={'block mx-auto'}
        disabled={!canBeginExploration}
      />
      <Button
        on_click={on_close}
        label={'Close'}
        variant={ButtonVariant.DANGER}
        additional_css={'block mx-auto'}
      />
    </div>
  );
};

export default MonsterExplorationConfiguration;
