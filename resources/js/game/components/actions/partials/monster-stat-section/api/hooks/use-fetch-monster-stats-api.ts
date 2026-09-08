import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig, AxiosResponse } from 'axios';
import { useCallback, useEffect, useState } from 'react';

import { MonsterApiUrls } from '../enums/monster-api-urls';
import SetMonsterPartsDefinition from './definitions/set-monster-params-definition';
import UseFetchMonsterStatsApiDefinition from './definitions/use-fetch-monster-stats-api-definition';
import MonsterDetailDefinition from '../../../../../../reusable-components/monster/api/definitions/monster-detail-definition';

export const useFetchMonsterStatsApi =
  (): UseFetchMonsterStatsApiDefinition => {
    const { apiHandler, getUrl } = useApiHandler();

    const [characterId, setCharacterId] = useState<number | null>(null);
    const [monsterId, setMonsterId] = useState<number | null>(null);

    const [loading, setLoading] = useState(false);
    const [data, setData] = useState<MonsterDetailDefinition | null>(null);
    const [error, setError] =
      useState<UseFetchMonsterStatsApiDefinition['error']>(null);

    let url = null;

    if (characterId && monsterId) {
      url = getUrl(MonsterApiUrls.STATS, {
        monster: monsterId,
        character: characterId,
      });
    }

    const setRequestParams = useCallback(
      (requestParams: SetMonsterPartsDefinition) => {
        setCharacterId(requestParams.character_id);
        setMonsterId(requestParams.monster_id);
      },
      []
    );

    const fetchMonsterStats = useCallback(async () => {
      if (!characterId || !monsterId || !url) {
        setLoading(false);
        return;
      }

      setLoading(true);
      setError(null);

      try {
        const result = await apiHandler.get<
          MonsterDetailDefinition,
          AxiosRequestConfig<AxiosResponse<MonsterDetailDefinition>>
        >(url);

        setData(result);
      } catch (err) {
        setData(null);

        if (err instanceof AxiosError) {
          setError(err.response?.data || null);
        }
      } finally {
        setLoading(false);
      }
    }, [apiHandler, url, characterId, monsterId]);

    useEffect(() => {
      fetchMonsterStats().catch(() => {});
    }, [fetchMonsterStats]);

    return {
      loading,
      data,
      setRequestParams,
      error,
    };
  };
