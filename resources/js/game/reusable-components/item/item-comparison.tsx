import React, { useState } from 'react';

import { ItemDetailsSectionLabels } from './enums/item-details-section-labels';
import ExpandedItemComparison from './partials/expanded-item-comparison';
import ArmourClassSection from './partials/item-detail-sections/armour-class-section';
import DamageSection from './partials/item-detail-sections/damage-section';
import StatsSection from './partials/item-detail-sections/stats-section';
import ItemComparisonProps from './types/item-comparison-props';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Separator from 'ui/separator/separator';

const ItemComparison = ({
  comparisonDetails,
  item_name,
}: ItemComparisonProps) => {
  const [expanded, setExpanded] = useState(false);

  const renderExpandedDetails = (
    detail: (typeof comparisonDetails.details)[0]
  ) => {
    if (!expanded) {
      return null;
    }

    return <ExpandedItemComparison expandedDetails={detail} />;
  };

  const columnsClass =
    comparisonDetails.details.length > 1 ? 'md:grid-cols-2' : '';

  return (
    <div>
      <div className={`grid grid-cols-1 ${columnsClass} gap-6`}>
        {comparisonDetails.details.map((detail) => {
          return (
            <div key={detail.name} className="space-y-6 p-4 md:p-6">
              <h3 className="text-lg font-semibold text-danube-600 dark:text-danube-300">
                {detail.name}
              </h3>
              <Separator />
              <Alert variant={AlertVariant.INFO}>
                The adjustments you see below are in relation to you replacing
                this item with the one you want to buy from the shop:{' '}
                {item_name}
              </Alert>
              <Separator />
              <DamageSection
                item={detail}
                attributes={[
                  {
                    label: ItemDetailsSectionLabels.DAMAGE,
                    attribute: 'damage_adjustment',
                  },
                  {
                    label: ItemDetailsSectionLabels.BONUS_DAMAGE_MOD,
                    attribute: 'base_damage_mod_adjustment',
                    expanded_only: true,
                  },
                ]}
                is_adjustment
                is_expanded={expanded}
              />
              <ArmourClassSection
                item={detail}
                attributes={[
                  {
                    label: ItemDetailsSectionLabels.AC,
                    attribute: 'ac_adjustment',
                  },
                ]}
                is_adjustment
              />
              <Separator />
              <StatsSection item={detail} is_adjustment />
              {renderExpandedDetails(detail)}
            </div>
          );
        })}
      </div>
      <div className="text-center mt-4">
        <Button
          on_click={() => setExpanded(!expanded)}
          label={expanded ? 'Show Less Details' : 'See Expanded Details'}
          variant={ButtonVariant.PRIMARY}
        />
      </div>
    </div>
  );
};

export default ItemComparison;
