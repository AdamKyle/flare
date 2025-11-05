export const isAcceptableFile = (file: File): boolean => {
  const maxBytes = 250 * 1024 * 1024;
  const validTypes = ['image/png', 'image/jpeg'];

  if (!validTypes.includes(file.type)) {
    return false;
  }

  return file.size <= maxBytes;
};

export const pickFirstValidFile = (files: FileList | null): File | null => {
  if (!files || files.length === 0) {
    return null;
  }

  for (let fileIndex = 0; fileIndex < files.length; fileIndex += 1) {
    const candidate = files.item(fileIndex);

    if (candidate && isAcceptableFile(candidate)) {
      return candidate;
    }
  }

  return null;
};
