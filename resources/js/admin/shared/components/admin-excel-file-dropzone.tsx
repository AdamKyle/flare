import clsx from 'clsx';
import React, { ChangeEvent, DragEvent, ReactNode, useState } from 'react';

import AdminExcelFileDropzoneProps from '../types/admin-excel-file-dropzone-props';

const ACCEPTED_EXTENSIONS = ['.xlsx', '.xls'];

const hasAcceptedExtension = (file: File): boolean =>
  ACCEPTED_EXTENSIONS.some((extension) =>
    file.name.toLowerCase().endsWith(extension)
  );

/**
 * Shared Admin Excel workbook upload dropzone: a polished drag-and-drop
 * presentation over a hidden native file input, styled after the Game Map
 * image uploader. Owns only file-selection presentation and validation; the
 * owning SidePeek retains the import request, success/error state, and
 * submission flow.
 */
const AdminExcelFileDropzone = ({
  id,
  label,
  disabled,
  error,
  on_file_selected: onFileSelected,
}: AdminExcelFileDropzoneProps): ReactNode => {
  const [selectedFileName, setSelectedFileName] = useState<string | null>(null);
  const [rejectionError, setRejectionError] = useState<string | null>(null);
  const [isDraggingOver, setIsDraggingOver] = useState(false);

  const inputId = `${id}-file`;
  const helpId = `${id}-help`;
  const errorId = `${id}-error`;
  const displayedError = rejectionError ?? error ?? null;
  const describedBy = displayedError ? `${helpId} ${errorId}` : helpId;

  const acceptFile = (file: File): void => {
    if (!hasAcceptedExtension(file)) {
      setRejectionError('Select a .xlsx or .xls Excel workbook file.');

      return;
    }

    setRejectionError(null);
    setSelectedFileName(file.name);
    onFileSelected(file);
  };

  const handleInputChange = (event: ChangeEvent<HTMLInputElement>): void => {
    const file = event.target.files?.item(0) ?? null;

    if (file) {
      acceptFile(file);
    }

    event.target.value = '';
  };

  const handleDrop = (event: DragEvent<HTMLLabelElement>): void => {
    event.preventDefault();
    event.stopPropagation();
    setIsDraggingOver(false);

    if (disabled) {
      return;
    }

    const file = event.dataTransfer.files.item(0);

    if (file) {
      acceptFile(file);
    }
  };

  const handleDragOver = (event: DragEvent<HTMLLabelElement>): void => {
    event.preventDefault();
    event.stopPropagation();
  };

  const handleDragEnter = (event: DragEvent<HTMLLabelElement>): void => {
    event.preventDefault();
    event.stopPropagation();

    if (!disabled) {
      setIsDraggingOver(true);
    }
  };

  const handleDragLeave = (event: DragEvent<HTMLLabelElement>): void => {
    event.preventDefault();
    event.stopPropagation();
    setIsDraggingOver(false);
  };

  const renderSelectedFile = (): ReactNode => {
    if (!selectedFileName) {
      return null;
    }

    return (
      <p className="text-glacier-800 dark:text-glacier-200 mt-2 text-sm">
        Selected file: {selectedFileName}
      </p>
    );
  };

  const renderError = (): ReactNode => {
    if (!displayedError) {
      return null;
    }

    return (
      <p
        id={errorId}
        role="alert"
        className="mt-2 text-sm text-rose-700 dark:text-rose-300"
      >
        {displayedError}
      </p>
    );
  };

  return (
    <div>
      <span
        id={`${id}-label`}
        className="text-glacier-900 dark:text-glacier-100 mb-2 block text-sm font-semibold"
      >
        {label}
      </span>
      <label
        htmlFor={inputId}
        onDragEnter={handleDragEnter}
        onDragOver={handleDragOver}
        onDragLeave={handleDragLeave}
        onDrop={handleDrop}
        className={clsx(
          'focus-within:ring-glacier-400 flex flex-col items-center justify-center rounded-xl border-4 border-dashed p-6 text-center transition-colors focus-within:ring-2',
          disabled && 'cursor-not-allowed opacity-60',
          !disabled && 'cursor-pointer',
          isDraggingOver
            ? 'border-glacier-500 bg-glacier-100 dark:bg-glacier-800'
            : 'border-glacier-300 bg-glacier-50 dark:border-glacier-700 dark:bg-glacier-950'
        )}
      >
        <span className="text-glacier-800 dark:text-glacier-200 text-sm">
          Drag &amp; drop your Excel workbook, or{' '}
          <span className="text-glacier-600 dark:text-glacier-300 underline">
            click to upload
          </span>
        </span>
        <span
          id={helpId}
          className="text-glacier-600 dark:text-glacier-400 mt-1 text-xs"
        >
          Accepted formats: .xlsx, .xls
        </span>
        <input
          id={inputId}
          name={inputId}
          type="file"
          accept=".xlsx,.xls"
          disabled={disabled}
          aria-describedby={describedBy}
          aria-invalid={!!displayedError}
          aria-labelledby={`${id}-label`}
          onChange={handleInputChange}
          className="sr-only"
        />
      </label>
      {renderSelectedFile()}
      {renderError()}
    </div>
  );
};

export default AdminExcelFileDropzone;
