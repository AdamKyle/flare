import { useState } from 'react';

import UseTooltipDisclosureDefinition from 'ui/tool-tips/hooks/definitions/use-tooltip-disclosure-definition';
import UseTooltipDisclosureParams from 'ui/tool-tips/hooks/definitions/use-tooltip-disclosure-params';

const useTooltipDisclosure = (
  params: UseTooltipDisclosureParams
): UseTooltipDisclosureDefinition => {
  const { isOpenProp, onOpen, onClose } = params;

  const [internalOpen, setInternalOpen] = useState(false);

  const open = typeof isOpenProp === 'boolean' ? isOpenProp : internalOpen;

  const setOpen = (next: boolean): void => {
    if (typeof isOpenProp !== 'boolean') {
      setInternalOpen(next);
    }
  };

  const openTip = (): void => {
    setOpen(true);

    if (onOpen) {
      onOpen();
    }
  };

  const closeTip = (): void => {
    setOpen(false);

    if (onClose) {
      onClose();
    }
  };

  const toggleTip = (): void => {
    if (open) {
      closeTip();

      return;
    }

    openTip();
  };

  return {
    open,
    openTip,
    closeTip,
    toggleTip,
    setOpen,
  };
};

export default useTooltipDisclosure;
