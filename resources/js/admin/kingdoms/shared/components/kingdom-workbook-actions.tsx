import React, { ReactNode } from 'react';

import KingdomWorkbookActionsProps from './types/kingdom-workbook-actions-props';
import AdminAnchorButton from '../../../shared/components/admin-anchor-button';
import { KingdomWebUrls } from '../enums/kingdom-web-urls';
import { useOpenKingdomImportSidePeek } from '../hooks/use-open-kingdom-import-side-peek';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const KingdomWorkbookActions = ({
  on_imported: onImported,
}: KingdomWorkbookActionsProps): ReactNode => {
  const handleImport = useOpenKingdomImportSidePeek(onImported);

  return (
    <div className="mb-4 flex flex-wrap items-center gap-3">
      <Button
        label="Import Kingdom Data"
        variant={ButtonVariant.PRIMARY}
        on_click={handleImport}
      />
      <AdminAnchorButton
        href={KingdomWebUrls.EXPORT}
        label="Export Kingdom Data"
        variant={ButtonVariant.PRIMARY}
      />
    </div>
  );
};

export default KingdomWorkbookActions;
