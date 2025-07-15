import React from 'react';

import DamageAcSection from './item-detail-sections/damage-ac-section';
import StatsSection from './item-detail-sections/stats-section';
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

    const damageSection = <DamageAcSection item={item} />;

    const sep = renderInBetweenSeparator();

    if (damage_ac_on_top) {
      return (
        <>
          {damageSection}
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
      </>
    );
  };

  console.log(item);

  return (
    <>
      {renderDamageOnTop()}

      {renderAdvancedButton()}
    </>
  );
};

export default ItemDetails;
