import { PassiveSkillScreens } from './passive-skill-screen-constants';

export type PassiveSkillListScreenProps = Record<string, never>;

export interface PassiveSkillShowScreenProps {
  passive_skill_id: number;
}

export interface PassiveSkillFormScreenProps {
  passive_skill_id: number | null;
}

export interface PassiveSkillScreenPropsMap {
  [PassiveSkillScreens.LIST]: PassiveSkillListScreenProps;
  [PassiveSkillScreens.SHOW]: PassiveSkillShowScreenProps;
  [PassiveSkillScreens.FORM]: PassiveSkillFormScreenProps;
}

export type PassiveSkillScreenName = keyof PassiveSkillScreenPropsMap;
