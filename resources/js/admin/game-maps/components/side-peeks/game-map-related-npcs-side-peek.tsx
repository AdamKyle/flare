import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import GameMapRelatedNpcsSidePeekProps from './types/game-map-related-npcs-side-peek-props';
import { resolveSidePeekComponent } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-mapper';
import { SidePeekComponentRegistrationEnum } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { isNpcType, NPC_TYPE_LABELS } from '../../../npcs/enums/npc-type';
import GameMapRelatedNpcDefinition from '../../api/definitions/game-map-related-npc-definition';
import { useGameMapRelatedNpcs } from '../../api/hooks/use-game-map-related-npcs';

import StackedCard from 'ui/cards/stacked-card';
import InfiniteScroll from 'ui/infinite-scroll/infinite-scroll';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

/**
 * Bounded, append-paginated browser for the NPCs on a Game Map, opened from
 * the Game Map's "Related Game Data" hub. Each result opens the canonical
 * NPC detail inside a `StackedCard` while preserving this relationship
 * browser underneath (mounted, with its scroll position intact) rather than
 * replacing it through the global SidePeek emitter.
 */
const GameMapRelatedNpcsSidePeek = ({
  game_map_id: gameMapId,
}: GameMapRelatedNpcsSidePeekProps): ReactNode => {
  const npcs = useGameMapRelatedNpcs(gameMapId);
  const [selectedNpcId, setSelectedNpcId] = useState<number | null>(null);

  const handleOpenNpc = (id: number): void => {
    setSelectedNpcId(id);
  };

  const handleCloseNpc = (): void => {
    setSelectedNpcId(null);
  };

  const handleScroll = (event: React.UIEvent<HTMLDivElement>): void => {
    const target = event.currentTarget;
    const nearBottom =
      target.scrollHeight - target.scrollTop - target.clientHeight < 100;

    if (nearBottom) {
      npcs.on_end_reached();
    }
  };

  const renderNpcType = (npc: GameMapRelatedNpcDefinition): string =>
    isNpcType(npc.type) ? NPC_TYPE_LABELS[npc.type] : String(npc.type);

  const renderRow = (npc: GameMapRelatedNpcDefinition): ReactNode => (
    <button
      key={npc.id}
      type="button"
      onClick={() => handleOpenNpc(npc.id)}
      className="border-glacier-200 dark:border-glacier-800 bg-glacier-50 dark:bg-glacier-900/40 hover:bg-glacier-100 dark:hover:bg-glacier-900 focus-visible:ring-danube-400 w-full rounded-md border px-3 py-2 text-left focus:outline-none focus-visible:ring-2"
    >
      <p className="text-glacier-900 dark:text-glacier-100 font-medium">
        {npc.name}
      </p>
      <p className="text-glacier-600 dark:text-glacier-400 text-xs">
        {renderNpcType(npc)} · X {npc.x_position}, Y {npc.y_position}
      </p>
    </button>
  );

  const renderSelectedNpc = (): ReactNode => {
    if (selectedNpcId === null) {
      return null;
    }

    const AdminNpcDetail = resolveSidePeekComponent(
      SidePeekComponentRegistrationEnum.ADMIN_NPC_DETAIL
    );

    return (
      <StackedCard on_close={handleCloseNpc} aria_label="NPC Details">
        <AdminNpcDetail is_open title="NPC Details" npc_id={selectedNpcId} />
      </StackedCard>
    );
  };

  const renderContent = (): ReactNode => {
    if (npcs.loading) {
      return <InfiniteLoader />;
    }

    if (npcs.error) {
      return (
        <div className="px-4">
          <ApiErrorAlert
            apiError={npcs.error.message ?? 'Unable to load NPCs.'}
          />
        </div>
      );
    }

    if (npcs.data.length === 0) {
      return (
        <p className="text-glacier-700 dark:text-glacier-300 px-4 text-sm">
          This Game Map has no NPCs.
        </p>
      );
    }

    return (
      <div className="h-[500px] max-h-[500px] px-4">
        <InfiniteScroll handle_scroll={handleScroll}>
          <div className="flex flex-col gap-2">
            {npcs.data.map(renderRow)}
            {npcs.is_loading_more && <InfiniteLoader />}
          </div>
        </InfiniteScroll>
      </div>
    );
  };

  return (
    <div className="space-y-4">
      {renderContent()}
      {renderSelectedNpc()}
    </div>
  );
};

export default GameMapRelatedNpcsSidePeek;
