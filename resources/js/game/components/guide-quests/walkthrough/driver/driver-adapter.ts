import { driver, Driver } from 'driver.js';
import 'driver.js/dist/driver.css';

import { GuideQuestWalkthroughStepDefinition } from '../../api/definitions/guide-quest-walkthrough-definition';

interface PresentGuideStepParams {
  step: GuideQuestWalkthroughStepDefinition;
  element: HTMLElement | null;
  on_continue: () => void;
  on_close: () => void;
}

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
    allowClose: true,
    disableActiveInteraction: false,
    showButtons: ['next', 'close'],
    nextBtnText: 'Continue',
    doneBtnText: 'Finish',
    onCloseClick: on_close,
    onNextClick: on_continue,
  });

  driverInstance.highlight({
    element: element ?? undefined,
    popover: {
      title: step.title,
      description: step.content,
      showButtons:
        step.step_type === 'continue' ? ['next', 'close'] : ['close'],
    },
  });

  return driverInstance;
};
