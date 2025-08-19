import React from 'react';

import TitleAndDescriptionProps from '../../../types/partials/item-comparison/comparison-column-sections/title-and-description-props';

const TitleAndDescription = ({
  title,
  description,
}: TitleAndDescriptionProps) => {
  return (
    <>
      <h3 className="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-200 break-words">
        {title}
      </h3>

      <p className="mb-4 text-base leading-relaxed text-gray-700 dark:text-gray-300 break-words">
        {description}
      </p>
    </>
  );
};

export default TitleAndDescription;
