import { SkillScreens } from './skill-screen-constants';

export type SkillListScreenProps = Record<string, never>;

export interface SkillShowScreenProps {
  skill_id: number;
}

export interface SkillFormScreenProps {
  skill_id: number | null;
}

export interface SkillScreenPropsMap {
  [SkillScreens.LIST]: SkillListScreenProps;
  [SkillScreens.SHOW]: SkillShowScreenProps;
  [SkillScreens.FORM]: SkillFormScreenProps;
}

export type SkillScreenName = keyof SkillScreenPropsMap;
