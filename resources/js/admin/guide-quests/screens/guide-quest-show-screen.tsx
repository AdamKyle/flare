import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode } from 'react';

import AdminAnchorButton from '../../shared/components/admin-anchor-button';
import AdminBackButton from '../../shared/components/admin-back-button';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';
import { GuideQuestApiMessages } from '../api/enums/guide-quest-api-messages';
import { useGuideQuestDetail } from '../api/hooks/use-guide-quest-detail';
import { useGuideQuestScreenNavigation } from '../screen-manager/guide-quest-screen-kit';
import { GuideQuestShowScreenProps } from '../screen-manager/guide-quest-screen-props';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Card from 'ui/cards/card';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const GuideQuestShowScreen = ({
  guide_quest_id: guideQuestId,
}: GuideQuestShowScreenProps): ReactNode => {
  const navigation = useGuideQuestScreenNavigation();
  const {
    guide_quest: guideQuest,
    loading,
    error,
  } = useGuideQuestDetail(guideQuestId);

  const renderRow = (
    label: string,
    value: number | string | null
  ): ReactNode => {
    if (value === null || value === '') {
      return null;
    }

    return (
      <React.Fragment key={label}>
        <Dt>{label}</Dt>
        <Dd>{value}</Dd>
      </React.Fragment>
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
        <Card>
          <h3 className="mb-3 text-lg font-semibold text-gray-900 dark:text-gray-100">
            Progression
          </h3>
          <Dl>
            {renderRow('Required Level', guideQuest.required_level)}
            {renderRow('Unlock At Level', guideQuest.unlock_at_level)}
            {renderRow(
              'Required Passive Level',
              guideQuest.required_passive_level
            )}
            {renderRow(
              'Required Class Rank Level',
              guideQuest.required_class_rank_level
            )}
          </Dl>
        </Card>
        <Card>
          <h3 className="mb-3 text-lg font-semibold text-gray-900 dark:text-gray-100">
            Rewards and Currency
          </h3>
          <Dl>
            {renderRow('XP Reward', guideQuest.xp_reward)}
            {renderRow('Gold Reward', guideQuest.gold_reward)}
            {renderRow('Gold Dust Reward', guideQuest.gold_dust_reward)}
            {renderRow('Shards Reward', guideQuest.shards_reward)}
            {renderRow('Required Gold', guideQuest.required_gold)}
            {renderRow('Required Gold Dust', guideQuest.required_gold_dust)}
            {renderRow('Required Shards', guideQuest.required_shards)}
          </Dl>
        </Card>
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
