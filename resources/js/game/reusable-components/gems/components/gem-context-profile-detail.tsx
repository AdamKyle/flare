import React, { Fragment, ReactNode } from 'react';

import RolledGemSourceDetail from './rolled-gem-source-detail';
import GemWorldSourceDefinition from '../api/definitions/gem-world-source-definition';
import GemContextProfileDetailProps from '../types/gem-context-profile-detail-props';
import { resolveRolledGemDisplayGroups } from '../utils/resolve-rolled-gem-display-groups';

import Separator from 'ui/separator/separator';

const GemContextProfileDetail = ({
  context,
  description,
}: GemContextProfileDetailProps): ReactNode => {
  const renderSource = (
    source: GemWorldSourceDefinition,
    index: number
  ): ReactNode => (
    <Fragment key={`${source.type}-${source.profile_id}`}>
      {index > 0 && <Separator />}
      <RolledGemSourceDetail
        source={source}
        display_groups={resolveRolledGemDisplayGroups(source)}
      />
    </Fragment>
  );

  return (
    <div className="flex h-full flex-col gap-4 overflow-y-auto px-4 py-4 sm:px-5">
      <p className="text-sm text-gray-600 dark:text-gray-400">{description}</p>
      {context.sources.map(renderSource)}
    </div>
  );
};

export default GemContextProfileDetail;
