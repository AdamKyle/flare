import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError } from 'axios';
import { useCallback, useState } from 'react';

import SendPublicEntityCommandRequest from './definitions/send-public-entity-command-request';
import UseSendPublicEntityCommandDefinition from './definitions/use-send-public-entity-command-definition';
import { ChatApiUrls } from '../enums/chat-api-urls';

export const useSendPublicEntityCommand =
  (): UseSendPublicEntityCommandDefinition => {
    const { apiHandler, getUrl } = useApiHandler();
    const { handleInactivity } = useActivityTimeout();

    const [error, setError] = useState<AxiosErrorDefinition | null>(null);
    const [loading, setLoading] = useState(false);

    const url = getUrl(ChatApiUrls.PUBLIC_ENTITY);

    const send_public_entity_command = useCallback(
      async (request: SendPublicEntityCommandRequest): Promise<boolean> => {
        setLoading(true);
        setError(null);

        try {
          await apiHandler.post<unknown, never, SendPublicEntityCommandRequest>(
            url,
            request
          );

          return true;
        } catch (requestError) {
          if (!(requestError instanceof AxiosError)) {
            setError({ message: 'Unable to use that command right now.' });

            return false;
          }

          setError(
            requestError.response?.data ?? {
              message: 'Unable to use that command right now.',
            }
          );

          handleInactivity({ setError, response: requestError });

          return false;
        } finally {
          setLoading(false);
        }
      },
      [apiHandler, handleInactivity, url]
    );

    return {
      loading,
      error,
      send_public_entity_command,
    };
  };
