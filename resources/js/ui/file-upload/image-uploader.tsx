import { AnimatePresence, motion } from 'framer-motion';
import React, { useMemo, useState, useEffect } from 'react';

import ImageUploaderProps from 'ui/file-upload/types/image-uploader-props';
import { pickFirstValidFile } from 'ui/file-upload/utils/file-upload-utils';

const ImageUploader = ({
  onFileChange,
  onDelete,
  initialImageUrl: initialImageUrlProp = null,
  className,
  deletable = true,
}: ImageUploaderProps) => {
  const [initialImageUrl, setInitialImageUrl] = useState(initialImageUrlProp);
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const [objectUrl, setObjectUrl] = useState<string | null>(null);
  const [isHovering, setIsHovering] = useState(false);
  const [isDraggingOver, setIsDraggingOver] = useState(false);

  const currentImageUrl = useMemo(() => {
    if (objectUrl) {
      return objectUrl;
    }

    if (initialImageUrl) {
      return initialImageUrl;
    }

    return null;
  }, [objectUrl, initialImageUrl]);

  useEffect(() => {
    if (!selectedFile) {
      return;
    }

    const url = URL.createObjectURL(selectedFile);
    setObjectUrl(url);

    return () => {
      URL.revokeObjectURL(url);
    };
  }, [selectedFile]);

  useEffect(() => {
    setInitialImageUrl(initialImageUrlProp ?? null);
  }, [initialImageUrlProp]);

  const showOverlay = useMemo(() => {
    if (!currentImageUrl) {
      return true;
    }

    if (isDraggingOver) {
      return true;
    }

    if (isHovering) {
      return true;
    }

    return false;
  }, [currentImageUrl, isDraggingOver, isHovering]);

  const handleInputChange = (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = pickFirstValidFile(event.target.files);

    if (!file) {
      return;
    }

    setSelectedFile(file);
    setInitialImageUrl(null);

    if (onFileChange) {
      onFileChange(file);
    }
  };

  const handleDrop = (event: React.DragEvent<HTMLDivElement>) => {
    event.preventDefault();
    event.stopPropagation();

    setIsDraggingOver(false);

    const file = pickFirstValidFile(event.dataTransfer.files);

    if (!file) {
      return;
    }

    setSelectedFile(file);
    setInitialImageUrl(null);

    if (onFileChange) {
      onFileChange(file);
    }
  };

  const handleDragOver = (event: React.DragEvent<HTMLDivElement>) => {
    event.preventDefault();
    event.stopPropagation();
  };

  const handleDragEnter = (event: React.DragEvent<HTMLDivElement>) => {
    event.preventDefault();
    event.stopPropagation();

    setIsDraggingOver(true);
  };

  const handleDragLeave = (event: React.DragEvent<HTMLDivElement>) => {
    event.preventDefault();
    event.stopPropagation();

    setIsDraggingOver(false);
  };

  const handleDelete = () => {
    setSelectedFile(null);
    setObjectUrl(null);
    setInitialImageUrl(null);

    if (onFileChange) {
      onFileChange(null);
    }

    if (onDelete) {
      onDelete();
    }
  };

  const handleMouseEnter = () => {
    setIsHovering(true);
  };

  const handleMouseLeave = () => {
    setIsHovering(false);
  };

  const renderImage = () => {
    if (!currentImageUrl) {
      return null;
    }

    return (
      <AnimatePresence>
        <motion.img
          key="uploaded-image"
          src={currentImageUrl}
          alt=""
          initial={{ opacity: 0 }}
          animate={{ opacity: showOverlay ? 0 : 1 }}
          exit={{ opacity: 0 }}
          transition={{ duration: 0.25 }}
          className="border-mango-tango-200 dark:border-mango-tango-700 absolute inset-0 h-full w-full rounded-xl border object-cover"
          data-testid="file-upload-image"
        />
      </AnimatePresence>
    );
  };

  const renderOverlay = () => {
    if (!showOverlay) {
      return null;
    }

    return (
      <AnimatePresence>
        <motion.div
          key="upload-overlay"
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          transition={{ duration: 0.25 }}
          className="absolute inset-0 flex h-full w-full items-center justify-center"
          data-testid="file-upload-overlay"
        >
          <label
            htmlFor="file-upload-input"
            className="cursor-pointer rounded-lg px-6 py-4 text-center"
          >
            <div className="text-mango-tango-700 dark:text-mango-tango-200 text-sm">
              Drag & drop your image, or{' '}
              <span className="text-mango-tango-500 underline">
                Click here to upload
              </span>
            </div>

            <div className="text-mango-tango-600 dark:text-mango-tango-300 mt-1 text-xs">
              PNG or JPG up to 250MB
            </div>

            <input
              id="file-upload-input"
              name="file-upload-input"
              type="file"
              accept="image/png,image/jpeg"
              className="sr-only"
              onChange={handleInputChange}
              data-testid="file-upload-input"
            />
          </label>
        </motion.div>
      </AnimatePresence>
    );
  };

  const renderDelete = () => {
    if (!deletable) {
      return null;
    }

    if (!currentImageUrl) {
      return null;
    }

    return (
      <button
        type="button"
        onClick={handleDelete}
        className="text-mango-tango-500 mt-2 underline"
        data-testid="file-upload-delete"
      >
        Delete image
      </button>
    );
  };

  return (
    <div
      className={`container w-full ${className || ''}`}
      data-testid="file-upload"
    >
      <div
        className="relative h-full w-full"
        onDragEnter={handleDragEnter}
        onDragOver={handleDragOver}
        onDragLeave={handleDragLeave}
        onDrop={handleDrop}
        onMouseEnter={handleMouseEnter}
        onMouseLeave={handleMouseLeave}
      >
        <div className="border-mango-tango-500 h-full w-full rounded-2xl border-4 border-dashed p-1">
          <div className="border-mango-tango-500 bg-mango-tango-100 relative h-full w-full overflow-hidden rounded-xl border dark:bg-gray-800">
            {renderImage()}
            {renderOverlay()}
          </div>
        </div>
      </div>

      {renderDelete()}
    </div>
  );
};

export default ImageUploader;
