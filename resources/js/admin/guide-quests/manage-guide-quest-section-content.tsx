import Input from "ui/input/input";
import ImageUploader from "ui/file-upload/image-uploader";
import MarkDownEditor from "ui/mark-down-editor/mark-down-editor";
import Button from "ui/buttons/button";
import {ButtonVariant} from "ui/buttons/enums/button-variant-enum";
import React from 'react';

const ManageGuideQuestSectionContent = () => {
  return (
    <div className="rounded-md border-1 border-gray-500 p-2 pb-4">
      <div className="my-2">
        <Input
          on_change={() => {}}
          value={''}
          place_holder={'Section Title'}
        />
      </div>
      <div className="grid grid-cols-2 gap-4">
        <ImageUploader />
        <MarkDownEditor />
      </div>
      <div className="mt-4 w-full text-right">
        <Button
          on_click={() => {}}
          label={'Add Another Section'}
          variant={ButtonVariant.PRIMARY}
        />
        <Button
          on_click={() => {}}
          label={'Remove Section'}
          variant={ButtonVariant.DANGER}
          additional_css={'ml-2'}
        />
      </div>
    </div>
  )
}

export default ManageGuideQuestSectionContent;