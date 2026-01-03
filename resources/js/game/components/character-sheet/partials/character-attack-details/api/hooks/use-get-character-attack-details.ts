import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig } from 'axios';
import { isNil } from 'lodash';
import { useCallback, useEffect, useState } from 'react';

import { CharacterAttackDetailsUrls } from '../enums/character-attack-details-urls';
import UseGetCharacterAttackDefinitionParams from './definitions/use-get-character-attack-definition-params';
import UseGetCharacterStatBreakdownDefinition from './definitions/use-get-character-attack-details-definition';
import UseGetCharacterAttackDetailsRequestParamsDefinition from './definitions/use-get-character-attack-details-request-params-definition';
import { getAttackTypeName } from '../../../../enums/attack-types';
import CharacterAttackBreakDownDefinition from '../definitions/character-attack-break-down-definition';

export const useGetCharacterAttackDetails = ({
  character_id,
  attack_type,
}: UseGetCharacterAttackDefinitionParams): UseGetCharacterStatBreakdownDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const url = getUrl(CharacterAttackDetailsUrls.CHARACTER_ATTACK_DETAILS, {
    character: character_id,
  });

  const [data, setData] = useState<CharacterAttackBreakDownDefinition | null>(
    null
  );
  const [loading, setLoading] = useState(true);
  const [error, setError] =
    useState<UseGetCharacterStatBreakdownDefinition['error']>(null);

  const fetchCharacterAttackDetails = useCallback(
    async ({
      attack_type,
    }: UseGetCharacterAttackDetailsRequestParamsDefinition) => {
      if (isNil(attack_type)) {
        return;
      }

      try {
        const result = await apiHandler.get<
          CharacterAttackBreakDownDefinition,
          AxiosRequestConfig<CharacterAttackBreakDownDefinition>
        >(url, { params: { type: getAttackTypeName(attack_type) } });

        setData(result);
      } catch (error) {
        if (error instanceof AxiosError) {
          setError(error.response?.data);
        } else {
          throw error;
        }
      } finally {
        setLoading(false);
      }
    },
    [apiHandler, url]
  );

  useEffect(
    () => {
      fetchCharacterAttackDetails({ attack_type }).catch((error) =>
        console.error(error)
      );
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [fetchCharacterAttackDetails]
  );

  return {
    data,
    loading,
    error,
  };
};
