import UnitFormDefinition from './unit-form-definition';
import UnitRecruitingBuildingDefinition from './unit-recruiting-building-definition';

export default interface UnitDetailDefinition extends UnitFormDefinition {
  recruited_from: UnitRecruitingBuildingDefinition[];
}
