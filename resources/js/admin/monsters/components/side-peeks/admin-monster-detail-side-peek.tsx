import ApiErrorAlert from 'api-handler/components/api-error-alert';
import clsx from 'clsx';
import React, { ReactNode, useState } from 'react';

import AdminMonsterDetailSidePeekProps from './types/admin-monster-detail-side-peek-props';
import { resolveSidePeekComponent } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-mapper';
import { SidePeekComponentRegistrationEnum } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import MonsterDetail from '../../../../game/reusable-components/monster/components/monster-detail';
import MonsterGemEffectContextCard from '../../../../game/reusable-components/monster/components/monster-gem-effect-context-card';
import { MonsterApiMessages } from '../../api/enums/monster-api-messages';
import { useMonsterDetail } from '../../api/hooks/use-monster-detail';
import { useMonsterGemEffectContexts } from '../../api/hooks/use-monster-gem-effect-contexts';
import MonsterFormContent from '../forms/monster-form-content';
import { MonsterNestedSelection } from '../types/monster-nested-selection';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const AdminMonsterDetailSidePeek = ({
  monster_id: monsterId,
  on_monster_changed: onMonsterChanged,
}: AdminMonsterDetailSidePeekProps): ReactNode => {
  const { monster, loading, error, refresh } = useMonsterDetail(monsterId);
  const [showEdit, setShowEdit] = useState(false);
  const [nestedSelection, setNestedSelection] =
    useState<MonsterNestedSelection | null>(null);
  const [announcement, setAnnouncement] = useState('');

  const gemEffectContexts = useMonsterGemEffectContexts({
    monster_id: monsterId,
    enabled: (monster?.gem_effect_context_count ?? 0) > 1,
  });

  const navigation = {
    on_open_item: (id: number) => setNestedSelection({ type: 'item', id }),
    on_open_map: (id: number) => setNestedSelection({ type: 'map', id }),
    on_open_map_gem: (id: number) =>
      setNestedSelection({ type: 'map_gem', id }),
    on_open_location_gem: (id: number) =>
      setNestedSelection({ type: 'location_gem', id }),
  };

  const handleEdit = (): void => {
    setShowEdit(true);
  };

  const handleCloseEdit = (): void => {
    setShowEdit(false);
  };

  const handleSaved = (): void => {
    refresh();
    onMonsterChanged?.();
    setShowEdit(false);
    setAnnouncement('Monster saved.');
  };

  const handleCloseNested = (): void => {
    setNestedSelection(null);
  };

  const renderNestedDetail = (): ReactNode => {
    if (!nestedSelection) {
      return null;
    }

    switch (nestedSelection.type) {
      case 'item': {
        const NestedItemDetail = resolveSidePeekComponent(
          SidePeekComponentRegistrationEnum.ADMIN_ITEM_DETAIL
        );

        return (
          <StackedCard
            on_close={handleCloseNested}
            aria_label="Item Details"
            content_mode={StackedCardContentMode.FULL_BLEED}
          >
            <NestedItemDetail
              is_open
              title="Item Details"
              item_id={nestedSelection.id}
            />
          </StackedCard>
        );
      }

      case 'map_gem': {
        const NestedMapGemDetail = resolveSidePeekComponent(
          SidePeekComponentRegistrationEnum.ADMIN_MAP_GEM_DETAIL
        );

        return (
          <StackedCard
            on_close={handleCloseNested}
            aria_label="Map Gem Details"
            content_mode={StackedCardContentMode.FULL_BLEED}
          >
            <NestedMapGemDetail
              is_open
              title="Map Gem Details"
              map_gem_id={nestedSelection.id}
            />
          </StackedCard>
        );
      }

      case 'location_gem': {
        const NestedLocationGemDetail = resolveSidePeekComponent(
          SidePeekComponentRegistrationEnum.ADMIN_LOCATION_GEM_DETAIL
        );

        return (
          <StackedCard
            on_close={handleCloseNested}
            aria_label="Location Gem Details"
            content_mode={StackedCardContentMode.FULL_BLEED}
          >
            <NestedLocationGemDetail
              is_open
              title="Location Gem Details"
              location_gem_id={nestedSelection.id}
            />
          </StackedCard>
        );
      }

      case 'gem_effect_context': {
        return (
          <StackedCard
            on_close={handleCloseNested}
            aria_label={nestedSelection.context.label}
            content_mode={StackedCardContentMode.FULL_BLEED}
          >
            <div className="h-full min-h-0 overflow-y-auto p-4">
              <MonsterGemEffectContextCard
                context={nestedSelection.context}
                navigation={navigation}
                single_column
              />
            </div>
          </StackedCard>
        );
      }

      default: {
        const NestedGameMapDetail = resolveSidePeekComponent(
          SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_DETAIL
        );

        return (
          <StackedCard
            on_close={handleCloseNested}
            aria_label="Game Map Details"
            content_mode={StackedCardContentMode.FULL_BLEED}
          >
            <NestedGameMapDetail
              is_open
              title="Game Map Details"
              game_map_id={nestedSelection.id}
            />
          </StackedCard>
        );
      }
    }
  };

  const renderContent = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error || !monster) {
      return (
        <ApiErrorAlert apiError={error?.message ?? MonsterApiMessages.Load} />
      );
    }

    return (
      <div className="space-y-4">
        <div className="flex justify-center py-2">
          <Button
            label="Edit Monster"
            variant={ButtonVariant.PRIMARY}
            additional_css="text-sm px-3 py-1.5"
            on_click={handleEdit}
          />
        </div>

        <MonsterDetail
          monster={monster}
          navigation={navigation}
          gem_effect_context_browser={{
            context_rows: gemEffectContexts.context_rows,
            loading: gemEffectContexts.loading,
            loading_more: gemEffectContexts.loading_more,
            error: gemEffectContexts.error,
            has_more: gemEffectContexts.has_more,
            on_load_next: gemEffectContexts.load_next,
            on_open_context: (context) =>
              setNestedSelection({ type: 'gem_effect_context', context }),
          }}
        />
      </div>
    );
  };

  const renderEdit = (): ReactNode => {
    if (!showEdit) {
      return null;
    }

    return (
      <StackedCard on_close={handleCloseEdit} aria_label="Edit Monster">
        <MonsterFormContent
          monster_id={monsterId}
          on_saved={handleSaved}
          on_cancel={handleCloseEdit}
          embedded
        />
      </StackedCard>
    );
  };

  const isStackActive = showEdit || nestedSelection !== null;

  return (
    <div className="relative flex h-full min-h-0 flex-col overflow-hidden">
      <p className="sr-only" role="status" aria-live="polite">
        {announcement}
      </p>
      <div
        className={clsx(
          'min-h-0 flex-1 px-4 py-4 sm:px-5',
          isStackActive ? 'overflow-hidden' : 'overflow-y-auto'
        )}
      >
        {renderContent()}
      </div>
      {renderEdit()}
      {renderNestedDetail()}
    </div>
  );
};

export default AdminMonsterDetailSidePeek;
