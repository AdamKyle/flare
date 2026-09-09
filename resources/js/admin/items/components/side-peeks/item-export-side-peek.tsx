import React, { ReactNode } from 'react';

import AdminAnchorButton from '../../../shared/components/admin-anchor-button';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

interface ExportOption {
  profile: string;
  label: string;
}

const EXPORT_OPTIONS: ExportOption[] = [
  { profile: 'weapons', label: 'Export Weapons Only' },
  { profile: 'armour', label: 'Export Armour Only' },
  { profile: 'rings', label: 'Export Rings Only' },
  { profile: 'spells', label: 'Export Spells Only' },
  { profile: 'quest', label: 'Export Quest Items Only' },
  { profile: 'alchemy', label: 'Export Alchemy Items Only' },
  { profile: 'trinket', label: 'Export Trinkets Only' },
  { profile: 'artifact', label: 'Export Artifacts Only' },
  { profile: 'specialty-shops', label: 'Export Specialty Shops Only' },
];

const ItemExportSidePeek = (): ReactNode => (
  <div className="flex flex-col gap-3">
    <p className="text-glacier-700 dark:text-glacier-300 text-sm">
      Choose what type of items you want to export
    </p>

    {EXPORT_OPTIONS.map((option) => (
      <AdminAnchorButton
        key={option.profile}
        href={`/admin/items/export?profile=${option.profile}`}
        label={option.label}
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full text-center"
      />
    ))}
  </div>
);

export default ItemExportSidePeek;
