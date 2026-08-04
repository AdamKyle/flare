import React, { ReactNode } from 'react';

import SeerManageSocketsFormProps from './types/seer-manage-sockets-form-props';
import { useSeerManageSocketsFlow } from '../hooks/use-seer-manage-sockets-flow';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const SeerManageSocketsForm = ({
  items,
  costs,
  characterId,
  onSuccess,
}: SeerManageSocketsFormProps): ReactNode => {
  const {
    selectedItem,
    options,
    submitting,
    error,
    canSubmit,
    selectItem,
    submit,
  } = useSeerManageSocketsFlow({ characterId, items, onSuccess });

  const handleSelectItem = (option: DropdownItem): void => {
    selectItem(Number(option.value));
  };

  const renderSelectedSummary = (): ReactNode => {
    if (!selectedItem) {
      return null;
    }

    return (
      <p>
        {selectedItem.name}: {selectedItem.socket_amount} sockets. Cost:{' '}
        {costs.socket} Gold Bars.
      </p>
    );
  };

  return (
    <div className="space-y-4">
      {error && <Alert variant={AlertVariant.DANGER}>{error}</Alert>}

      <label id="seer-sockets-item-label" className="block font-semibold">
        Item
      </label>
      <Dropdown
        aria_labelled_by="seer-sockets-item-label"
        items={options}
        on_select={handleSelectItem}
        selection_placeholder="Select an item"
      />

      {renderSelectedSummary()}

      <Button
        label={submitting ? 'Creating…' : 'Create/ReRoll Sockets'}
        on_click={() => void submit()}
        variant={ButtonVariant.PRIMARY}
        disabled={!canSubmit}
      />
    </div>
  );
};

export default SeerManageSocketsForm;
