import React from 'react';
import { match } from 'ts-pattern';

import { ItemActions } from './enums/item-actions';
import ItemActionProps from './types/item-action-props';

import ActionBoxBase from 'ui/action-boxes/action-box-base';
import { ActionBoxVariant } from 'ui/action-boxes/enums/action-box-varient';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import IconButton from 'ui/buttons/icon-button';

const ItemAction = ({
  action_type,
  item,
  on_cancel,
  on_confirmation,
  processing,
}: ItemActionProps) => {
  const handleConfirmation = (action: ItemActions) => {
    on_confirmation(action);
  };

  const renderLoadingIcon = () => {
    if (!processing) {
      return null;
    }

    return <i className="fas fa-spinner fa-spin" aria-hidden="true"></i>;
  };

  const renderSpecialMessage = () => {
    return match({ action_type, item })
      .with(
        { action_type: ItemActions.SELL, item: { is_cosmic: true } },
        () => {
          return (
            <p>
              This item is cosmic. Are you sure you want to sell it? Outside of
              the Christmas event, cosmic items are extremely rare drops.
              Selling is irreversible, and you will receive at most two billion
              gold. You may want to list it instead.
            </p>
          );
        }
      )
      .with(
        { action_type: ItemActions.SELL, item: { is_mythic: true } },
        () => {
          return (
            <p>
              This item is mythic. Are you sure you want to sell it? Mythic
              items are hard to come by. Selling is irreversible, and you will
              receive at most two billion gold. You may want to list it instead.
            </p>
          );
        }
      )
      .with(
        { action_type: ItemActions.SELL, item: { is_unique: true } },
        () => {
          return (
            <p>
              This item is unique. Are you sure you want to sell it? Selling is
              irreversible, and you will receive at most two billion gold. You
              may want to list it instead.
            </p>
          );
        }
      )
      .with({ action_type: ItemActions.SELL }, () => {
        return (
          <p>
            Are you sure you want to sell this item? Selling is irreversible. If
            the item is enchanted maybe try listing it to see if you can more
            money? Items sold to the store are capped at two billion gold, so if
            you have an expensive item, it might be more worth while to list it?
          </p>
        );
      })
      .with({ action_type: ItemActions.DESTROY }, () => {
        return (
          <p>
            Are you sure you want to destroy this item? This action is
            irreversible and the item will be permanently lost.
          </p>
        );
      })
      .with({ action_type: ItemActions.DISENCHANT }, () => {
        return (
          <p>
            Are you sure you want to disenchant this item? This action is
            irreversible and the item will be permanently destroyed.
          </p>
        );
      })
      .otherwise(() => null);
  };

  const renderHeader = () => {
    return match(action_type)
      .with(ItemActions.SELL, () => 'Sell Item')
      .with(ItemActions.DESTROY, () => 'Destroy Item')
      .with(ItemActions.DISENCHANT, () => 'Disenchant Item')
      .with(ItemActions.LIST, () => 'List the item')
      .with(ItemActions.MOVE_TO_SET, () => 'Move to set')
      .otherwise(() => 'Unknown action specified');
  };

  const renderAction = () => {
    return match(action_type)
      .with(ItemActions.SELL, () => {
        return (
          <div className="grid grid-cols-2 items-stretch gap-2">
            <IconButton
              disabled={processing}
              on_click={on_cancel}
              label={'Cancel'}
              variant={ButtonVariant.DANGER}
              additional_css={`w-full justify-center`}
            />

            <IconButton
              disabled={processing}
              on_click={() => handleConfirmation(ItemActions.SELL)}
              label={'Sell Item'}
              variant={ButtonVariant.SUCCESS}
              additional_css={`w-full justify-center`}
              icon={renderLoadingIcon()}
            />
          </div>
        );
      })
      .with(ItemActions.DESTROY, () => {
        return (
          <div className="grid grid-cols-2 items-stretch gap-2">
            <IconButton
              disabled={processing}
              on_click={on_cancel}
              label={'Cancel'}
              variant={ButtonVariant.DANGER}
              additional_css={`w-full justify-center`}
            />

            <IconButton
              disabled={processing}
              on_click={() => handleConfirmation(ItemActions.DESTROY)}
              label={'Destroy Item'}
              variant={ButtonVariant.SUCCESS}
              additional_css={`w-full justify-center`}
              icon={renderLoadingIcon()}
            />
          </div>
        );
      })
      .with(ItemActions.DISENCHANT, () => {
        return (
          <div className="grid grid-cols-2 items-stretch gap-2">
            <IconButton
              disabled={processing}
              on_click={on_cancel}
              label={'Cancel'}
              variant={ButtonVariant.DANGER}
              additional_css={`w-full justify-center`}
            />

            <IconButton
              disabled={processing}
              on_click={() => handleConfirmation(ItemActions.DISENCHANT)}
              label={'Disenchant Item'}
              variant={ButtonVariant.SUCCESS}
              additional_css={`w-full justify-center`}
              icon={renderLoadingIcon()}
            />
          </div>
        );
      })
      .with(ItemActions.LIST, () => {
        return (
          <div className="grid grid-cols-2 items-stretch gap-2">
            <IconButton
              disabled={processing}
              on_click={on_cancel}
              label={'Cancel'}
              variant={ButtonVariant.DANGER}
              additional_css={`w-full justify-center`}
            />

            <IconButton
              disabled={processing}
              on_click={() => handleConfirmation(ItemActions.LIST)}
              label={'List Item'}
              variant={ButtonVariant.SUCCESS}
              additional_css={`w-full justify-center`}
              icon={renderLoadingIcon()}
            />
          </div>
        );
      })
      .with(ItemActions.MOVE_TO_SET, () => {
        return (
          <div className="grid grid-cols-2 items-stretch gap-2">
            <IconButton
              disabled={processing}
              on_click={on_cancel}
              label={'Cancel'}
              variant={ButtonVariant.DANGER}
              additional_css={`w-full justify-center`}
            />

            <IconButton
              disabled={processing}
              on_click={() => handleConfirmation(ItemActions.MOVE_TO_SET)}
              label={'Move item to set'}
              variant={ButtonVariant.SUCCESS}
              additional_css={`w-full justify-center`}
              icon={renderLoadingIcon()}
            />
          </div>
        );
      })
      .otherwise(() => null);
  };

  return (
    <ActionBoxBase variant={ActionBoxVariant.DANGER} actions={renderAction()}>
      <h4 className={'font-bold text-rose-800'}>{renderHeader()}</h4>

      <div className={'my-4 text-rose-700'}>
        <p className={'my-2'}>Are you sure you want to do this?</p>
        {renderSpecialMessage()}
      </div>
    </ActionBoxBase>
  );
};

export default ItemAction;
