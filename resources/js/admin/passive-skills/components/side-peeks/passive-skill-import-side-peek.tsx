import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { FormEvent, ReactNode, useState } from 'react';

import PassiveSkillImportSidePeekProps from './types/passive-skill-import-side-peek-props';
import { useCloseSidePeekEmitter } from '../../../../game/components/side-peeks/base/hooks/use-close-side-peek-emitter';
import AdminExcelFileDropzone from '../../../shared/components/admin-excel-file-dropzone';
import { useImportPassiveSkills } from '../../api/hooks/use-import-passive-skills';
import { PassiveSkillImportCopy } from '../../enums/passive-skill-import-copy';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const PassiveSkillImportSidePeek = ({
  on_imported: onImported,
}: PassiveSkillImportSidePeekProps): ReactNode => {
  const { closeSidePeek } = useCloseSidePeekEmitter();
  const {
    import_passive_skills: importPassiveSkills,
    importing,
    error,
  } = useImportPassiveSkills();
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
      setLocalError(PassiveSkillImportCopy.MissingFile);

      return;
    }

    const imported = await importPassiveSkills(selectedFile);

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
    ? PassiveSkillImportCopy.Importing
    : PassiveSkillImportCopy.Import;

  return (
    <form onSubmit={handleSubmit} className="space-y-5" aria-busy={importing}>
      {renderApiError()}

      <AdminExcelFileDropzone
        id="passive-skills-import"
        label={PassiveSkillImportCopy.FileLabel}
        disabled={importing}
        error={localError}
        on_file_selected={handleFileSelected}
      />

      <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <Button
          label={PassiveSkillImportCopy.Cancel}
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

export default PassiveSkillImportSidePeek;
