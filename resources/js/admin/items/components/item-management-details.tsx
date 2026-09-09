import React, { ReactNode } from 'react';

import ItemManagementDetailsProps from './types/item-management-details-props';
import { ITEM_ALCHEMY_TYPE_LABELS } from '../enums/item-alchemy-type';
import { ITEM_CATALOG_TYPE_LABELS } from '../enums/item-catalog-type';
import { ITEM_CRAFTING_TYPE_LABELS } from '../enums/item-crafting-type';
import { ITEM_DEFAULT_POSITION_LABELS } from '../enums/item-default-position';
import { ITEM_SPECIALTY_TYPE_LABELS } from '../enums/item-specialty-type';

import DetailGridRow from 'ui/detail-grid/detail-grid-row';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const ItemManagementDetails = ({
  type,
  management,
}: ItemManagementDetailsProps): ReactNode => {
  const isSpecialtyItem = management.specialty_type !== null;

  const resolveCraftingAvailability = (): string | null => {
    if (management.craft_only) {
      return 'Craft-only';
    }

    if (isSpecialtyItem && !management.can_craft) {
      return 'Unavailable';
    }

    return null;
  };

  const renderSpecialAcquisition = (): ReactNode => {
    const craftingAvailability = resolveCraftingAvailability();
    const hasSpecialAcquisition =
      isSpecialtyItem ||
      management.craft_only ||
      management.is_generated_variant;

    if (!hasSpecialAcquisition) {
      return null;
    }

    return (
      <section>
        <h2 className="text-marigold-700 dark:text-marigold-500 mb-2 text-base font-semibold">
          Special Acquisition
        </h2>
        <Dl>
          {management.specialty_type !== null && (
            <>
              <Dt>Specialty Type</Dt>
              <Dd>{ITEM_SPECIALTY_TYPE_LABELS[management.specialty_type]}</Dd>
            </>
          )}
          {craftingAvailability && (
            <>
              <Dt>Crafting Availability</Dt>
              <Dd>{craftingAvailability}</Dd>
            </>
          )}
          {isSpecialtyItem && !management.market_sellable && (
            <>
              <Dt>Market Availability</Dt>
              <Dd>Unavailable</Dd>
            </>
          )}
          {isSpecialtyItem && !management.can_drop && (
            <>
              <Dt>Drop Availability</Dt>
              <Dd>Unavailable</Dd>
            </>
          )}
          {management.is_generated_variant && (
            <>
              <Dt>Generated Variant</Dt>
              <Dd>Yes</Dd>
            </>
          )}
        </Dl>
      </section>
    );
  };

  return (
    <DetailGridRow>
      {renderSpecialAcquisition()}

      <section>
        <h2 className="text-marigold-700 dark:text-marigold-500 mb-2 text-base font-semibold">
          Catalog Management
        </h2>
        <Dl>
          <Dt>Type</Dt>
          <Dd>{ITEM_CATALOG_TYPE_LABELS[type]}</Dd>
          {management.can_craft && (
            <>
              <Dt>Craftable</Dt>
              <Dd>Yes</Dd>
            </>
          )}
          {management.crafting_type !== null && (
            <>
              <Dt>Crafting Type</Dt>
              <Dd>{ITEM_CRAFTING_TYPE_LABELS[management.crafting_type]}</Dd>
            </>
          )}
          {management.market_sellable && (
            <>
              <Dt>Market Sellable</Dt>
              <Dd>Yes</Dd>
            </>
          )}
          {management.can_drop && (
            <>
              <Dt>Can Drop</Dt>
              <Dd>Yes</Dd>
            </>
          )}
          {management.default_position !== null && (
            <>
              <Dt>Default Position</Dt>
              <Dd>
                {ITEM_DEFAULT_POSITION_LABELS[management.default_position]}
              </Dd>
            </>
          )}
          {management.alchemy_type !== null && (
            <>
              <Dt>Alchemy Type</Dt>
              <Dd>{ITEM_ALCHEMY_TYPE_LABELS[management.alchemy_type]}</Dd>
            </>
          )}
          {management.unlocks_class && (
            <>
              <Dt>Unlocks Class</Dt>
              <Dd>{management.unlocks_class.name}</Dd>
            </>
          )}
          {management.item_skill && (
            <>
              <Dt>Item Skill</Dt>
              <Dd>{management.item_skill.name}</Dd>
            </>
          )}
        </Dl>
      </section>
    </DetailGridRow>
  );
};

export default ItemManagementDetails;
