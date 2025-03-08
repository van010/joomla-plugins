import { stripTag } from "@/Helper/String";
import { clearFilter } from "@/Helper/utils/commands";
import { selectListPrompts } from "@/Helper/utils/commands";
import { selectedText, editorId, showPrompt, showSearch, wrapStyle } from "@/Helper/Store";
import { cleanWhiteSpace } from "@/Helper/Utilities";
import * as utils from "@/Helper/Utilities";

const editor_not_found = 'Editor not found!';

const promptShow = (editor) => {
    wrapStyle.display = 'flex';
    showSearch.value = true;
    showPrompt.value = true;
    editorId.value = editor.id;
    clearFilter();
    utils.focusCursorOnSearch();
    selectListPrompts(editor.id);
    const html = editor.selection.getContent();
    selectedText.value = stripTag(html);
}

export function addPlugin() {
    tinymce.PluginManager.add('jaaiassistant', function (editor, url) {
        // load ai ask into tinymce editor
        editor.ui.registry.addButton('aiask', {
            text: 'Ask AI',
            onAction: function () {
                promptShow(editor);
            }
        });
        // load ai prompt into tinymce editor
        editor.ui.registry.addButton('ai_prompt', {
            text: 'AI Prompt',
            onAction: function () {
                promptShow(editor);
            }
        });

        return {
            getMetadata: function () {
                return {
                    name: 'JA AI Editors',
                    url: 'https://www.joomlart.com/'
                };
            }
        };
    });
}

export function insertValueToEditor(value) {
    const editor = tinymce.get(editorId.value);

    if (!editor) {
        alert(editor_not_found);
        return;
    }

    editor.selection.setContent(value);
    editor.undoManager.add();

    setTimeout(() => {
        editor.focus();
    }, 300);
}

export function insertValueToEditorByPlace(value, place){
    const editor = tinymce.get(editorId.value);
    if (!editor){
    	alert(editor_not_found);
        return ;
    }
    const selectedContent = editor.selection.getContent();
    const format = {format: 'text'};
    const startPos = editor.selection.getRng().startOffset;
    const endPos = editor.selection.getRng().endOffset;
    const content_before_selection = editor.getContent(format).substring(0, startPos);
    const content_after_selection = editor.getContent(format).substring(endPos);
    var newText = '';
    if (place === 'before'){
        newText = value + selectedContent;
    }else if(place === 'after'){
        newText = selectedContent + value;
    }
    newText = cleanWhiteSpace(newText);
    editor.selection.setContent(newText);
    editor.undoManager.add();
    setTimeout(() => {
        editor.focus();
    }, 300);
}