import React, { ReactNode } from 'react';

import EnchantingFlow from './components/enchanting-flow';
import EnchantingIntroduction from './components/enchanting-introduction';
import { useEnchantingIntroduction } from './hooks/use-enchanting-introduction';

const EnchantingSection = (): ReactNode => {
  const { introductionAcknowledged, acknowledgeIntroduction } =
    useEnchantingIntroduction();

  if (!introductionAcknowledged) {
    return <EnchantingIntroduction onAcknowledge={acknowledgeIntroduction} />;
  }

  return <EnchantingFlow />;
};

export default EnchantingSection;
