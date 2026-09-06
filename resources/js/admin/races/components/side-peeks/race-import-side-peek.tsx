import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { FormEvent, ReactNode, useState } from 'react';

import RaceImportSidePeekProps from './types/race-import-side-peek-props';
import { useCloseSidePeekEmitter } from '../../../../game/components/side-peeks/base/hooks/use-close-side-peek-emitter';
import AdminExcelFileDropzone from '../../../shared/components/admin-excel-file-dropzone';
import { useImportRaces } from '../../api/hooks/use-import-races';
import { RaceImportCopy } from '../../enums/race-import-copy';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const RaceImportSidePeek = ({
  on_imported: onImported,
}: RaceImportSidePeekProps): ReactNode => {
  const { closeSidePeek } = useCloseSidePeekEmitter();
  const { import_races: importRaces, importing, error } = useImportRaces();
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const [localError, setLocalError] = useState<string | null>(null);

  const handleFileSelected = (file: File): void => {
    setSelectedFile(file);
    setLocalError(null);
  };

  const handleSubmit = async (
    event: FormEvent<HTMLFormElement>
  ): Promise<void> => {
    event.preventDefault();

    if (!selectedFile) {
      setLocalError(RaceImportCopy.MissingFile);

      return;
    }

    const imported = await importRaces(selectedFile);

    if (!imported) {
      return;
    }

    onImported();
    closeSidePeek();
  };

  const renderApiError = (): ReactNode => {
    if (!error) {
      return null;
    }

    return <ApiErrorAlert apiError={error.message} />;
  };

  const renderImportLabel = (): string => {
    if (importing) {
      return RaceImportCopy.Importing;
    }

    return RaceImportCopy.Import;
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-5" aria-busy={importing}>
      {renderApiError()}

      <AdminExcelFileDropzone
        id="races-import"
        label={RaceImportCopy.FileLabel}
        disabled={importing}
        error={localError}
        on_file_selected={handleFileSelected}
      />

      <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <Button
          label={RaceImportCopy.Cancel}
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

export default RaceImportSidePeek;
