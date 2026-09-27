import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode } from 'react';

import AdminAnchorButton from '../../shared/components/admin-anchor-button';
import AdminBackButton from '../../shared/components/admin-back-button';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';
import { GuideQuestApiMessages } from '../api/enums/guide-quest-api-messages';
import { useGuideQuestDetail } from '../api/hooks/use-guide-quest-detail';
import GuideQuestContentSection from '../components/guide-quest-content-section';
import { useGuideQuestScreenNavigation } from '../screen-manager/guide-quest-screen-kit';
import { GuideQuestShowScreenProps } from '../screen-manager/guide-quest-screen-props';
import GuideQuestShowSectionDefinition, {
  GuideQuestShowRowDefinition,
  GuideQuestShowRowValue,
} from '../types/guide-quest-show-section-definition';
import { buildGuideQuestShowSections } from '../utils/build-guide-quest-show-sections';

import { formatNumberWithCommas } from 'game-utils/format-number';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Card from 'ui/cards/card';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const isHiddenValue = (value: GuideQuestShowRowValue): boolean => {
  if (Array.isArray(value)) {
    return value.length === 0;
  }

  return value === null || value === '' || value === 0 || value === false;
};

const formatRowValue = (value: GuideQuestShowRowValue): string => {
  if (Array.isArray(value)) {
    return value.join(', ');
  }

  if (typeof value === 'number') {
    return formatNumberWithCommas(value);
  }

  if (value === true) {
    return 'Yes';
  }

  return String(value);
};

const GuideQuestShowScreen = ({
  guide_quest_id: guideQuestId,
}: GuideQuestShowScreenProps): ReactNode => {
  const navigation = useGuideQuestScreenNavigation();
  const {
    guide_quest: guideQuest,
    loading,
    error,
  } = useGuideQuestDetail(guideQuestId);

  const renderRow = (row: GuideQuestShowRowDefinition): ReactNode => {
    if (isHiddenValue(row.value)) {
      return null;
    }

    return (
      <React.Fragment key={row.label}>
        <Dt>{row.label}</Dt>
        <Dd>{formatRowValue(row.value)}</Dd>
      </React.Fragment>
    );
  };

  const renderSection = (
    section: GuideQuestShowSectionDefinition
  ): ReactNode => {
    const hasVisibleRows = section.rows.some(
      (row) => !isHiddenValue(row.value)
    );

    if (!hasVisibleRows) {
      return null;
    }

    return (
      <Card key={section.title}>
        <h3 className="mb-3 text-lg font-semibold text-gray-900 dark:text-gray-100">
          {section.title}
        </h3>
        <Dl>{section.rows.map(renderRow)}</Dl>
      </Card>
    );
  };

  const renderContent = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error || !guideQuest) {
      return (
        <ApiErrorAlert
          apiError={error?.message ?? GuideQuestApiMessages.LOAD_DETAIL}
        />
      );
    }

    return (
      <div className="space-y-6">
        <div className="flex justify-start">
          <AdminAnchorButton
            href={`/admin/guide-quests/edit/${guideQuest.id}`}
            label="Edit Guide Quest"
            variant={ButtonVariant.PRIMARY}
          />
        </div>
        {buildGuideQuestShowSections(guideQuest).map(renderSection)}
        <GuideQuestContentSection
          title="Intro"
          blocks={guideQuest.intro_text}
        />
        <GuideQuestContentSection
          title="Desktop Instructions"
          blocks={guideQuest.desktop_instructions}
        />
        <GuideQuestContentSection
          title="Mobile Instructions"
          blocks={guideQuest.mobile_instructions}
        />
      </div>
    );
  };

  return (
    <AdminPage
      title={guideQuest?.name ?? 'Guide Quest'}
      width={AdminPageWidth.Detail}
      header_actions={<AdminBackButton on_click={() => navigation.pop()} />}
    >
      {renderContent()}
    </AdminPage>
  );
};

export default GuideQuestShowScreen;
