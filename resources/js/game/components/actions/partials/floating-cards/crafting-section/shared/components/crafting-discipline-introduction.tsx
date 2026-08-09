import React, { ReactNode } from 'react';

import CraftingDisciplineIntroductionProps from './types/crafting-discipline-introduction-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const CraftingDisciplineIntroduction = ({
  title,
  description,
  on_acknowledge,
}: CraftingDisciplineIntroductionProps): ReactNode => {
  return (
    <section className="prose dark:prose-invert">
      <h2>{title}</h2>
      <p>{description}</p>
      <Button
        on_click={on_acknowledge}
        label="I understand"
        variant={ButtonVariant.PRIMARY}
      />
    </section>
  );
};

export default CraftingDisciplineIntroduction;
