import ClassSpecialtiesResponseDefinition from '../../definitions/class-specialties-response-definition';

export default interface UseClassSpecialtiesApiDefinition {
  data: ClassSpecialtiesResponseDefinition | null;
  loading: boolean;
  error: string | null;
  equippingSpecialtyId: number | null;
  unequippingSpecialtyId: number | null;
  swappingSpecialtyId: number | null;
  successMessage: string | null;
  mutationError: string | null;
  equipSpecialty: (gameClassSpecialId: number) => Promise<void>;
  unequipSpecialty: (classSpecialEquippedId: number) => Promise<void>;
  swapSpecialty: (
    gameClassSpecialId: number,
    classSpecialEquippedId: number
  ) => Promise<void>;
}
