import React from 'react';

import CardBackProps from './types/card-back-props';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LinkButton from 'ui/buttons/link-button';

const CardBack = ({ children, link_title, on_click_link }: CardBackProps) => {
  return (
    <div
      className="absolute inset-0 flex h-full flex-col items-center justify-between rounded-t-xl border-x border-t border-gray-900/60 bg-gray-900 px-4 py-4 text-center text-gray-50 shadow-sm transition-shadow group-hover:shadow-md dark:border-gray-700/80 dark:bg-gray-700"
      style={{
        backfaceVisibility: 'hidden',
        WebkitBackfaceVisibility: 'hidden',
        transform: 'rotateY(180deg)',
      }}
    >
      <div className="px-1">{children}</div>

      <div className="mt-3 flex justify-center pb-1">
        <LinkButton
          label={link_title}
          variant={ButtonVariant.PRIMARY}
          on_click={on_click_link}
        />
      </div>
    </div>
  );
};

export default CardBack;
