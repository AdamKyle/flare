import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { resolveApiErrorMessage } from 'api-handler/utils/resolve-api-error-message';
import axios from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import UseSkillDetailDefinition from './definitions/use-skill-detail-definition';
import SkillDetailDefinition from '../definitions/skill-detail-definition';
import { SkillsApiUrls } from '../enums/skills-api-urls';

export const useSkillDetail = (
  characterId: number,
  skillId: number
): UseSkillDetailDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [skill, setSkill] = useState<SkillDetailDefinition | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const abortControllerRef = useRef<AbortController | null>(null);

  const fetchSkill = useCallback(async (): Promise<void> => {
    if (characterId <= 0 || skillId <= 0) {
      return;
    }

    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    try {
      const result = await apiHandler.get<SkillDetailDefinition, never>(
        getUrl(SkillsApiUrls.SKILL, { character: characterId, skill: skillId }),
        { signal: controller.signal }
      );

      if (abortControllerRef.current !== controller) {
        return;
      }

      setSkill(result);
    } catch (requestError) {
      if (axios.isCancel(requestError)) {
        return;
      }

      if (abortControllerRef.current !== controller) {
        return;
      }

      setError(
        resolveApiErrorMessage(requestError, 'Unable to load this Skill.')
      );
    } finally {
      if (abortControllerRef.current === controller) {
        abortControllerRef.current = null;
        setLoading(false);
      }
    }
  }, [apiHandler, getUrl, characterId, skillId]);

  useEffect(() => {
    void fetchSkill();

    return () => {
      abortControllerRef.current?.abort();
    };
  }, [fetchSkill]);

  const refetch = useCallback(() => {
    void fetchSkill();
  }, [fetchSkill]);

  return { skill, loading, error, refetch };
};
