import React from 'react';

import BaseSectionProps from './types/base-section-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const EnchantingSection = ({ setActiveCraftingType }: BaseSectionProps) => {
  return (
    <div className={'prose dark:prose-invert'}>
      <h2>Enchanting</h2>
      <p>
        Enchanting is a secondary vital aspect that compliments crafting. While
        creatures can drop enchanted gear they will never drop the higher end
        enchantments and no shop sells enchanted gear with the exception of the
        market place for player crafted items.
      </p>
      <p>
        Crafting weapons, armour, rings and spells allows a player to outfit
        their character with items that raise stats, damage, armour defence (AC)
        and healing potency. Applying enchants to those items can increase
        things like crafting skills, general skills like Accuracy, damage, life
        stealing and much more.
      </p>
      <p>
        Enchanting is one of the most useful aspects of Tlessa, right after
        crafting. Disenchanting your own crafted items can gain you whats known
        as Gold Dust a currency used in later forms of crafting such as Alchemy.
      </p>
      <Button
        on_click={() => {}}
        label={'I understand'}
        variant={ButtonVariant.PRIMARY}
      />
    </div>
  );
};

export default EnchantingSection;
