import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { resolveApiErrorMessage } from 'api-handler/utils/resolve-api-error-message';
import axios, { AxiosError, AxiosRequestConfig, AxiosResponse } from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';
import { match } from 'ts-pattern';

import UseAttackMonsterDefinition from './definitions/use-attack-monster-definition';
import UseAttackMonsterRequestParams from './definitions/use-attack-monster-request-params';
import { AttackType } from '../../enums/attack-type';
import { BattleType } from '../../enums/battle-type';
import { BattleApiUrls } from '../enums/battle-api-urls';
import UseAttackMonsterInitiationResponse from './definitions/use-attack-monster-initiation-response';
import UseAttackMonsterRequestDefinition from './definitions/use-attack-monster-request-definition';
import { GuideWalkthroughEvent } from '../../../../../guide-quests/walkthrough/enums/guide-walkthrough-event';
import { useGuideWalkthroughEventEmitter } from '../../../../../guide-quests/walkthrough/hooks/use-guide-walkthrough-event-emitter';

const isTerminalBattleResult = (
  result: UseAttackMonsterInitiationResponse
): boolean =>
  result.health.current_monster_health <= 0 ||
  result.health.current_character_health <= 0;

export const useAttackMonster = (): UseAttackMonsterDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();
  const guideWalkthroughEventEmitter = useGuideWalkthroughEventEmitter();

  const [data, setData] = useState<UseAttackMonsterInitiationResponse | null>(
    null
  );
  const [error, setError] = useState<UseAttackMonsterDefinition['error']>(null);
  const [loading, setLoading] = useState(false);
  const [disableAttackButtons, setDisableAttackButtons] = useState(false);
  const [
    awaitingAttackCooldownConfirmation,
    setAwaitingAttackCooldownConfirmation,
  ] = useState(false);
  const [reinitializeFight, setReinitializeFight] = useState(false);
  const [requestData, setRequestData] = useState<UseAttackMonsterRequestParams>(
    {
      monster_id: 0,
      character_id: 0,
      attack_type: AttackType.ATTACK,
      battle_type: BattleType.INITIATE,
    }
  );

  const abortControllerRef = useRef<AbortController | null>(null);

  useEffect(() => {
    return () => {
      const activeController = abortControllerRef.current;

      abortControllerRef.current = null;
      activeController?.abort();
    };
  }, []);

  const acknowledgeAttackCooldown = useCallback(() => {
    setAwaitingAttackCooldownConfirmation(false);
  }, []);

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

  const handleBattleRequestFailure = useCallback(
    (
      requestError: unknown,
      controller: AbortController,
      fallbackMessage: string
    ): void => {
      if (axios.isCancel(requestError)) {
        return;
      }

      if (abortControllerRef.current !== controller) {
        return;
      }

      setError({
        message: resolveApiErrorMessage(requestError, fallbackMessage),
      });

      if (requestError instanceof AxiosError) {
        handleInactivity({ response: requestError, setError });
      }
    },
    [handleInactivity]
  );

  const handleInitiateFightRequest = useCallback(
    async (controller: AbortController): Promise<void> => {
      setLoading(true);

      try {
        const result = await apiHandler.get<
          UseAttackMonsterInitiationResponse,
          AxiosRequestConfig<AxiosResponse<UseAttackMonsterInitiationResponse>>
        >(urlToUse, {
          params: {
            attack_type: requestData.attack_type,
          },
          signal: controller.signal,
        });

        if (abortControllerRef.current !== controller) {
          return;
        }

        setData(result);
        guideWalkthroughEventEmitter.emit(
          GuideWalkthroughEvent.FIGHT_STARTED,
          undefined
        );

        if (isTerminalBattleResult(result)) {
          setAwaitingAttackCooldownConfirmation(true);
        }
      } catch (requestError) {
        handleBattleRequestFailure(
          requestError,
          controller,
          'Unable to start the fight.'
        );
      } finally {
        if (abortControllerRef.current === controller) {
          abortControllerRef.current = null;
          setLoading(false);
        }
      }
    },
    [
      apiHandler,
      guideWalkthroughEventEmitter,
      handleBattleRequestFailure,
      requestData.attack_type,
      urlToUse,
    ]
  );

  const handleAttackMonster = useCallback(
    async (controller: AbortController): Promise<void> => {
      if (!requestData.attack_type) {
        abortControllerRef.current = null;

        return;
      }

      setDisableAttackButtons(true);

      try {
        const result = await apiHandler.post<
          UseAttackMonsterInitiationResponse,
          AxiosRequestConfig<AxiosResponse<UseAttackMonsterInitiationResponse>>,
          UseAttackMonsterRequestDefinition
        >(
          urlToUse,
          {
            attack_type: requestData.attack_type,
          },
          { signal: controller.signal }
        );

        if (abortControllerRef.current !== controller) {
          return;
        }

        setData(result);
        guideWalkthroughEventEmitter.emit(
          GuideWalkthroughEvent.COMBAT_ACTION_SUCCEEDED,
          undefined
        );

        if (isTerminalBattleResult(result)) {
          setAwaitingAttackCooldownConfirmation(true);
        }
      } catch (requestError) {
        handleBattleRequestFailure(
          requestError,
          controller,
          'Unable to complete the attack.'
        );
      } finally {
        if (abortControllerRef.current === controller) {
          abortControllerRef.current = null;
          setDisableAttackButtons(false);
        }
      }
    },
    [
      apiHandler,
      guideWalkthroughEventEmitter,
      handleBattleRequestFailure,
      requestData.attack_type,
      urlToUse,
    ]
  );

  const fetchBattleResults = useCallback(async (): Promise<void> => {
    const { character_id, monster_id, battle_type } = requestData;

    if (character_id === 0 || monster_id === 0) {
      setLoading(false);

      return;
    }

    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;

    if (battle_type === BattleType.INITIATE) {
      await handleInitiateFightRequest(controller);

      return;
    }

    if (battle_type === BattleType.ATTACK) {
      await handleAttackMonster(controller);
    }
  }, [handleAttackMonster, handleInitiateFightRequest, requestData]);

  // Toggling `reinitializeFight` (Reset Fight / Attack Again) must force a
  // fresh fight request even when the request data is otherwise unchanged.
  useEffect(() => {
    void fetchBattleResults();
  }, [fetchBattleResults, reinitializeFight]);

  return {
    loading,
    disableAttackButtons,
    awaitingAttackCooldownConfirmation,
    setRequestData,
    setReinitializeFight,
    acknowledgeAttackCooldown,
    error,
    data,
  };
};
