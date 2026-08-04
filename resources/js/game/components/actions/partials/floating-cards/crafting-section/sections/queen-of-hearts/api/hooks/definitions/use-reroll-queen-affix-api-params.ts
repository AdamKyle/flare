import RerollQueenAffixRequestDefinition from '../../definitions/reroll-queen-affix-request-definition';

export default interface UseRerollQueenAffixApiParams {
  characterId: number;
  request: RerollQueenAffixRequestDefinition | null;
}
