import React, { ReactNode, useState } from 'react';

import EnchantingIntroductionProps from './types/enchanting-introduction-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const EnchantingIntroduction = ({
  onAcknowledge,
}: EnchantingIntroductionProps): ReactNode => {
  const [hidePermanently, setHidePermanently] = useState<boolean>(false);

  const handleHidePermanentlyChange = (
    event: React.ChangeEvent<HTMLInputElement>
  ): void => {
    setHidePermanently(event.target.checked);
  };

  const handleAcknowledgeIntroduction = (): void => {
    onAcknowledge(hidePermanently);
  };

  return (
    <section className="prose dark:prose-invert">
      <h2>Enchanting</h2>

      <p>
        Server Messages report Enchanting outcomes. Your Intelligence and
        Enchanting skill govern the enchantments available to you. Class ranks,
        staves, damage spells, stat modifiers, and spell crafting can raise
        Intelligence.
      </p>

      <label className="flex items-center gap-2">
        <input
          type="checkbox"
          checked={hidePermanently}
          onChange={handleHidePermanentlyChange}
        />{' '}
        Do not show this help again
      </label>

      <Button
        label="I understand"
        on_click={handleAcknowledgeIntroduction}
        variant={ButtonVariant.PRIMARY}
      />
    </section>
  );
};

export default EnchantingIntroduction;
