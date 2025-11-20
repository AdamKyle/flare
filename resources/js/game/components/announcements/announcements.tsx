import React from 'react';

import AnnouncementsProps from './types/announcements-props';

import Card from 'ui/cards/card';
import ContainerWithTitle from 'ui/container/container-with-title';

const Announcements = ({ on_close }: AnnouncementsProps) => {
  return (
    <ContainerWithTitle
      manageSectionVisibility={on_close}
      title={'Game Announcements'}
    >
      <Card>Content here ....</Card>
    </ContainerWithTitle>
  );
};

export default Announcements;
