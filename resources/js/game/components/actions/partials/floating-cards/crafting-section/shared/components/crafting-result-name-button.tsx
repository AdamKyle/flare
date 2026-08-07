import clsx from 'clsx';
import React, { ReactNode } from 'react';

import CraftingResultNameButtonProps from './types/crafting-result-name-button-props';

import { baseStyles } from 'ui/buttons/styles/link-buttons/base-styles';

const CraftingResultNameButton = ({
  name,
  class_name,
  on_click,
}: CraftingResultNameButtonProps): ReactNode => {
  return (
    <button
      type="button"
      aria-label={`View ${name} details`}
      className={clsx(baseStyles(), 'font-semibold', class_name)}
      onClick={on_click}
    >
      {name}
    </button>
  );
};

export default CraftingResultNameButton;
