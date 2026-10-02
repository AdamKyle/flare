import { Driver } from 'driver.js';
import { useCallback, useEffect, useRef } from 'react';

import UseGuideWalkthroughDriverDefinition from './definitions/use-guide-walkthrough-driver-definition';
import { presentGuideStep } from '../driver/driver-adapter';
import PresentGuideStepParams from '../types/present-guide-step-params';

export const useGuideWalkthroughDriver =
  (): UseGuideWalkthroughDriverDefinition => {
    const driverRef = useRef<Driver | null>(null);

    const destroyDriver = useCallback((): void => {
      driverRef.current?.destroy();
      driverRef.current = null;
    }, []);

    const presentDriverStep = useCallback(
      (params: PresentGuideStepParams): void => {
        destroyDriver();
        driverRef.current = presentGuideStep(params);
      },
      [destroyDriver]
    );

    useEffect(() => destroyDriver, [destroyDriver]);

    return { presentDriverStep, destroyDriver };
  };
