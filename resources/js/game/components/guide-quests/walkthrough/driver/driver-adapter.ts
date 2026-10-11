import { driver, Driver } from 'driver.js';
import 'driver.js/dist/driver.css';

import { GuideWalkthroughInteraction } from '../enums/guide-walkthrough-interaction';
import PresentGuideStepParams from '../types/present-guide-step-params';
import { resolveGuideStepInteraction } from '../utils/resolve-guide-step-interaction';

export const presentGuideStep = ({
  step,
  element,
  on_continue,
  on_close,
}: PresentGuideStepParams): Driver => {
  const reduceMotion = window.matchMedia(
    '(prefers-reduced-motion: reduce)'
  ).matches;
  const presentation = resolveGuideStepInteraction(step);
  const isOpenAndSelect =
    presentation.interaction === GuideWalkthroughInteraction.OPEN_AND_SELECT;
  const driverInstance = driver({
    animate: !reduceMotion,
    popoverClass: 'tlessa-guide-popover',
    allowClose: true,
    allowKeyboardControl: !isOpenAndSelect,
    overlayClickBehavior: isOpenAndSelect ? () => undefined : 'close',
    disableActiveInteraction: false,
    showButtons: ['next', 'close'],
    nextBtnText: 'Continue',
    doneBtnText: 'Finish',
    onDestroyStarted: on_close,
    onCloseClick: on_close,
    onNextClick: on_continue,
  });

  driverInstance.highlight({
    element: element ?? undefined,
    popover: {
      title: step.title,
      description: step.content,
      side: presentation.popover_side,
      align: presentation.popover_align,
      showButtons:
        presentation.interaction === GuideWalkthroughInteraction.CONTINUE
          ? ['next', 'close']
          : ['close'],
    },
  });

  return driverInstance;
};
