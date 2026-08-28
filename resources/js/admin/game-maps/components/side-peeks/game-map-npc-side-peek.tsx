import React, { ReactNode, useState } from 'react';

import { GameMapSidePeekMessages } from './enums/game-map-side-peek-messages';
import GameMapNpcSidePeekProps from './types/game-map-npc-side-peek-props';
import NpcDefinition from '../../../npcs/api/definitions/npc-definition';
import { NpcApiMessages } from '../../../npcs/api/enums/npc-api-messages';
import { useNpc } from '../../../npcs/api/hooks/use-npc';
import NpcFormScreen from '../../../npcs/screens/npc-form-screen';

import ApiErrorAlert from 'api-handler/components/api-error-alert';
import StackedCard from 'ui/cards/stacked-card';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const GameMapNpcSidePeek = ({
  game_map_id: gameMapId,
  npc_id: npcId,
  on_editor_changed: onEditorChanged,
  on_move_requested: onMoveRequested,
}: GameMapNpcSidePeekProps): ReactNode => {
  const { npc, loading, error } = useNpc(gameMapId, npcId);
  const [editedNpc, setEditedNpc] = useState<NpcDefinition | null>(null);
  const [showEdit, setShowEdit] = useState(false);
  const [announcement, setAnnouncement] = useState('');

  const displayedNpc = editedNpc ?? npc;

  const handleEdit = (): void => {
    setShowEdit(true);
  };

  const handleCloseEdit = (): void => {
    setShowEdit(false);
  };

  const handleSaved = (updated: NpcDefinition): void => {
    setEditedNpc(updated);
    void onEditorChanged();
    setShowEdit(false);
    setAnnouncement(GameMapSidePeekMessages.NpcSaved);
  };

  const handleMove = (): void => {
    onMoveRequested(npcId);
  };

  const renderContent = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error || !displayedNpc) {
      return <ApiErrorAlert apiError={error?.message ?? NpcApiMessages.Load} />;
    }

    return (
      <div className="space-y-4 px-4">
        <Dl>
          <Dt>Real Name</Dt>
          <Dd>{displayedNpc.real_name}</Dd>
          <Dt>Type</Dt>
          <Dd>{displayedNpc.type_name}</Dd>
          <Dt>Coordinates</Dt>
          <Dd>
            X {displayedNpc.x_position}, Y {displayedNpc.y_position}
          </Dd>
        </Dl>

        <div className="flex items-center gap-3">
          <button
            type="button"
            onClick={handleEdit}
            className="focus-visible:ring-danube-400 bg-danube-600 hover:bg-danube-500 rounded-md px-3 py-2 text-sm font-medium text-white focus:outline-none focus-visible:ring-2"
          >
            Edit
          </button>
          <button
            type="button"
            onClick={handleMove}
            className="focus-visible:ring-glacier-400 border-glacier-300 text-glacier-700 hover:bg-glacier-50 dark:border-glacier-700 dark:text-glacier-200 dark:hover:bg-glacier-900 rounded-md border px-3 py-2 text-sm font-medium focus:outline-none focus-visible:ring-2"
          >
            Move
          </button>
        </div>
      </div>
    );
  };

  const renderEdit = (): ReactNode => {
    if (!showEdit) {
      return null;
    }

    return (
      <StackedCard on_close={handleCloseEdit}>
        <NpcFormScreen
          game_map_id={gameMapId}
          npc_id={npcId}
          initial_x={null}
          initial_y={null}
          on_saved={handleSaved}
          on_cancel={handleCloseEdit}
          embedded
        />
      </StackedCard>
    );
  };

  return (
    <>
      <p className="sr-only" role="status" aria-live="polite">
        {announcement}
      </p>
      {renderContent()}
      {renderEdit()}
    </>
  );
};

export default GameMapNpcSidePeek;
