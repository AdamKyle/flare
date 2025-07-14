import React from 'react';

import SimpleItemDetailsProps from './types/simple-item-details-props';
import { decodeHtmlEntities } from '../util/decode-string';
import ItemDetailSection from './partials/item-detail-section';
import ItemDetails from './partials/item-details';

import Card from 'ui/cards/card';
import ContainerWithTitle from 'ui/container/container-with-title';
import Dl from 'ui/dl/dl';
import Separator from 'ui/separator/separator';

const SimpleItemDetails = ({
  item,
  on_close,
  show_advanced_button,
}: SimpleItemDetailsProps) => {
  return (
    <ContainerWithTitle manageSectionVisibility={on_close} title={item.name}>
      <Card>
        <p className="mb-4 text-sm text-gray-700 dark:text-gray-300">
          {decodeHtmlEntities(item.description)}
        </p>
        <Separator />

        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 auto-rows-min items-start">
          <div>
            <h4 className="mb-2 text-sm font-semibold text-mango-tango-500 dark:text-mango-tango-300">
              Cost & Crafting
            </h4>
            <Separator />
            <Dl>
              <ItemDetailSection
                label={'Cost'}
                item_type={item.type}
                value={item.cost}
              />
              <ItemDetailSection
                label={'Crafting (Req.)'}
                item_type={item.type}
                value={item.skill_level_req}
              />
              <ItemDetailSection
                label={'Crafting (Trivial)'}
                item_type={item.type}
                value={item.skill_level_trivial}
              />
            </Dl>
          </div>

          <ItemDetails
            item={item}
            show_advanced_button={show_advanced_button}
          />
        </div>
      </Card>
    </ContainerWithTitle>
  );
};

export default SimpleItemDetails;
