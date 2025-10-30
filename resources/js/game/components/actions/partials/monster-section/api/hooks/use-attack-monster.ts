import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig, AxiosResponse } from 'axios';
import { useCallback, useEffect, useState } from 'react';
import { match } from 'ts-pattern';

import UseAttackMonsterDefinition from './definitions/use-attack-monster-definition';
import UseAttackMonsterRequestParams from './definitions/use-attack-monster-request-params';
import { AttackType } from '../../enums/attack-type';
import { BattleType } from '../../enums/battle-type';
import { BattleApiUrls } from '../enums/battle-api-urls';
import UseAttackMonsterInitiationResponse from './definitions/use-attack-monster-initiation-response';

export const useAttackMonster = (): UseAttackMonsterDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [data, setData] = useState<UseAttackMonsterInitiationResponse | null>(
    null
  );
  const [error, setError] = useState<UseAttackMonsterDefinition['error']>(null);
  const [loading, setLoading] = useState(false);
  const [requestData, setRequestData] = useState<UseAttackMonsterRequestParams>(
    {
      monster_id: 0,
      character_id: 0,
      attack_type: AttackType.ATTACK,
      battle_type: BattleType.INITIATE,
    }
  );

  const urlToUse = match(requestData.battle_type)
    .with(BattleType.INITIATE, () =>
      getUrl(BattleApiUrls.INITIATE_FIGHT, {
        character: requestData.character_id,
        monster: requestData.monster_id,
      })
    )
    .with(BattleType.ATTACK, () =>
      getUrl(BattleApiUrls.FIGHT, {
        character: requestData.character_id,
      })
    )
    .otherwise(() =>
      getUrl(BattleApiUrls.INITIATE_FIGHT, {
        character: requestData.character_id,
        monster: requestData.monster_id,
      })
    );

  const fetchBattleResults = useCallback(
    async () => {
      if (requestData.character_id === 0 || requestData.monster_id === 0) {
        setLoading(false);
        return;
      }

      setLoading(true);

      try {
        const result = await apiHandler.get<
          UseAttackMonsterInitiationResponse,
          AxiosRequestConfig<AxiosResponse<UseAttackMonsterInitiationResponse>>
        >(urlToUse, {
          params: {
            attack_type: requestData.attack_type,
          },
        });

        setData(result);
      } catch (err) {
        if (err instanceof AxiosError) {
          setError(err.response?.data || null);
        }
      } finally {
        setLoading(false);
      }
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [apiHandler, urlToUse]
  );

  useEffect(() => {
    fetchBattleResults().catch(() => {});
  }, [fetchBattleResults]);

  return {
    loading,
    setRequestData,
    error,
    data,
  };
};
