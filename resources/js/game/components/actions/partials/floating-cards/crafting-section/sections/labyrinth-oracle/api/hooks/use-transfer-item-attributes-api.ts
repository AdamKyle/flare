import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError } from 'axios';
import { useState } from 'react';

import LabyrinthOracleApiResponseDefinition from '../definitions/labyrinth-oracle-api-response-definition';
import TransferItemAttributesRequestDefinition from '../definitions/transfer-item-attributes-request-definition';
import { LabyrinthOracleApiUrls } from '../enums/labyrinth-oracle-api-urls';
import UseTransferItemAttributesApiDefinition from './definitions/use-transfer-item-attributes-api-definition';
import UseTransferItemAttributesApiParams from './definitions/use-transfer-item-attributes-api-params';

export const useTransferItemAttributesApi = ({
  characterId,
  request,
}: UseTransferItemAttributesApiParams): UseTransferItemAttributesApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const transfer =
    async (): Promise<LabyrinthOracleApiResponseDefinition | null> => {
      if (!request) return null;
      setSubmitting(true);
      setError(null);
      try {
        return await apiHandler.post<
          LabyrinthOracleApiResponseDefinition,
          never,
          TransferItemAttributesRequestDefinition
        >(
          getUrl(LabyrinthOracleApiUrls.TRANSFER, { character: characterId }),
          request
        );
      } catch (requestError) {
        setError(
          requestError instanceof AxiosError
            ? (requestError.response?.data?.message ??
                'Unable to transfer the item attributes.')
            : 'Unable to transfer the item attributes.'
        );
        return null;
      } finally {
        setSubmitting(false);
      }
    };
  return { submitting, error, transfer };
};
