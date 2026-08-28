import React, { ReactNode, useState } from 'react';

import NpcDefinition from '../api/definitions/npc-definition';
import NpcFormScreen from '../screens/npc-form-screen';
import NpcsStandaloneAppProps from '../types/npcs-standalone-app-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

/**
 * Standalone embedded mount for the Npc form, used when this entry is mounted on its
 * own outside the Game Maps editor stack. Owns only the state needed to show a
 * saved-success confirmation and to reset the form back to its initial state.
 */
const NpcsStandaloneApp = ({
  game_map_id,
}: NpcsStandaloneAppProps): ReactNode => {
  const [formInstanceKey, setFormInstanceKey] = useState(0);
  const [savedNpc, setSavedNpc] = useState<NpcDefinition | null>(null);

  const handleSaved = (npc: NpcDefinition): void => {
    setSavedNpc(npc);
  };

  const handleReset = (): void => {
    setSavedNpc(null);
    setFormInstanceKey((value) => value + 1);
  };

  if (savedNpc) {
    return (
      <div className="container mx-auto my-4 px-4">
        <p
          role="status"
          className="mb-4 text-sm text-gray-800 dark:text-gray-200"
        >
          Npc &quot;{savedNpc.real_name}&quot; saved.
        </p>
        <Button
          label="Add Another Npc"
          variant={ButtonVariant.PRIMARY}
          on_click={handleReset}
        />
      </div>
    );
  }

  return (
    <NpcFormScreen
      key={formInstanceKey}
      game_map_id={game_map_id}
      npc_id={null}
      initial_x={null}
      initial_y={null}
      on_saved={handleSaved}
      on_cancel={handleReset}
    />
  );
};

export default NpcsStandaloneApp;
