import ApiErrorAlert from 'api-handler/components/api-error-alert';
import { AnimatePresence } from 'framer-motion';
import { isEmpty, isNil } from 'lodash';
import React, { useState } from 'react';

import CelestialOptionDefinition from './api/definitions/celestial-option-definition';
import { ConjureType } from './api/enums/conjure-type';
import { useConjureCelestialApi } from './api/hooks/use-conjure-celestial-api';
import { useFetchCelestialOptionsApi } from './api/hooks/use-fetch-celestial-options-api';
import { useFetchCelestialStatsApi } from './api/hooks/use-fetch-celestial-stats-api';
import CelestialDetailsPanel from './partials/celestial-details-panel';
import CelestialDropDown from './partials/celestial-drop-down';
import ConjureConfirmation from './partials/conjure-confirmation';
import ConjureCostSection from './partials/conjure-cost-section';
import SelectedCelestialDetails from './partials/selected-celestial-details';
import ConjureProps from './types/conjure-props';

import { GameDataError } from 'game-data/components/game-data-error';

import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import Separator from 'ui/separator/separator';

const CELESTIAL_LABEL_ID = 'conjure-celestial-label';

const Conjure = ({ character_data }: ConjureProps) => {
  const {
    data: optionsData,
    loading: optionsLoading,
    error: optionsError,
  } = useFetchCelestialOptionsApi({ character_id: character_data.id });

  const {
    data: statsData,
    loading: statsLoading,
    error: statsError,
    fetchCelestialStats,
  } = useFetchCelestialStatsApi({ character_id: character_data.id });

  const {
    conjureCelestial,
    loading: isConjuring,
    error: conjureError,
  } = useConjureCelestialApi({ character_id: character_data.id });

  const [selectedCelestial, setSelectedCelestial] =
    useState<CelestialOptionDefinition | null>(null);
  const [isDetailsOpen, setIsDetailsOpen] = useState(false);
  const [confirmationType, setConfirmationType] = useState<ConjureType | null>(
    null
  );

  const handleSelectCelestial = (celestial: CelestialOptionDefinition) => {
    setSelectedCelestial(celestial);
    void fetchCelestialStats(celestial.id);
  };

  const handleClearCelestial = () => {
    setSelectedCelestial(null);
  };

  const handleViewDetails = () => {
    setIsDetailsOpen(true);
  };

  const handleCloseDetails = () => {
    setIsDetailsOpen(false);
  };

  const handleRequestPrivate = () => {
    setConfirmationType(ConjureType.PRIVATE);
  };

  const handleRequestPublic = () => {
    setConfirmationType(ConjureType.PUBLIC);
  };

  const handleCancelConfirmation = () => {
    setConfirmationType(null);
  };

  const handleConfirmConjure = () => {
    if (!selectedCelestial || !confirmationType) {
      return;
    }

    void conjureCelestial({
      monster_id: selectedCelestial.id,
      type: confirmationType,
    });
  };

  if (optionsLoading) {
    return (
      <div
        className="flex items-center justify-center bg-white p-4 dark:bg-gray-800"
        role="status"
        aria-live="polite"
      >
        <InfiniteLoader />
        <span className="sr-only">Loading conjurable celestials.</span>
      </div>
    );
  }

  if (optionsError) {
    return (
      <div className="bg-white p-4 dark:bg-gray-800">
        <ApiErrorAlert apiError={optionsError.message} />
      </div>
    );
  }

  if (isNil(optionsData)) {
    return (
      <div className="flex items-center justify-center bg-white p-4 dark:bg-gray-800">
        <GameDataError />
      </div>
    );
  }

  const renderEmptyState = () => {
    if (!isEmpty(optionsData.celestial_monsters)) {
      return null;
    }

    return (
      <p className="text-sm text-gray-700 dark:text-gray-300">
        There are no conjurable celestials on this map.
      </p>
    );
  };

  const renderDropdown = () => {
    if (isEmpty(optionsData.celestial_monsters)) {
      return null;
    }

    return (
      <div className="grid gap-2">
        <label
          id={CELESTIAL_LABEL_ID}
          className="text-sm font-semibold text-gray-700 dark:text-gray-300"
        >
          Celestial
        </label>
        <CelestialDropDown
          celestials={optionsData.celestial_monsters}
          on_select={handleSelectCelestial}
          on_clear={handleClearCelestial}
          aria_labelled_by={CELESTIAL_LABEL_ID}
        />
      </div>
    );
  };

  const renderStatsLoading = () => {
    if (!selectedCelestial || !statsLoading) {
      return null;
    }

    return (
      <div role="status" aria-live="polite" className="mt-4">
        <InfiniteLoader />
        <span className="sr-only">Loading celestial details.</span>
      </div>
    );
  };

  const renderStatsError = () => {
    if (!statsError) {
      return null;
    }

    return (
      <div className="mt-4">
        <ApiErrorAlert apiError={statsError.message} />
      </div>
    );
  };

  const renderSelectedDetails = () => {
    if (!selectedCelestial || !statsData) {
      return null;
    }

    return (
      <>
        <SelectedCelestialDetails
          monster={statsData.monster}
          on_view_details={handleViewDetails}
        />

        <ConjureCostSection
          gold_cost={statsData.monster.gold_cost ?? 0}
          gold_dust_cost={statsData.monster.gold_dust_cost ?? 0}
          can_afford={statsData.can_afford}
          on_request_private={handleRequestPrivate}
          on_request_public={handleRequestPublic}
        />
      </>
    );
  };

  const renderConjureError = () => {
    if (!conjureError || confirmationType) {
      return null;
    }

    return (
      <div className="mt-4">
        <ApiErrorAlert apiError={conjureError.message} />
      </div>
    );
  };

  const renderDetailsPanel = () => {
    if (!isDetailsOpen || !statsData) {
      return null;
    }

    return (
      <CelestialDetailsPanel
        monster={statsData.monster}
        on_close={handleCloseDetails}
      />
    );
  };

  const renderConfirmationPanel = () => {
    if (!confirmationType || !selectedCelestial || !statsData) {
      return null;
    }

    return (
      <ConjureConfirmation
        type={confirmationType}
        celestial_name={statsData.monster.name}
        gold_cost={statsData.monster.gold_cost ?? 0}
        gold_dust_cost={statsData.monster.gold_dust_cost ?? 0}
        is_submitting={isConjuring}
        api_error={conjureError ? conjureError.message : null}
        on_confirm={handleConfirmConjure}
        on_cancel={handleCancelConfirmation}
      />
    );
  };

  return (
    <div className="bg-white p-4 text-gray-900 dark:bg-gray-800 dark:text-gray-100">
      {renderEmptyState()}
      {renderDropdown()}

      <Separator />

      {renderStatsLoading()}
      {renderStatsError()}
      {renderSelectedDetails()}
      {renderConjureError()}

      <AnimatePresence mode="wait">{renderDetailsPanel()}</AnimatePresence>
      <AnimatePresence mode="wait">{renderConfirmationPanel()}</AnimatePresence>
    </div>
  );
};

export default Conjure;
