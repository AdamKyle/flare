import ApiErrorAlert from 'api-handler/components/api-error-alert';
import { isNil } from 'lodash';
import React, { ReactNode } from 'react';

import GemWorldActionsProps from './types/gem-world-actions-props';
import GemWorldSourceDefinition from '../../../../../reusable-components/gems/api/definitions/gem-world-source-definition';
import GemWorldEntryDefinition from '../../../../map-section/api/definitions/gem-world-entry-definition';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LoadingButton from 'ui/buttons/loading-button';

const describeGemSources = (sources: GemWorldSourceDefinition[]): string =>
  sources
    .map(
      (source) =>
        `${source.type === 'map_gem' ? 'Map Gem' : 'Location Gem'}: ${source.profile_name}`
    )
    .join(' · ');

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
  const renderGemEntrySourceText = (
    entry: GemWorldEntryDefinition
  ): ReactNode => {
    const matchingSource = entry.context.sources.find(
      (source) => source.type === entry.type
    );

    if (!matchingSource) {
      return null;
    }

    return (
      <div className="text-sm text-gray-700 dark:text-gray-300">
        {describeGemSources([matchingSource])}
      </div>
    );
  };

  const renderInsideSection = (): ReactNode => {
    const currentContext = status?.current_context;

    if (isNil(currentContext)) {
      return null;
    }

    return (
      <div className="my-2 flex flex-col gap-2 p-2">
        <div className="text-sm text-gray-700 dark:text-gray-300">
          Gem World: {currentContext.label}
        </div>
        <div className="flex flex-col justify-center gap-2 md:flex-row">
          <Button
            on_click={onViewEffects}
            label={'View Gem Effects'}
            variant={ButtonVariant.PRIMARY}
          />
          <LoadingButton
            on_click={() => onExit()}
            label={'Exit Gem World'}
            loading_label={'Exiting Gem World…'}
            variant={ButtonVariant.DANGER}
            is_loading={exiting}
            disabled={!canMove}
          />
        </div>
        {!isNil(exitError) && <ApiErrorAlert apiError={exitError} />}
      </div>
    );
  };

  const renderEntrySection = (entry: GemWorldEntryDefinition): ReactNode => (
    <div className="my-2 flex flex-col gap-2 p-2">
      {renderGemEntrySourceText(entry)}
      <Button
        on_click={onEnter}
        label={entry.label}
        variant={ButtonVariant.PRIMARY}
        additional_css={'w-full'}
      />
    </div>
  );

  const renderCurrentEffectsSection = (): ReactNode => {
    const currentContext = status?.current_context;

    if (isNil(currentContext)) {
      return null;
    }

    return (
      <div className="my-2 flex flex-col gap-2 p-2">
        <div className="text-sm text-gray-700 dark:text-gray-300">
          {describeGemSources(currentContext.sources)}
        </div>
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
    <div className="my-2 flex flex-col justify-center gap-2 p-2 md:flex-row">
      <Button
        on_click={onOpenGemProgressHistory}
        label={'Gem Progress History'}
        variant={ButtonVariant.PRIMARY}
      />
      <Button
        on_click={onOpenAllActiveGemScrolls}
        label={'All Active Gem Scrolls'}
        variant={ButtonVariant.PRIMARY}
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
