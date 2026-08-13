import { ConjureType } from '../enums/conjure-type';

export default interface ConjureCelestialRequestDefinition {
  monster_id: number;
  type: ConjureType;
}
