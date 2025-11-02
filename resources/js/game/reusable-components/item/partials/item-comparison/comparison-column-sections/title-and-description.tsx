import clsx from 'clsx';
import React from 'react';

import { itemTextColor } from '../../../../../util/item-colors/item-text-color';
import TitleAndDescriptionProps from '../../../types/partials/item-comparison/comparison-column-sections/title-and-description-props';

const TitleAndDescription = ({ item }: TitleAndDescriptionProps) => {
  const color = itemTextColor(item);

  return (
    <>
      <h3 className={clsx('mb-2 text-lg font-semibold break-words', color)}>
        {item.name}
      </h3>

      <p className="mb-4 text-base leading-relaxed break-words text-gray-700 dark:text-gray-300">
        {item.description}
      </p>
    </>
  );
};

export default TitleAndDescription;
