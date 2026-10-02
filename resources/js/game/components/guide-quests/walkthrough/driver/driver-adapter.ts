import { driver, Driver } from 'driver.js';
import 'driver.js/dist/driver.css';

import { GuideWalkthroughStepType } from '../enums/guide-walkthrough-step-type';
import { GuideTargets } from '../registry/guide-targets';
import PresentGuideStepParams from '../types/present-guide-step-params';

export const presentGuideStep = ({
  step,
  element,
  on_continue,
  on_close,
}: PresentGuideStepParams): Driver => {
  const reduceMotion = window.matchMedia(
    '(prefers-reduced-motion: reduce)'
  ).matches;
  const driverInstance = driver({
    animate: !reduceMotion,
    popoverClass: 'tlessa-guide-popover',
    allowClose: true,
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
      side: step.target === GuideTargets.ITEM_ACTIONS ? 'left' : undefined,
      align: step.target === GuideTargets.ITEM_ACTIONS ? 'start' : undefined,
      showButtons:
        step.step_type === GuideWalkthroughStepType.CONTINUE
          ? ['next', 'close']
          : ['close'],
    },
  });

  return driverInstance;
};
