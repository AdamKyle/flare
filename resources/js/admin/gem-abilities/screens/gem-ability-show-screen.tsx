import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode } from 'react';

import AdminBackButton from '../../shared/components/admin-back-button';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';
import { GemAbilityApiMessages } from '../api/enums/gem-ability-api-messages';
import { useGemAbilityDetail } from '../api/hooks/use-gem-ability-detail';
import GemAbilityDetail from '../components/gem-ability-detail';
import { GemAbilityScreens } from '../screen-manager/gem-ability-screen-constants';
import { useGemAbilityScreenNavigation } from '../screen-manager/gem-ability-screen-kit';
import { GemAbilityShowScreenProps } from '../screen-manager/gem-ability-screen-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const GemAbilityShowScreen = ({
  gem_ability_id: gemAbilityId,
}: GemAbilityShowScreenProps): ReactNode => {
  const navigation = useGemAbilityScreenNavigation();
  const {
    gem_ability: gemAbility,
    loading,
    error,
  } = useGemAbilityDetail(gemAbilityId);

  const handleBack = (): void => {
    navigation.pop();
  };

  const handleEdit = (): void => {
    navigation.navigateTo(GemAbilityScreens.FORM, {
      gem_ability_id: gemAbilityId,
    });
  };

  const renderContent = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error || !gemAbility) {
      return (
        <ApiErrorAlert
          apiError={error?.message ?? GemAbilityApiMessages.Load}
        />
      );
    }

    return (
      <div className="flex flex-col gap-6">
        <div className="flex justify-start py-2">
          <Button
            label="Edit Gem Ability"
            variant={ButtonVariant.PRIMARY}
            additional_css="text-sm px-3 py-1.5"
            on_click={handleEdit}
          />
        </div>
        <GemAbilityDetail gem_ability={gemAbility} />
      </div>
    );
  };

  return (
    <AdminPage
      title={gemAbility?.name ?? 'Gem Ability'}
      width={AdminPageWidth.Detail}
      header_actions={<AdminBackButton on_click={handleBack} />}
    >
      {renderContent()}
    </AdminPage>
  );
};

export default GemAbilityShowScreen;
