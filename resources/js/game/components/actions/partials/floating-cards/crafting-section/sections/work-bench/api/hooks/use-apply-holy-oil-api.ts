import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError } from 'axios';
import { useState } from 'react';

import ApplyHolyOilRequestDefinition from '../definitions/apply-holy-oil-request-definition';
import WorkBenchApiResponseDefinition from '../definitions/work-bench-api-response-definition';
import { WorkBenchApiUrls } from '../enums/work-bench-api-urls';
import UseApplyHolyOilApiDefinition from './definitions/use-apply-holy-oil-api-definition';
import UseApplyHolyOilApiParams from './definitions/use-apply-holy-oil-api-params';
export const useApplyHolyOilApi = ({
  characterId,
  request,
}: UseApplyHolyOilApiParams): UseApplyHolyOilApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const apply = async (): Promise<WorkBenchApiResponseDefinition | null> => {
    if (!request) return null;
    setSubmitting(true);
    setError(null);
    try {
      return await apiHandler.post<
        WorkBenchApiResponseDefinition,
        never,
        ApplyHolyOilRequestDefinition
      >(getUrl(WorkBenchApiUrls.APPLY, { character: characterId }), request);
    } catch (requestError) {
      setError(
        requestError instanceof AxiosError
          ? (requestError.response?.data?.message ??
              'Unable to apply the Holy Oil.')
          : 'Unable to apply the Holy Oil.'
      );
      return null;
    } finally {
      setSubmitting(false);
    }
  };
  return { submitting, error, apply };
};
