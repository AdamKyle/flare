import clsx from 'clsx';
import { capitalize } from 'lodash';
import React from 'react';

import DefinitionRow from '../../../../../../reusable-components/viewable-sections/definition-row';
import InfoLabel from '../../../../../../reusable-components/viewable-sections/info-label';
import ItemMetaProps from '../../types/partials/item-view/item-meta-props';

import Dl from 'ui/dl/dl';
import Separator from 'ui/separator/separator';

const ItemMetaSection = ({
  name,
  description,
  type,
  titleClassName,
  effect,
}: ItemMetaProps) => {
  const renderEffect = () => {
    if (!effect || effect === 'N/A') {
      return null;
    }

    return (
      <p className="mb-4 text-gray-800 dark:text-gray-300">
        <strong>Item effect</strong>: {effect}
      </p>
    );
  };

  return (
    <>
      <div>
        <h2 className={clsx(titleClassName, 'my-2 text-lg')}>{name}</h2>

        <Separator />

        <p className="my-4 text-gray-800 dark:text-gray-300">{description}</p>
        {renderEffect()}
        <Separator />
      </div>

      <div>
        <Dl>
          <DefinitionRow
            left={<InfoLabel label="Type" />}
            right={<span>{capitalize(type)}</span>}
          />
        </Dl>
      </div>
    </>
  );
};

export default ItemMetaSection;
