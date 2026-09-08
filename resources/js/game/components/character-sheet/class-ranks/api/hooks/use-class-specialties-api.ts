import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError } from 'axios';
import { useEffect, useState } from 'react';

import UseClassSpecialtiesApiDefinition from './definitions/use-class-specialties-api-definition';
import UseClassSpecialtiesApiParams from './definitions/use-class-specialties-api-params';
import ClassSpecialtiesResponseDefinition from '../definitions/class-specialties-response-definition';
import SwapClassSpecialtyResponseDefinition from '../definitions/swap-class-specialty-response-definition';
import { ClassRanksApiUrls } from '../enums/class-ranks-api-urls';

const errorMessage = (error: unknown, fallback: string): string =>
  error instanceof AxiosError
    ? (error.response?.data?.message ?? fallback)
    : fallback;

export const useClassSpecialtiesApi = ({
  characterId,
}: UseClassSpecialtiesApiParams): UseClassSpecialtiesApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const [data, setData] = useState<ClassSpecialtiesResponseDefinition | null>(
    null
  );
  const [loading, setLoading] = useState(characterId > 0);
  const [error, setError] = useState<string | null>(null);
  const [equippingSpecialtyId, setEquippingSpecialtyId] = useState<
    number | null
  >(null);
  const [unequippingSpecialtyId, setUnequippingSpecialtyId] = useState<
    number | null
  >(null);
  const [swappingSpecialtyId, setSwappingSpecialtyId] = useState<number | null>(
    null
  );
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const [mutationError, setMutationError] = useState<string | null>(null);

  useEffect(() => {
    if (characterId <= 0) {
      setLoading(false);

      return;
    }

    setLoading(true);
    setError(null);

    apiHandler
      .get<ClassSpecialtiesResponseDefinition, never>(
        getUrl(ClassRanksApiUrls.SPECIALTIES, { character: characterId })
      )
      .then(setData)
      .catch((requestError: unknown) =>
        setError(
          errorMessage(requestError, 'Unable to load Class Specialties.')
        )
      )
      .finally(() => setLoading(false));
  }, [apiHandler, characterId, getUrl]);

  const equipSpecialty = async (gameClassSpecialId: number): Promise<void> => {
    if (characterId <= 0) {
      return;
    }

    setEquippingSpecialtyId(gameClassSpecialId);
    setSuccessMessage(null);
    setMutationError(null);

    try {
      const response = await apiHandler.post<
        ClassSpecialtiesResponseDefinition,
        never,
        Record<string, never>
      >(
        getUrl(ClassRanksApiUrls.EQUIP_SPECIALTY, {
          character: characterId,
          gameClassSpecial: gameClassSpecialId,
        }),
        {}
      );

      setData(response);
      setSuccessMessage(response.message ?? null);
    } catch (requestError) {
      setMutationError(
        errorMessage(requestError, 'Unable to equip the Class Specialty.')
      );
    } finally {
      setEquippingSpecialtyId(null);
    }
  };

  const unequipSpecialty = async (
    classSpecialEquippedId: number
  ): Promise<void> => {
    if (characterId <= 0) {
      return;
    }

    setUnequippingSpecialtyId(classSpecialEquippedId);
    setSuccessMessage(null);
    setMutationError(null);

    try {
      const response = await apiHandler.post<
        ClassSpecialtiesResponseDefinition,
        never,
        Record<string, never>
      >(
        getUrl(ClassRanksApiUrls.UNEQUIP_SPECIALTY, {
          character: characterId,
          classSpecialEquipped: classSpecialEquippedId,
        }),
        {}
      );

      setData(response);
      setSuccessMessage(response.message ?? null);
    } catch (requestError) {
      setMutationError(
        errorMessage(requestError, 'Unable to unequip the Class Specialty.')
      );
    } finally {
      setUnequippingSpecialtyId(null);
    }
  };

  const swapSpecialty = async (
    gameClassSpecialId: number,
    classSpecialEquippedId: number
  ): Promise<void> => {
    if (characterId <= 0) {
      return;
    }

    setSwappingSpecialtyId(gameClassSpecialId);
    setSuccessMessage(null);
    setMutationError(null);

    try {
      const response = await apiHandler.post<
        SwapClassSpecialtyResponseDefinition,
        never,
        Record<string, never>
      >(
        getUrl(ClassRanksApiUrls.SWAP_SPECIALTY, {
          character: characterId,
          gameClassSpecial: gameClassSpecialId,
          classSpecialEquipped: classSpecialEquippedId,
        }),
        {}
      );

      setData(response);
      setSuccessMessage(response.message ?? null);
    } catch (requestError) {
      setMutationError(
        errorMessage(requestError, 'Unable to swap the Class Specialty.')
      );
    } finally {
      setSwappingSpecialtyId(null);
    }
  };

  return {
    data,
    loading,
    error,
    equippingSpecialtyId,
    unequippingSpecialtyId,
    swappingSpecialtyId,
    successMessage,
    mutationError,
    equipSpecialty,
    unequipSpecialty,
    swapSpecialty,
  };
};
