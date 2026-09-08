import React, { ReactNode } from 'react';

import CharacterActiveBoonsScreenProps from './types/character-active-boons-screen-props';
import ActiveBoonsContent from '../../crafting-section/sections/alchemy/active-boons/components/active-boons-content';

const CharacterActiveBoonsScreen = (
  props: CharacterActiveBoonsScreenProps
): ReactNode => (
  <div className="absolute inset-0 z-10 flex h-full min-h-0 flex-col overflow-hidden bg-gray-200 px-4 py-4 sm:px-5 dark:bg-gray-700">
    <h4 className="text-danube-700 dark:text-danube-300 mb-2 shrink-0 text-base font-semibold">
      Active Alchemy Boons
    </h4>
    <div className="min-h-0 flex-1 overflow-hidden">
      <ActiveBoonsContent
        boons={props.boons}
        loading={props.loading}
        error={props.error}
        success_message={props.success_message}
        mutation_error={props.mutation_error}
        filling_boon_id={props.filling_boon_id}
        removing_boon_id={props.removing_boon_id}
        on_view_source_item={props.on_view_source_item}
        on_fill_up={props.on_fill_up}
        on_remove={props.on_remove}
        bounded_height
      />
    </div>
  </div>
);

export default CharacterActiveBoonsScreen;
