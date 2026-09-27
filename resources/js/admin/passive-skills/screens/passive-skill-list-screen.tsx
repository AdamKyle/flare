import React, { ReactNode, useState } from 'react';

import AdminAnchorButton from '../../shared/components/admin-anchor-button';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';
import PassiveSkillTreeView from '../components/passive-skill-tree-view';
import { PassiveSkillImportCopy } from '../enums/passive-skill-import-copy';
import { PassiveSkillWebUrls } from '../enums/passive-skill-web-urls';
import { useOpenPassiveSkillImportSidePeek } from '../hooks/use-open-passive-skill-import-side-peek';
import { PassiveSkillScreens } from '../screen-manager/passive-skill-screen-constants';
import { usePassiveSkillScreenNavigation } from '../screen-manager/passive-skill-screen-kit';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const PassiveSkillListScreen = (): ReactNode => {
  const navigation = usePassiveSkillScreenNavigation();
  const [announcement, setAnnouncement] = useState('');
  const [treeVersion, setTreeVersion] = useState(0);

  const handleCreate = (): void => {
    navigation.navigateTo(PassiveSkillScreens.FORM, { passive_skill_id: null });
  };

  const handleImported = (): void => {
    setAnnouncement(PassiveSkillImportCopy.SuccessAnnouncement);
    setTreeVersion((currentVersion) => currentVersion + 1);
  };

  const handleImport = useOpenPassiveSkillImportSidePeek(handleImported);

  const handlePassiveSkillActivate = (passiveSkillId: number): void => {
    navigation.navigateTo(PassiveSkillScreens.SHOW, {
      passive_skill_id: passiveSkillId,
    });
  };

  return (
    <AdminPage
      title="Passive Skills"
      width={AdminPageWidth.Standard}
      header_actions={
        <>
          <AdminAnchorButton
            href={PassiveSkillWebUrls.ADMIN_HOME}
            label="Back"
            variant={ButtonVariant.DANGER}
          />
          <Button
            label="Create Passive Skill"
            variant={ButtonVariant.PRIMARY}
            on_click={handleCreate}
          />
        </>
      }
    >
      <div className="mb-4 flex flex-wrap items-center gap-3">
        <Button
          label="Import"
          variant={ButtonVariant.PRIMARY}
          on_click={handleImport}
        />
        <AdminAnchorButton
          href={PassiveSkillWebUrls.EXPORT}
          label="Export"
          variant={ButtonVariant.PRIMARY}
        />
      </div>
      <PassiveSkillTreeView
        key={treeVersion}
        on_activate={handlePassiveSkillActivate}
      />

      <p className="sr-only" role="status" aria-live="polite">
        {announcement}
      </p>
    </AdminPage>
  );
};

export default PassiveSkillListScreen;
