import React, { ChangeEvent, FormEvent, ReactNode, useState } from 'react';

import GameMapImportSidePeekProps from './types/game-map-import-side-peek-props';
import { useImportGameMaps } from '../../api/hooks/use-import-game-maps';
import { GameMapImportCopy } from '../../enums/game-map-import-copy';

import { useCloseSidePeekEmitter } from '../../../../game/components/side-peeks/base/hooks/use-close-side-peek-emitter';

import ApiErrorAlert from 'api-handler/components/api-error-alert';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const GameMapImportSidePeek = ({
  on_imported: onImported,
}: GameMapImportSidePeekProps): ReactNode => {
  const { closeSidePeek } = useCloseSidePeekEmitter();
  const {
    import_game_maps: importGameMaps,
    importing,
    error,
  } = useImportGameMaps();
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const [localError, setLocalError] = useState<string | null>(null);

  const handleFileChange = (event: ChangeEvent<HTMLInputElement>): void => {
    const file = event.target.files?.item(0) ?? null;

    setSelectedFile(file);

    if (file) {
      setLocalError(null);
    }
  };

  const handleSubmit = async (
    event: FormEvent<HTMLFormElement>
  ): Promise<void> => {
    event.preventDefault();

    if (!selectedFile) {
      setLocalError(GameMapImportCopy.MissingFile);

      return;
    }

    const imported = await importGameMaps(selectedFile);

    if (!imported) {
      return;
    }

    onImported();
    closeSidePeek();
  };

  const renderSelectedFile = (): ReactNode => {
    if (!selectedFile) {
      return null;
    }

    return (
      <p className="text-glacier-800 dark:text-glacier-200 mt-2 text-sm">
        {GameMapImportCopy.SelectedFile} {selectedFile.name}
      </p>
    );
  };

  const renderLocalError = (): ReactNode => {
    if (!localError) {
      return null;
    }

    return (
      <p
        id="game-maps-import-error"
        role="alert"
        className="mt-2 text-sm text-rose-700 dark:text-rose-300"
      >
        {localError}
      </p>
    );
  };

  const renderApiError = (): ReactNode => {
    if (!error) {
      return null;
    }

    return <ApiErrorAlert apiError={error.message} />;
  };

  const renderImportLabel = (): string => {
    if (importing) {
      return GameMapImportCopy.Importing;
    }

    return GameMapImportCopy.Import;
  };

  const describedBy = localError
    ? 'game-maps-import-help game-maps-import-error'
    : 'game-maps-import-help';

  return (
    <form onSubmit={handleSubmit} className="space-y-5" aria-busy={importing}>
      {renderApiError()}

      <div>
        <label
          htmlFor="game-maps-import-file"
          className="text-glacier-900 dark:text-glacier-100 mb-2 block text-sm font-semibold"
        >
          {GameMapImportCopy.FileLabel}
        </label>
        <input
          id="game-maps-import-file"
          type="file"
          accept=".xlsx,.xls"
          disabled={importing}
          aria-describedby={describedBy}
          aria-invalid={!!localError}
          onChange={handleFileChange}
          className="border-glacier-300 text-glacier-900 file:bg-glacier-100 file:text-glacier-800 hover:file:bg-glacier-200 focus-visible:ring-glacier-400 dark:border-glacier-700 dark:bg-glacier-950 dark:text-glacier-100 dark:file:bg-glacier-800 dark:file:text-glacier-100 dark:hover:file:bg-glacier-700 block w-full rounded-md border text-sm file:mr-4 file:border-0 file:px-4 file:py-2 focus:outline-none focus-visible:ring-2 disabled:cursor-not-allowed disabled:opacity-60"
        />
        <p
          id="game-maps-import-help"
          className="text-glacier-700 dark:text-glacier-300 mt-2 text-sm"
        >
          {GameMapImportCopy.AcceptedFormats}
        </p>
        {renderSelectedFile()}
        {renderLocalError()}
      </div>

      <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <Button
          label={GameMapImportCopy.Cancel}
          variant={ButtonVariant.PRIMARY}
          on_click={closeSidePeek}
          disabled={importing}
        />
        <Button
          type="submit"
          label={renderImportLabel()}
          variant={ButtonVariant.SUCCESS}
          disabled={importing}
          aria_busy={importing}
        />
      </div>
    </form>
  );
};

export default GameMapImportSidePeek;
