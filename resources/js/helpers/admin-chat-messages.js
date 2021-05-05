import React from 'react';
import {render} from 'react-dom';
import ChatMessages from "../components/admin/ChatMessages";


function renderChatMessages(id) {
  const chatMessages = document.getElementById(id);
  const userId       = document.querySelector('#' + id).dataset.user;


  render(
    <ChatMessages userId={userId} />,
    chatMessages
  );
}

window.renderChatMessages = renderChatMessages;
