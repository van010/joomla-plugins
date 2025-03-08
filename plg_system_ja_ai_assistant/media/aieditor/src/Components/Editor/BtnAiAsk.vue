<template>
	<button :id="prompt_btn_id" class="btn-prompt" type="button"
			:data="onAction" @click="event => btnPromptClick(event)">
		<i class="fas fa-robot"></i>
		<span>{{ ASK_AI_STR }}</span>
	</button>
</template>

<script setup>

import {
	aiInputContent, editorId,
	onAction,
	previousAction,
	prompt_btn_id,
	resMessage, selectedText, showAfterRes,
	showPrompt, showRes,
	showSearch,
	showTabList, wrapStyle
} from "../../Helper/Store";
import {clearFilter, selectListPrompts} from "@/Helper/utils/commands";
import * as utils from "@/Helper/Utilities";
import {ASK_AI_STR} from "../../constants";
import {abortRequest} from "@/Helper/utils/api";

function btnPromptClick(event){
	openPrompt();
	if (showRes.value || showAfterRes.value){
		showRes.value = showAfterRes.value = false;
	}
	// event.target return a child node of this element: tag <i></i>
	const currentEl = event.currentTarget;
	// const className = currentEl.classList[0];
	const inputField = currentEl.previousElementSibling;
	selectedText.value = utils.findToSelectAllText(inputField);
	selectListPrompts(editorId.value);
}

const openPrompt = () => {
	abortRequest();
	showTabList.value = {};
	showSearch.value = true;
	showPrompt.value = true;
	resMessage.value = '';
	previousAction.value = null;
	onAction.value = 'opening';
	wrapStyle.display = 'flex';
	aiInputContent.value = '';
	clearFilter();
}

</script>