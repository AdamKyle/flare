import React from 'react';
import GuideQuestProps from "./types/guide-quest-props";
import ContainerWithTitle from "ui/container/container-with-title";
import Card from "ui/cards/card";

const GuideQuest = ({ on_close }: GuideQuestProps) => {
  return (
    <ContainerWithTitle manageSectionVisibility={on_close} title={'The Next Guide Quest'}>
      <Card>
        Content
      </Card>
    </ContainerWithTitle>
  )
};

export default GuideQuest;