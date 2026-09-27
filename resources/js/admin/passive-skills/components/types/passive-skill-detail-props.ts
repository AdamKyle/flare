import PassiveSkillDetailDefinition from '../../api/definitions/passive-skill-detail-definition';

export default interface PassiveSkillDetailProps {
  passive_skill: PassiveSkillDetailDefinition;
  on_open_passive_skill?: (passiveSkillId: number) => void;
}
