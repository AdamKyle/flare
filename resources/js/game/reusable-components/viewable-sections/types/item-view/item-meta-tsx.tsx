import clsx from 'clsx';
import { capitalize } from 'lodash';
import React from 'react';

import ItemMetaProps from '../../../../components/side-peeks/character-inventory/inventory-item/types/partials/item-view/item-meta-props';
import DefinitionRow from '../../definition-row';
import InfoLabel from '../../info-label';

import Dl from 'ui/dl/dl';
import Separator from 'ui/separator/separator';

const ItemMetaSection = ({
  name,
  description,
  type,
  titleClassName,
}: ItemMetaProps) => {
  return (
    <>
      <div>
        <h2 className={clsx(titleClassName, 'text-lg my-2')}>{name}</h2>

        <Separator />

        <p className="my-4 text-gray-800 dark:text-gray-300">{description}</p>

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
