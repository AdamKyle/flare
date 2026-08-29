import React, { ReactNode, useState } from 'react';

import LocationFormScreen from './location-form-screen';
import { LocationScreens } from '../screen-manager/location-screen-constants';
import { useLocationScreenNavigation } from '../screen-manager/location-screen-kit';
import { LocationFormScreenEntryProps } from '../screen-manager/location-screen-props';

import { useGameMaps } from '../../game-maps/api/hooks/use-game-maps';

import AdminBackButton from '../../shared/components/admin-back-button';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import FieldWrapper from 'ui/forms/field-wrapper';

const LocationFormEntryScreen = ({
  game_map_id: initialGameMapId,
}: LocationFormScreenEntryProps): ReactNode => {
  const navigation = useLocationScreenNavigation();
  const [selectedGameMapId, setSelectedGameMapId] = useState<number | null>(
    initialGameMapId
  );
  const {
    data: gameMaps,
    loading,
    search_text: searchText,
    set_search_text: setSearchText,
  } = useGameMaps();

  const handleCancel = (): void => {
    navigation.pop();
  };

  const handleSaved = (): void => {
    navigation.pop();
  };

  if (selectedGameMapId !== null) {
    return (
      <LocationFormScreen
        game_map_id={selectedGameMapId}
        location_id={null}
        initial_x={null}
        initial_y={null}
        on_saved={handleSaved}
        on_cancel={handleCancel}
      />
    );
  }

  const mapItems: DropdownItem[] = gameMaps.map((gameMap) => ({
    label: gameMap.name,
    value: gameMap.id,
  }));

  return (
    <AdminPage
      title="Create Location"
      width={AdminPageWidth.Standard}
      header_actions={<AdminBackButton on_click={handleCancel} />}
    >
      <div className="space-y-4">
        <p className="text-glacier-700 dark:text-glacier-300 text-sm">
          Select the Game Map this Location belongs to.
        </p>
        <FieldWrapper id="location-create-game-map" label="Game Map" required>
          {(describedBy) => (
            <Dropdown
              id="location-create-game-map"
              aria_label="Game Map"
              aria_described_by={describedBy}
              aria_required
              searchable
              search_value={searchText}
              on_search={setSearchText}
              items={mapItems}
              on_select={(item) => setSelectedGameMapId(Number(item.value))}
              selection_placeholder={
                loading ? 'Loading Game Maps…' : 'Select a Game Map'
              }
            />
          )}
        </FieldWrapper>
      </div>
    </AdminPage>
  );
};

export default LocationFormEntryScreen;
