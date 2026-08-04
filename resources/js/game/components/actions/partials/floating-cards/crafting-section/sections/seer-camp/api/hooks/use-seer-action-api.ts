import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError } from 'axios';
import { useCallback, useState } from 'react';

import SeerCampApiResponseDefinition from '../definitions/seer-camp-api-response-definition';
import UseSeerActionApiDefinition from './definitions/use-seer-action-api-definition';
import UseSeerActionApiParams, {
  SeerActionRequest,
} from './definitions/use-seer-action-api-params';
export const useSeerActionApi = ({
  characterId,
  url,
  request,
}: UseSeerActionApiParams): UseSeerActionApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const submit = useCallback(async () => {
    if (!request) return null;
    setSubmitting(true);
    setError(null);
    try {
      return await apiHandler.post<
        SeerCampApiResponseDefinition,
        never,
        SeerActionRequest
      >(getUrl(url, { character: characterId }), request);
    } catch (requestError) {
      setError(
        requestError instanceof AxiosError
          ? (requestError.response?.data?.message ??
              'Unable to complete the Seer Camp action.')
          : 'Unable to complete the Seer Camp action.'
      );
      return null;
    } finally {
      setSubmitting(false);
    }
  }, [apiHandler, characterId, getUrl, request, url]);
  return { submitting, error, submit };
};
