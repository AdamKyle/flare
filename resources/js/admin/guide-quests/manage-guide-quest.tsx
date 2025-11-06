import React from 'react';

import ManageGuideQuestSectionContent from './components/manage-guide-quest-section-content';

import WideContainerWrapper from 'ui/container/wide-container-wrapper';
import FormWizard from 'ui/form-wizard/form-wizard';
import Step from 'ui/form-wizard/step';
import Input from 'ui/input/input';

const ManageGuideQuest = () => {
  return (
    <WideContainerWrapper>
      <FormWizard
        total_steps={2}
        name="Create / Edit Guide Quest"
        is_loading={false}
        on_request_next={(current_index: number) => {
          return true;
        }}
      >
        <Step step_title="Basic Info" key="basic">
          <Input
            on_change={() => {}}
            key={'guide-quest-name'}
            value={''}
            place_holder={'Whats the name?'}
          />
        </Step>

        <Step step_title="Introduction" key="intro">
          <ManageGuideQuestSectionContent />
        </Step>
      </FormWizard>
    </WideContainerWrapper>
  );
};

export default ManageGuideQuest;
