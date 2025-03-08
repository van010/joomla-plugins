<template>
	<div v-if="showAfterRes" :style="afterResStyle" class="after-response">
		<ul v-for="(list, idx) in listsAfterRes" v-show="selectedListsAfterRes.includes(list.name)"
			:key="idx" :class="`action-list ${list.name}`">
			<li v-for="(action, i) in list.items" @click="event => handleAfterRes(event, action.cEvent)"
				:key="i" :class="action.class" :data="action.customData">
				<span>{{ action.label }}</span>
			</li>
		</ul>
	</div>
</template>
<script setup>
import {afterResStyle, previousAction, resMessage, selectedListsAfterRes, showAfterRes} from "@/Helper/Store";
import {listsAfterRes} from "@/Helper/utils/commands";
import * as utils from "@/Helper/Utilities";
import {insertValueToEditor, insertValueToEditorByPlace} from "@/Helper/Editor";
import {stripTag} from "@/Helper/String";
import {requestApiAfterRes, requestApiRes} from "@/Helper/utils/api";

const fillingContent = (field, rawContent) => {
	const content = stripTag(rawContent);
	const fieldType = field.tagName.toLowerCase();
	switch (fieldType) {
		case 'input':
			field.value = content;
			break;
		case 'textarea':
			field.innerText = content;
			break;
		default:
			break;
	}
}

const handleAfterRes = (event, task) => {
	const resContent = resMessage.value;
	if (!resContent){
		alert(`nothing to ${task}`);
		return ;
	}
	const textField = utils.getTextField();
	const debugMode = configs.debugMode;
	if (debugMode) {
		console.log(`After Res TASK: ${task}`);
	}
	var htmlContent = resMessage.value;
    switch (task){
	    case 'replace_text':
			fillingContent(textField, resContent);
			utils.destroyActions();
			break;
	    case 'replace_text_in_editor':
			insertValueToEditor(htmlContent);
			utils.destroyActions();
			break;
	    case 'try_again':
			resMessage.value = '';
			if (debugMode) {
				console.log(`Previous action: ${previousAction.value}`);
			}
			requestApiAfterRes();
			break;
	    case 'insert_before':
			insertValueToEditorByPlace(htmlContent, 'before');
			utils.destroyActions();
			break;
		case 'insert_after':
			insertValueToEditorByPlace(htmlContent, 'after');
			utils.destroyActions();
			break;
		case 'keep_writing':
			const fullCommand = utils.getPromptCommands(task, resMessage.value);
			if (debugMode) {
				console.log(`After res: ${fullCommand}`);
			}
			requestApiRes(fullCommand);
			break;
	    case 'make_longer':
			if (debugMode) {
			    console.log(`${task} in developing`);
			}
			break;
	    case 'discard':
	    default:
			utils.destroyActions();
			break;
    }
}
</script>