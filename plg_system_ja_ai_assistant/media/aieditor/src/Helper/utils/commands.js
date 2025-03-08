import {listTones, tonePrompts} from "@/Helper/utils/tones";
import {langPrompts, listLanguages} from "@/Helper/utils/languages";
import {ref} from "vue";
import {
	aiInputContent, editorId, previousAction,
	resMessage, showAfterRes, showRes,
	selectedListsAfterRes, editors, selectedLists,
	selected_lists_clone
} from "@/Helper/Store";
import * as utils from "@/Helper/Utilities";
import {updatePromptStyle} from "@/Helper/UpdateStyle";


const continueWriting = {label: 'Continue writing', class: 'action-continue', cEvent: 'keep_writing', customData: ''};
const discard = {label: 'Discard', class: 'do-action action-discard', cEvent: 'discard', customData: ''};
const tryAgain = {label: 'Try again', class: 'do-action action-try-again', cEvent: 'try_again', customData: previousAction};
const makeShorter = {label: 'Make shorter', class: '', cEvent: 'make_shorter', customData: ''};
const makeLonger = {label: 'Make longer', class: '', cEvent: 'make_longer', customData: ''};
const edit_or_review = {label: 'Edit or review selection', class: 'text-preview', cEvent: '', customData: ''};
const improve_writing_title = {label: 'Improve writing', class: '', cEvent: 'improve_writing_title', customData: ''};
const improve_writing_content = {label: 'Improve writing', class: '', cEvent: 'improve_writing_content', customData: ''};
const fixSpelling = {label: 'Fix spelling & grammar', class: '', cEvent: 'fix_spelling_grammar', customData: ''};
const simplifyLanguage = {label: 'Simplify language', class: '', cEvent: 'simplify_lang', customData: ''};
const summarize = {label: 'Summarize', class: '', cEvent: 'summarize', customData: ''};
const explain = {label: 'Explain', class: '', cEvent: 'explain', customData: ''};
const changeTones = {label: 'Change tone', class: '', cEvent: 'change_tones', customData: '', subList: listTones};
const translateLangs = {label: 'Translate', class: '', cEvent: 'change_langs', customData: '', subList: listLanguages};
const generate_from_selection_text = {label: 'Generate from selection', class: 'text-preview', cEvent: '', customData: ''};
const improve_writing_metadata = {label: 'Improve metadata', class: '', cEvent: 'improve_metadata_writing', customData: ''};
const generate_from_title_and_content = {label: 'Generate metadata', class: '', cEvent: 'generate_from_title_and_content', customData: 'generate_desc_metadata'};

let allPrompts = {
    keep_writing: continueWriting,
    discard: discard,
    try_again: tryAgain,
    make_longer: makeLonger,
    make_shorter: makeShorter,
    edit_or_review: edit_or_review,
    improve_writing_title: improve_writing_title,
    improve_writing_content: improve_writing_content,
    fix_spelling_grammar: fixSpelling,
    simplify_lang: simplifyLanguage,
    summarize: summarize,
    explain: explain,
    change_tone: changeTones,
    translate_langs: translateLangs,
    improve_metadata_writing: improve_writing_metadata,
    generate_from_title_and_content: generate_from_title_and_content
};

const listsAfterRes = ref([
    {
        name: 'action-after-response',
        items: [
            {label: 'Replace selection', class: 'action-replace', cEvent: 'replace_text', customData: ''},
            tryAgain,
            discard,
        ]
    },
    {
        name: 'action-in-editor',
        items: [
            {label: 'Replace selection', class: 'action-replace', cEvent: 'replace_text_in_editor', customData: ''},
            continueWriting,
            // {label: 'Insert before', class: '', cEvent: 'insert_before', customData: ''},
            {label: 'Insert after', class: 'action-insert', cEvent: 'insert_after', customData: ''},
            tryAgain,
            discard
        ]
    }
]);

const listsAction = ref([
	{
		name: 'title-edit-selection',
		items: [
			edit_or_review,
            improve_writing_title,
            fixSpelling,
            makeShorter,
            changeTones,
		],
	},
    {
        name: 'editor-edit-selection',
        items: [
            edit_or_review,
            improve_writing_content,
            fixSpelling,
            makeShorter, makeLonger,
            simplifyLanguage,
            changeTones,
        ]
    },
    {
        name: 'generate-selection-title',
        items: [
            generate_from_selection_text,
            {label: 'Generate title', class: '', cEvent: 'generate_title_from_content', customData: ''},
            summarize,
            translateLangs,
            explain,
        ]
    },
	{
		name: 'generate-selection-content',
		items: [
			generate_from_selection_text,
            summarize,
            translateLangs,
            explain,
        ]
	},
	{
		name: 'write-with-ai',
		items: [
			{label: 'Writing with AI', class: 'text-preview', cEvent: '', customData: ''},
			{label: 'Continue writing', class: '', cEvent: 'continue_writing', customData: ''},
		]
	},
	{
		name: 'ai-draft',
		items: [
			{label: 'Draft with AI', class: 'text-preview', cEvent: '', customData: ''},
			{label: 'Brainstorm ideas', class: '', cEvent: 'brainstorm_ideas', customData: ''},
			{label: 'Outline', class: '', cEvent: 'make_outline', customData: ''},
		],
	},
	{
        // for metadata description field
		name: 'textarea-section',
		items: [
			{label: 'Generate', class: 'text-preview', cEvent: '', customData: ''},
            improve_writing_metadata,
            generate_from_title_and_content,
		]
	},
    {
        name: 'selected-list-names',
        items: [],
    },
    {
        name: 'search-selection',
        items: [],
    }
]);

allPrompts = Object.assign(allPrompts, tonePrompts(), langPrompts());


const selectListPrompts = (option) => {
    updatePromptStyle(utils.getSizeParentEl(editorId.value));
	selectedLists.value = [];
	switch (option) {
		case 'jform_title':
			selectedLists.value.push('title-edit-selection', 'generate-selection-title');
			return ;
		case 'jform_metadesc':
			selectedLists.value.push('textarea-section')
			return ;
		case '':
		default:
			break;
	}

    if (editors.includes(option)){
        selectedLists.value.push('editor-edit-selection', 'generate-selection-content', 'write-with-ai', 'ai-draft');
        return ;
    }
}

const selectPromptAfterRes = (option) => {
    selectedListsAfterRes.value = [];
    switch (option) {
        case 'jform_title':
		case 'jform_metadesc':
			selectedListsAfterRes.value.push('action-after-response');
			return ;
		case '':
		default:
			break;
	}
    if (editors.includes(option)){
        selectedListsAfterRes.value.push('action-in-editor');
        return ;
    }
}

const clearFilter = () => {
    if (showRes.value){
        showRes.value = showAfterRes.value = false;
        resMessage.value = '';
        aiInputContent.value = '';
        previousAction.value = null;
    }
    selected_lists_clone.value.length = 0;
    listsAction.value.find(prompt => prompt.name === 'search-selection').items.length = 0;
	listsAction.value.find(prompt => prompt.name === 'selected-list-names').items.length = 0;
}


export {
	allPrompts,
	listsAction,
	listsAfterRes,
	clearFilter,
	selectListPrompts,
	selectPromptAfterRes,
}