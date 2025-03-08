import {editorId, classes, prompt_btn_id} from "@/Helper/Store";
import {createElement, targetsInput} from "@/Helper/Utilities";

function findBtnPrompt(field) {
    const btnPrompt = document.getElementById(prompt_btn_id);
    if (!btnPrompt) return ;
    btnPrompt.setAttribute('class', `btn-prompt-${field.id}`);
    btnPrompt.classList.add(classes.btn_prompt);
    field.insertAdjacentElement('afterend', btnPrompt);
}

function insertBtnAiAsk(e) {
    const targets = targetsInput.input.concat(targetsInput.textarea);
    const currEl = e.target;
    const elId = currEl.id.toLowerCase();
    if (!targets.includes(elId)) return;

    const btnPrompt = document.getElementById(prompt_btn_id);
    if (!btnPrompt) return;

    const id_frame_overlay = 'ai-frame-overlay';
    const frameOverlay = createElement('div', 'overlay-hide', id_frame_overlay);
    document.body.appendChild(frameOverlay);
    btnPrompt.classList.add(classes.btn_hide);
    editorId.value = elId;
    findBtnPrompt(currEl);
}

export const insertBtnAiToJFields = () => {
    document.addEventListener('mouseover', insertBtnAiAsk);
}

export const removeInsertBtnAiToJFields = () => {
    document.removeEventListener('mouseover', insertBtnAiAsk);
}