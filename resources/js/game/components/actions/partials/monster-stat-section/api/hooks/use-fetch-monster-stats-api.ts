import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios, { AxiosError, AxiosRequestConfig, AxiosResponse } from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

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
    const abortControllerRef = useRef<AbortController | null>(null);

    let url: string | null = null;

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

    useEffect(() => {
      return () => {
        const activeController = abortControllerRef.current;

        abortControllerRef.current = null;
        activeController?.abort();
      };
    }, []);

    const fetchMonsterStats = useCallback(async (): Promise<void> => {
      if (!characterId || !monsterId || !url) {
        abortControllerRef.current?.abort();
        abortControllerRef.current = null;
        setLoading(false);
        return;
      }

      abortControllerRef.current?.abort();
      const controller = new AbortController();
      abortControllerRef.current = controller;

      setLoading(true);
      setError(null);

      try {
        const result = await apiHandler.get<
          MonsterDetailDefinition,
          AxiosRequestConfig<AxiosResponse<MonsterDetailDefinition>>
        >(url, { signal: controller.signal });

        if (abortControllerRef.current !== controller) {
          return;
        }

        setData(result);
      } catch (requestError) {
        if (axios.isCancel(requestError)) {
          return;
        }

        if (abortControllerRef.current !== controller) {
          return;
        }

        setData(null);

        if (requestError instanceof AxiosError) {
          setError(requestError.response?.data || null);
        }
      } finally {
        if (abortControllerRef.current === controller) {
          abortControllerRef.current = null;
          setLoading(false);
        }
      }
    }, [apiHandler, url, characterId, monsterId]);

    useEffect(() => {
      void fetchMonsterStats();
    }, [fetchMonsterStats]);

    return {
      loading,
      data,
      setRequestParams,
      error,
    };
  };
