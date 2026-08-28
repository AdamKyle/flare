import React, { ReactNode } from 'react';

import GameMapImageField from './game-map-image-field';
import GameMapBasicFieldsProps from '../../types/game-map-basic-fields-props';

import CheckboxField from 'ui/forms/checkbox-field';
import FieldWrapper from 'ui/forms/field-wrapper';
import Input from 'ui/input/input';
import MarkDownEditor from 'ui/mark-down-editor/mark-down-editor';

const GameMapBasicFields = ({
  state,
  errors,
  current_map_url: currentMapUrl,
  game_map_id: gameMapId,
  on_change: onChange,
}: GameMapBasicFieldsProps): ReactNode => {
  return (
    <div className="space-y-4">
      <FieldWrapper
        id="game-map-name"
        label="Name"
        required
        error={errors.name}
      >
        {(describedBy) => (
          <Input
            id="game-map-name"
            value={state.name}
            on_change={(value) => onChange('name', value)}
            described_by={describedBy}
            invalid={!!errors.name}
            required
          />
        )}
      </FieldWrapper>

      <FieldWrapper
        id="game-map-description"
        label="Description"
        error={errors.description}
      >
        {() => (
          <MarkDownEditor
            id="game-map-description-editor"
            initial_markdown={state.description}
            placeholder="Describe this Game Map…"
            on_value_change={(value) => onChange('description', value)}
          />
        )}
      </FieldWrapper>

      <FieldWrapper
        id="game-map-kingdom-color"
        label="Kingdom Color"
        required
        error={errors.kingdom_color}
      >
        {(describedBy) => (
          <input
            id="game-map-kingdom-color"
            type="color"
            value={state.kingdom_color}
            onChange={(event) => onChange('kingdom_color', event.target.value)}
            aria-describedby={describedBy}
            aria-invalid={!!errors.kingdom_color}
            className="border-glacier-300 dark:border-glacier-700 dark:bg-glacier-900 h-10 w-20 rounded-sm border bg-white p-1"
          />
        )}
      </FieldWrapper>

      <CheckboxField
        id="game-map-default"
        label="Is Default"
        checked={state.default}
        on_change={(value) => onChange('default', value)}
      />

      <GameMapImageField
        current_image_url={currentMapUrl}
        error={errors.map}
        acknowledgement_error={errors.replacement_image_acknowledged}
        replacement_image_acknowledged={state.replacement_image_acknowledged}
        show_replacement_warning={gameMapId !== null && state.map !== null}
        on_change={(file) => onChange('map', file)}
        on_acknowledgement_change={(acknowledged) =>
          onChange('replacement_image_acknowledged', acknowledged)
        }
      />
    </div>
  );
};

export default GameMapBasicFields;
