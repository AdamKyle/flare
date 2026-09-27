import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { FormEvent, ReactNode, useState } from 'react';

import SkillImportSidePeekProps from './types/skill-import-side-peek-props';
import { useCloseSidePeekEmitter } from '../../../../game/components/side-peeks/base/hooks/use-close-side-peek-emitter';
import AdminExcelFileDropzone from '../../../shared/components/admin-excel-file-dropzone';
import { useImportSkills } from '../../api/hooks/use-import-skills';
import { SkillImportCopy } from '../../enums/skill-import-copy';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const SkillImportSidePeek = ({
  on_imported: onImported,
}: SkillImportSidePeekProps): ReactNode => {
  const { closeSidePeek } = useCloseSidePeekEmitter();
  const { import_skills: importSkills, importing, error } = useImportSkills();
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
      setLocalError(SkillImportCopy.MissingFile);

      return;
    }

    const imported = await importSkills(selectedFile);

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
    ? SkillImportCopy.Importing
    : SkillImportCopy.Import;

  return (
    <form onSubmit={handleSubmit} className="space-y-5" aria-busy={importing}>
      {renderApiError()}

      <AdminExcelFileDropzone
        id="skills-import"
        label={SkillImportCopy.FileLabel}
        disabled={importing}
        error={localError}
        on_file_selected={handleFileSelected}
      />

      <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <Button
          label={SkillImportCopy.Cancel}
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

export default SkillImportSidePeek;
