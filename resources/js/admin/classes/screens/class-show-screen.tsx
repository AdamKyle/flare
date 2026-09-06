import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { Fragment, ReactNode } from 'react';

import AdminBackButton from '../../shared/components/admin-back-button';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';
import { ClassApiMessages } from '../api/enums/class-api-messages';
import { useClassDetail } from '../api/hooks/use-class-detail';
import { coreStatLabel } from '../enums/core-stat';
import { ClassScreens } from '../screen-manager/class-screen-constants';
import { useClassScreenNavigation } from '../screen-manager/class-screen-kit';
import { ClassShowScreenProps } from '../screen-manager/class-screen-props';

import { formatPercent } from 'game-utils/format-number';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Card from 'ui/cards/card';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

interface LabeledValueRow {
  label: string;
  value: number;
}

const ClassShowScreen = ({
  class_id: classId,
}: ClassShowScreenProps): ReactNode => {
  const navigation = useClassScreenNavigation();
  const { game_class: gameClass, loading, error } = useClassDetail(classId);

  const handleBack = (): void => {
    navigation.pop();
  };

  const handleEdit = (): void => {
    navigation.navigateTo(ClassScreens.FORM, { class_id: classId });
  };

  const renderLabeledValueRows = (rows: LabeledValueRow[]): ReactNode => {
    return rows.map((row) => (
      <Fragment key={row.label}>
        <Dt>{row.label}</Dt>
        <Dd>{row.value}</Dd>
      </Fragment>
    ));
  };

  const renderPercentValueRows = (rows: LabeledValueRow[]): ReactNode => {
    return rows.map((row) => (
      <Fragment key={row.label}>
        <Dt>{row.label}</Dt>
        <Dd>{formatPercent(row.value)}</Dd>
      </Fragment>
    ));
  };

  const renderAttributesCard = (): ReactNode => {
    if (!gameClass) {
      return null;
    }

    const attributeRows: LabeledValueRow[] = [
      { label: 'Strength', value: gameClass.attributes.str_mod },
      { label: 'Durability', value: gameClass.attributes.dur_mod },
      { label: 'Dexterity', value: gameClass.attributes.dex_mod },
      { label: 'Charisma', value: gameClass.attributes.chr_mod },
      { label: 'Intelligence', value: gameClass.attributes.int_mod },
      { label: 'Agility', value: gameClass.attributes.agi_mod },
      { label: 'Focus', value: gameClass.attributes.focus_mod },
    ].filter((row) => row.value > 0);

    if (attributeRows.length === 0) {
      return null;
    }

    return (
      <Card>
        <h2 className="text-glacier-900 dark:text-glacier-100 mb-4 text-lg font-semibold">
          Attributes
        </h2>
        <Dl>{renderLabeledValueRows(attributeRows)}</Dl>
      </Card>
    );
  };

  const renderCombatModifiersCard = (): ReactNode => {
    if (!gameClass) {
      return null;
    }

    const combatModifierRows: LabeledValueRow[] = [
      { label: 'Accuracy', value: gameClass.combat_modifiers.accuracy_mod },
      { label: 'Dodge', value: gameClass.combat_modifiers.dodge_mod },
      { label: 'Defense', value: gameClass.combat_modifiers.defense_mod },
      { label: 'Looting', value: gameClass.combat_modifiers.looting_mod },
    ].filter((row) => row.value > 0);

    if (combatModifierRows.length === 0) {
      return null;
    }

    return (
      <Card>
        <h2 className="text-glacier-900 dark:text-glacier-100 mb-4 text-lg font-semibold">
          Combat Modifiers
        </h2>
        <Dl>{renderPercentValueRows(combatModifierRows)}</Dl>
      </Card>
    );
  };

  const renderContent = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error || !gameClass) {
      return (
        <ApiErrorAlert apiError={error?.message ?? ClassApiMessages.Load} />
      );
    }

    return (
      <div className="flex flex-col gap-6">
        <div className="flex justify-start py-2">
          <Button
            label="Edit Class"
            variant={ButtonVariant.PRIMARY}
            additional_css="text-sm px-3 py-1.5"
            on_click={handleEdit}
          />
        </div>

        {gameClass.description && (
          <Card>
            <p className="text-glacier-800 dark:text-glacier-200">
              {gameClass.description}
            </p>
          </Card>
        )}

        <Card>
          <h2 className="text-glacier-900 dark:text-glacier-100 mb-4 text-lg font-semibold">
            Basic
          </h2>
          <Dl>
            <Dt>Damage Stat</Dt>
            <Dd>{coreStatLabel(gameClass.damage_stat)}</Dd>
            <Dt>To Hit Stat</Dt>
            <Dd>{coreStatLabel(gameClass.to_hit_stat)}</Dd>
          </Dl>
        </Card>

        {renderAttributesCard()}

        {renderCombatModifiersCard()}

        {gameClass.unlock_requirements && (
          <Card>
            <h2 className="text-glacier-900 dark:text-glacier-100 mb-4 text-lg font-semibold">
              Unlock Requirements
            </h2>
            <Dl>
              <Dt>Primary Required Class</Dt>
              <Dd>
                {gameClass.unlock_requirements.primary_required_class.name}{' '}
                (Level{' '}
                {gameClass.unlock_requirements.primary_required_class_level})
              </Dd>
              <Dt>Secondary Required Class</Dt>
              <Dd>
                {gameClass.unlock_requirements.secondary_required_class.name}{' '}
                (Level{' '}
                {gameClass.unlock_requirements.secondary_required_class_level})
              </Dd>
            </Dl>
          </Card>
        )}
      </div>
    );
  };

  return (
    <AdminPage
      title={gameClass?.name ?? 'Class'}
      width={AdminPageWidth.Detail}
      header_actions={<AdminBackButton on_click={handleBack} />}
    >
      {renderContent()}
    </AdminPage>
  );
};

export default ClassShowScreen;
