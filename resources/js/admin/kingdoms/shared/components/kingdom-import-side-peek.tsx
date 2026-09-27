import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { FormEvent, ReactNode, useState } from 'react';

import KingdomImportSidePeekProps from './types/kingdom-import-side-peek-props';
import { useCloseSidePeekEmitter } from '../../../../game/components/side-peeks/base/hooks/use-close-side-peek-emitter';
import AdminExcelFileDropzone from '../../../shared/components/admin-excel-file-dropzone';
import { useImportKingdomData } from '../api/hooks/use-import-kingdom-data';
import { KingdomImportCopy } from '../enums/kingdom-import-copy';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const KingdomImportSidePeek = ({
  on_imported: onImported,
}: KingdomImportSidePeekProps): ReactNode => {
  const { closeSidePeek } = useCloseSidePeekEmitter();
  const {
    import_kingdom_data: importKingdomData,
    importing,
    error,
  } = useImportKingdomData();
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
      setLocalError(KingdomImportCopy.MissingFile);

      return;
    }

    const imported = await importKingdomData(selectedFile);

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

  const importLabel = importing
    ? KingdomImportCopy.Importing
    : KingdomImportCopy.Import;

  return (
    <form onSubmit={handleSubmit} className="space-y-5" aria-busy={importing}>
      {renderApiError()}

      <AdminExcelFileDropzone
        id="kingdom-import"
        label={KingdomImportCopy.FileLabel}
        disabled={importing}
        error={localError}
        on_file_selected={handleFileSelected}
      />

      <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <Button
          label={KingdomImportCopy.Cancel}
          variant={ButtonVariant.PRIMARY}
          on_click={closeSidePeek}
          disabled={importing}
        />
        <Button
          type="submit"
          label={importLabel}
          variant={ButtonVariant.SUCCESS}
          disabled={importing}
          aria_busy={importing}
        />
      </div>
    </form>
  );
};

export default KingdomImportSidePeek;
