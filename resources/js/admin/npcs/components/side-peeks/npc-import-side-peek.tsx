import React, { ChangeEvent, FormEvent, ReactNode, useState } from 'react';

import NpcImportSidePeekProps from './types/npc-import-side-peek-props';
import { useImportNpcs } from '../../api/hooks/use-import-npcs';
import { NpcImportCopy } from '../../enums/npc-import-copy';

import { useCloseSidePeekEmitter } from '../../../../game/components/side-peeks/base/hooks/use-close-side-peek-emitter';

import ApiErrorAlert from 'api-handler/components/api-error-alert';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const NpcImportSidePeek = ({
  on_imported: onImported,
}: NpcImportSidePeekProps): ReactNode => {
  const { closeSidePeek } = useCloseSidePeekEmitter();
  const { import_npcs: importNpcs, importing, error } = useImportNpcs();
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
      setLocalError(NpcImportCopy.MissingFile);

      return;
    }

    const imported = await importNpcs(selectedFile);

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
        {NpcImportCopy.SelectedFile} {selectedFile.name}
      </p>
    );
  };

  const renderLocalError = (): ReactNode => {
    if (!localError) {
      return null;
    }

    return (
      <p
        id="npcs-import-error"
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
      return NpcImportCopy.Importing;
    }

    return NpcImportCopy.Import;
  };

  const describedBy = localError
    ? 'npcs-import-help npcs-import-error'
    : 'npcs-import-help';

  return (
    <form onSubmit={handleSubmit} className="space-y-5" aria-busy={importing}>
      {renderApiError()}

      <div>
        <label
          htmlFor="npcs-import-file"
          className="text-glacier-900 dark:text-glacier-100 mb-2 block text-sm font-semibold"
        >
          {NpcImportCopy.FileLabel}
        </label>
        <input
          id="npcs-import-file"
          type="file"
          accept=".xlsx,.xls"
          disabled={importing}
          aria-describedby={describedBy}
          aria-invalid={!!localError}
          onChange={handleFileChange}
          className="border-glacier-300 text-glacier-900 file:bg-glacier-100 file:text-glacier-800 hover:file:bg-glacier-200 focus-visible:ring-glacier-400 dark:border-glacier-700 dark:bg-glacier-950 dark:text-glacier-100 dark:file:bg-glacier-800 dark:file:text-glacier-100 dark:hover:file:bg-glacier-700 block w-full rounded-md border text-sm file:mr-4 file:border-0 file:px-4 file:py-2 focus:outline-none focus-visible:ring-2 disabled:cursor-not-allowed disabled:opacity-60"
        />
        <p
          id="npcs-import-help"
          className="text-glacier-700 dark:text-glacier-300 mt-2 text-sm"
        >
          {NpcImportCopy.AcceptedFormats}
        </p>
        {renderSelectedFile()}
        {renderLocalError()}
      </div>

      <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <Button
          label={NpcImportCopy.Cancel}
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

export default NpcImportSidePeek;
