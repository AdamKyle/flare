import ApiParametersDefinitions from 'api-handler/definitions/api-parameters-definitions';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosResponse } from 'axios';
import { useCallback } from 'react';

import AnnouncementMessageDefinition from '../../../../api-definitions/chat/annoucement-message-definition';

import MonsterDefinition from 'game-data/api-data-definitions/monsters/monster-definition';

export const UseAnnouncementsApi = (params: ApiParametersDefinitions) => {
  const { apiHandler, getUrl } = useApiHandler();
  const url = getUrl(params.url);

  const fetchAnnouncements = useCallback(async () => {
    try {
      return await apiHandler.get<
        AnnouncementMessageDefinition[],
        AxiosResponse<MonsterDefinition[]>
      >(url);
    } catch (error) {
      if (error instanceof AxiosError) {
        throw error.response?.data || new Error('Unknown API error');
      }

      throw error;
    }
  }, [apiHandler, url]);

  return { fetchAnnouncements };
};
