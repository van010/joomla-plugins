import {tonePromptCommands} from "@/Helper/utils/tones";
import {langPromptCommands} from "@/Helper/utils/languages";
import {
    aiInputContent,
    editorId,
    onAction,
    previousAction, resMessage,
    showAfterRes,
    showPrompt,
    showRes,
    showSearch,
    showTabList, wrapStyle
} from "@/Helper/Store";


const titleWords = 10;
const metadataCharacters = 250;
const baseUri = Joomla.getOptions('system.paths');
// #id of some input fields
const targetsInput = {
    'input': ['jform_title'],
    'textarea': ['jform_metadesc']
};

const addClass = (field, a_class_name) => {
    if (!field) return ;
    field.classList.add(a_class_name);
}

const removeClass = (field, a_class_name) => {
    if (!field) return ;
    var allClasses = field.className;
    var classLists = field.classList;
    if (allClasses.includes(a_class_name)){
        classLists.remove(a_class_name);
        return true;
    }
    return false;
}

const sleep = (ms) => {
    return new Promise(resolve => setTimeout(resolve, ms));
};

const splitPrompts = () => {
    const lang = configs.lang_communicate ?? 'input';
    return {
        follow_writing: `following my writing`,
        keep_origin_lang: `respond in the ${lang} language`,
        limit_under_words_title: `under ${titleWords} words`,
        limit_under_words_metadata: `under ${metadataCharacters} characters`,
    };
}

/**
 * compile fully content with prompt to attach in api request
 *
 * @param task
 * @param text
 * @param lang
 * @returns {*}
 */
const getPromptCommands = (task, text, lang=null) => {
    let wordsContext = '';
    if (editorId.value !== targetsInput.textarea[0]){
        const words = countWords(text);
        wordsContext = ` around ${words} words`;
    }

    const subPrompts = Object.assign({}, splitPrompts(), {limit_words: wordsContext});
    const basePrompts = {
        'improve_writing_title': `Make the title better limits by ${titleWords} words, ${subPrompts.keep_origin_lang}`,
        'improve_writing_content': `Make it better, ${subPrompts.keep_origin_lang}, ${wordsContext}`,
        'improve_metadata_writing': `improve metadata, ${subPrompts.keep_origin_lang}, ${subPrompts.limit_under_words_metadata}`,
        'make_shorter': `${subPrompts.keep_origin_lang}, make shorter, ${subPrompts.follow_writing}`,
        'make_longer': `${subPrompts.keep_origin_lang}, make longer, ${subPrompts.follow_writing}`,
        'fix_spelling_grammar': `fix spelling and grammar, ${subPrompts.keep_origin_lang}`,
        'simplify_lang': `simplify language, ${subPrompts.keep_origin_lang}, ${subPrompts.limit_words}`,
        'summarize': `summarize, ${subPrompts.keep_origin_lang}`,
        'translate': `translate into ${lang}`,
        'explain': `${subPrompts.keep_origin_lang}, explain context and meaning`,
        'keep_writing': `continue writing, ${subPrompts.keep_origin_lang}`,
        'continue_writing': `continue writing, ${subPrompts.keep_origin_lang}`,
        'brainstorm_ideas': `let brainstorm some ideas, ${subPrompts.keep_origin_lang}`,
        'make_outline': `make an outline, ${subPrompts.keep_origin_lang}`,
        'generate_from_title_and_content': `Craft a metadata description, ${subPrompts.keep_origin_lang}, ${subPrompts.limit_under_words_metadata}`,
        'generate_title_from_content': `Craft a title, ${subPrompts.keep_origin_lang}, ${subPrompts.limit_under_words_title}`,
    };
    const prompts = Object.assign({}, basePrompts, tonePromptCommands(), langPromptCommands());
    if (typeof text === 'string' || text instanceof String){
    	return prompts[task] + ': ' + text;
    }
    return prompts[task] + ': ' + text.title + '. ' + text.content;
}

/**
 * generate a random meaningful sentence with 15 -> 20 words
 *
 * @returns {string}
 */
const generateRandomSentence = (words=10) => {
    const subjects = ['The cat', 'The dog', 'The bird', 'The fish', 'The elephant'];
    const verbs = ['eats', 'sleeps', 'runs', 'jumps', 'flies'];
    const objects = ['the food', 'the ball', 'the book', 'the tree', 'the water'];
    const adjectives = ['big', 'small', 'beautiful', 'ugly', 'fast'];
    const adverbs = ['quickly', 'slowly', 'happily', 'sadly', 'carefully'];
    const prepositions = ['on', 'in', 'under', 'over', 'beside'];

    const sentenceLength = Math.floor(Math.random() * 6) + words;

    let sentence = '';
    for (let i = 0; i < sentenceLength; i++) {
        if (i > 0) {
            sentence += ' ';
        }
        const randomSubject = subjects[Math.floor(Math.random() * subjects.length)];
        const randomVerb = verbs[Math.floor(Math.random() * verbs.length)];
        const randomObject = objects[Math.floor(Math.random() * objects.length)];
        const randomAdjective = adjectives[Math.floor(Math.random() * adjectives.length)];
        const randomAdverb = adverbs[Math.floor(Math.random() * adverbs.length)];
        const randomPreposition = prepositions[Math.floor(Math.random() * prepositions.length)];

        const randomNumber = Math.random();
        if (randomNumber < 0.2) { // Add adverb
            sentence += randomAdverb;
        } else if (randomNumber < 0.4) { // Add adjective
            sentence += randomAdjective;
        } else if (randomNumber < 0.6) { // Add preposition
            sentence += randomPreposition;
        } else { // Add subject, verb, or object
            const randomChoice = Math.random();
            if (randomChoice < 0.33) {
                sentence += randomSubject;
            } else if (randomChoice < 0.66) {
                sentence += randomVerb;
            } else {
                sentence += randomObject;
            }
        }
    }

    return sentence + '.';
}

function createElement(tagName, className, id, attributes, innerText) {
    const element = document.createElement(tagName);
    // Set class name
    if (className) {
        element.className = className;
    }
    // Set ID
    if (id) {
        element.id = id;
    }
    // Set custom attributes
    if (attributes) {
        for (const [key, value] of Object.entries(attributes)) {
            element.setAttribute(key, value);
        }
    }
    // Set inner text
    if (innerText) {
        element.innerText = innerText;
    }

    return element;
}

const findToSelectAllText = (field) => {
    const parentEl = field.parentElement;
    // const selectedText = document.getElementById('selectedText');
    let targetText = null;
    Object.keys(targetsInput).forEach(function (el, idx){
        const targetEl = parentEl.querySelector(el);
        if (!targetEl) return ;
        const elementType = targetEl.tagName.toLowerCase();
        switch (elementType) {
            case 'input':
                targetText = targetEl.value;
                break;
            case 'textarea':
            default:
                targetText = targetEl.textContent;
                break;
        }
        targetEl.select();
        // selectedText.textContent = targetText;
        // selectedText.setAttribute('data-id', targetEl.id);
    });
    return targetText;
}

const getSizeParentEl = (el_id) => {
    const el = el_id
        ? document.getElementById(el_id)
        : document.getElementById('promptButton');
    if (!el) return ;
    const parentDimension = el.parentNode.getBoundingClientRect();
    return {el_id: el_id || 'promptButton', dim: parentDimension};
}

const getTextField = () => {
    const btnPrompt = document.getElementById('promptButton');
    const parentEl = btnPrompt.parentNode;
    let textField = null;
    Object.keys(targetsInput).forEach(function (el, idx){
    	const node = parentEl.querySelector(el);
        if (node) textField = node;
    })
    return textField;
}

const stopScroll = () => {
	document.body.style.overflow = 'hidden';
	document.documentElement.style.overflow = 'hidden';
}

const startScroll = () => {
	document.body.style.overflow = 'auto';
	document.documentElement.style.overflow = 'auto';
}

const overlayAction = (action) => {
    const overlay = document.getElementById('ai-frame-overlay');
    const bodyWrapper = document.getElementById('wrapper');
    if (action === 'show'){
    	removeClass(overlay, 'overlay-hide');
        overlay.classList.add('overlay-show');
        bodyWrapper.style.pointerEvents = 'none';
        stopScroll();
    }else if (action === 'hide'){
        removeClass(overlay, 'overlay-show');
        overlay.classList.add('overlay-hide');
        bodyWrapper.style.pointerEvents = 'auto';
        startScroll();
    }else{
        return ;
    }
}

const requestSampleApi = async (fullCommand) => {
	const endPoint = baseUri.base + '/index.php?option=com_ajax&aitask=dev_fake_api&plugin=jaaiassistant&format=json&group=system';
	try {
		const response = await fetch(endPoint, {
			method: 'POST',
			headers: {
				'Content-type': 'application/json',
				'userAgent': 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:41.0) Gecko/20100101 Firefox/41.0',
			},
		});
		if (!response.ok){
			throw new Error('Something went wrong!');
		}
		const data = await response.json();
		return data.data;
	}catch (e) {
		console.log(e);
	}
}

const showHint = (element, attr) => {
    let text = '';
    switch (attr){
        case 'generate_desc_metadata':
            text = 'Generate metadata from Title and Content';
            break;
        case '':
        default:
            break;
    }
    const hint = createElement('p', 'hint', `hint-${attr}`, '', text);
    element.insertAdjacentElement('afterend', hint);
}

const getEditorContent = () => {
    // support tinymce
    const tinyIframe = document.getElementById('jform_articletext_ifr')
    if (tinyIframe) {
        // Get the document inside the iframe
        const iframeDocument = tinyIframe.contentDocument || tinyIframe.contentWindow.document;
        // Access the body element of the iframe document
        const bodyElement = iframeDocument.querySelector('body');
        const bodyText = bodyElement.innerText;
        return sanitizeText(bodyText);
    }
}

const devMode = () => {
	const currentUrl = location.href;
	var urlOptions = currentUrl.split('index.php?')[1];
    if (!urlOptions){ // handle for some urls do not hanve index.php
        urlOptions = currentUrl.split('&');
        const fakeApi = urlOptions.find(param => param === 'api=fake_api');
        if (fakeApi){
            return fakeApi.split('api=')[1];
        }
        return null;
    }
	const paramsString = urlOptions.split('&');
	const params = {};
	paramsString.forEach(paramsString => {
		const [key, value] = paramsString.split('=');
		params[key] = value;
	})
	return params.api;
}

const cleanWhiteSpace = (text) => {
    return text.replace(/\s{2,}/g,' ');
};

const sanitizeText = (text) => {
    const sanitizedText = text.replace(/<[^>]*^>?/gm, '');
    const escapeMap = {
        '&': '', // &amp;
        '<': '', // &lt;
        '>': '', // &gt;
        '"': '', // &quot;
        "'": '', // &#39;
    };
    return sanitizedText.replace(/[&<>"']/g, match => escapeMap[match]);
}

const textToHTMLParagraphs = (content) => {
    const frags = content.split('\n');
	return frags
        .filter(frag => frag.trim())
        .map(frag => {
            return `<p>${frag}</p>`;
        }).join('');
};

const parseResToHtml = (resContent) => {
    const content = resContent.replace(/\n\n/g, '<br><br>');
    return content;
}

const escapeAllSpecialChars = (text) => {
    const pattern = /[^\w\s]/g;
    return text.replace(pattern, '');
}

// find input search and move cursor to the search field, let user type text
const focusCursorOnSearch = () => {
    setTimeout(function (){
        const ai_search_el = document.querySelector('.ai-ask-search');
        if (!ai_search_el) return;
        ai_search_el.querySelector('textarea').focus();
    }, 250);
}

const countWords = (text) => {
    if (!text) return;
    const cleanText = escapeAllSpecialChars(text);
    var allText = cleanText.split(' ');
    allText = allText.filter(text => text !== '');
    return allText.length;
}

const destroyActions = () => {
    showTabList.value = {};
	showSearch.value = showPrompt.value = false;
	showRes.value = showAfterRes.value = false;
	previousAction.value = null;
	onAction.value = null;
	resMessage.value = '';
	aiInputContent.value = '';
	wrapStyle.display = 'none';
}


export {
    targetsInput,
    sleep,
    devMode,
    showHint,
    addClass,
    stopScroll,
    countWords,
    startScroll,
    removeClass,
    splitPrompts,
    getEditorContent,
    sanitizeText,
    getTextField,
    cleanWhiteSpace,
    createElement,
    overlayAction,
    getSizeParentEl,
    requestSampleApi,
    getPromptCommands,
    findToSelectAllText,
    focusCursorOnSearch,
    textToHTMLParagraphs,
    parseResToHtml,
    destroyActions,
    generateRandomSentence,
}