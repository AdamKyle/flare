import { useEffect, useState } from 'react';

import UseLogFilesDefinition from './definitions/use-log-files-definition';
import { LogsDashboardMessages } from '../api/enums/logs-dashboard-messages';
import LogFileInfoDefinition from '../api/definitions/log-file-info-definition';

export default function useLogFiles(
  fetch_log_files: () => Promise<LogFileInfoDefinition[]>
): UseLogFilesDefinition {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [files, setFiles] = useState<LogFileInfoDefinition[]>([]);
  const [selectedFile, setSelectedFile] = useState('');

  useEffect(() => {
    fetch_log_files()
      .then((fetchedFiles) => {
        setFiles(fetchedFiles);

        const first = fetchedFiles.find((file) => file.exists);

        if (first) {
          setSelectedFile((currentFile) => currentFile || first.key);
        }
      })
      .catch(() => setError(LogsDashboardMessages.LoadFiles))
      .finally(() => setLoading(false));
  }, [fetch_log_files]);

  return {
    loading,
    error,
    files,
    selected_file: selectedFile,
    set_selected_file: setSelectedFile,
  };
}
