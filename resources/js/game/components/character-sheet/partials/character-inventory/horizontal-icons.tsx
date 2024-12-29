import React, { ReactNode } from 'react';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import IconButton from 'ui/buttons/icon-button';

const HorizontalIcons = (): ReactNode => {
  return (
    <div className="relative left-0 top-4 flex flex-row items-center space-x-4 mb-16">
      <IconButton
        label="Backpack"
        icon={<i className="ra ra-player text-sm" aria-hidden="true"></i>}
        variant={ButtonVariant.PRIMARY}
        on_click={() => {}}
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
        label="Gems"
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

export default HorizontalIcons;
