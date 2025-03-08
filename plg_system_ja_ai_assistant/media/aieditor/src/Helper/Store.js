import {reactive, ref} from 'vue';

const aiInputContent = ref('');
const selectedText = ref('');
const editorId = ref('');
const showPrompt = ref(false);
const previousAction = ref(null);
const showSearch = ref(false);
const showRes = ref(false);
const showAfterRes = ref(false);
const showDiscard = ref(false);
const showTabList = ref({});
const onAction = ref(null);
const resMessage = ref('');
const selectedLists = ref([]);
const selected_lists_clone = ref([]);
const selectedListsAfterRes = ref([]);
const responseStyle = reactive({});
const afterResStyle = reactive({});
const aiPromptStyle = reactive({});
const aiSearchStyle = reactive({});
const wrapStyle = reactive({});
const editorWrapStyle = reactive({});
const waitingStyle = reactive({});

const prompt_btn_id = 'promptButton';
const editors = [
    'jform_articletext', // field textarea in article, iframe jform_articletext_ifr
    'jform_content', // field textarea in module type Custom, iframe jform_content_ifr
    'jform_description' // field textarea in category, iframe jform_description_ifr
];

const ifrs = {
    WYSIWYG_ifr_id: 'jform_content_ifr',
    WYSIWYG_ifr_content: 'jform_content',
    tinymce_ifr_id: 'jform_articletext_ifr',
    tinymce_ifr_content: 'jform_articletext'
};

const classes = {
    'btn_prompt': 'btn-prompt',
    'btn_show': 'btn-prompt-show',
    'btn_hide': 'btn-prompt-hide',
    'promt_show': 'ai-prompt-show',
    'promt_hide': 'ai-prompt-hide',
};

export {
    classes,
    editors,
    editorId,
    showPrompt,
    showSearch,
    showRes,
    showDiscard,
    onAction,
    resMessage,
    showAfterRes,
    showTabList,
    selectedText,
    responseStyle,
    afterResStyle,
    aiPromptStyle,
    aiSearchStyle,
    prompt_btn_id,
    wrapStyle,
    waitingStyle,
    editorWrapStyle,
    previousAction,
    aiInputContent,
    selectedLists,
    selectedListsAfterRes,
    selected_lists_clone,
}
