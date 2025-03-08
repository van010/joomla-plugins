<template>
	<div class="jaaiassistant-app-wrap" :style="editorWrapStyle">
		<div v-if="showDiscard" class="discard-action">
			<p>Do you want to discard the AI response?</p>
			<div>
				<div class="btn-discard" role="button" @click="btnDiscard( 'discard')">Discard</div>
				<div class="btn-cancel" role="button" @click="btnDiscard( 'keepGoing')">Cancel</div>
			</div>
		</div>
		<AiResponse />
		<AfterAiResponse />
		<div class="ai-selected-text">{{ selectedText }}</div>
		<div class="ai-editor-wrap" :style="wrapStyle">
			<div class="ai-prompt-wrap">
				<span class="ai-modal-close" @click="closePrompt">&times;</span>
				<div v-if="showSearch" :style="aiSearchStyle" class="ai-ask-search">
					<span class="iconStart"></span>
					<div class="user-input">
						<span>{{ ask_ai_write }}</span>
						<textarea style="width: 100%" v-model="aiInputContent"
						          @keydown="handleAreaKeydown" :rows="areaRow" @input="handleUpdateRows">
						</textarea>
					</div>
					<span class="btn-enter"></span>
				</div>
				<div v-if="showPrompt" class="ai-prompt" :style="aiPromptStyle">
					<ul v-for="(list, index) in listsAction" v-show="selectedLists.includes(list.name)"
					    :key="index" :class="`action-list ${list.name}`">
						<li v-for="(item, i) in list.items" @click="event => handleEvent(event, item.cEvent)"
						    :key="i" :class="item.class" :data="item.customData" :action="item.cEvent"
						    @mouseover="event => handleActionHover(event, 'action_mouse_enter')"
						    @mouseout="event => handleActionHover(event, 'action_mouse_out')">
							{{ item.label }}
							<ul v-show="showTabList[item.cEvent]" @mouseenter="handleMouseEnter(item.cEvent)" @mouseleave="handleMouseLeave(item.cEvent)">
								<li v-for="(sub, subIdx) in item.subList" :class="sub.class" :key="subIdx"
								    :action="sub.cEvent" @click="event => handleSubEvent(event, sub.cEvent)">
									<span>{{ sub.label }}</span>
								</li>
							</ul>
						</li>
					</ul>
					<!-- end a list of actions -->
				</div>
			</div>
		</div>
		<div id="selectedText"></div>
	</div>
</template>
<script setup>

import * as utils from '@/Helper/Utilities';
import { nextTick, ref, onMounted, onUpdated } from 'vue';
import { selectedLists, editors, selected_lists_clone} from "@/Helper/Store";
import {
	allPrompts, listsAction
} from "@/Helper/utils/commands";
import {
	showRes,
	showSearch,
	showAfterRes,
	showTabList,
	showPrompt,
	showDiscard,
	selectedText,
	previousAction,
	aiPromptStyle,
	wrapStyle,
	resMessage,
	editorId, aiInputContent, aiSearchStyle, editorWrapStyle
} from "@/Helper/Store";
import AiResponse from "@/Components/Editor/AiResponse.vue";
import AfterAiResponse from "@/Components/Editor/AfterAiResponse.vue";
import {requestApiRes, abortRequest} from "@/Helper/utils/api";


const areaRow = ref(1);
const ask_ai_str = 'Ask AI';
const alert_no_content = 'No content found!';
const ask_ai_write = 'Ctrl + Enter to ask AI...';

// ------------------------------------------|
// open and close actions
// ------------------------------------------|
const closePrompt = () => {
	utils.destroyActions();
	editorId.value = '';
}

const btnDiscard = (task) => {
	if (task === 'discard'){
		nextTick(() => {
		    showDiscard.value = false;
		})
		resMessage.value = '';
		showRes.value = false;
		showAfterRes.value = false;
		showPrompt.value = false;
	}else if(task === "keepGoing"){
		nextTick(() => {
			showDiscard.value = false;
			utils.overlayAction('hide');
		})
	}
}

const openResponse = () => {
    showRes.value = true;
	showSearch.value = false;
	showPrompt.value = false;
	wrapStyle.display = 'none';
}

const handleActionHover = (event, task) => {
	const currentTarget = event.target;
	const dataAttr = currentTarget.getAttribute('data');
	const actionAttr = currentTarget.getAttribute('action');
	if (task === 'action_mouse_enter') {
		// show sub lists of language and tones
		showTabList.value[actionAttr] = true;
		if (dataAttr === 'generate_desc_metadata'){
			utils.showHint(currentTarget, dataAttr);
		}
	}else{
		if (dataAttr === 'generate_desc_metadata'){
			document.getElementById(`hint-${dataAttr}`).remove();
		}
		// hide sub lists of language and tones
		showTabList.value[actionAttr] = false;
	}
}

const handleMouseEnter = (itemEvent) => {
    showTabList.value[itemEvent] = true;
}

const handleMouseLeave = (itemEvent) => {
	showTabList.value[itemEvent] = false;
};

const handleSubEvent = (event, task) => {
	var textSelected = selectedText.value;
	if (!textSelected) {
		alert(alert_no_content);
		return ;
	}
	openResponse();
	let fullCommand = utils.getPromptCommands(task, textSelected);
	previousAction.value = fullCommand;
	if (configs.debugMode) {
	    console.log(`Full command: ${task} - ${fullCommand}`);
	}
	requestApiRes(fullCommand);
}

const handleEvent = (event, task) => {
	var textSelected = selectedText.value;
	let fullCommand = '';
	const currElement = event.target;
	const editorContent = utils.getEditorContent();
	switch (task){
		case 'user_request_prompt':
			const userPrompt = currElement.value;
			if (!textSelected){
				fullCommand = `keep your response in the ${configs.lang_communicate} language: ${userPrompt}`;
			} else {
				fullCommand = `${userPrompt}, keep your response in the ${configs.lang_communicate} language, following my content: ${textSelected}`;
			}
			break;
		// prepare input to generate content for meta description
		case 'generate_from_title_and_content':
			const title = document.getElementById('jform_title').value;
			fullCommand = utils.getPromptCommands(task, {title: title, content: editorContent});
			break;
		case 'generate_title_from_content':
			fullCommand = utils.getPromptCommands(task, editorContent);
			break;
		case 'change_tones':
		case 'change_langs':
		case '':
			return ;
		default:
			if (textSelected === '') {
				alert(alert_no_content);
				return ;
			}
			fullCommand = utils.getPromptCommands(task, textSelected);
			break;
	}
	openResponse();
	previousAction.value = fullCommand;
	if (configs.debugMode) {
	    console.log(`Full command: ${task} - ${fullCommand}`);
	}
	requestApiRes(fullCommand);
}

// ------------------------------------------|
// Effects
// ------------------------------------------|

const clonePromptObj = (selectedLists) => {
	const selected_list_names = Object.values(selectedLists);
	const targetLists = [
		['textarea-section'],
		['title-edit-selection', 'generate-selection-title'],
		['editor-edit-selection', 'generate-selection-content', 'write-with-ai', 'ai-draft']
	];

	let list_names_wanted = [];

	for (const list of targetLists) {
		if (selected_list_names.length !== list.length) continue;
		const sorted_selected_arr = selected_list_names.sort();
		const sorted_target_arr = list.sort();

		const isEqual = sorted_selected_arr.every((value, index) => value === sorted_target_arr[index]);
		if (isEqual) {
			list_names_wanted = list;
			break;
		}
	}

	let list_actions_wanted = [];
	list_names_wanted.forEach(function (listName, idx){
		listsAction.value.find(
			function (action, idx, arr){
				if (action.name === listName && action.items.cEvent !== ''){
					list_actions_wanted.push(action.items);
				}
			}
		);
	});
	// list_actions_wanted
	if (list_actions_wanted.length === 0){
		console.log('List actions clone is empty!');
		return ;
	}
	let lastList = [];
	let all_sub_list = [];
	list_actions_wanted.flat(Infinity).filter(function (list, idx, arr){
		if (list.cEvent !== ''){
			const all = {};
			all[list.cEvent] = list.label;
			lastList.push(all);
		}
		if (list.subList){
			all_sub_list.push(list.subList);
		}
	});

	let last_sub_list = [];
	all_sub_list.flat(Infinity).forEach(function (el, idx){
		const list = {};
		list[el.cEvent] = el.label;
		last_sub_list.push(list);
	})

	selected_lists_clone.value.push(lastList.concat(last_sub_list));
	// selected_lists_clone.value.push(lastList);
	const origin_list_action_names = listsAction.value.find(actionName => actionName.name === 'selected-list-names');
	origin_list_action_names.items.push(selected_list_names);

	// const searchClone = listsAction.value.find((actionName, idx) => actionName.name === 'search-selection-clone');
	// searchClone.items.push(lastList);
}

const filterSearchPrompts = (userTyping) => {
	const text = userTyping.toLowerCase();
	clonePromptObj(selectedLists.value);

	const searchOptions = (promptActions) => {
		let listActions = [];
		promptActions.filter(
			function (action, idx, arr){
				if (text === '') return;
				const promptText = Object.values(action)[0].toLowerCase();
				if (promptText.includes(text)){
					listActions.push(allPrompts[Object.keys(action)[0]]);
				}
			}
		)
		return listActions.flat(Infinity);
	};

	const search_actions_obj = listsAction.value.find(action => action.name === 'search-selection');
	const prompts = selected_lists_clone.value[0];
	const listActions = searchOptions(prompts);
	if (listActions.length === 0){
		search_actions_obj.items.length = 0;
		selectedLists.value.length = 0;
		const first_list_names = listsAction.value.find(action => action.name === 'selected-list-names');
		Object.values(first_list_names.items[0]).forEach(function (name, idx){
			selectedLists.value.push(name);
		});
		selected_lists_clone.value.length = 0;  // reset list clone for search
		first_list_names.items.length = 0;  // reset first list name
		return ;
	}

	search_actions_obj.items.length = 0;
	listActions.forEach(function (action, idx) {
		if (!action) return;
		search_actions_obj.items.push(action);
	});
	selectedLists.value.length = 0;
	selectedLists.value.push('search-selection');
}

// recalculate rows
const handleUpdateRows = (event) => {
	const elTarget = event.target;
	const spanText = elTarget.previousElementSibling;
	spanText.style.opacity = 0;
	elTarget.style.overflow = 'hidden';
	elTarget.style.height = 'auto';
	elTarget.style.height = `${elTarget.scrollHeight}px`;
	areaRow.value = elTarget.rows;
	filterSearchPrompts(elTarget.value);
}

const handleAreaKeydown = (event) => {
	const elTarget = event.target;
    const elName = elTarget.nodeName;
	const userTyping = elTarget.value;

	if (event.key === 'Escape'){
		utils.destroyActions();
	}

	if (event.ctrlKey && event.key === 'Enter') {
		handleEvent(event, 'user_request_prompt')
		aiInputContent.value = '';
	}
}

function moveCursorOnPromptBtn(){
	const promptBtn = document.getElementById('promptButton');
	if (!promptBtn) return;
	promptBtn.addEventListener('click', function (){
		const ask_search_el = document.querySelector('.ai-ask-search');
		if (!ask_search_el) return;
		ask_search_el.querySelector('textarea').focus();
	});
}

// ------------------------------------------|
// Hooks
// ------------------------------------------|
// click outside
onMounted(() => {
	const tinymceEditor = document.querySelector('.js-editor-tinymce');
	setTimeout(function (){
		tinymceEditor.querySelectorAll('button').forEach(function (btn, idx, array) {
		if (btn.querySelector('span')
			&& btn.querySelector('span').innerText.toLowerCase().trim() === ask_ai_str.toLowerCase()) {
			// style here
			btn.style.width = '96px';
			// btn.style.borderWidth = '1px';
			// btn.style.borderColor = '#b8c9e0';
			// btn.style.borderStyle = 'solid';
			btn.style.padding = '.375rem .75rem';
			btn.style.margin = '0';
			btn.style.cursor = 'pointer';
			const tagRobot = utils.createElement('rect', 'fa fa-robot');
			btn.insertAdjacentElement('afterbegin', tagRobot);
			tagRobot.style.color = '#457D54';
			return ;
		}
	});
	}, 800);
	document.addEventListener('click', function (e){
		onClickOutSide(e);
        onClickInsideEditor(e);
	});
	moveCursorOnPromptBtn();
})

onUpdated(() => {
    onClickInsideEditor();
} )

const onClickInsideEditor = (event) => {
    if (showPrompt.value || showRes.value || showAfterRes.value) {
        editors.forEach(function (editor, idx) {
            const tinyIfr = document.getElementById(`${editor}_ifr`);
            if (tinyIfr) {
                const tiny_ifr_doc = tinyIfr.contentDocument || tinyIfr.contentWindow.document;
                tiny_ifr_doc.addEventListener('click', function (e) {
                    e.preventDefault();
                    abortRequest();
                    utils.destroyActions();
                });
            }
        });
    }
}

const onClickOutSide = (event) => {
	const target = event.target;
	const textField = utils.getTextField();
	if (!textField) return;
	let isClickInsidePrompt = null;

	if (textField.id){
		isClickInsidePrompt = target.closest('.ai-prompt') || target.closest('#promptButton') || target.closest(`#${textField.id}`);
	}else{
		isClickInsidePrompt = target.closest('.ai-prompt') || target.closest('#promptButton');
	}
	const isClickInsideSaveButton = target.closest('.button-save') || target.closest('.button-apply') || target.closest('.button-cancel');
	const isClickInsideResponse = target.closest('.ai-response') || target.closest('.after-response');

	if (showPrompt.value || showRes.value || showAfterRes.value) {
		if(!isClickInsidePrompt && !isClickInsideSaveButton && !isClickInsideResponse
			&& !target.closest('.btn-cancel') && !target.closest('.ai-editor-wrap')){
            abortRequest();
			utils.destroyActions();
		} else {
			// showDiscard.value = false;
		}
	}else{
		// utils.overlayAction('hide');
	}
}

</script>

<style scoped>

div.ai-selected-text {
	display: none;
}

</style>
