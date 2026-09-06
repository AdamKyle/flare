import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { Fragment, ReactNode } from 'react';

import AdminBackButton from '../../shared/components/admin-back-button';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';
import { ClassMasteryApiMessages } from '../api/enums/class-mastery-api-messages';
import { useClassMasteryDetail } from '../api/hooks/use-class-mastery-detail';
import { attackTypeLabel } from '../enums/attack-type';
import { ClassMasteryScreens } from '../screen-manager/class-mastery-screen-constants';
import { useClassMasteryScreenNavigation } from '../screen-manager/class-mastery-screen-kit';
import { ClassMasteryShowScreenProps } from '../screen-manager/class-mastery-screen-props';

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
  value: number | null;
}

const ClassMasteryShowScreen = ({
  class_mastery_id: classMasteryId,
}: ClassMasteryShowScreenProps): ReactNode => {
  const navigation = useClassMasteryScreenNavigation();
  const {
    class_mastery: classMastery,
    loading,
    error,
  } = useClassMasteryDetail(classMasteryId);

  const handleBack = (): void => {
    navigation.pop();
  };

  const handleEdit = (): void => {
    navigation.navigateTo(ClassMasteryScreens.FORM, {
      class_mastery_id: classMasteryId,
    });
  };

  const meaningfulRows = (rows: LabeledValueRow[]): LabeledValueRow[] =>
    rows.filter((row) => row.value !== null && row.value > 0);

  const renderNumberRows = (rows: LabeledValueRow[]): ReactNode =>
    meaningfulRows(rows).map((row) => (
      <Fragment key={row.label}>
        <Dt>{row.label}</Dt>
        <Dd>{row.value}</Dd>
      </Fragment>
    ));

  const renderPercentRows = (rows: LabeledValueRow[]): ReactNode =>
    meaningfulRows(rows).map((row) => (
      <Fragment key={row.label}>
        <Dt>{row.label}</Dt>
        <Dd>{formatPercent(row.value as number)}</Dd>
      </Fragment>
    ));

  const renderAttackCard = (): ReactNode => {
    if (!classMastery || classMastery.type !== 'attack') {
      return null;
    }

    const numberRows: LabeledValueRow[] = [
      {
        label: 'Specialty Damage',
        value: classMastery.attack.specialty_damage,
      },
      {
        label: 'Increase Per Level',
        value: classMastery.attack.increase_specialty_damage_per_level,
      },
    ];
    const percentRows: LabeledValueRow[] = [
      {
        label: 'Specialty Damage Uses Damage Stat Amount',
        value: classMastery.attack.specialty_damage_uses_damage_stat_amount,
      },
    ];
    const attackTypeRequired = classMastery.attack.attack_type_required;
    const hasAttackTypeRequired = Boolean(attackTypeRequired);

    if (
      meaningfulRows(numberRows).length === 0 &&
      meaningfulRows(percentRows).length === 0 &&
      !hasAttackTypeRequired
    ) {
      return null;
    }

    return (
      <Card>
        <h2 className="text-glacier-900 dark:text-glacier-100 mb-4 text-lg font-semibold">
          Attack
        </h2>
        <Dl>
          {renderNumberRows(numberRows)}
          {renderPercentRows(percentRows)}
          {hasAttackTypeRequired && (
            <Fragment>
              <Dt>Attack Type Required</Dt>
              <Dd>{attackTypeLabel(attackTypeRequired as string)}</Dd>
            </Fragment>
          )}
        </Dl>
      </Card>
    );
  };

  const renderModifiersCard = (): ReactNode => {
    if (!classMastery) {
      return null;
    }

    const rows: LabeledValueRow[] = [
      {
        label: 'Base Damage Modifier',
        value: classMastery.modifiers.base_damage_mod,
      },
      { label: 'Base AC Modifier', value: classMastery.modifiers.base_ac_mod },
      {
        label: 'Base Healing Modifier',
        value: classMastery.modifiers.base_healing_mod,
      },
      {
        label: 'Base Spell Damage Modifier',
        value: classMastery.modifiers.base_spell_damage_mod,
      },
      { label: 'Health Modifier', value: classMastery.modifiers.health_mod },
      {
        label: 'Base Damage Stat Increase',
        value: classMastery.modifiers.base_damage_stat_increase,
      },
    ];

    if (meaningfulRows(rows).length === 0) {
      return null;
    }

    return (
      <Card>
        <h2 className="text-glacier-900 dark:text-glacier-100 mb-4 text-lg font-semibold">
          Modifiers
        </h2>
        <Dl>{renderPercentRows(rows)}</Dl>
      </Card>
    );
  };

  const renderEvasionAndReductionsCard = (): ReactNode => {
    if (!classMastery) {
      return null;
    }

    const rows: LabeledValueRow[] = [
      {
        label: 'Spell Evasion',
        value: classMastery.evasion_and_reductions.spell_evasion,
      },
      {
        label: 'Affix Damage Reduction',
        value: classMastery.evasion_and_reductions.affix_damage_reduction,
      },
      {
        label: 'Healing Reduction',
        value: classMastery.evasion_and_reductions.healing_reduction,
      },
      {
        label: 'Skill Reduction',
        value: classMastery.evasion_and_reductions.skill_reduction,
      },
      {
        label: 'Resistance Reduction',
        value: classMastery.evasion_and_reductions.resistance_reduction,
      },
    ];

    if (meaningfulRows(rows).length === 0) {
      return null;
    }

    return (
      <Card>
        <h2 className="text-glacier-900 dark:text-glacier-100 mb-4 text-lg font-semibold">
          Evasion and Reductions
        </h2>
        <Dl>{renderPercentRows(rows)}</Dl>
      </Card>
    );
  };

  const renderContent = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error || !classMastery) {
      return (
        <ApiErrorAlert
          apiError={error?.message ?? ClassMasteryApiMessages.Load}
        />
      );
    }

    const hasRequiredClassRankLevel =
      classMastery.requires_class_rank_level > 0;

    return (
      <div className="flex flex-col gap-6">
        <div className="flex justify-start py-2">
          <Button
            label="Edit Class Mastery"
            variant={ButtonVariant.PRIMARY}
            additional_css="text-sm px-3 py-1.5"
            on_click={handleEdit}
          />
        </div>

        <Card>
          <Dl>
            <Dt>Class</Dt>
            <Dd>{classMastery.game_class.name}</Dd>
            <Dt>Type</Dt>
            <Dd>{classMastery.type === 'attack' ? 'Attack' : 'Passive'}</Dd>
            {hasRequiredClassRankLevel && (
              <Fragment>
                <Dt>Requires Class Rank Level</Dt>
                <Dd>{classMastery.requires_class_rank_level}</Dd>
              </Fragment>
            )}
          </Dl>
          {classMastery.description && (
            <p className="text-glacier-800 dark:text-glacier-200 mt-4">
              {classMastery.description}
            </p>
          )}
        </Card>

        {renderAttackCard()}

        {renderModifiersCard()}

        {renderEvasionAndReductionsCard()}
      </div>
    );
  };

  return (
    <AdminPage
      title={classMastery?.name ?? 'Class Mastery'}
      width={AdminPageWidth.Detail}
      header_actions={<AdminBackButton on_click={handleBack} />}
    >
      {renderContent()}
    </AdminPage>
  );
};

export default ClassMasteryShowScreen;
