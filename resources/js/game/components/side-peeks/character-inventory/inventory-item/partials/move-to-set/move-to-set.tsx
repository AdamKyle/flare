import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { useState } from 'react';

import MoveToSetProps from './types/move-to-set-props';
import { isSetEquippable } from './utils/is-set-equippable';
import SetChoices from '../../../sets/set-choices';
import UseGetSetEquippabilityResponse from '../../api/definitions/use-get-set-equippability-response-definition';
import { UseGetSetEquippabilityDetails } from '../../api/hooks/use-get-set-equippability-details';
import { useMoveItemToSet } from '../../api/hooks/use-move-item-to-set';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import IconButton from 'ui/buttons/icon-button';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import Separator from 'ui/separator/separator';

const MoveToSet = ({
  character_id,
  item_slot_id,
  on_action,
}: MoveToSetProps) => {
  const { data, loading, error, setRequestParams } =
    UseGetSetEquippabilityDetails();

  const {
    loading: isMovingToSet,
    error: movingToSetError,
    setRequestParams: setMovingToSetRequestParams,
  } = useMoveItemToSet();

  const [inventorySetId, setInventorySetId] = useState<number | null>(null);

  const handleSelection = (selected: DropdownItem) => {
    const setId = parseInt(selected.value as string) || 0;

    setInventorySetId(setId);

    setRequestParams({
      character_id,
      inventory_set_id: setId,
    });
  };

  const handleClearSelection = () => {
    setInventorySetId(null);
  };

  const handleMoveToSet = () => {
    setMovingToSetRequestParams({
      character_id,
      inventory_set_id: inventorySetId || 0,
      inventory_slot_id: item_slot_id,
      on_success: on_action,
    });
  };

  const renderIsProcessingMoveRequest = () => {
    if (!isMovingToSet) {
      return null;
    }

    return <i className="fas fa-spinner fa-spin" aria-hidden="true"></i>;
  };

  const renderSetItems = (setItems: UseGetSetEquippabilityResponse[]) => {
    if (!setItems.length) {
      return null;
    }

    return (
      <Dl>
        {setItems.map((setItem) => {
          return (
            <React.Fragment key={setItem.type}>
              <Dt>{setItem.type}</Dt>
              <Dd>{setItem.count}</Dd>
            </React.Fragment>
          );
        })}
      </Dl>
    );
  };

  const renderEquippabilityWarning = (equippable: boolean) => {
    if (equippable) {
      return null;
    }

    return (
      <div className="mt-4">
        <Alert variant={AlertVariant.WARNING}>
          You can add items to this set, but it&#39;s not equippable. It will be
          treated as a bottomless stash tab.
        </Alert>
      </div>
    );
  };

  const renderSetData = () => {
    if (!inventorySetId || inventorySetId === 0) {
      return null;
    }

    if (loading) {
      return <InfiniteLoader />;
    }

    if (error) {
      return <ApiErrorAlert apiError={error.message} />;
    }

    if (movingToSetError) {
      return <ApiErrorAlert apiError={movingToSetError.message} />;
    }

    if (!data) {
      return null;
    }

    const equippable = isSetEquippable(data);

    const totalItemCount = data.reduce((total, setItem) => {
      return total + setItem.count;
    }, 0);

    return (
      <div className="mt-4">
        <h3 className="text-base font-semibold text-gray-900 dark:text-gray-100">
          Set Details
        </h3>

        <div className="mt-3">
          <Dl>
            <Dt>Equippable</Dt>
            <Dd>{equippable ? 'Yes' : 'No'}</Dd>

            <Dt>Total Items</Dt>
            <Dd>{totalItemCount}</Dd>
          </Dl>
        </div>

        <div className="mt-4">{renderSetItems(data)}</div>

        {renderEquippabilityWarning(equippable)}

        <div className="mt-2">
          <IconButton
            on_click={handleMoveToSet}
            label={'Move to this set'}
            variant={ButtonVariant.SUCCESS}
            additional_css={'w-full justify-center'}
            center_content={true}
            disabled={isMovingToSet}
            icon={renderIsProcessingMoveRequest()}
          />
        </div>
      </div>
    );
  };

  return (
    <>
      <div>
        <h2 className="my-2 text-lg font-semibold text-gray-900 dark:text-gray-100">
          Move To Set
        </h2>
        <div className={'my-4'}>
          <SetChoices
            character_id={character_id}
            on_set_change={handleSelection}
            on_set_selection_clear={handleClearSelection}
            dont_show_equipped_set
          />
        </div>
        {renderSetData()}
      </div>
      <Separator />
      <div className="prose dark:prose-invert">
        <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
          Set Rules
        </h2>

        <p className="mt-2 text-sm leading-6 text-gray-700 dark:text-gray-300">
          You can move any item to any set from your inventory, but if you plan
          to equip that set you must follow the rules below.
        </p>

        <ul className="mt-3 space-y-2 text-sm text-gray-700 dark:text-gray-300">
          <li className="flex gap-2">
            <span className="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-gray-400 dark:bg-gray-500" />
            <span>
              <span className="font-semibold text-gray-900 dark:text-gray-100">
                Hands:
              </span>{' '}
              1 or 2 weapons for hands, or 1 or 2 shields or 1 duel wielded
              weapon (bow, hammer or stave).
            </span>
          </li>

          <li className="flex gap-2">
            <span className="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-gray-400 dark:bg-gray-500" />
            <span>
              <span className="font-semibold text-gray-900 dark:text-gray-100">
                Armour:
              </span>{' '}
              1 of each type, body, head, leggings, sleeves, gloves and feet
            </span>
          </li>

          <li className="flex gap-2">
            <span className="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-gray-400 dark:bg-gray-500" />
            <span>
              <span className="font-semibold text-gray-900 dark:text-gray-100">
                Spells:
              </span>{' '}
              Max of 2 regardless of type.
            </span>
          </li>

          <li className="flex gap-2">
            <span className="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-gray-400 dark:bg-gray-500" />
            <span>
              <span className="font-semibold text-gray-900 dark:text-gray-100">
                Rings:
              </span>{' '}
              Max of 2
            </span>
          </li>

          <li className="flex gap-2">
            <span className="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-gray-400 dark:bg-gray-500" />
            <span>
              <span className="font-semibold text-gray-900 dark:text-gray-100">
                Trinkets:
              </span>{' '}
              Max of 1
            </span>
          </li>

          <li className="flex gap-2">
            <span className="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-gray-400 dark:bg-gray-500" />
            <span>
              <span className="font-semibold text-gray-900 dark:text-gray-100">
                Uniques (green items):
              </span>{' '}
              Max of 1 item regardless of type. Ie, if you have a unique helmet,
              you cannot have a unique ring as well.<sup>*</sup>
            </span>
          </li>

          <li className="flex gap-2">
            <span className="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-gray-400 dark:bg-gray-500" />
            <span>
              <span className="font-semibold text-gray-900 dark:text-gray-100">
                Mythics (orange items):
              </span>{' '}
              Max of 1 item regardless of type. Ie, if you have a mythic helmet,
              you cannot have a mythic ring as well.<sup>*</sup>
            </span>
          </li>

          <li className="flex gap-2">
            <span className="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-gray-400 dark:bg-gray-500" />
            <span>
              <span className="font-semibold text-gray-900 dark:text-gray-100">
                Comsic (light purple items):
              </span>{' '}
              Max of 1 item regardless of type. Ie, if you have a cosmic helmet,
              you cannot have a cosmic ring as well.<sup>*</sup>
            </span>
          </li>

          <li className="flex gap-2">
            <span className="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-gray-400 dark:bg-gray-500" />
            <span>
              <span className="font-semibold text-gray-900 dark:text-gray-100">
                Ancestral Items (purple items):
              </span>{' '}
              1 Ancestral item only.
            </span>
          </li>
        </ul>

        <p className="mt-3 text-sm leading-6 text-gray-700 dark:text-gray-300">
          <sup>*</sup>
          <strong>Note:</strong> A Set may only have one unique OR one mythic OR
          one cosmic. You may not have one of each type.
        </p>

        <p className="mt-3 text-sm leading-6 text-gray-700 dark:text-gray-300">
          The above rules only apply to characters who want to equip the set,
          You may also use a set as a stash tab with unlimited items.
        </p>
      </div>
    </>
  );
};

export default MoveToSet;
