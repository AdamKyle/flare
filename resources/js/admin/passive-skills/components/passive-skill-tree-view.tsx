import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode } from 'react';

import PassiveSkillTreeViewProps from './types/passive-skill-tree-view-props';
import PassiveSkillTree from '../../../game/reusable-components/passive-skill/components/passive-skill-tree';
import { usePassiveSkillTree } from '../api/hooks/use-passive-skill-tree';
import { passiveSkillEffectLabel } from '../enums/passive-skill-effect';

import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const PassiveSkillTreeView = ({
  on_activate: onActivate,
}: PassiveSkillTreeViewProps): ReactNode => {
  const {
    passive_skills: passiveSkills,
    loading,
    error,
  } = usePassiveSkillTree();

  if (loading) {
    return <InfiniteLoader />;
  }

  if (error) {
    return <ApiErrorAlert apiError={error.message} />;
  }

  return (
    <PassiveSkillTree
      passive_skills={passiveSkills}
      effect_label={passiveSkillEffectLabel}
      on_activate={onActivate}
    />
  );
};

export default PassiveSkillTreeView;
