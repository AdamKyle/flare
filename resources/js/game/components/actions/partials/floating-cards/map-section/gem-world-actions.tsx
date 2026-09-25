import ApiErrorAlert from 'api-handler/components/api-error-alert';
import { isNil } from 'lodash';
import React, { ReactNode } from 'react';

import GemWorldActionsProps from './types/gem-world-actions-props';
import GemWorldEntryDefinition from '../../../../map-section/api/definitions/gem-world-entry-definition';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LoadingButton from 'ui/buttons/loading-button';

const GemWorldActions = ({
  status,
  loading,
  context_error: contextError,
  can_move: canMove,
  exiting,
  exit_error: exitError,
  on_enter: onEnter,
  on_view_effects: onViewEffects,
  on_exit: onExit,
  on_open_gem_progress_history: onOpenGemProgressHistory,
  on_open_all_active_gem_scrolls: onOpenAllActiveGemScrolls,
}: GemWorldActionsProps): ReactNode => {
  const renderExitError = (): ReactNode => {
    if (isNil(exitError)) {
      return null;
    }

    return <ApiErrorAlert apiError={exitError} />;
  };

  const renderInsideSection = (): ReactNode => {
    if (isNil(status?.current_context)) {
      return null;
    }

    return (
      <div className="my-2 flex flex-col gap-2 p-2">
        <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
          <Button
            on_click={onViewEffects}
            label={'View Gem Effects'}
            variant={ButtonVariant.PRIMARY}
            additional_css={'w-full'}
          />
          <LoadingButton
            on_click={() => onExit()}
            label={'Exit Gem World'}
            loading_label={'Exiting Gem World…'}
            variant={ButtonVariant.DANGER}
            is_loading={exiting}
            disabled={!canMove}
            additional_css={'w-full'}
          />
        </div>
        {renderExitError()}
      </div>
    );
  };

  const renderEntrySection = (entry: GemWorldEntryDefinition): ReactNode => (
    <div className="my-2 p-2">
      <Button
        on_click={onEnter}
        label={entry.label}
        variant={ButtonVariant.PRIMARY}
        additional_css={'w-full'}
        disabled={!canMove}
      />
    </div>
  );

  const renderCurrentEffectsSection = (): ReactNode => {
    if (isNil(status?.current_context)) {
      return null;
    }

    return (
      <div className="my-2 p-2">
        <Button
          on_click={onViewEffects}
          label={'View Gem Effects'}
          variant={ButtonVariant.PRIMARY}
          additional_css={'w-full'}
        />
      </div>
    );
  };

  const renderSection = (): ReactNode => {
    if (isNil(status)) {
      return null;
    }

    if (status.inside_gem_world) {
      return renderInsideSection();
    }

    if (!isNil(status.entry)) {
      return renderEntrySection(status.entry);
    }

    return renderCurrentEffectsSection();
  };

  const renderContextError = (): ReactNode => {
    if (isNil(contextError)) {
      return null;
    }

    return (
      <div className="my-2 p-2">
        <ApiErrorAlert apiError={contextError} />
      </div>
    );
  };

  const renderLoadingStatus = (): ReactNode => {
    if (!loading || !isNil(status)) {
      return null;
    }

    return (
      <div
        className="my-2 p-2 text-sm text-gray-500 dark:text-gray-400"
        role="status"
      >
        Loading Gem effects…
      </div>
    );
  };

  const renderGlobalBrowserActions = (): ReactNode => (
    <div className="my-2 grid grid-cols-1 gap-2 p-2 sm:grid-cols-2">
      <Button
        on_click={onOpenGemProgressHistory}
        label={'Gem Progress History'}
        variant={ButtonVariant.PRIMARY}
        additional_css={'w-full'}
      />
      <Button
        on_click={onOpenAllActiveGemScrolls}
        label={'All Active Gem Scrolls'}
        variant={ButtonVariant.PRIMARY}
        additional_css={'w-full'}
      />
    </div>
  );

  return (
    <>
      {renderContextError()}
      {renderLoadingStatus()}
      {renderSection()}
      {renderGlobalBrowserActions()}
    </>
  );
};

export default GemWorldActions;
