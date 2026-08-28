import React, { ReactNode } from 'react';

import GameMapMoveConfirmationProps from '../types/game-map-move-confirmation-props';

import ApiErrorAlert from 'api-handler/components/api-error-alert';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const GameMapMoveConfirmation = ({
  entity_type_label: entityTypeLabel,
  entity_label: entityLabel,
  origin_x: originX,
  origin_y: originY,
  destination_x: destinationX,
  destination_y: destinationY,
  is_moving: isMoving,
  api_error: apiError,
  on_confirm: onConfirm,
  on_cancel: onCancel,
  on_clear_error: onClearError,
}: GameMapMoveConfirmationProps): ReactNode => {
  const renderError = (): ReactNode => {
    if (!apiError) {
      return null;
    }

    return (
      <ApiErrorAlert apiError={apiError} closable on_close={onClearError} />
    );
  };

  return (
    <div className="space-y-4">
      <h3 className="text-glacier-900 dark:text-glacier-100 text-lg font-semibold">
        Confirm Move
      </h3>

      <Dl>
        <Dt>Type</Dt>
        <Dd>{entityTypeLabel}</Dd>
        <Dt>Name</Dt>
        <Dd>{entityLabel}</Dd>
        <Dt>Current Coordinates</Dt>
        <Dd>
          X {originX}, Y {originY}
        </Dd>
        <Dt>Destination Coordinates</Dt>
        <Dd>
          X {destinationX}, Y {destinationY}
        </Dd>
      </Dl>

      {renderError()}

      <div className="flex items-center gap-3">
        <button
          type="button"
          onClick={onConfirm}
          disabled={isMoving}
          aria-busy={isMoving}
          className="bg-danube-600 hover:bg-danube-500 focus-visible:ring-danube-400 rounded-md px-4 py-2 text-sm font-medium text-white focus:outline-none focus-visible:ring-2 disabled:cursor-not-allowed disabled:opacity-60"
        >
          {isMoving ? 'Moving…' : 'Confirm Move'}
        </button>
        <button
          type="button"
          onClick={onCancel}
          disabled={isMoving}
          className="focus-visible:ring-glacier-400 border-glacier-300 text-glacier-700 dark:border-glacier-700 dark:text-glacier-200 rounded-md border px-4 py-2 text-sm font-medium focus:outline-none focus-visible:ring-2 disabled:cursor-not-allowed disabled:opacity-60"
        >
          Cancel
        </button>
      </div>
    </div>
  );
};

export default GameMapMoveConfirmation;
