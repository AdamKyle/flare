import React from 'react';
import {render} from 'react-dom';
import SignInChart from "../components/admin/SignInChart";
import RegisterChart from "../components/admin/RegisterChart";

function renderSignInStats(id) {
  const signInStats = document.getElementById(id);
  const userId      = document.querySelector('#' + id).dataset.user;

  render(
    <SignInChart userId={userId}/>,
    signInStats
  );
}

function renderRegisterStats(id) {
  const registerStats = document.getElementById(id);
  const userId        = document.querySelector('#' + id).dataset.user;

  render(
    <RegisterChart userId={userId}/>,
    registerStats
  );
}

window.renderSignIn  = renderSignInStats;
window.renderRegister = renderRegisterStats;
