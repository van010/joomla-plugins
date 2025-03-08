import {
    afterResStyle, editorId, editors,
    editorWrapStyle, responseStyle, wrapStyle
} from "@/Helper/Store";
import * as utils from "@/Helper/Utilities";


const targetInputs = utils.targetsInput;

const getEditorIframe = (editorId) => {
    return `${editorId}_ifr`;
};

const getSelectionDim = (el) => {
	const selection = el.getSelection();
	if (selection.rangeCount > 0){
		const range = selection.getRangeAt(0);
		return range.getBoundingClientRect();
	}
}

const getPaddingConstant = () => {
    const randomNum = (A, B) => {
        return Math.floor(Math.random() * (B - A + 1)) + A;
    };
    // calculate bases on dimension.top | dimension top will between: 0 < dim < 300; dim > 300
    // return randomNum(97, 100);
    return 97;
}

const setTopLeftInContent = () => {
    let top, left;
    const tinyIframe = document.getElementById(getEditorIframe(editorId.value));
    if (!tinyIframe) return ;
    const tiny_iframe_doc = tinyIframe.contentDocument || tinyIframe.contentWindow.document;
    const selected_text_dim = getSelectionDim(tiny_iframe_doc);
    const parentDim = utils.getSizeParentEl(editorId.value).dim;
    const selected_text_width = Math.floor(selected_text_dim.width);
    if (selected_text_width < 1){
        top = parentDim.top + window.pageYOffset + 75;
        left = parentDim.left + 13;
    }else {
        top = selected_text_dim.bottom + parentDim.top + window.pageYOffset + getPaddingConstant();
        left = selected_text_dim.left + parentDim.left;
    }
    return {top: top, left: left};
}

const updatePromptStyle = (parentDim) => {
    const elDim = parentDim.dim;
    const el_id = parentDim.el_id;
    const handleResize = (el_id, elDim) => {
        let bottom, top, left, right, width, height;
        if (targetInputs.input.concat(targetInputs.textarea).includes(el_id)) {
            top = elDim.top + elDim.height;
            left = elDim.left;
            width = elDim.width;
        } else if (editors.includes(el_id)) {  // tinymce editor
            const calculated_dim_content = setTopLeftInContent();
            if (!calculated_dim_content) return;
            top = calculated_dim_content.top;
            left = calculated_dim_content.left;
            width = elDim.width / 2.5;
        }
        editorWrapStyle.top = `${top}px`;
        editorWrapStyle.left = `${left}px`;
        wrapStyle.width = `${width}px`;
    }
    window.addEventListener('resize', function (){
        const element = document.getElementById(el_id);
        const parentEl = element.parentNode;
        const parentDim = parentEl.getBoundingClientRect();
        handleResize(el_id, parentDim);
    });
    handleResize(el_id, elDim);
}

const updateResponseStyle = (parentDim) => {
    const elDim = parentDim.dim;
    const el_id = parentDim.el_id;

    const handleResize = (el_id, elDim) => {
        let bottom, top, left, right, width, height;
        if (targetInputs.input.concat(targetInputs.textarea).includes(el_id)) {
            bottom = elDim.bottom;
            top = elDim.top + elDim.height;
            left = elDim.left;
            width = elDim.width;
            height = elDim.height + elDim.width / 8;
        } else if (editors.includes(el_id)) { // content inside tinymce editor: textarea#jform_articletext
            const calculated_dim_content = setTopLeftInContent();
            if (!calculated_dim_content) return;
            top = calculated_dim_content.top;
            left = calculated_dim_content.left;
            width = elDim.width / 1.5;
            height = elDim.height / 4;
        }
        if (left + width > window.innerWidth){
            width = width - (left + width - window.innerWidth) - 20;
            afterResStyle.width = `${width}px`;
        }
        responseStyle.width = `${width}px`;
    }

    window.addEventListener('resize', function (){
        const element = document.getElementById(el_id);
        const parentEl = element.parentNode;
        const parentDim = parentEl.getBoundingClientRect();
        handleResize(el_id, parentDim);
    });
    handleResize(el_id, elDim);
}


export {
    updatePromptStyle,
    updateResponseStyle,
}