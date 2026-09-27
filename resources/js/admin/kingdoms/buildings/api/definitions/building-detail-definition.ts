import BuildingFormDefinition from './building-form-definition';
import BuildingRecruitableUnitDefinition from './building-recruitable-unit-definition';
import BuildingRelatedIdentityDefinition from './building-related-identity-definition';

export default interface BuildingDetailDefinition extends BuildingFormDefinition {
  passive_skill: BuildingRelatedIdentityDefinition | null;
  units: BuildingRecruitableUnitDefinition[];
}
