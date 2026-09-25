import { ApiHandlerProvider } from 'api-handler/components/api-handler-provider';
import React, { ReactNode } from 'react';
import { ServiceContainer } from 'service-container-provider/service-container';

import OnboardingScreen from './onboarding-screen';
import OnboardingAppProps from './types/onboarding-app-props';

const OnboardingApp = ({ character_id }: OnboardingAppProps): ReactNode => {
  return (
    <ServiceContainer>
      <ApiHandlerProvider>
        <OnboardingScreen character_id={character_id} />
      </ApiHandlerProvider>
    </ServiceContainer>
  );
};

export default OnboardingApp;
