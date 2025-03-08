import {editorId, previousAction, resMessage, showAfterRes, showRes, waitingStyle} from "@/Helper/Store";
import * as utils from "@/Helper/Utilities";
import {updateResponseStyle} from "@/Helper/UpdateStyle";
import {sanitizeText} from "@/Helper/Utilities";
import {nextTick} from "vue";
import {selectPromptAfterRes} from "@/Helper/utils/commands";

const baseUri = Joomla.getOptions('system.paths');
const joomlaApi = baseUri.base + '/index.php?option=com_ajax&plugin=jaaiassistant&format=json&group=system';
let apiRequestController = null;

const abortRequest = () => {
    if (apiRequestController){
    	apiRequestController.abort();
    }
}

const typeWriter = (resContent) => {
	// handle api response
	let index = 0;
	const content = utils.textToHTMLParagraphs(resContent);

	function addText() {
		if (index < content.length) {
			resMessage.value += content.charAt(index);
			index++;
			setTimeout(addText, 5);
		} else {
			nextTick(() => {
				showAfterRes.value = true;
				selectPromptAfterRes(editorId.value);
			});
		}
	}
	waitingStyle.display = 'none';
	addText();
}

const requestApiAfterRes = () => {
	waitingStyle.display = 'block';
	if (utils.devMode() !== 'fake_api') {
		requestApiThroughJoomla(previousAction.value);
		return ;
		// requestApiPrompt(previousAction.value);
	}
	requestSampleApi(previousAction.value);
}

const requestApiRes = (fullCommand) => {
	const parentDim = utils.getSizeParentEl(editorId.value);
	updateResponseStyle(parentDim);
	const apiMode = utils.devMode();
	if (configs.debugMode) {
		console.log(`dev mode: ${apiMode}`);
	}
	waitingStyle.display = 'block';
	if (apiMode !== 'fake_api') {
		requestApiThroughJoomla(fullCommand);
		return ;
	}
	requestSampleApi(fullCommand);
}

const requestApiThroughJoomla = async (command) => {
    abortRequest();
    apiRequestController = new AbortController();
	const formData = new FormData();
	formData.append('aitask', 'ai_prompt');
	formData.append('content', command);
	try{
		const response = await fetch(joomlaApi, {
			method: 'post',
			body: formData,
            signal: apiRequestController.signal,
		});
		const rawData = await response.json();
		if (rawData.success){
			const data = rawData.data.shift();
			const content = data.data;
			typeWriter(sanitizeText(content));
		}else{
			showRes.value = false;
			const alertApi = utils.createElement('div', 'alert-api', );
			const alert_api_container = utils.createElement('div', 'alert-api-container');
			const api_res_msg = rawData.message;
			if (api_res_msg.length > 0) {
				if (!rawData.success && api_res_msg.includes("didn't provide an API key")) {
					alert_api_container.innerHTML = configs.alertApi;
				} else {
					alert_api_container.innerHTML = rawData.message;
				}
			} else {
				alert_api_container.innerHTML = configs.alertApi;
			}
			const closeBtn = utils.createElement('span', 'closebtn', '', {onclick: "this.parentElement.parentElement.style.display=\'none\'"});
			closeBtn.innerHTML = '&times';
			document.body.appendChild(alertApi);
			alertApi.appendChild(alert_api_container);
			alert_api_container.appendChild(closeBtn);
		}
	}catch (Error){
		console.log(Error);
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
		const content = data.data[0].data;
		console.log(`Fake API response: ${content}`);
		await utils.sleep(1000);
		typeWriter(content);
	}catch (e) {
		console.log(e);
	}
}


export {
	abortRequest,
	requestApiRes,
	requestApiAfterRes
}