import React from 'react';

import { SelectedEquippableItemsOptions } from './enums/selected-equippable-items-options';
import BackPackSelectionActionsProps from './types/back-pack-selection-actions-props';

import ActionBox from 'ui/action-boxes/action-box';
import { ActionBoxVariant } from 'ui/action-boxes/enums/action-box-varient';

const BackPackSelectionActions = ({
  on_submit_action,
  on_action_bar_close,
  action_type,
  is_loading,
}: BackPackSelectionActionsProps) => {
  const renderSellAllAction = () => {
    if (action_type !== SelectedEquippableItemsOptions.SELL) {
      return null;
    }

    return (
      <>
        <h4 className="mb-2 text-rose-950  dark:text-rose-800 font-bold">
          Are you sure?
        </h4>
        <p className="text-rose-800 dark:text-rose-700 ">
          All items you have selected will be sold. If an items value would go
          above two billion gold, it will be capped at two billion gold. Thee is
          a 5% tax applied to the sale of all selected items. You cannot undo
          this action.
        </p>
      </>
    );
  };

  const renderDestroyAll = () => {
    if (action_type !== SelectedEquippableItemsOptions.DESSTROY) {
      return null;
    }

    return (
      <>
        <h4 className="mb-2 text-rose-950  dark:text-rose-800 font-bold">
          Are you sure?
        </h4>
        <p className="text-rose-800 dark:text-rose-700 ">
          All selected items will be destroyed. You cannot undo this action.
        </p>
      </>
    );
  };

  const renderDisenchantAll = () => {
    if (action_type !== SelectedEquippableItemsOptions.DISENCHANT) {
      return null;
    }

    return (
      <>
        <h4 className="mb-2 text-rose-950  dark:text-rose-800 font-bold">
          Are you sure?
        </h4>
        <p className="text-rose-800 dark:text-rose-700 ">
          All selected items will be disenchanted. You will gain Gold Dust for
          each item and gain XP in Disenchanting Skill. The more you level this
          skill by disenchanting useless items, the more Gold Dust you will
          gain.
        </p>
      </>
    );
  };

  return (
    <ActionBox
      variant={ActionBoxVariant.DANGER}
      on_submit={on_submit_action}
      on_close={on_action_bar_close}
      additional_css={'my-4'}
      is_loading={is_loading}
    >
      {renderSellAllAction()}
      {renderDestroyAll()}
      {renderDisenchantAll()}
    </ActionBox>
  );
};

export default BackPackSelectionActions;
