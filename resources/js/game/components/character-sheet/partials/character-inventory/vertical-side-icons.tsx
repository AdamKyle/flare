import React, { ReactNode } from 'react';

import { useOpenCharacterBackpack } from './hooks/use-open-character-backpack';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import IconButton from 'ui/buttons/icon-button';

const VerticalSideIcons = (): ReactNode => {
  const { openBackpack } = useOpenCharacterBackpack();

  return (
    <div className="absolute left-0 top-4 flex flex-col items-start space-y-4">
      <IconButton
        label="Backpack"
        icon={<i className="ra ra-player text-sm" aria-hidden="true"></i>}
        variant={ButtonVariant.PRIMARY}
        on_click={() => {
          openBackpack();
        }}
        additional_css="w-full"
      />
      <IconButton
        label="Usable"
        icon={<i className="ra ra-player text-sm" aria-hidden="true"></i>}
        variant={ButtonVariant.PRIMARY}
        on_click={() => {}}
        additional_css="w-full"
      />
      <IconButton
        label="Gem"
        icon={<i className="ra ra-player text-sm" aria-hidden="true"></i>}
        variant={ButtonVariant.PRIMARY}
        on_click={() => {}}
        additional_css="w-full"
      />
      <IconButton
        label="Sets"
        icon={<i className="ra ra-player text-sm" aria-hidden="true"></i>}
        variant={ButtonVariant.PRIMARY}
        on_click={() => {}}
        additional_css="w-full"
      />
    </div>
  );
};

export default VerticalSideIcons;
