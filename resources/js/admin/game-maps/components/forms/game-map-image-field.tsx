import React, { ReactNode } from 'react';

import { GameMapImageReplacementCopy } from '../../enums/game-map-image-replacement-copy';
import GameMapImageFieldProps from '../../types/game-map-image-field-props';

import ImageUploader from 'ui/file-upload/image-uploader';
import CheckboxField from 'ui/forms/checkbox-field';

const GameMapImageField = ({
  current_image_url: currentImageUrl,
  error,
  acknowledgement_error: acknowledgementError,
  replacement_image_acknowledged: replacementImageAcknowledged,
  show_replacement_warning: showReplacementWarning,
  on_change: onChange,
  on_acknowledgement_change: onAcknowledgementChange,
}: GameMapImageFieldProps): ReactNode => {
  const renderError = (): ReactNode => {
    if (!error) {
      return null;
    }

    return (
      <p role="alert" className="mt-2 text-xs text-rose-600 dark:text-rose-400">
        {error}
      </p>
    );
  };

  const renderReplacementWarning = (): ReactNode => {
    if (!showReplacementWarning) {
      return null;
    }

    return (
      <div
        role="alert"
        className="border-mango-tango-300 bg-mango-tango-50 text-glacier-900 dark:border-mango-tango-700 dark:bg-glacier-950 dark:text-glacier-100 mt-4 rounded-md border p-4"
      >
        <p className="mb-3 text-sm font-medium">
          {GameMapImageReplacementCopy.Warning}
        </p>
        <CheckboxField
          id="game-map-image-replacement-acknowledgement"
          label={GameMapImageReplacementCopy.Acknowledgement}
          checked={replacementImageAcknowledged}
          on_change={onAcknowledgementChange}
          error={acknowledgementError}
        />
      </div>
    );
  };

  return (
    <div className="mb-4">
      <span className="text-glacier-900 dark:text-glacier-100 mb-1 block text-sm font-medium">
        Map Image
      </span>
      <div className="h-64 w-full">
        <ImageUploader
          initialImageUrl={currentImageUrl}
          onFileChange={onChange}
          deletable={showReplacementWarning}
          restoreInitialImageOnDelete
          deleteLabel={GameMapImageReplacementCopy.ClearReplacement}
          className="h-full"
        />
      </div>
      {renderError()}
      {renderReplacementWarning()}
    </div>
  );
};

export default GameMapImageField;
