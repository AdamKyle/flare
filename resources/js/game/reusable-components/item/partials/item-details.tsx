import React from 'react';

import ArmourClassSection from './item-detail-sections/armour-class-section';
import DamageSection from './item-detail-sections/damage-section';
import StatsSection from './item-detail-sections/stats-section';
import { ItemDetailsSectionLabels } from '../enums/item-details-section-labels';
import ItemDetailsProps from '../types/partials/item-details-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Separator from 'ui/separator/separator';

const ItemDetails = ({
  item,
  show_advanced_button,
  show_in_between_separator,
  damage_ac_on_top,
}: ItemDetailsProps) => {
  const renderAdvancedButton = () => {
    if (!show_advanced_button) {
      return null;
    }

    return (
      <div className="mt-6">
        <Button
          on_click={() => {}}
          label="Advanced Details"
          variant={ButtonVariant.PRIMARY}
        />
      </div>
    );
  };

  const renderInBetweenSeparator = () => {
    if (show_in_between_separator) {
      return <Separator />;
    }

    return <Separator additional_css={'block md:hidden'} />;
  };

  const renderDamageOnTop = () => {
    const statsSection = <StatsSection item={item} />;

    const damageSection = (
      <DamageSection
        item={item}
        attributes={[
          {
            label: ItemDetailsSectionLabels.DAMAGE,
            attribute: 'base_damage',
          },
        ]}
      />
    );

    const armourClassSection = (
      <ArmourClassSection
        item={item}
        attributes={[
          {
            label: ItemDetailsSectionLabels.AC,
            attribute: 'base_ac',
          },
        ]}
      />
    );

    const sep = renderInBetweenSeparator();

    if (damage_ac_on_top) {
      return (
        <>
          {damageSection}
          {armourClassSection}
          {sep}
          {statsSection}
        </>
      );
    }

    return (
      <>
        {statsSection}
        {sep}
        {damageSection}
        {armourClassSection}
      </>
    );
  };

  return (
    <>
      {renderDamageOnTop()}

      {renderAdvancedButton()}
    </>
  );
};

export default ItemDetails;
