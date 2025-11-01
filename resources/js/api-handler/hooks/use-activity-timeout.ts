import UseActivityTimeoutDefinitions from 'api-handler/hooks/definitions/use-activity-timeout-definitions';
import UseActivityTimeoutParams from 'api-handler/hooks/definitions/use-activity-timeout-params-definitions';
import { AxiosError } from 'axios';

export const useActivityTimeout = (): UseActivityTimeoutDefinitions => {
  const handleInactivity = ({
    response,
    setError,
  }: UseActivityTimeoutParams) => {
    if (!(response instanceof AxiosError)) {
      return;
    }

    if (response.response?.status === 401) {
      setError({
        message:
          'You have been logged out due to inactivity. One moment while we redirect you.',
      });

      window.location.reload();

      return;
    }
  };

  return {
    handleInactivity,
  };
};
