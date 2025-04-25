import React from 'react';

import { useDynamicModalVisibility } from './hooks/use-dynamic-modal-visibility';

import Modal from 'ui/modal/modal';

const BaseModal = () => {
  const { ComponentToRender, componentProps, closeModal } =
    useDynamicModalVisibility();

  return (
    <Modal
      title={componentProps.title}
      is_open={componentProps.is_open}
      on_close={closeModal}
      allow_clicking_outside={componentProps.allow_clicking_outside}
    >
      <div className="flex flex-col">
        {ComponentToRender && <ComponentToRender {...componentProps} />}
      </div>
    </Modal>
  );
};

export default BaseModal;
