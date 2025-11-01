import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
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
import UseAttackMonsterRequestDefinition from './definitions/use-attack-monster-request-definition';

export const useAttackMonster = (): UseAttackMonsterDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();

  const [data, setData] = useState<UseAttackMonsterInitiationResponse | null>(
    null
  );
  const [error, setError] = useState<UseAttackMonsterDefinition['error']>(null);
  const [loading, setLoading] = useState(false);
  const [disableAttackButtons, setDisableAttackButtons] = useState(false);
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

  const handleInitiateFightRequest = useCallback(async () => {
    console.log('Initiate Fight Request');
    console.log(urlToUse);

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
        handleInactivity({
          response: err,
          setError,
        });

        setError(err.response?.data || null);
      }
    }
  }, [apiHandler, urlToUse]);

  const handleAttackMonster = useCallback(async () => {
    if (!requestData.attack_type) {
      return;
    }

    try {
      const result = await apiHandler.post<
        UseAttackMonsterInitiationResponse,
        AxiosRequestConfig<AxiosResponse<UseAttackMonsterInitiationResponse>>,
        UseAttackMonsterRequestDefinition
      >(urlToUse, {
        attack_type: requestData.attack_type,
      });

      setData(result);
    } catch (err) {
      if (err instanceof AxiosError) {
        handleInactivity({
          response: err,
          setError,
        });

        setError(err.response?.data || null);
      }
    }
  }, [apiHandler, urlToUse]);

  const fetchBattleResults = useCallback(
    async () => {
      if (requestData.character_id === 0 || requestData.monster_id === 0) {
        setLoading(false);
        return;
      }

      if (requestData.battle_type === BattleType.INITIATE) {
        console.log('initiating');

        setLoading(true);

        handleInitiateFightRequest().finally(() => setLoading(false));

        return;
      }

      if (requestData.battle_type === BattleType.ATTACK) {
        setDisableAttackButtons(true);

        handleAttackMonster().finally(() => setDisableAttackButtons(false));

        return;
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
    disableAttackButtons,
    setRequestData,
    error,
    data,
  };
};
