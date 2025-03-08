import { createApp } from 'vue';
import { addPlugin } from "@/Helper/Editor";
import { addHotKeys } from "@/Helper/Hotkeys";
import { insertBtnAiToJFields } from "@/Helper/InsertBtnAiToJFields";
import App from './App.vue';

import './Assets/Scss/App.scss';

const $el = document.createElement('DIV');
$el.id = 'jaaiassistant-app'
document.body.appendChild($el);

const app = createApp(App);
app.mount('#jaaiassistant-app');

addPlugin();
addHotKeys();
insertBtnAiToJFields();