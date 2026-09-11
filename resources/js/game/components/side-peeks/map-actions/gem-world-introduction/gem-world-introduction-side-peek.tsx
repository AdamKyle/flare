import React, { ReactNode } from 'react';

import GemWorldIntroductionSidePeekProps from './types/gem-world-introduction-side-peek-props';
import { useAcknowledgeGemWorldIntroduction } from '../../../../reusable-components/gems/progression/api/hooks/use-acknowledge-gem-world-introduction';
import GemWorldIntroduction from '../../../../reusable-components/gems/progression/components/gem-world-introduction';
import { useOpenGemWorldSidePeek } from '../../../map-section/hooks/use-open-gem-world-side-peek';
import { useCloseSidePeekEmitter } from '../../base/hooks/use-close-side-peek-emitter';

const GemWorldIntroductionSidePeek = ({
  character_id: characterId,
  entry_context: entryContext,
  entry_can_enter: entryCanEnter,
}: GemWorldIntroductionSidePeekProps): ReactNode => {
  const { loading, error, acknowledge } =
    useAcknowledgeGemWorldIntroduction(characterId);
  const { openGemWorld } = useOpenGemWorldSidePeek();
  const { closeSidePeek } = useCloseSidePeekEmitter();

  const handleAcknowledge = async (): Promise<void> => {
    const acknowledged = await acknowledge();

    if (!acknowledged) {
      return;
    }

    closeSidePeek();
    openGemWorld(characterId, entryContext, entryCanEnter);
  };

  return (
    <div className="flex h-full min-h-0 flex-col gap-4 overflow-y-auto px-4 py-4 sm:px-5">
      <GemWorldIntroduction
        loading={loading}
        error={error}
        on_acknowledge={() => void handleAcknowledge()}
      />
    </div>
  );
};

export default GemWorldIntroductionSidePeek;
