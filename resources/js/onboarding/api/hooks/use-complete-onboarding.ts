import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios, { AxiosRequestConfig } from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import UseCompleteOnboardingDefinition from './definitions/use-complete-onboarding-definition';
import { OnboardingApiUrls } from '../enums/onboarding-api-urls';

const GAME_URL = '/game';

export const useCompleteOnboarding = (
  characterId: number
): UseCompleteOnboardingDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const abortControllerRef = useRef<AbortController | null>(null);
  const isSubmittingRef = useRef(false);

  useEffect(() => {
    return () => {
      abortControllerRef.current?.abort();
    };
  }, []);

  const complete = useCallback(async (): Promise<boolean> => {
    if (isSubmittingRef.current || characterId <= 0) {
      return false;
    }

    isSubmittingRef.current = true;
    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    try {
      await apiHandler.post<
        Record<string, never>,
        AxiosRequestConfig<Record<string, never>>,
        Record<string, never>
      >(
        getUrl(OnboardingApiUrls.COMPLETE_ONBOARDING, {
          character: characterId,
        }),
        {},
        { signal: controller.signal }
      );
    } catch (requestError) {
      isSubmittingRef.current = false;
      abortControllerRef.current = null;
      setLoading(false);

      if (!axios.isCancel(requestError)) {
        setError('Unable to complete onboarding. Please try again.');
      }

      return false;
    }

    // Loading stays active while the browser navigates so the finish action cannot be resubmitted.
    window.location.assign(GAME_URL);

    return true;
  }, [apiHandler, getUrl, characterId]);

  return { loading, error, complete };
};
