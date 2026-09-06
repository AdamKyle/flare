import React, { ReactNode } from 'react';

import RaceFieldsProps from './types/race-fields-props';

import ImageUploader from 'ui/file-upload/image-uploader';
import FieldWrapper from 'ui/forms/field-wrapper';
import TextAreaField from 'ui/forms/text-area-field';
import Input from 'ui/input/input';

const RaceFields = ({
  state,
  errors,
  on_change: onChange,
}: RaceFieldsProps): ReactNode => {
  return (
    <div className="space-y-4">
      <FieldWrapper id="race-name" label="Name" required error={errors.name}>
        {(describedBy) => (
          <Input
            id="race-name"
            value={state.name}
            on_change={(value) => onChange('name', value)}
            described_by={describedBy}
            invalid={!!errors.name}
            required
          />
        )}
      </FieldWrapper>

      <TextAreaField
        id="race-description"
        label="Description"
        value={state.description}
        on_change={(value) => onChange('description', value)}
        error={errors.description}
      />

      <div>
        <span className="text-glacier-900 dark:text-glacier-100 mb-1 block text-sm font-medium">
          Race Image
        </span>
        <div className="h-64 w-full">
          <ImageUploader
            initialImageUrl={state.current_image_url}
            onFileChange={(file) => onChange('image', file)}
            className="h-full"
          />
        </div>
        {errors.image && (
          <p
            role="alert"
            className="mt-1 text-xs text-rose-600 dark:text-rose-400"
          >
            {errors.image}
          </p>
        )}
      </div>
    </div>
  );
};

export default RaceFields;
